<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// 기본값
$controller_name = 'MainController';
$method = 'main';

if ($uri !== '') {
	$segments = explode('/', $uri);

	if (!empty($segments[0])) {
		$controller_name = ucfirst($segments[0]) . 'Controller';
	}

	if (!empty($segments[1])) {
		$method = preg_replace('/[^a-zA-Z0-9_]/', '', $segments[1]);
	}
}

$controller_file = CONTROLLER_PATH . '/' . $controller_name . '.php';

if (!is_file($controller_file)) {
	http_response_code(404);
	require VIEW_PATH . '/404.php';
	exit;
}

require_once $controller_file;

$controller = new $controller_name();

if (!method_exists($controller, $method)) {
	http_response_code(404);
	require VIEW_PATH . '/404.php';
	exit;
}

$controller->$method();
