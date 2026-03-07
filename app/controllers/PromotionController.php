<?php
class PromotionController
{
    private function require_login(): void  { login_check(); }
    private function require_manager(): void { login_check(); role_check('manager'); }

    // 프로모션 목록 (전체 열람 가능)
    public function index(): void
    {
        $this->require_login();
        global $db_local;

        $today  = date('Y-m-d');
        $filter = $_GET['filter'] ?? 'active'; // active | all | expired

        $where = match($filter) {
            'active'  => "WHERE is_active = 1 AND end_date >= '{$today}'",
            'expired' => "WHERE end_date < '{$today}'",
            default   => '',
        };

        $promos = [];
        $result = $db_local->query("
            SELECT *,
                CASE
                    WHEN end_date < '{$today}'          THEN 'expired'
                    WHEN is_active = 0                  THEN 'inactive'
                    WHEN start_date > '{$today}'        THEN 'scheduled'
                    ELSE 'active'
                END AS status_key
            FROM tndnjstl_promotion
            {$where}
            ORDER BY is_active DESC, end_date DESC
        ");
        while ($row = $result->fetch_assoc()) $promos[] = $row;

        include VIEW_PATH . '/promotion_list_view.php';
    }

    // 등록 폼
    public function add(): void
    {
        $this->require_manager();
        $promo = null;
        include VIEW_PATH . '/promotion_form_view.php';
    }

    // 등록 처리
    public function store(): void
    {
        $this->require_manager();
        global $db_local;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /Promotion/index'); exit; }

        $fields = $this->parse_post($db_local);
        $tc = $fields['target_category'] ? "'{$fields['target_category']}'" : 'NULL';

        $db_local->query("
            INSERT INTO tndnjstl_promotion
                (promo_name, target_category, apply_unit, discount_target, min_items,
                 discount_type, discount_value,
                 base_fee, special_fee, start_date, end_date, description, register_id)
            VALUES
                ('{$fields['promo_name']}', {$tc}, '{$fields['apply_unit']}',
                 '{$fields['discount_target']}', {$fields['min_items']},
                 '{$fields['discount_type']}', {$fields['discount_value']},
                 {$fields['base_fee']}, {$fields['special_fee']},
                 '{$fields['start_date']}', '{$fields['end_date']}',
                 '{$fields['description']}', '{$fields['register_id']}')
        ");

        header('Location: /Promotion/index');
        exit;
    }

    // 수정 폼
    public function edit(): void
    {
        $this->require_manager();
        global $db_local;

        $uid   = (int)($_GET['id'] ?? 0);
        $promo = $db_local->query("SELECT * FROM tndnjstl_promotion WHERE uid = {$uid} LIMIT 1")->fetch_assoc();
        if (!$promo) { header('Location: /Promotion/index'); exit; }

        include VIEW_PATH . '/promotion_form_view.php';
    }

    // 수정 처리
    public function update(): void
    {
        $this->require_manager();
        global $db_local;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /Promotion/index'); exit; }

        $uid    = (int)($_POST['uid'] ?? 0);
        $fields = $this->parse_post($db_local);
        $tc     = $fields['target_category'] ? "'{$fields['target_category']}'" : 'NULL';

        $db_local->query("
            UPDATE tndnjstl_promotion SET
                promo_name      = '{$fields['promo_name']}',
                target_category = {$tc},
                apply_unit      = '{$fields['apply_unit']}',
                discount_target = '{$fields['discount_target']}',
                min_items       = {$fields['min_items']},
                discount_type   = '{$fields['discount_type']}',
                discount_value  = {$fields['discount_value']},
                base_fee        = {$fields['base_fee']},
                special_fee     = {$fields['special_fee']},
                start_date      = '{$fields['start_date']}',
                end_date        = '{$fields['end_date']}',
                description     = '{$fields['description']}'
            WHERE uid = {$uid}
        ");

        header('Location: /Promotion/index');
        exit;
    }

    // 활성/비활성 토글 (AJAX)
    public function toggle(): void
    {
        $this->require_manager();
        global $db_local;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $uid = (int)($_POST['uid'] ?? 0);
        $db_local->query("UPDATE tndnjstl_promotion SET is_active = 1 - is_active WHERE uid = {$uid}");
        echo json_encode(['success' => true]);
        exit;
    }

    // 삭제 (AJAX)
    public function delete(): void
    {
        $this->require_manager();
        global $db_local;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $uid = (int)($_POST['uid'] ?? 0);
        $db_local->query("DELETE FROM tndnjstl_promotion WHERE uid = {$uid}");
        echo json_encode(['success' => true]);
        exit;
    }

    // 현재 유효 프로모션 목록 (AJAX - 견적서 연동용)
    public function activeList(): void
    {
        $this->require_login();
        global $db_local;

        $today  = date('Y-m-d');
        $result = $db_local->query("
            SELECT uid, promo_name, target_category, apply_unit, discount_target, min_items,
                   discount_type, discount_value, base_fee, special_fee, start_date, end_date, description
            FROM tndnjstl_promotion
            WHERE is_active = 1 AND start_date <= '{$today}' AND end_date >= '{$today}'
            ORDER BY apply_unit ASC, promo_name ASC
        ");
        $list = [];
        while ($row = $result->fetch_assoc()) $list[] = $row;
        header('Content-Type: application/json');
        echo json_encode($list);
        exit;
    }

    private function parse_post($db_local): array
    {
        return [
            'promo_name'      => $db_local->real_escape_string(trim($_POST['promo_name']      ?? '')),
            'target_category' => $db_local->real_escape_string(trim($_POST['target_category'] ?? '')),
            'apply_unit'      => in_array($_POST['apply_unit'] ?? '', ['per_item','per_order']) ? $_POST['apply_unit'] : 'per_item',
            'discount_target' => in_array($_POST['discount_target'] ?? '', ['rent_amount','setup_amount','free_months']) ? $_POST['discount_target'] : 'rent_amount',
            'min_items'       => max(1, (int)($_POST['min_items'] ?? 1)),
            'discount_type'   => in_array($_POST['discount_type'] ?? '', ['amount','percent']) ? $_POST['discount_type'] : 'amount',
            'discount_value'  => (int)($_POST['discount_value'] ?? 0),
            'base_fee'        => (int)($_POST['base_fee']       ?? 200000),
            'special_fee'     => (int)($_POST['special_fee']    ?? 0),
            'start_date'      => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date'        => $_POST['end_date']   ?? date('Y-m-d'),
            'description'     => $db_local->real_escape_string(trim($_POST['description']     ?? '')),
            'register_id'     => $_SESSION['info']['member_id'] ?? '',
        ];
    }
}
