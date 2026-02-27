<?php
class AuthController
{
	//로그인
	public function login()
	{
		include VIEW_PATH . '/login_view.php';
	}

	//로그인 처리
	public function loginProc(): void
	{
		global $db_local;

		// 이미 로그인 상태면 메인으로
		if (isset($_SESSION['is_login']) && $_SESSION['is_login']) {
			header('Location: /');
			exit;
		}

		$member_id = trim($_POST['member_id'] ?? '');
		$password  = trim($_POST['password']  ?? '');

		if ($member_id === '' || $password === '') {
			echo "<script>alert('아이디와 비밀번호를 입력해주세요.');history.back();</script>";
			exit;
		}

		$member_id_esc = $db_local->real_escape_string($member_id);
		$password_esc  = $db_local->real_escape_string($password);

		$sql = "
			SELECT *
			FROM tndnjstl_member
			WHERE member_id = '{$member_id_esc}'
			  AND password  = '{$password_esc}'
			LIMIT 1
		";

		$result = $db_local->query($sql);

		if (!$result || $result->num_rows === 0) {
			echo "<script>alert('아이디 또는 비밀번호를 확인해주세요.');history.back();</script>";
			exit;
		}

		$row = $result->fetch_assoc();

		$_SESSION['is_login']  = true;
		$_SESSION['member_id'] = $member_id;
		$_SESSION['info']      = $row;

		header('Location: /');
		exit;
	}

	public function logout()
	{
		if (session_status() === PHP_SESSION_NONE)
		{
			session_start();
		}

		$_SESSION = [];

		if (ini_get('session.use_cookies'))
		{
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		session_destroy();

		header('Location: /Auth/login');
		exit;
	}

}
