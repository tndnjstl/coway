<?php
class LocationController
{
	// 추적 세션 시작 (POST, JSON)
	public function startSession(): void
	{
		global $db_local;
		login_check();
		header('Content-Type: application/json');

		$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');

		// 기존 active 세션이 있으면 먼저 종료
		$active = $db_local->query("SELECT uid FROM tndnjstl_location_session WHERE member_id = '{$mid}' AND status = 'active' LIMIT 1");
		if ($active && $active->num_rows > 0)
		{
			$ar = $active->fetch_assoc();
			$db_local->query("UPDATE tndnjstl_location_session SET status = 'stopped', end_time = NOW() WHERE uid = {$ar['uid']}");
		}

		$today = date('Y-m-d');
		$db_local->query("
			INSERT INTO tndnjstl_location_session SET
				member_id   = '{$mid}',
				start_time  = NOW(),
				status      = 'active',
				point_count = 0,
				log_date    = '{$today}'
		");
		$session_uid = $db_local->insert_id;

		echo json_encode(['success' => true, 'session_uid' => $session_uid]);
		exit;
	}

	// 위치 좌표 배치 저장 (POST, JSON)
	public function logBatch(): void
	{
		global $db_local;
		login_check();
		header('Content-Type: application/json');

		$mid         = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		$session_uid = (int)($_POST['session_uid'] ?? 0);
		$points_raw  = $_POST['points'] ?? '[]';
		$points      = json_decode($points_raw, true);

		if (!is_array($points) || count($points) === 0)
		{
			echo json_encode(['success' => true, 'inserted' => 0]);
			exit;
		}

		$inserted = 0;
		foreach ($points as $p)
		{
			$lat      = (float)($p['lat'] ?? 0);
			$lng      = (float)($p['lng'] ?? 0);
			$accuracy = isset($p['accuracy']) ? (float)$p['accuracy'] : 'NULL';
			$speed    = isset($p['speed']) && $p['speed'] !== null ? (float)$p['speed'] : 'NULL';

			if ($lat === 0.0 || $lng === 0.0) continue;

			$logged_at_raw = $p['timestamp'] ?? date('Y-m-d H:i:s');
			// ISO 8601 → MySQL datetime 변환
			$logged_at = date('Y-m-d H:i:s', strtotime($logged_at_raw));
			$log_date  = substr($logged_at, 0, 10);

			$logged_at_esc = $db_local->real_escape_string($logged_at);
			$log_date_esc  = $db_local->real_escape_string($log_date);

			$accuracy_val = is_numeric($accuracy) ? $accuracy : 'NULL';
			$speed_val    = is_numeric($speed) ? $speed : 'NULL';

			$db_local->query("
				INSERT INTO tndnjstl_location_log SET
					member_id  = '{$mid}',
					latitude   = {$lat},
					longitude  = {$lng},
					accuracy   = {$accuracy_val},
					speed      = {$speed_val},
					logged_at  = '{$logged_at_esc}',
					log_date   = '{$log_date_esc}'
			");
			$inserted++;
		}

		// 세션 point_count 업데이트
		if ($session_uid > 0 && $inserted > 0)
		{
			$db_local->query("
				UPDATE tndnjstl_location_session SET
					point_count = COALESCE(point_count, 0) + {$inserted}
				WHERE uid = {$session_uid} AND member_id = '{$mid}'
			");
		}

		echo json_encode(['success' => true, 'inserted' => $inserted]);
		exit;
	}

	// 세션 종료 (POST, JSON)
	public function stopSession(): void
	{
		global $db_local;
		login_check();
		header('Content-Type: application/json');

		$mid         = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		$session_uid = (int)($_POST['session_uid'] ?? 0);

		if ($session_uid === 0)
		{
			// member_id 기준 active 세션 찾기
			$r = $db_local->query("SELECT uid FROM tndnjstl_location_session WHERE member_id = '{$mid}' AND status = 'active' ORDER BY uid DESC LIMIT 1");
			if ($r && $r->num_rows > 0) $session_uid = (int)$r->fetch_assoc()['uid'];
		}

		if ($session_uid === 0)
		{
			echo json_encode(['success' => false, 'message' => '활성 세션이 없습니다.']);
			exit;
		}

		// 세션 좌표로 총 이동거리 계산 (Haversine)
		$sr = $db_local->query("SELECT uid FROM tndnjstl_location_session WHERE uid = {$session_uid} AND member_id = '{$mid}' LIMIT 1");
		if (!$sr || $sr->num_rows === 0)
		{
			echo json_encode(['success' => false, 'message' => '세션 권한 오류.']);
			exit;
		}

		// 해당 세션 start_time ~ now 사이 좌표 조회
		$sess_r = $db_local->query("SELECT start_time, log_date FROM tndnjstl_location_session WHERE uid = {$session_uid} LIMIT 1");
		$sess   = $sess_r->fetch_assoc();
		$start  = $db_local->real_escape_string($sess['start_time']);
		$log_date = $db_local->real_escape_string($sess['log_date']);

		$lr = $db_local->query("
			SELECT latitude, longitude
			FROM tndnjstl_location_log
			WHERE member_id = '{$mid}'
			  AND log_date  = '{$log_date}'
			  AND logged_at >= '{$start}'
			ORDER BY logged_at ASC
		");

		$total_distance = 0.0;
		$prev = null;
		if ($lr) while ($lrow = $lr->fetch_assoc())
		{
			if ($prev !== null)
			{
				$total_distance += $this->haversine(
					(float)$prev['latitude'], (float)$prev['longitude'],
					(float)$lrow['latitude'], (float)$lrow['longitude']
				);
			}
			$prev = $lrow;
		}

		$db_local->query("
			UPDATE tndnjstl_location_session SET
				status         = 'stopped',
				end_time       = NOW(),
				total_distance = {$total_distance}
			WHERE uid = {$session_uid}
		");

		echo json_encode(['success' => true, 'total_distance' => round($total_distance, 3)]);
		exit;
	}

	// 현재 추적 상태 확인 (GET, JSON)
	public function trackingStatus(): void
	{
		global $db_local;
		login_check();
		header('Content-Type: application/json');

		$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		$r   = $db_local->query("SELECT uid FROM tndnjstl_location_session WHERE member_id = '{$mid}' AND status = 'active' ORDER BY uid DESC LIMIT 1");

		if ($r && $r->num_rows > 0)
		{
			$row = $r->fetch_assoc();
			echo json_encode(['tracking' => true, 'session_uid' => (int)$row['uid']]);
		}
		else
		{
			echo json_encode(['tracking' => false, 'session_uid' => null]);
		}
		exit;
	}

	// 내 동선 확인 화면 (staff+)
	public function myRoute(): void
	{
		global $db_local;
		login_check();

		$mid  = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		$date = $db_local->real_escape_string(trim($_GET['date'] ?? date('Y-m-d')));

		// 오늘 세션 목록
		$sr = $db_local->query("
			SELECT * FROM tndnjstl_location_session
			WHERE member_id = '{$mid}' AND log_date = '{$date}'
			ORDER BY start_time ASC
		");
		$sessions = [];
		if ($sr) while ($row = $sr->fetch_assoc()) $sessions[] = $row;

		$kakao_map_key = KAKAO_MAP_KEY;

		include VIEW_PATH . '/location_my_view.php';
	}

	// 특정 날짜/영업자 경로 데이터 반환 (GET, JSON, manager+)
	public function getRoute(): void
	{
		global $db_local;
		login_check();
		role_check('manager');
		header('Content-Type: application/json');

		$mid  = $db_local->real_escape_string(trim($_GET['member_id'] ?? ''));
		$date = $db_local->real_escape_string(trim($_GET['date'] ?? date('Y-m-d')));

		if ($mid === '')
		{
			echo json_encode(['success' => false, 'message' => '영업자를 선택해주세요.']);
			exit;
		}

		$r = $db_local->query("
			SELECT latitude, longitude, logged_at
			FROM tndnjstl_location_log
			WHERE member_id = '{$mid}' AND log_date = '{$date}'
			ORDER BY logged_at ASC
		");
		$points = [];
		if ($r) while ($row = $r->fetch_assoc())
		{
			$points[] = [
				'lat' => (float)$row['latitude'],
				'lng' => (float)$row['longitude'],
				'time' => $row['logged_at'],
			];
		}

		echo json_encode(['success' => true, 'points' => $points, 'count' => count($points)]);
		exit;
	}

	// 동선 관리 대시보드 (manager+)
	public function dashboard(): void
	{
		global $db_local;
		login_check();
		role_check('manager');

		$date = $db_local->real_escape_string(trim($_GET['date'] ?? date('Y-m-d')));

		// 영업자 목록
		$mr = $db_local->query("SELECT member_id, member_name FROM tndnjstl_member ORDER BY member_name ASC");
		$members = [];
		if ($mr) while ($row = $mr->fetch_assoc()) $members[] = $row;

		// 날짜별 영업자별 이동 요약
		$summary = [];
		foreach ($members as $m)
		{
			$m_mid = $db_local->real_escape_string($m['member_id']);
			$sr = $db_local->query("
				SELECT
					SUM(COALESCE(total_distance, 0)) AS total_km,
					SUM(COALESCE(point_count, 0))    AS total_points,
					MAX(status)                       AS last_status
				FROM tndnjstl_location_session
				WHERE member_id = '{$m_mid}' AND log_date = '{$date}'
			");
			$s = $sr ? $sr->fetch_assoc() : [];
			$summary[$m['member_id']] = [
				'total_km'     => round((float)($s['total_km'] ?? 0), 2),
				'total_points' => (int)($s['total_points'] ?? 0),
				'is_active'    => ($s['last_status'] ?? '') === 'active',
			];
		}

		$kakao_map_key = KAKAO_MAP_KEY;

		include VIEW_PATH . '/location_dashboard_view.php';
	}

	// Haversine 공식으로 두 좌표 간 거리 계산 (km)
	private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
	{
		$R    = 6371.0; // 지구 반경 km
		$dLat = deg2rad($lat2 - $lat1);
		$dLng = deg2rad($lng2 - $lng1);
		$a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
		$c    = 2 * atan2(sqrt($a), sqrt(1 - $a));
		return $R * $c;
	}
}
