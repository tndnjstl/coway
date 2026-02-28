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

		$member_id   = trim($_POST['member_id']   ?? '');
		$password    = trim($_POST['password']     ?? '');
		$save_id     = isset($_POST['save_id'])     && $_POST['save_id']     === '1';
		$remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

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

		$cookie_expire = 30 * 24 * 3600; // 30일
		$secure        = !empty($_SERVER['HTTPS']);

		// 아이디 저장
		if ($save_id) {
			setcookie('coway_saved_id', $member_id, [
				'expires'  => time() + $cookie_expire,
				'path'     => '/',
				'secure'   => $secure,
				'httponly' => false, // JS에서 읽어야 하므로 false
				'samesite' => 'Lax',
			]);
		} else {
			setcookie('coway_saved_id', '', ['expires' => time() - 3600, 'path' => '/']);
		}

		// 로그인 유지
		if ($remember_me) {
			$token = hash('sha256', $member_id . '|' . $row['password'] . '|' . COOKIE_SECRET);
			setcookie('coway_remember', $member_id . '|' . $token, [
				'expires'  => time() + $cookie_expire,
				'path'     => '/',
				'secure'   => $secure,
				'httponly' => true,
				'samesite' => 'Lax',
			]);
		} else {
			setcookie('coway_remember', '', ['expires' => time() - 3600, 'path' => '/']);
		}

		header('Location: /');
		exit;
	}

	public function logout()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$_SESSION = [];

		if (ini_get('session.use_cookies')) {
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

		// remember-me 쿠키 삭제 (아이디 저장은 유지)
		setcookie('coway_remember', '', ['expires' => time() - 3600, 'path' => '/']);

		header('Location: /Auth/login');
		exit;
	}

}
