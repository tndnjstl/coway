<?php
class ScheduleController
{
	private function json(array $data): void
	{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// 달력 뷰
	public function index(): void
	{
		login_check();
		include VIEW_PATH . '/schedule_view.php';
	}

	// AJAX: FullCalendar events 포맷
	public function getEvents(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);

		$start = $db_local->real_escape_string($_GET['start'] ?? '');
		$end   = $db_local->real_escape_string($_GET['end']   ?? '');

		$conds = ["s.schedule_date >= '{$start}'", "s.schedule_date <= '{$end}'"];
		if (!is_manager())
		{
			$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
			$conds[] = "s.member_id = '{$mid}'";
		}
		$where = 'WHERE ' . implode(' AND ', $conds);

		$r = $db_local->query("
			SELECT s.*, c.customer_name
			FROM tndnjstl_schedule AS s
			LEFT JOIN tndnjstl_customer AS c ON c.uid = s.customer_uid
			{$where}
			ORDER BY s.schedule_date ASC, s.schedule_time ASC
		");

		$color_map = [
			'visit'   => '#1e40af',
			'as'      => '#f97316',
			'install' => '#16a34a',
			'consult' => '#7c3aed',
		];

		$events = [];
		if ($r) while ($row = $r->fetch_assoc())
		{
			$start_dt = $row['schedule_date'];
			if (!empty($row['schedule_time'])) $start_dt .= 'T' . $row['schedule_time'];
			$color = $color_map[$row['schedule_type']] ?? '#64748b';
			if ($row['status'] === 'done')   $color = '#94a3b8';
			if ($row['status'] === 'cancel') $color = '#d1d5db';

			$events[] = [
				'id'              => $row['uid'],
				'title'           => $row['title'],
				'start'           => $start_dt,
				'backgroundColor' => $color,
				'borderColor'     => $color,
				'textColor'       => '#fff',
				'extendedProps'   => [
					'schedule_type' => $row['schedule_type'],
					'member_id'     => $row['member_id'],
					'customer_name' => $row['customer_name'] ?? '',
					'memo'          => $row['memo'] ?? '',
					'status'        => $row['status'],
					'schedule_time' => $row['schedule_time'] ?? '',
					'customer_uid'  => $row['customer_uid'],
				],
			];
		}

		$this->json($events);
	}

	// AJAX: 등록
	public function addProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);

		$json  = json_decode(file_get_contents('php://input'), true) ?? [];
		$type  = $db_local->real_escape_string(trim($json['schedule_type'] ?? ''));
		$title = $db_local->real_escape_string(trim($json['title']         ?? ''));
		$date  = $db_local->real_escape_string(trim($json['schedule_date'] ?? ''));
		$time  = $db_local->real_escape_string(trim($json['schedule_time'] ?? ''));
		$memo  = $db_local->real_escape_string(trim($json['memo']          ?? ''));
		$cuid  = (int)($json['customer_uid'] ?? 0);
		$mid   = $db_local->real_escape_string($_SESSION['member_id'] ?? '');

		if ($title === '' || $date === '')
		{
			$this->json(['success' => false, 'message' => '제목과 날짜는 필수입니다.']);
		}

		$time_val  = $time !== '' ? "'{$time}'" : 'NULL';
		$cuid_val  = $cuid > 0   ? $cuid        : 'NULL';

		$db_local->query("
			INSERT INTO tndnjstl_schedule SET
				member_id     = '{$mid}',
				customer_uid  = {$cuid_val},
				schedule_type = '{$type}',
				title         = '{$title}',
				schedule_date = '{$date}',
				schedule_time = {$time_val},
				memo          = '{$memo}',
				status        = 'pending',
				register_date = NOW()
		");

		$this->json(['success' => true, 'message' => '등록 완료', 'id' => $db_local->insert_id]);
	}

	// AJAX: 수정
	public function editProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);

		$json   = json_decode(file_get_contents('php://input'), true) ?? [];
		$uid    = (int)($json['uid'] ?? 0);
		$type   = $db_local->real_escape_string(trim($json['schedule_type'] ?? ''));
		$title  = $db_local->real_escape_string(trim($json['title']         ?? ''));
		$date   = $db_local->real_escape_string(trim($json['schedule_date'] ?? ''));
		$time   = $db_local->real_escape_string(trim($json['schedule_time'] ?? ''));
		$memo   = $db_local->real_escape_string(trim($json['memo']          ?? ''));
		$status = $db_local->real_escape_string(trim($json['status']        ?? 'pending'));
		$cuid   = (int)($json['customer_uid'] ?? 0);

		$time_val = $time !== '' ? "'{$time}'" : 'NULL';
		$cuid_val = $cuid > 0   ? $cuid        : 'NULL';

		// staff는 본인 일정만 수정 가능
		if (!is_manager())
		{
			$r = $db_local->query("SELECT member_id FROM tndnjstl_schedule WHERE uid = {$uid} LIMIT 1");
			if (!$r || $r->num_rows === 0) $this->json(['success' => false, 'message' => '일정을 찾을 수 없습니다.']);
			$row = $r->fetch_assoc();
			if ($row['member_id'] !== ($_SESSION['member_id'] ?? ''))
			{
				$this->json(['success' => false, 'message' => '권한이 없습니다.']);
			}
		}

		$db_local->query("
			UPDATE tndnjstl_schedule SET
				customer_uid  = {$cuid_val},
				schedule_type = '{$type}',
				title         = '{$title}',
				schedule_date = '{$date}',
				schedule_time = {$time_val},
				memo          = '{$memo}',
				status        = '{$status}'
			WHERE uid = {$uid}
		");

		$this->json(['success' => true, 'message' => '수정 완료']);
	}

	// AJAX: 삭제
	public function deleteProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);

		$json = json_decode(file_get_contents('php://input'), true) ?? [];
		$uid  = (int)($json['uid'] ?? 0);

		if (!is_manager())
		{
			$r = $db_local->query("SELECT member_id FROM tndnjstl_schedule WHERE uid = {$uid} LIMIT 1");
			if (!$r || $r->num_rows === 0) $this->json(['success' => false, 'message' => '일정을 찾을 수 없습니다.']);
			$row = $r->fetch_assoc();
			if ($row['member_id'] !== ($_SESSION['member_id'] ?? ''))
			{
				$this->json(['success' => false, 'message' => '권한이 없습니다.']);
			}
		}

		$db_local->query("DELETE FROM tndnjstl_schedule WHERE uid = {$uid}");
		$this->json(['success' => true, 'message' => '삭제 완료']);
	}
}
