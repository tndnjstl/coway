<?php
class CustomerController
{
	// 계정 필터 (manager+ 는 전체, staff는 본인만)
	private function member_filter(string $alias = 'c'): string
	{
		if (is_manager()) return '';
		global $db_local;
		$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
		return "{$alias}.member_id = '{$mid}'";
	}

	// 고객 목록
	public function list(): void
	{
		global $db_local;
		login_check();

		$keyword   = trim($_GET['keyword']   ?? '');
		$filter_type = trim($_GET['type']    ?? '');
		$filter_mid  = trim($_GET['member_id'] ?? '');

		$conds = [];
		$mf = $this->member_filter();
		if ($mf !== '') $conds[] = $mf;
		if ($keyword !== '')
		{
			$kw = $db_local->real_escape_string($keyword);
			$conds[] = "(c.customer_name LIKE '%{$kw}%' OR c.customer_phone LIKE '%{$kw}%')";
		}
		if (in_array($filter_type, ['P', 'B', 'C']))
		{
			$tp = $db_local->real_escape_string($filter_type);
			$conds[] = "c.customer_type = '{$tp}'";
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
				COUNT(o.uid) AS order_count
			FROM tndnjstl_customer AS c
			LEFT JOIN tndnjstl_order AS o ON o.customer_uid = c.uid
			{$where}
			GROUP BY c.uid
			ORDER BY c.register_date DESC
		");
		$customers = [];
		if ($r) while ($row = $r->fetch_assoc()) $customers[] = $row;

		// manager+ : 담당자 목록 (필터용)
		$members = [];
		if (is_manager())
		{
			$mr = $db_local->query("SELECT member_id, member_name FROM tndnjstl_member ORDER BY member_name ASC");
			if ($mr) while ($row = $mr->fetch_assoc()) $members[] = $row;
		}

		include VIEW_PATH . '/customer_list_view.php';
	}

	// 고객 상세
	public function detail(): void
	{
		global $db_local;
		login_check();

		$uid = (int)($_GET['uid'] ?? 0);
		if ($uid === 0)
		{
			echo "<script>alert('잘못된 접근입니다.');history.back();</script>"; exit;
		}

		$r = $db_local->query("SELECT * FROM tndnjstl_customer WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0)
		{
			echo "<script>alert('고객을 찾을 수 없습니다.');history.back();</script>"; exit;
		}
		$customer = $r->fetch_assoc();

		// 권한: staff는 본인 고객만
		if (!is_manager() && $customer['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			echo "<script>alert('접근 권한이 없습니다.');history.back();</script>"; exit;
		}

		// 주문 이력
		$orders = [];
		$or = $db_local->query("
			SELECT
				o.uid, o.status, o.register_date,
				COUNT(oi.uid)     AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			WHERE o.customer_uid = {$uid}
			GROUP BY o.uid
			ORDER BY o.register_date DESC
		");
		if ($or) while ($row = $or->fetch_assoc()) $orders[] = $row;

		include VIEW_PATH . '/customer_detail_view.php';
	}

	// 고객 등록 폼
	public function add(): void
	{
		login_check();
		include VIEW_PATH . '/customer_form_view.php';
	}

	// 고객 등록 처리
	public function addProc(): void
	{
		global $db_local;
		login_check();

		$type  = $db_local->real_escape_string(trim($_POST['customer_type']  ?? ''));
		$name  = $db_local->real_escape_string(trim($_POST['customer_name']  ?? ''));
		$phone = $db_local->real_escape_string(trim($_POST['customer_phone'] ?? ''));
		$email = $db_local->real_escape_string(trim($_POST['customer_email'] ?? ''));
		$addr  = $db_local->real_escape_string(trim($_POST['address']        ?? ''));
		$memo  = $db_local->real_escape_string(trim($_POST['memo']           ?? ''));
		$mid   = $db_local->real_escape_string($_SESSION['member_id'] ?? '');

		if ($name === '' || $phone === '')
		{
			echo "<script>alert('고객명과 전화번호는 필수입니다.');history.back();</script>"; exit;
		}

		$db_local->query("
			INSERT INTO tndnjstl_customer SET
				customer_type  = '{$type}',
				customer_name  = '{$name}',
				customer_phone = '{$phone}',
				customer_email = '{$email}',
				address        = '{$addr}',
				memo           = '{$memo}',
				member_id      = '{$mid}',
				register_date  = NOW()
		");

		header('Location: /Customer/list');
		exit;
	}

	// 고객 수정 폼
	public function edit(): void
	{
		global $db_local;
		login_check();

		$uid = (int)($_GET['uid'] ?? 0);
		$r = $db_local->query("SELECT * FROM tndnjstl_customer WHERE uid = {$uid} LIMIT 1");
		if (!$r || $r->num_rows === 0)
		{
			echo "<script>alert('고객을 찾을 수 없습니다.');history.back();</script>"; exit;
		}
		$customer = $r->fetch_assoc();

		if (!is_manager() && $customer['member_id'] !== ($_SESSION['member_id'] ?? ''))
		{
			echo "<script>alert('접근 권한이 없습니다.');history.back();</script>"; exit;
		}

		include VIEW_PATH . '/customer_form_view.php';
	}

	// 고객 수정 처리
	public function editProc(): void
	{
		global $db_local;
		login_check();

		$uid   = (int)($_POST['uid'] ?? 0);
		$type  = $db_local->real_escape_string(trim($_POST['customer_type']  ?? ''));
		$name  = $db_local->real_escape_string(trim($_POST['customer_name']  ?? ''));
		$phone = $db_local->real_escape_string(trim($_POST['customer_phone'] ?? ''));
		$email = $db_local->real_escape_string(trim($_POST['customer_email'] ?? ''));
		$addr  = $db_local->real_escape_string(trim($_POST['address']        ?? ''));
		$memo  = $db_local->real_escape_string(trim($_POST['memo']           ?? ''));

		$db_local->query("
			UPDATE tndnjstl_customer SET
				customer_type  = '{$type}',
				customer_name  = '{$name}',
				customer_phone = '{$phone}',
				customer_email = '{$email}',
				address        = '{$addr}',
				memo           = '{$memo}',
				update_date    = NOW()
			WHERE uid = {$uid}
		");

		header("Location: /Customer/detail?uid={$uid}");
		exit;
	}
}
