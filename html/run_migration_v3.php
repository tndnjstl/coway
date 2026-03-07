<?php
/**
 * v3 DB 마이그레이션 - 구매자 프로모션 시스템
 * 실행 후 반드시 삭제할 것
 */
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

require_once HELPER_PATH . '/common_db.php';

function col_exists($db, $table, $col) {
    $r = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    return $r && $r->num_rows > 0;
}
function table_exists($db, $table) {
    $r = $db->query("SHOW TABLES LIKE '{$table}'");
    return $r && $r->num_rows > 0;
}

$db = $db_local;

echo "<!DOCTYPE html><html lang='ko'><head><meta charset='UTF-8'><title>v3 마이그레이션</title>";
echo "<style>body{font-family:sans-serif;padding:40px;background:#f0f2f5;}.card{background:#fff;border-radius:8px;padding:30px;max-width:700px;margin:auto;box-shadow:0 2px 12px rgba(0,0,0,.1);}h2{color:#1e40af;margin-bottom:20px;}p{margin:6px 0;font-size:14px;line-height:1.6;}.ok{color:#16a34a;}.err{color:#dc2626;}.skip{color:#94a3b8;}hr{margin:20px 0;}</style></head><body><div class='card'>";
echo "<h2>v3 마이그레이션 - 구매자 프로모션</h2>";

// 1. tndnjstl_promotion.apply_unit (건당/주문 전체)
if (!col_exists($db, 'tndnjstl_promotion', 'apply_unit')) {
    $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `apply_unit` ENUM('per_item','per_order') NOT NULL DEFAULT 'per_item' AFTER `target_category`");
    echo $r ? "<p class='ok'>OK promotion.apply_unit 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP promotion.apply_unit 이미 존재</p>"; }

// 2. tndnjstl_promotion.discount_target (할인 대상)
if (!col_exists($db, 'tndnjstl_promotion', 'discount_target')) {
    $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `discount_target` ENUM('rent_amount','setup_amount','free_months') NOT NULL DEFAULT 'rent_amount' AFTER `apply_unit`");
    echo $r ? "<p class='ok'>OK promotion.discount_target 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP promotion.discount_target 이미 존재</p>"; }

// 3. tndnjstl_promotion.min_items (최소 상품 수 조건)
if (!col_exists($db, 'tndnjstl_promotion', 'min_items')) {
    $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `min_items` INT NOT NULL DEFAULT 1 AFTER `discount_target`");
    echo $r ? "<p class='ok'>OK promotion.min_items 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP promotion.min_items 이미 존재</p>"; }

// 4. tndnjstl_order_item.customer_promos (per_item 프로모션 uid JSON)
if (!col_exists($db, 'tndnjstl_order_item', 'customer_promos')) {
    $r = $db->query("ALTER TABLE tndnjstl_order_item ADD COLUMN `customer_promos` TEXT DEFAULT NULL");
    echo $r ? "<p class='ok'>OK order_item.customer_promos 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP order_item.customer_promos 이미 존재</p>"; }

// 5. tndnjstl_order_item.customer_promo_discount (per_item 할인 총액)
if (!col_exists($db, 'tndnjstl_order_item', 'customer_promo_discount')) {
    $r = $db->query("ALTER TABLE tndnjstl_order_item ADD COLUMN `customer_promo_discount` INT NOT NULL DEFAULT 0");
    echo $r ? "<p class='ok'>OK order_item.customer_promo_discount 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP order_item.customer_promo_discount 이미 존재</p>"; }

// 6. tndnjstl_order.per_order_promo_uid (주문 전체 프로모션 uid)
if (table_exists($db, 'tndnjstl_order')) {
    if (!col_exists($db, 'tndnjstl_order', 'per_order_promo_uid')) {
        $r = $db->query("ALTER TABLE tndnjstl_order ADD COLUMN `per_order_promo_uid` INT DEFAULT NULL");
        echo $r ? "<p class='ok'>OK order.per_order_promo_uid 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
    } else { echo "<p class='skip'>SKIP order.per_order_promo_uid 이미 존재</p>"; }

    // 7. tndnjstl_order.per_order_discount (주문 전체 할인액)
    if (!col_exists($db, 'tndnjstl_order', 'per_order_discount')) {
        $r = $db->query("ALTER TABLE tndnjstl_order ADD COLUMN `per_order_discount` INT NOT NULL DEFAULT 0");
        echo $r ? "<p class='ok'>OK order.per_order_discount 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
    } else { echo "<p class='skip'>SKIP order.per_order_discount 이미 존재</p>"; }
} else {
    echo "<p class='err'>ERR tndnjstl_order 테이블 없음</p>";
}

echo "<hr><p>마이그레이션 완료. <strong>이 파일을 삭제해주세요.</strong> <a href='/'>홈으로 이동</a></p>";
echo "</div></body></html>";
