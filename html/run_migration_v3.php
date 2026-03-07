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

// 0. tndnjstl_promotion 기본 컬럼 보완 (target_category 등)
$promo_base_cols = [
    'target_category' => "VARCHAR(200) DEFAULT NULL",
    'discount_type'   => "ENUM('amount','percent') NOT NULL DEFAULT 'amount'",
    'discount_value'  => "INT NOT NULL DEFAULT 0",
    'base_fee'        => "INT NOT NULL DEFAULT 200000",
    'special_fee'     => "INT NOT NULL DEFAULT 0",
    'start_date'      => "DATE NOT NULL DEFAULT (CURDATE())",
    'end_date'        => "DATE NOT NULL DEFAULT (CURDATE())",
    'description'     => "TEXT DEFAULT NULL",
    'is_active'       => "TINYINT(1) NOT NULL DEFAULT 1",
    'register_id'     => "VARCHAR(50) NOT NULL DEFAULT ''",
    'register_date'   => "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
];
if (table_exists($db, 'tndnjstl_promotion')) {
    foreach ($promo_base_cols as $col => $def) {
        if (!col_exists($db, 'tndnjstl_promotion', $col)) {
            $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `{$col}` {$def}");
            echo $r ? "<p class='ok'>OK promotion.{$col} 추가 완료</p>" : "<p class='err'>ERR promotion.{$col}: ".$db->error."</p>";
        }
    }
} else {
    $db->query("CREATE TABLE tndnjstl_promotion (
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
        is_active       TINYINT(1)   NOT NULL DEFAULT 1,
        register_date   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        register_id     VARCHAR(50)  NOT NULL DEFAULT '',
        PRIMARY KEY (uid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "<p class='ok'>OK tndnjstl_promotion 테이블 생성 완료</p>";
}

// 1. tndnjstl_promotion.apply_unit (건당/주문 전체)
if (!col_exists($db, 'tndnjstl_promotion', 'apply_unit')) {
    $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `apply_unit` ENUM('per_item','per_order') NOT NULL DEFAULT 'per_item'");
    echo $r ? "<p class='ok'>OK promotion.apply_unit 추가 완료</p>" : "<p class='err'>ERR: ".$db->error."</p>";
} else { echo "<p class='skip'>SKIP promotion.apply_unit 이미 존재</p>"; }

// 2. tndnjstl_promotion.discount_target (할인 대상)
if (!col_exists($db, 'tndnjstl_promotion', 'discount_target')) {
    $r = $db->query("ALTER TABLE tndnjstl_promotion ADD COLUMN `discount_target` ENUM('rent_amount','setup_amount','free_months') NOT NULL DEFAULT 'rent_amount'");
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

// ==========================================
// 8. 2026년 3월 구매자 프로모션 시드 데이터
// ==========================================
echo "<hr><h3 style='color:#1e40af;'>3월 프로모션 시드 데이터</h3>";

$promos = [
    [
        'promo_name'      => '타사보상할인 (정수기)',
        'target_category' => '정수기',
        'apply_unit'      => 'per_item',
        'discount_target' => 'rent_amount',
        'min_items'       => 1,
        'discount_type'   => 'percent',
        'discount_value'  => 15,
        'base_fee'        => 200000,
        'special_fee'     => 0,
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-03-31',
        'description'     => '설치 당일 타사 정수기가 설치장소에 있어야 함. KC마크·주요필터(UF/NF/RO) 사용 제품 한정. 패키지 할인과 중복 불가',
        'check_name'      => '타사보상할인 (정수기)',
    ],
    [
        'promo_name'      => '타사보상할인 (공기청정기)',
        'target_category' => '공기청정기',
        'apply_unit'      => 'per_item',
        'discount_target' => 'rent_amount',
        'min_items'       => 1,
        'discount_type'   => 'amount',
        'discount_value'  => 10000,
        'base_fee'        => 200000,
        'special_fee'     => 0,
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-03-31',
        'description'     => '설치 당일 타사 공기청정기가 설치장소에 있어야 함. 월 1만원 추가 할인. 반값할인 중복 불가',
        'check_name'      => '타사보상할인 (공기청정기)',
    ],
    [
        'promo_name'      => '재렌탈 등록비 할인',
        'target_category' => '',
        'apply_unit'      => 'per_item',
        'discount_target' => 'setup_amount',
        'min_items'       => 1,
        'discount_type'   => 'amount',
        'discount_value'  => 100000,
        'base_fee'        => 200000,
        'special_fee'     => 0,
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-03-31',
        'description'     => '기존 코웨이 렌탈 고객 재계약 시 등록비 10만원 할인 (3년·6년 약정 한정)',
        'check_name'      => '재렌탈 등록비 할인',
    ],
    [
        'promo_name'      => '패키지 15% 할인 (2대 이상)',
        'target_category' => '',
        'apply_unit'      => 'per_order',
        'discount_target' => 'rent_amount',
        'min_items'       => 2,
        'discount_type'   => 'percent',
        'discount_value'  => 15,
        'base_fee'        => 200000,
        'special_fee'     => 0,
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-03-31',
        'description'     => '2대 이상 동시 렌탈 시 전체 월 렌탈료 합계 15% 할인. 타사보상·선납·재렌탈 혜택과 중복 불가',
        'check_name'      => '패키지 15% 할인 (2대 이상)',
    ],
    [
        'promo_name'      => '패키지 3개월 추가 무료 (3대 이상)',
        'target_category' => '',
        'apply_unit'      => 'per_order',
        'discount_target' => 'free_months',
        'min_items'       => 3,
        'discount_type'   => 'amount',
        'discount_value'  => 3,
        'base_fee'        => 200000,
        'special_fee'     => 0,
        'start_date'      => '2026-03-01',
        'end_date'        => '2026-03-31',
        'description'     => '3대 이상 동시 렌탈 시 3개월 추가 무료 (패키지 15% 할인과 택1)',
        'check_name'      => '패키지 3개월 추가 무료 (3대 이상)',
    ],
];

foreach ($promos as $pr) {
    $exists = $db->query("SELECT uid FROM tndnjstl_promotion WHERE promo_name = '" . $db->real_escape_string($pr['promo_name']) . "' AND start_date = '{$pr['start_date']}' LIMIT 1");
    if ($exists && $exists->num_rows > 0) {
        echo "<p class='skip'>SKIP [{$pr['promo_name']}] 이미 존재</p>";
        continue;
    }
    $tc   = $pr['target_category'] ? "'" . $db->real_escape_string($pr['target_category']) . "'" : 'NULL';
    $desc = $db->real_escape_string($pr['description']);
    $name = $db->real_escape_string($pr['promo_name']);
    $r = $db->query("
        INSERT INTO tndnjstl_promotion
            (promo_name, target_category, apply_unit, discount_target, min_items,
             discount_type, discount_value, base_fee, special_fee,
             start_date, end_date, description, is_active, register_id)
        VALUES
            ('{$name}', {$tc}, '{$pr['apply_unit']}', '{$pr['discount_target']}', {$pr['min_items']},
             '{$pr['discount_type']}', {$pr['discount_value']}, {$pr['base_fee']}, {$pr['special_fee']},
             '{$pr['start_date']}', '{$pr['end_date']}', '{$desc}', 1, 'system')
    ");
    echo $r ? "<p class='ok'>OK [{$pr['promo_name']}] 등록 완료</p>" : "<p class='err'>ERR [{$pr['promo_name']}]: " . $db->error . "</p>";
}

echo "<hr><p>마이그레이션 완료. <strong>이 파일을 삭제해주세요.</strong> <a href='/'>홈으로 이동</a></p>";
echo "</div></body></html>";
