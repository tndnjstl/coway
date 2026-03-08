<?php
class MainController
{
	// 관리자 여부 확인
	private function is_admin(): bool
	{
		return !empty($_SESSION['info']['is_admin']) && (int)$_SESSION['info']['is_admin'] === 1;
	}

	//메인
	public function main(): void
	{
		global $db_local;
		login_check();

		// 계약만료 임박 조회 (90일 이내)
		$expire_contracts = [];
		$main_add = '';
		if (!$this->is_admin()) {
			$mid = $db_local->real_escape_string($_SESSION['member_id'] ?? '');
			$main_add = "AND o.member_id = '{$mid}'";
		}
		$r = $db_local->query("
			SELECT
				c.uid           AS contract_uid,
				c.order_uid,
				c.contract_start,
				c.contract_end,
				c.duty_year,
				o.customer_name,
				o.customer_phone,
				o.customer_type,
				o.member_id,
				DATEDIFF(c.contract_end, CURDATE()) AS days_left
			FROM tndnjstl_contract AS c
			JOIN tndnjstl_order AS o ON o.uid = c.order_uid
			WHERE c.contract_end >= CURDATE()
			  AND c.contract_end <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
			  {$main_add}
			ORDER BY c.contract_end ASC
		");
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$expire_contracts[] = $row;
			}
		}

		include VIEW_PATH . '/main_view.php';
	}
}
