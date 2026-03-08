<?php
class ManageController
{
	private function json(array $data): void
	{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// 팀 주문 현황
	public function orderList(): void
	{
		global $db_local;
		login_check();
		role_check('manager');

		$keyword       = trim($_GET['keyword']   ?? '');
		$filter_status = trim($_GET['status']    ?? '');
		$filter_type   = trim($_GET['type']      ?? '');
		$filter_mid    = trim($_GET['member_id'] ?? '');
		$date_from     = trim($_GET['date_from'] ?? '');
		$date_to       = trim($_GET['date_to']   ?? '');

		$conds = [];
		if ($keyword !== '')
		{
			$kw = $db_local->real_escape_string($keyword);
			$conds[] = "(o.customer_name LIKE '%{$kw}%' OR o.customer_phone LIKE '%{$kw}%')";
		}
		if (in_array($filter_status, ['prospect', 'contracted', 'installed']))
		{
			$st = $db_local->real_escape_string($filter_status);
			$conds[] = "o.status = '{$st}'";
		}
		if (in_array($filter_type, ['P', 'B', 'C']))
		{
			$tp = $db_local->real_escape_string($filter_type);
			$conds[] = "o.customer_type = '{$tp}'";
		}
		if ($filter_mid !== '')
		{
			$fm = $db_local->real_escape_string($filter_mid);
			$conds[] = "o.member_id = '{$fm}'";
		}
		if ($date_from !== '')
		{
			$df = $db_local->real_escape_string($date_from);
			$conds[] = "DATE(o.register_date) >= '{$df}'";
		}
		if ($date_to !== '')
		{
			$dt = $db_local->real_escape_string($date_to);
			$conds[] = "DATE(o.register_date) <= '{$dt}'";
		}
		$where = count($conds) > 0 ? 'WHERE ' . implode(' AND ', $conds) : '';

		$r = $db_local->query("
			SELECT
				o.uid, o.customer_type, o.customer_name, o.customer_phone,
				o.member_id, o.memo, o.status, o.register_date,
				COUNT(oi.uid)     AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			{$where}
			GROUP BY o.uid
			ORDER BY o.register_date DESC
		");
		$orders = [];
		if ($r) while ($row = $r->fetch_assoc()) $orders[] = $row;

		// 요약 카드
		$sr = $db_local->query("
			SELECT
				COUNT(*) AS total,
				SUM(CASE WHEN o.status = 'contracted' THEN 1 ELSE 0 END) AS contracted,
				SUM(CASE WHEN o.status = 'installed'  THEN 1 ELSE 0 END) AS installed,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
		");
		$summary = ($sr && $sr->num_rows > 0) ? $sr->fetch_assoc() : [];

		// 담당자 목록 (필터용)
		$mr = $db_local->query("SELECT member_id, member_name FROM tndnjstl_member ORDER BY member_name ASC");
		$members = [];
		if ($mr) while ($row = $mr->fetch_assoc()) $members[] = $row;

		include VIEW_PATH . '/manage_order_list_view.php';
	}

	// 영업자 실적
	public function performance(): void
	{
		global $db_local;
		login_check();
		role_check('manager');

		$sel_month = trim($_GET['month'] ?? date('Y-m'));
		$month_esc = $db_local->real_escape_string($sel_month);

		// 이번 달 기준으로 전달 계산
		$prev_month = date('Y-m', strtotime($sel_month . '-01 -1 month'));
		$prev_esc   = $db_local->real_escape_string($prev_month);

		$r = $db_local->query("
			SELECT
				m.member_id,
				m.member_name,
				SUM(CASE WHEN DATE_FORMAT(o.register_date, '%Y-%m') = '{$month_esc}' AND o.status IN ('contracted','installed') THEN 1 ELSE 0 END) AS this_month,
				SUM(CASE WHEN DATE_FORMAT(o.register_date, '%Y-%m') = '{$prev_esc}'  AND o.status IN ('contracted','installed') THEN 1 ELSE 0 END) AS prev_month,
				SUM(CASE WHEN o.status IN ('contracted','installed') THEN 1 ELSE 0 END) AS total,
				COALESCE(SUM(CASE WHEN DATE_FORMAT(o.register_date, '%Y-%m') = '{$month_esc}' THEN oi.total_pay ELSE 0 END), 0) AS this_pay
			FROM tndnjstl_member AS m
			LEFT JOIN tndnjstl_order AS o ON o.member_id = m.member_id
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			GROUP BY m.member_id, m.member_name
			ORDER BY this_month DESC
		");
		$performance = [];
		if ($r) while ($row = $r->fetch_assoc()) $performance[] = $row;

		include VIEW_PATH . '/manage_performance_view.php';
	}

	// 주문 승인 관리
	public function approval(): void
	{
		global $db_local;
		login_check();
		role_check('manager');

		$r = $db_local->query("
			SELECT
				o.uid, o.customer_type, o.customer_name, o.customer_phone,
				o.member_id, o.register_date,
				COUNT(oi.uid)     AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			WHERE o.status = 'prospect'
			GROUP BY o.uid
			ORDER BY o.register_date ASC
		");
		$orders = [];
		if ($r) while ($row = $r->fetch_assoc()) $orders[] = $row;

		include VIEW_PATH . '/manage_approval_view.php';
	}

	// AJAX: 승인 처리
	public function approvalProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);
		role_check('manager');

		$json      = json_decode(file_get_contents('php://input'), true) ?? [];
		$order_uid = (int)($json['order_uid'] ?? 0);
		$action    = trim($json['action'] ?? ''); // 'approve' | 'reject'

		if ($order_uid === 0) $this->json(['success' => false, 'message' => '잘못된 요청']);

		if ($action === 'approve')
		{
			$db_local->query("UPDATE tndnjstl_order SET status = 'contracted' WHERE uid = {$order_uid}");
			$this->json(['success' => true, 'message' => '승인 완료']);
		}
		elseif ($action === 'reject')
		{
			$db_local->query("UPDATE tndnjstl_order SET status = 'rejected' WHERE uid = {$order_uid}");
			$this->json(['success' => true, 'message' => '반려 완료']);
		}
		else
		{
			$this->json(['success' => false, 'message' => '알 수 없는 액션']);
		}
	}
}
