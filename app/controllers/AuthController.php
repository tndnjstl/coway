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

		//로그인 된 상태면 바로 메인 페이지 이동
		if(isset($_SESSION['is_login']) && $_SESSION['is_login'])
		{
			//접근 페이지가 있으면
			if( $_SERVER['HTTP_REFERER'] != '' )
			{
				header('Location: ' . $_SERVER['HTTP_REFERER']);
			}
			//업으면 메인으로 이동
			else
			{
				header('Location: /');
			}
		}
		else
		{
			$member_id = $_REQUEST['member_id'];
			$password = $_REQUEST['password'];

			$sql = "
				SELECT
					*
				FROM tndnjstl_member
				WHERE 1=1
					AND member_id = '{$member_id}'
					AND password = '{$password}'
			";

			$result = $db_local->query($sql);

			$row = $result->fetch_assoc();

			//정보 없음
			if (!$result || $result->num_rows === 0)
			{
				alert('아이디 또는 비밀번호를 확인해주세요.');
				exit;
			}

			//세선 저장
			$_SESSION['is_login'] = true;
			$_SESSION['member_id'] = $member_id;
			$_SESSION['info'] = $row;

			//페이지 이동
			header('Location: /');
		}
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

		header('Location: /');
	}

}
