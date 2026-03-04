<?php
/**
 * v2 DB 마이그레이션 실행 스크립트
 * 실행 후 반드시 삭제할 것
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

require_once HELPER_PATH . '/common_db.php';

$sql_file = BASE_PATH . '/sql/v2_sales_features.sql';
if (!file_exists($sql_file)) {
    die('SQL 파일을 찾을 수 없습니다: ' . $sql_file);
}

$sql_raw = file_get_contents($sql_file);

// 주석 제거 및 세미콜론으로 분리
$statements = array_filter(
    array_map('trim', explode(';', $sql_raw)),
    fn($s) => $s !== '' && !preg_match('/^--/', trim($s))
);

echo '<pre>';
echo "=== v2 마이그레이션 시작 ===\n\n";

$success = 0;
$errors  = 0;

foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if (empty($stmt) || substr($stmt, 0, 2) === '--') continue;

    if ($db_local->query($stmt)) {
        echo "[OK] " . substr($stmt, 0, 80) . "...\n";
        $success++;
    } else {
        echo "[ERR] " . $db_local->error . "\n";
        echo "  SQL: " . substr($stmt, 0, 120) . "\n";
        $errors++;
    }
}

echo "\n=== 완료: 성공 {$success}건 / 오류 {$errors}건 ===\n";
echo '</pre>';
