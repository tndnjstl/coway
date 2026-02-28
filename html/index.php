<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| 기본 PHP 설정
|--------------------------------------------------------------------------
*/
ini_set('default_charset', 'UTF-8');
ini_set('display_errors', 'On');	// 안정화 후 Off
ini_set('log_errors', 'On');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Seoul');

/*
|--------------------------------------------------------------------------
| 경로 상수
|--------------------------------------------------------------------------
*/
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');
define('CONTROLLER_PATH', APP_PATH . '/controllers');
define('VIEW_PATH', APP_PATH . '/views');

/*
|--------------------------------------------------------------------------
| 세션 설정
|--------------------------------------------------------------------------
*/
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

$secure = !empty($_SERVER['HTTPS']);

session_set_cookie_params([
	'lifetime'	=> 0,
	'path'		=> '/',
	'domain'	=> '',
	'secure'	=> $secure,
	'httponly'	=> true,
	'samesite'	=> 'Lax',
]);

session_name('SID');
session_start();

/*
|--------------------------------------------------------------------------
| 오토로드 (helpers / controllers)
|--------------------------------------------------------------------------
*/
spl_autoload_register(function ($class) {
	foreach ([HELPER_PATH, CONTROLLER_PATH] as $path) {
		$file = $path . '/' . $class . '.php';
		if (is_file($file)) {
			require_once $file;
			return;
		}
	}
});

//공통파일
require_once APP_PATH . '/helpers/config.php';
require_once APP_PATH . '/helpers/common_db.php';
require_once APP_PATH . '/helpers/common_function.php';

/*
|--------------------------------------------------------------------------
| Router
|--------------------------------------------------------------------------
*/
if( $_SERVER['REQUEST_URI'] === '/product.php' ) {
	if( file_exists( BASE_PATH . '/product.php' ) )
	{
		require_once BASE_PATH . '/product.php';
		exit;
	}
	else
	{
		http_response_code(404);
		exit;
	}
}
require_once APP_PATH . '/router.php';
