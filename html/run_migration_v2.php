<?php
/**
 * v2 DB 마이그레이션 실행 스크립트
 * 실행 후 반드시 삭제할 것
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

require_once HELPER_PATH . '/common_db.php';

function col_exists($db, $table, $col) {
    $r = $db->query("SHOW COLUMNS FROM {$table} LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}
function table_exists($db, $table) {
    $r = $db->query("SHOW TABLES LIKE '{$table}'");
    return $r && $r->num_rows > 0;
}

$db = $db_local;

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>v2 마이그레이션</title>";
echo "<style>body{font-family:sans-serif;padding:40px;background:#f0f2f5;}.card{background:#fff;border-radius:8px;padding:30px;max-width:700px;margin:auto;box-shadow:0 2px 12px rgba(0,0,0,.1);}h2{color:#1e40af;margin-bottom:20px;}p{margin:6px 0;font-size:14px;line-height:1.6;}.ok{color:#16a34a;}.err{color:#dc2626;}.skip{color:#94a3b8;}hr{margin:20px 0;}</style></head><body><div class='card'>";
echo "<h2>v2 마이그레이션 실행</h2>";

// 1. tndnjstl_member: position
if (!col_exists($db, 'tndnjstl_member', 'position')) {
    $r = $db->query("ALTER TABLE tndnjstl_member ADD COLUMN `position` ENUM('staff','team_leader','director','branch_manager') NOT NULL DEFAULT 'staff' AFTER `role`");
    echo $r ? "<p class='ok'>OK member.position 추가 완료</p>" : "<p class='err'>ERR member.position 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP member.position 이미 존재</p>"; }

// 2. tndnjstl_member: branch_name
if (!col_exists($db, 'tndnjstl_member', 'branch_name')) {
    $r = $db->query("ALTER TABLE tndnjstl_member ADD COLUMN `branch_name` VARCHAR(100) DEFAULT NULL AFTER `position`");
    echo $r ? "<p class='ok'>OK member.branch_name 추가 완료</p>" : "<p class='err'>ERR member.branch_name 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP member.branch_name 이미 존재</p>"; }

// 3. tndnjstl_member: team_name
if (!col_exists($db, 'tndnjstl_member', 'team_name')) {
    $r = $db->query("ALTER TABLE tndnjstl_member ADD COLUMN `team_name` VARCHAR(100) DEFAULT NULL AFTER `branch_name`");
    echo $r ? "<p class='ok'>OK member.team_name 추가 완료</p>" : "<p class='err'>ERR member.team_name 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP member.team_name 이미 존재</p>"; }

// 4. tndnjstl_order_item: promo_uid
if (!col_exists($db, 'tndnjstl_order_item', 'promo_uid')) {
    $r = $db->query("ALTER TABLE tndnjstl_order_item ADD COLUMN `promo_uid` INT DEFAULT NULL AFTER `model_color`");
    echo $r ? "<p class='ok'>OK order_item.promo_uid 추가 완료</p>" : "<p class='err'>ERR order_item.promo_uid 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP order_item.promo_uid 이미 존재</p>"; }

// 5-a. tndnjstl_promotion: 누락 컬럼 추가
$promo_cols = ['discount_type'=>"ENUM('amount','percent') NOT NULL DEFAULT 'amount'", 'discount_value'=>'INT NOT NULL DEFAULT 0', 'base_fee'=>'INT NOT NULL DEFAULT 200000', 'special_fee'=>'INT NOT NULL DEFAULT 0', 'start_date'=>'DATE NOT NULL DEFAULT (CURDATE())', 'end_date'=>'DATE NOT NULL DEFAULT (CURDATE())', 'description'=>'TEXT DEFAULT NULL', 'image_path'=>'VARCHAR(300) DEFAULT NULL', 'is_active'=>'TINYINT(1) NOT NULL DEFAULT 1', 'register_id'=>"VARCHAR(50) NOT NULL DEFAULT ''"];
if (table_exists($db, 'tndnjstl_promotion')) {
    foreach ($promo_cols as $col => $def) {
        if (!col_exists($db, 'tndnjstl_promotion', $col)) {
            $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `{$col}` {$def}");
            echo $r ? "<p class='ok'>OK promotion.{$col} 추가 완료</p>" : "<p class='err'>ERR promotion.{$col} 실패: ".$db->error."</p>";
        } else { echo "<p class='skip'>SKIP promotion.{$col} 이미 존재</p>"; }
    }
}

// 5. tndnjstl_promotion 테이블
if (!table_exists($db, 'tndnjstl_promotion')) {
    $r = $db->query("CREATE TABLE tndnjstl_promotion (
        uid             INT          NOT NULL AUTO_INCREMENT,
        promo_name      VARCHAR(200) NOT NULL,
        target_category VARCHAR(200) DEFAULT NULL,
        discount_type   ENUM('amount','percent') NOT NULL DEFAULT 'amount',
        discount_value  INT          NOT NULL DEFAULT 0,
        base_fee        INT          NOT NULL DEFAULT 200000,
        special_fee     INT          NOT NULL DEFAULT 0,
        start_date      DATE         NOT NULL,
        end_date        DATE         NOT NULL,
        description     TEXT         DEFAULT NULL,
        image_path      VARCHAR(300) DEFAULT NULL,
        is_active       TINYINT(1)   NOT NULL DEFAULT 1,
        register_date   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        register_id     VARCHAR(50)  NOT NULL DEFAULT '',
        PRIMARY KEY (uid),
        KEY idx_active_date (is_active, start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo $r ? "<p class='ok'>OK tndnjstl_promotion 생성 완료</p>" : "<p class='err'>ERR promotion 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP tndnjstl_promotion 이미 존재</p>"; }

// 6. tndnjstl_commission 테이블
if (!table_exists($db, 'tndnjstl_commission')) {
    $r = $db->query("CREATE TABLE tndnjstl_commission (
        uid               INT         NOT NULL AUTO_INCREMENT,
        order_uid         INT         NOT NULL,
        order_item_uid    INT         NOT NULL DEFAULT 0,
        member_id         VARCHAR(50) NOT NULL,
        commission_type   ENUM('base','special','team','director','branch') NOT NULL,
        gross_amount      INT         NOT NULL DEFAULT 0,
        hold_amount       INT         NOT NULL DEFAULT 0,
        net_amount        INT         NOT NULL DEFAULT 0,
        hold_release_date DATE        DEFAULT NULL,
        hold_released     TINYINT(1)  NOT NULL DEFAULT 0,
        promo_uid         INT         DEFAULT NULL,
        register_date     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (uid),
        KEY idx_member       (member_id),
        KEY idx_order        (order_uid),
        KEY idx_hold_release (hold_release_date, hold_released),
        KEY idx_reg_date     (register_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo $r ? "<p class='ok'>OK tndnjstl_commission 생성 완료</p>" : "<p class='err'>ERR commission 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP tndnjstl_commission 이미 존재</p>"; }

// 7. tndnjstl_price_history 테이블
if (!table_exists($db, 'tndnjstl_price_history')) {
    $r = $db->query("CREATE TABLE tndnjstl_price_history (
        uid                INT          NOT NULL AUTO_INCREMENT,
        model_uid          VARCHAR(100) NOT NULL,
        model_name         VARCHAR(200) NOT NULL DEFAULT '',
        rent_price_before  INT          DEFAULT NULL,
        rent_price_after   INT          NOT NULL DEFAULT 0,
        normal_price_before INT         DEFAULT NULL,
        normal_price_after INT          NOT NULL DEFAULT 0,
        setup_price_before INT          DEFAULT NULL,
        setup_price_after  INT          NOT NULL DEFAULT 0,
        change_type        ENUM('auto','manual') NOT NULL DEFAULT 'auto',
        change_reason      VARCHAR(200) DEFAULT NULL,
        changed_date       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (uid),
        KEY idx_model        (model_uid),
        KEY idx_changed_date (changed_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo $r ? "<p class='ok'>OK tndnjstl_price_history 생성 완료</p>" : "<p class='err'>ERR price_history 실패: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP tndnjstl_price_history 이미 존재</p>"; }

echo "<hr><p>마이그레이션 완료. <a href='/'>홈으로 이동</a></p>";
echo "</div></body></html>";
