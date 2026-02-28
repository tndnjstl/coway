<?php
/**
 * SQL 마이그레이션 실행 스크립트 (1회용)
 * 접속: https://도메인/run_sql.php?key=coway2024
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

$secret = 'coway2024';
if (($_GET['key'] ?? '') !== $secret) {
	http_response_code(403);
	exit('403 Forbidden');
}

ini_set('default_charset', 'UTF-8');
require_once HELPER_PATH . '/common_db.php';

$sqls = [
	// 1. member role 컬럼 추가
	"ALTER TABLE tndnjstl_member ADD COLUMN IF NOT EXISTS `role` ENUM('staff','manager','admin') NOT NULL DEFAULT 'staff' AFTER is_admin",
	// 2. member register_date 컬럼 추가 (없을 수 있음)
	"ALTER TABLE tndnjstl_member ADD COLUMN IF NOT EXISTS register_date DATETIME DEFAULT NULL",
	// 3. 기존 is_admin=1 → role=admin 동기화
	"UPDATE tndnjstl_member SET `role` = 'admin' WHERE is_admin = 1 AND `role` = 'staff'",
	// 4. customer 테이블 생성
	"CREATE TABLE IF NOT EXISTS tndnjstl_customer (
		uid           INT          AUTO_INCREMENT PRIMARY KEY,
		customer_type CHAR(1)      NOT NULL,
		customer_name VARCHAR(100) NOT NULL,
		customer_phone VARCHAR(20) NOT NULL,
		customer_email VARCHAR(100) DEFAULT NULL,
		address       VARCHAR(300) DEFAULT NULL,
		memo          TEXT         DEFAULT NULL,
		member_id     VARCHAR(50)  NOT NULL,
		register_date DATETIME     NOT NULL DEFAULT NOW(),
		update_date   DATETIME     DEFAULT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
	// 5. order 테이블에 customer_uid 추가
	"ALTER TABLE tndnjstl_order ADD COLUMN IF NOT EXISTS customer_uid INT DEFAULT NULL AFTER member_id",
	// 6. schedule 테이블 생성
	"CREATE TABLE IF NOT EXISTS tndnjstl_schedule (
		uid           INT          AUTO_INCREMENT PRIMARY KEY,
		member_id     VARCHAR(50)  NOT NULL,
		customer_uid  INT          DEFAULT NULL,
		schedule_type VARCHAR(20)  NOT NULL,
		title         VARCHAR(200) NOT NULL,
		schedule_date DATE         NOT NULL,
		schedule_time TIME         DEFAULT NULL,
		memo          TEXT         DEFAULT NULL,
		status        CHAR(10)     NOT NULL DEFAULT 'pending',
		register_date DATETIME     NOT NULL DEFAULT NOW()
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>SQL 실행</title>";
echo "<style>body{font-family:sans-serif;padding:40px;background:#f0f2f5;}
.card{background:#fff;border-radius:8px;padding:30px;max-width:700px;margin:auto;box-shadow:0 2px 12px rgba(0,0,0,.1);}
h2{color:#1e40af;}.ok{color:#16a34a;}.err{color:#dc2626;}.info{color:#64748b;}</style></head><body><div class='card'>";
echo "<h2>SQL 마이그레이션 실행</h2>";

foreach ($sqls as $sql) {
	$preview = trim(preg_replace('/\s+/', ' ', substr($sql, 0, 80)));
	$result = $db_local->query($sql);
	if ($result === false) {
		echo "<p class='err'>❌ {$preview}...<br>&nbsp;&nbsp;&nbsp;오류: " . $db_local->error . "</p>";
	} else {
		echo "<p class='ok'>✅ {$preview}...</p>";
	}
}

echo "<hr><p class='info'>완료. 이 파일을 삭제하거나 접근을 차단하세요.</p>";
echo "<p><a href='/'>홈으로 이동</a></p>";
echo "</div></body></html>";
