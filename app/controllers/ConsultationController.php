<?php
class ConsultationController
{
	// 계정 필터 (manager+는 전체, staff는 본인만)
	private function member_filter(string $alias = 'c'): string
	{
		if (is_manager()) return '';
		global $db_local;
		$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		return "{$alias}.member_id = '{$mid}'";
	}

	// 상담 내역 목록
	public function list(): void
	{
		global $db_local;
		login_check();

		$keyword      = trim($_GET['keyword']      ?? '');
		$filter_type  = trim($_GET['consult_type'] ?? '');
		$filter_date_from = trim($_GET['date_from'] ?? '');
		$filter_date_to   = trim($_GET['date_to']   ?? '');
		$filter_mid   = trim($_GET['member_id']    ?? '');

		$conds = [];
		$mf = $this->member_filter();
		if ($mf !== '') $conds[] = $mf;

		if ($keyword !== '')
		{
			$kw = $db_local->real_escape_string($keyword);
			$conds[] = "(c.title LIKE '%{$kw}%' OR cu.customer_name LIKE '%{$kw}%')";
		}
		if (in_array($filter_type, ['visit', 'phone', 'online'], true))
		{
			$tp = $db_local->real_escape_string($filter_type);
			$conds[] = "c.consult_type = '{$tp}'";
		}
		if ($filter_date_from !== '')
		{
			$df = $db_local->real_escape_string($filter_date_from);
			$conds[] = "DATE(c.consult_date) >= '{$df}'";
		}
		if ($filter_date_to !== '')
		{
			$dt = $db_local->real_escape_string($filter_date_to);
			$conds[] = "DATE(c.consult_date) <= '{$dt}'";
		}
		if (is_manager() && $filter_mid !== '')
		{
			$fm = $db_local->real_escape_string($filter_mid);
			$conds[] = "c.member_id = '{$fm}'";
		}
		$where = count($conds) > 0 ? 'WHERE ' . implode(' AND ', $conds) : '';

		$r = $db_local->query("
			SELECT
				c.*,
				cu.customer_name
			FROM tndnjstl_consultation AS c
			LEFT JOIN tndnjstl_customer AS cu ON cu.uid = c.customer_uid
			{$where}
			ORDER BY c.consult_date DESC
		");
		$consultations = [];
		if ($r) while ($row = $r->fetch_assoc()) $consultations[] = $row;

		// manager+: 담당자 목록 (필터용)
		$members = [];
		if (is_manager())
		{
			$mr = $db_local->query("SELECT member_id, member_name FROM tndnjstl_member ORDER BY member_name ASC");
			if ($mr) while ($row = $mr->fetch_assoc()) $members[] = $row;
		}

		include VIEW_PATH . '/consultation_list_view.php';
	}

	// 상담 등록 폼 (녹음 UI 포함)
	public function form(): void
	{
		global $db_local;
		login_check();

		// 고객 목록 (본인 고객만 or 전체)
		$mf = is_manager() ? '' : "WHERE member_id = '{$db_local->real_escape_string($_SESSION['member_id'] ?? '')}'";
		$cr = $db_local->query("SELECT uid, customer_name, customer_phone FROM tndnjstl_customer {$mf} ORDER BY customer_name ASC");
		$customers = [];
		if ($cr) while ($row = $cr->fetch_assoc()) $customers[] = $row;

		include VIEW_PATH . '/consultation_form_view.php';
	}

	// 상담 상세
	public function detail(): void
	{
		global $db_local;
		login_check();

		$uid = (int)($_GET['uid'] ?? 0);
		if ($uid === 0)
		{
			echo "<script>alert('잘못된 접근입니다.');history.back();</script>"; exit;
		}

		$r = $db_local->query("
			SELECT c.*, cu.customer_name, cu.customer_phone
			FROM tndnjstl_consultation AS c
			LEFT JOIN tndnjstl_customer AS cu ON cu.uid = c.customer_uid
			WHERE c.uid = {$uid}
			LIMIT 1
		");
		if (!$r || $r->num_rows === 0)
		{
			echo "<script>alert('상담 내역을 찾을 수 없습니다.');history.back();</script>"; exit;
		}
		$consultation = $r->fetch_assoc();

		// 권한: staff는 본인 상담만
		if (!is_manager() && $consultation['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			echo "<script>alert('접근 권한이 없습니다.');history.back();</script>"; exit;
		}

		include VIEW_PATH . '/consultation_detail_view.php';
	}

	// 상담 저장 (POST, multipart - 텍스트 + 음성파일)
	public function addProc(): void
	{
		global $db_local;
		login_check();

		$mid          = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		$customer_uid = (int)($_POST['customer_uid'] ?? 0);
		$order_uid    = (int)($_POST['order_uid'] ?? 0);
		$title        = $db_local->real_escape_string(trim($_POST['title'] ?? ''));
		$consult_type = $db_local->real_escape_string(trim($_POST['consult_type'] ?? 'visit'));
		$consult_date = $db_local->real_escape_string(trim($_POST['consult_date'] ?? date('Y-m-d H:i:s')));
		$stt_text     = $db_local->real_escape_string(trim($_POST['stt_text'] ?? ''));
		$stt_summary  = $db_local->real_escape_string(trim($_POST['stt_summary'] ?? ''));
		$duration     = (int)($_POST['audio_duration'] ?? 0);

		if ($title === '')
		{
			echo json_encode(['success' => false, 'message' => '상담 제목을 입력해주세요.']);
			exit;
		}

		$customer_val = $customer_uid > 0 ? $customer_uid : 'NULL';
		$order_val    = $order_uid > 0 ? $order_uid : 'NULL';

		// 우선 레코드 INSERT
		$db_local->query("
			INSERT INTO tndnjstl_consultation SET
				member_id      = '{$mid}',
				customer_uid   = {$customer_val},
				order_uid      = {$order_val},
				title          = '{$title}',
				consult_type   = '{$consult_type}',
				consult_date   = '{$consult_date}',
				stt_text       = '{$stt_text}',
				stt_summary    = '{$stt_summary}',
				audio_duration = {$duration},
				status         = 'completed',
				register_date  = NOW()
		");
		$insert_id = $db_local->insert_id;

		// 음성 파일 처리
		$audio_file = '';
		if (!empty($_FILES['audio_file']['tmp_name']))
		{
			$ym_dir = AUDIO_UPLOAD_PATH . date('Ym') . '/';
			if (!is_dir($ym_dir)) mkdir($ym_dir, 0755, true);

			$ext       = 'webm';
			$filename  = "consultation_{$insert_id}_" . time() . ".{$ext}";
			$dest      = $ym_dir . $filename;

			if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $dest))
			{
				$audio_file = AUDIO_UPLOAD_URL . date('Ym') . '/' . $filename;
				$af_esc = $db_local->real_escape_string($audio_file);
				$db_local->query("UPDATE tndnjstl_consultation SET audio_file = '{$af_esc}' WHERE uid = {$insert_id}");
			}
		}

		if (isset($_POST['ajax']))
		{
			echo json_encode(['success' => true, 'uid' => $insert_id]);
			exit;
		}

		header('Location: /Consultation/detail?uid=' . $insert_id);
		exit;
	}

	// STT 텍스트 자동 저장 (녹음 중 30초마다 호출)
	public function updateTextProc(): void
	{
		global $db_local;
		login_check();

		header('Content-Type: application/json');

		$uid      = (int)($_POST['uid'] ?? 0);
		$stt_text = $db_local->real_escape_string($_POST['stt_text'] ?? '');
		$mid      = $db_local->real_escape_string($_SESSION['member_id'] ?? '');

		if ($uid === 0)
		{
			// 신규 레코드 생성 (녹음 시작 시점)
			$customer_uid = (int)($_POST['customer_uid'] ?? 0);
			$title        = $db_local->real_escape_string(trim($_POST['title'] ?? '녹음 중...'));
			$consult_type = $db_local->real_escape_string(trim($_POST['consult_type'] ?? 'visit'));
			$customer_val = $customer_uid > 0 ? $customer_uid : 'NULL';

			$db_local->query("
				INSERT INTO tndnjstl_consultation SET
					member_id    = '{$mid}',
					customer_uid = {$customer_val},
					title        = '{$title}',
					consult_type = '{$consult_type}',
					consult_date = NOW(),
					stt_text     = '{$stt_text}',
					status       = 'recording',
					register_date = NOW()
			");
			$uid = $db_local->insert_id;
			echo json_encode(['success' => true, 'uid' => $uid]);
			exit;
		}

		// 기존 레코드 업데이트 (status가 recording인 경우만)
		$r = $db_local->query("SELECT status, member_id FROM tndnjstl_consultation WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0)
		{
			echo json_encode(['success' => false, 'message' => '레코드를 찾을 수 없습니다.']);
			exit;
		}
		$row = $r->fetch_assoc();
		if ($row['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			echo json_encode(['success' => false, 'message' => '권한이 없습니다.']);
			exit;
		}

		$db_local->query("
			UPDATE tndnjstl_consultation SET
				stt_text    = '{$stt_text}',
				update_date = NOW()
			WHERE uid = {$uid}
		");
		echo json_encode(['success' => true, 'uid' => $uid]);
		exit;
	}

	// 상담 수정 처리 (POST)
	public function editProc(): void
	{
		global $db_local;
		login_check();

		$uid          = (int)($_POST['uid'] ?? 0);
		$title        = $db_local->real_escape_string(trim($_POST['title'] ?? ''));
		$consult_type = $db_local->real_escape_string(trim($_POST['consult_type'] ?? ''));
		$consult_date = $db_local->real_escape_string(trim($_POST['consult_date'] ?? ''));
		$stt_text     = $db_local->real_escape_string(trim($_POST['stt_text'] ?? ''));
		$stt_summary  = $db_local->real_escape_string(trim($_POST['stt_summary'] ?? ''));

		// 권한 체크
		$r = $db_local->query("SELECT member_id FROM tndnjstl_consultation WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0) { echo "<script>alert('상담을 찾을 수 없습니다.');history.back();</script>"; exit; }
		$row = $r->fetch_assoc();
		if (!is_manager() && $row['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			echo "<script>alert('접근 권한이 없습니다.');history.back();</script>"; exit;
		}

		$db_local->query("
			UPDATE tndnjstl_consultation SET
				title        = '{$title}',
				consult_type = '{$consult_type}',
				consult_date = '{$consult_date}',
				stt_text     = '{$stt_text}',
				stt_summary  = '{$stt_summary}',
				status       = 'completed',
				update_date  = NOW()
			WHERE uid = {$uid}
		");

		header("Location: /Consultation/detail?uid={$uid}");
		exit;
	}

	// 상담 삭제 (manager+ 전용)
	public function deleteProc(): void
	{
		global $db_local;
		login_check();
		role_check('manager');

		header('Content-Type: application/json');

		$uid = (int)($_POST['uid'] ?? 0);
		$r   = $db_local->query("SELECT audio_file FROM tndnjstl_consultation WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0)
		{
			echo json_encode(['success' => false, 'message' => '상담을 찾을 수 없습니다.']);
			exit;
		}
		$row = $r->fetch_assoc();

		// 음성 파일 삭제
		if (!empty($row['audio_file']))
		{
			$file_path = BASE_PATH . $row['audio_file'];
			if (file_exists($file_path)) @unlink($file_path);
		}

		$db_local->query("DELETE FROM tndnjstl_consultation WHERE uid = {$uid}");
		echo json_encode(['success' => true]);
		exit;
	}

	// 녹음 파일 다운로드 (권한 체크 후 스트림)
	public function downloadAudio(): void
	{
		global $db_local;
		login_check();

		$uid = (int)($_GET['uid'] ?? 0);
		$r   = $db_local->query("SELECT member_id, audio_file, title FROM tndnjstl_consultation WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0)
		{
			http_response_code(404); exit;
		}
		$row = $r->fetch_assoc();

		// 권한 체크
		if (!is_manager() && $row['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			http_response_code(403); exit;
		}

		if (empty($row['audio_file']))
		{
			http_response_code(404); exit;
		}

		$file_path = BASE_PATH . $row['audio_file'];
		if (!file_exists($file_path))
		{
			http_response_code(404); exit;
		}

		$filename = urlencode($row['title']) . '.webm';
		header('Content-Type: audio/webm');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . filesize($file_path));
		readfile($file_path);
		exit;
	}
}
