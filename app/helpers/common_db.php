<?php
$db_config = array(
	'host'		=> 'localhost',
	'user'		=> 'tndnjstl',
	'password'	=> 'rkddkwl1!',
	'database'	=> 'tndnjstl',
	'port'		=> 3306
);

$db_local = new mysqli(
	$db_config['host'],
	$db_config['user'],
	$db_config['password'],
	$db_config['database'],
	$db_config['port']
);

if ($db_local->connect_errno) {
	die('DB 연결 실패 : ' . $db_local->connect_error);
}

$db_local->set_charset('utf8mb4');