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

// 컬럼 존재 여부 확인 헬퍼
function col_exists($db, $table, $col) {
	$r = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'");
	return $r && $r->num_rows > 0;
}

// 테이블 존재 여부 확인 헬퍼
function table_exists($db, $table) {
	$r = $db->query("SHOW TABLES LIKE '{$table}'");
	return $r && $r->num_rows > 0;
}

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>SQL 마이그레이션</title>";
echo "<style>
body{font-family:sans-serif;padding:40px;background:#f0f2f5;}
.card{background:#fff;border-radius:8px;padding:30px;max-width:700px;margin:auto;box-shadow:0 2px 12px rgba(0,0,0,.1);}
h2{color:#1e40af;margin-bottom:20px;}
p{margin:6px 0;font-size:14px;line-height:1.6;}
.ok{color:#16a34a;} .err{color:#dc2626;} .skip{color:#94a3b8;} .info{color:#64748b;}
hr{margin:20px 0;}
</style></head><body><div class='card'>";
echo "<h2>SQL 마이그레이션 실행</h2>";

$db = $db_local;

// ────────────────────────────────────────────
// 1. tndnjstl_member: role 컬럼
// ────────────────────────────────────────────
if (!col_exists($db, 'tndnjstl_member', 'role')) {
	$r = $db->query("ALTER TABLE tndnjstl_member ADD COLUMN `role` ENUM('staff','manager','admin') NOT NULL DEFAULT 'staff' AFTER is_admin");
	echo $r ? "<p class='ok'>✅ tndnjstl_member.role 컬럼 추가 완료</p>"
	        : "<p class='err'>❌ role 컬럼 추가 실패: " . $db->error . "</p>";
} else {
	echo "<p class='skip'>⏭ tndnjstl_member.role 컬럼 이미 존재 (스킵)</p>";
}

// ────────────────────────────────────────────
// 2. tndnjstl_member: register_date 컬럼
// ────────────────────────────────────────────
if (!col_exists($db, 'tndnjstl_member', 'register_date')) {
	$r = $db->query("ALTER TABLE tndnjstl_member ADD COLUMN register_date DATETIME DEFAULT NULL");
	echo $r ? "<p class='ok'>✅ tndnjstl_member.register_date 컬럼 추가 완료</p>"
	        : "<p class='err'>❌ register_date 컬럼 추가 실패: " . $db->error . "</p>";
} else {
	echo "<p class='skip'>⏭ tndnjstl_member.register_date 컬럼 이미 존재 (스킵)</p>";
}

// ────────────────────────────────────────────
// 3. is_admin=1 → role='admin' 동기화
// ────────────────────────────────────────────
$r = $db->query("UPDATE tndnjstl_member SET `role` = 'admin' WHERE is_admin = 1 AND `role` = 'staff'");
echo $r ? "<p class='ok'>✅ is_admin=1 계정 role='admin' 동기화 완료 (영향: " . $db->affected_rows . "건)</p>"
        : "<p class='err'>❌ role 동기화 실패: " . $db->error . "</p>";

// ────────────────────────────────────────────
// 4. tndnjstl_customer 테이블 생성
// ────────────────────────────────────────────
if (!table_exists($db, 'tndnjstl_customer')) {
	$r = $db->query("
		CREATE TABLE tndnjstl_customer (
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
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	");
	echo $r ? "<p class='ok'>✅ tndnjstl_customer 테이블 생성 완료</p>"
	        : "<p class='err'>❌ customer 테이블 생성 실패: " . $db->error . "</p>";
} else {
	echo "<p class='skip'>⏭ tndnjstl_customer 테이블 이미 존재 (스킵)</p>";
}

// ────────────────────────────────────────────
// 5. tndnjstl_order: customer_uid 컬럼
// ────────────────────────────────────────────
if (!col_exists($db, 'tndnjstl_order', 'customer_uid')) {
	$r = $db->query("ALTER TABLE tndnjstl_order ADD COLUMN customer_uid INT DEFAULT NULL AFTER member_id");
	echo $r ? "<p class='ok'>✅ tndnjstl_order.customer_uid 컬럼 추가 완료</p>"
	        : "<p class='err'>❌ customer_uid 컬럼 추가 실패: " . $db->error . "</p>";
} else {
	echo "<p class='skip'>⏭ tndnjstl_order.customer_uid 컬럼 이미 존재 (스킵)</p>";
}

// ────────────────────────────────────────────
// 6. tndnjstl_schedule 테이블 생성
// ────────────────────────────────────────────
if (!table_exists($db, 'tndnjstl_schedule')) {
	$r = $db->query("
		CREATE TABLE tndnjstl_schedule (
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
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
	");
	echo $r ? "<p class='ok'>✅ tndnjstl_schedule 테이블 생성 완료</p>"
	        : "<p class='err'>❌ schedule 테이블 생성 실패: " . $db->error . "</p>";
} else {
	echo "<p class='skip'>⏭ tndnjstl_schedule 테이블 이미 존재 (스킵)</p>";
}

echo "<hr><p class='info'>✔ 마이그레이션 완료. 로그아웃 후 재로그인하면 반영됩니다.</p>";
echo "<p><a href='/'>홈으로 이동</a></p>";
echo "</div></body></html>";
