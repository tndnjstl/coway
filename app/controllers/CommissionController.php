<?php
class CommissionController
{
    private function require_login(): void { login_check(); }

    // 수수료 대시보드
    public function index(): void
    {
        $this->require_login();
        global $db_local;

        $position  = get_position();
        $member_id = $db_local->real_escape_string($_SESSION['info']['member_id'] ?? '');
        $team_name = $db_local->real_escape_string($_SESSION['info']['team_name'] ?? '');

        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $month_start = sprintf('%04d-%02d-01', $year, $month);
        $month_end   = date('Y-m-t', strtotime($month_start));

        // 조회 범위 WHERE
        $scope_where = $this->build_scope_where($position, $member_id, $team_name, $db_local);

        // 이달 계약 완료 주문 아이템 조회
        $items = [];
        $result = $db_local->query("
            SELECT
                oi.uid AS item_uid,
                oi.model_name,
                oi.model_no,
                oi.payment_type,
                oi.promo_uid,
                o.uid AS order_uid,
                o.customer_name,
                o.customer_type,
                o.member_id,
                o.register_date,
                m.member_name,
                m.position   AS member_position,
                m.team_name  AS member_team,
                p.promo_name,
                COALESCE(p.base_fee, 200000)  AS base_fee,
                COALESCE(p.special_fee, 0)    AS special_fee
            FROM tndnjstl_order o
            JOIN tndnjstl_order_item oi ON oi.order_uid = o.uid
            LEFT JOIN tndnjstl_member m  ON m.member_id  = o.member_id
            LEFT JOIN tndnjstl_promotion p ON p.uid = oi.promo_uid
            WHERE o.status IN ('contracted','installed')
              AND DATE(o.register_date) BETWEEN '{$month_start}' AND '{$month_end}'
              {$scope_where}
            ORDER BY o.register_date DESC
        ");

        $total_gross   = 0;
        $total_hold    = 0;
        $total_net     = 0;
        $total_team_fee = 0;

        while ($row = $result->fetch_assoc()) {
            $base    = (int)$row['base_fee'];
            $special = (int)$row['special_fee'];
            $gross   = $base + $special;
            $hold    = (int)round($gross * 0.5);
            $net     = $gross - $hold;

            // 팀장 팀비 (팀원 건당 3.5%)
            $team_fee = 0;
            if ($position === 'team_leader' && $row['member_id'] !== $member_id) {
                $team_fee = (int)round($base * 0.035);
            }

            $row['calc_gross']     = $gross;
            $row['calc_hold']      = $hold;
            $row['calc_net']       = $net;
            $row['calc_team_fee']  = $team_fee;
            $row['hold_release']   = date('Y-m-d', strtotime('+1 year', strtotime($row['register_date'])));

            $total_gross    += $gross;
            $total_hold     += $hold;
            $total_net      += $net;
            $total_team_fee += $team_fee;

            $items[] = $row;
        }

        // 팀/멤버별 집계 (팀장 이상)
        $member_summary = [];
        if (in_array($position, ['team_leader', 'director', 'branch_manager'])) {
            $res2 = $db_local->query("
                SELECT
                    o.member_id,
                    m.member_name,
                    m.team_name  AS member_team,
                    m.position   AS member_position,
                    COUNT(oi.uid)  AS cnt,
                    SUM(COALESCE(p.base_fee, 200000) + COALESCE(p.special_fee, 0)) AS gross
                FROM tndnjstl_order o
                JOIN tndnjstl_order_item oi ON oi.order_uid = o.uid
                LEFT JOIN tndnjstl_member m    ON m.member_id = o.member_id
                LEFT JOIN tndnjstl_promotion p ON p.uid = oi.promo_uid
                WHERE o.status IN ('contracted','installed')
                  AND DATE(o.register_date) BETWEEN '{$month_start}' AND '{$month_end}'
                  {$scope_where}
                GROUP BY o.member_id
                ORDER BY gross DESC
            ");
            while ($r = $res2->fetch_assoc()) $member_summary[] = $r;
        }

        // 해지방어비 지급 예정 내역 (향후 1년)
        $hold_pending = [];
        $res3 = $db_local->query("
            SELECT
                o.uid AS order_uid,
                o.customer_name,
                o.register_date,
                oi.model_name,
                o.member_id,
                m.member_name,
                COALESCE(p.base_fee, 200000) + COALESCE(p.special_fee, 0) AS gross,
                ROUND((COALESCE(p.base_fee, 200000) + COALESCE(p.special_fee, 0)) * 0.5) AS hold,
                DATE_ADD(DATE(o.register_date), INTERVAL 1 YEAR) AS release_date
            FROM tndnjstl_order o
            JOIN tndnjstl_order_item oi ON oi.order_uid = o.uid
            LEFT JOIN tndnjstl_member m    ON m.member_id = o.member_id
            LEFT JOIN tndnjstl_promotion p ON p.uid = oi.promo_uid
            WHERE o.status IN ('contracted','installed')
              AND DATE_ADD(DATE(o.register_date), INTERVAL 1 YEAR) > CURDATE()
              AND DATE_ADD(DATE(o.register_date), INTERVAL 1 YEAR) <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR)
              {$scope_where}
            ORDER BY release_date ASC
            LIMIT 30
        ");
        while ($r = $res3->fetch_assoc()) $hold_pending[] = $r;

        include VIEW_PATH . '/commission_view.php';
    }

    private function build_scope_where(string $position, string $member_id, string $team_name, $db): string
    {
        return match($position) {
            'staff'       => "AND o.member_id = '{$member_id}'",
            'team_leader' => $team_name
                                ? "AND m.team_name = '{$team_name}'"
                                : "AND o.member_id = '{$member_id}'",
            default       => '', // director, branch_manager: 전체 조회
        };
    }
}
