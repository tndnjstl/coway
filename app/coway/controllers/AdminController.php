<?php
class AdminController
{
	private function json(array $data): void
	{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// 사용자 목록
	public function memberList(): void
	{
		global $db_local;
		login_check();
		role_check('admin');

		$r = $db_local->query("SELECT uid, member_id, member_name, role, register_date FROM tndnjstl_member ORDER BY uid ASC");
		$members = [];
		if ($r) while ($row = $r->fetch_assoc()) $members[] = $row;

		include VIEW_PATH . '/admin_member_list_view.php';
	}

	// AJAX: 사용자 등록
	public function memberAddProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);
		role_check('admin');

		$json  = json_decode(file_get_contents('php://input'), true) ?? [];
		$id    = $db_local->real_escape_string(trim($json['member_id']   ?? ''));
		$name  = $db_local->real_escape_string(trim($json['member_name'] ?? ''));
		$pw    = $db_local->real_escape_string(trim($json['password']    ?? ''));
		$role  = $db_local->real_escape_string(trim($json['role']        ?? 'staff'));

		if ($id === '' || $name === '' || $pw === '')
		{
			$this->json(['success' => false, 'message' => 'ID, 이름, 비밀번호는 필수입니다.']);
		}
		if (!in_array($role, ['staff', 'manager', 'admin']))
		{
			$this->json(['success' => false, 'message' => '잘못된 role 값입니다.']);
		}

		// 중복 확인
		$chk = $db_local->query("SELECT uid FROM tndnjstl_member WHERE member_id = '{$id}' LIMIT 1");
		if ($chk && $chk->num_rows > 0)
		{
			$this->json(['success' => false, 'message' => '이미 사용 중인 아이디입니다.']);
		}

		$is_admin_val = ($role === 'admin') ? 1 : 0;
		$db_local->query("
			INSERT INTO tndnjstl_member SET
				member_id     = '{$id}',
				member_name   = '{$name}',
				password      = '{$pw}',
				role          = '{$role}',
				is_admin      = {$is_admin_val},
				register_date = NOW()
		");

		$this->json(['success' => true, 'message' => '등록 완료', 'uid' => $db_local->insert_id]);
	}

	// AJAX: 사용자 수정 (role 변경 포함)
	public function memberEditProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);
		role_check('admin');

		$json  = json_decode(file_get_contents('php://input'), true) ?? [];
		$uid   = (int)($json['uid']         ?? 0);
		$name  = $db_local->real_escape_string(trim($json['member_name'] ?? ''));
		$role  = $db_local->real_escape_string(trim($json['role']        ?? 'staff'));
		$pw    = trim($json['password'] ?? '');

		if (!in_array($role, ['staff', 'manager', 'admin']))
		{
			$this->json(['success' => false, 'message' => '잘못된 role 값입니다.']);
		}

		$is_admin_val = ($role === 'admin') ? 1 : 0;
		$pw_set = '';
		if ($pw !== '')
		{
			$pw_esc = $db_local->real_escape_string($pw);
			$pw_set = ", password = '{$pw_esc}'";
		}

		$db_local->query("
			UPDATE tndnjstl_member SET
				member_name = '{$name}',
				role        = '{$role}',
				is_admin    = {$is_admin_val}
				{$pw_set}
			WHERE uid = {$uid}
		");

		$this->json(['success' => true, 'message' => '수정 완료']);
	}

	// AJAX: 사용자 삭제
	public function memberDeleteProc(): void
	{
		global $db_local;
		if (!login_check(true)) $this->json(['success' => false, 'message' => '로그인 필요']);
		role_check('admin');

		$json = json_decode(file_get_contents('php://input'), true) ?? [];
		$uid  = (int)($json['uid'] ?? 0);

		// 자기 자신 삭제 방지
		$my_id = $_SESSION['member_id'] ?? '';
		$r = $db_local->query("SELECT member_id FROM tndnjstl_member WHERE uid = {$uid} LIMIT 1");
		if ($r && $r->num_rows > 0)
		{
			$row = $r->fetch_assoc();
			if ($row['member_id'] === $my_id)
			{
				$this->json(['success' => false, 'message' => '자기 자신은 삭제할 수 없습니다.']);
			}
		}

		$db_local->query("DELETE FROM tndnjstl_member WHERE uid = {$uid}");
		$this->json(['success' => true, 'message' => '삭제 완료']);
	}

	// 제품 목록
	public function productList(): void
	{
		global $db_local;
		login_check();
		role_check('admin');

		$category = trim($_GET['category'] ?? '');
		$where    = '';
		if ($category !== '')
		{
			$cat = $db_local->real_escape_string($category);
			$where = "WHERE m.category = '{$cat}'";
		}

		$r = $db_local->query("
			SELECT m.*, p.rent_price, p.normal_price, p.setup_price
			FROM tndnjstl_model AS m
			LEFT JOIN tndnjstl_price AS p ON p.model_uid = m.model_uid
			{$where}
			ORDER BY m.category ASC, m.register_date DESC
		");
		$products = [];
		if ($r) while ($row = $r->fetch_assoc()) $products[] = $row;

		// 카테고리 목록
		$cr = $db_local->query("SELECT DISTINCT category FROM tndnjstl_model ORDER BY category ASC");
		$categories = [];
		if ($cr) while ($row = $cr->fetch_assoc()) $categories[] = $row['category'];

		include VIEW_PATH . '/admin_product_list_view.php';
	}

	// 제품 크롤링 실행
	public function productCrawlProc(): void
	{
		login_check();
		role_check('admin');
		crawl_product();
		exit;
	}

	// 공통코드 목록 (추후 확장용)
	public function codeList(): void
	{
		login_check();
		role_check('admin');
		include VIEW_PATH . '/admin_code_list_view.php';
	}
}
