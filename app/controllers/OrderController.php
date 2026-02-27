<?php
class OrderController
{
	//주문접수 폼
	public function addOrder(): void
	{
		login_check();
		include VIEW_PATH . '/order_form_view.php';
	}

	//주문현황 목록
	public function orderList(): void
	{
		global $db_local;
		login_check();

		$sql = "
			SELECT
				o.uid,
				o.customer_type,
				o.customer_name,
				o.customer_phone,
				o.member_id,
				o.memo,
				o.register_date,
				COUNT(oi.uid)    AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			GROUP BY o.uid
			ORDER BY o.register_date DESC
		";

		$result = $db_local->query($sql);
		$orders = [];
		while ($row = $result->fetch_assoc()) {
			$orders[] = $row;
		}

		include VIEW_PATH . '/order_list_view.php';
	}

	//주문 상세 (AJAX)
	public function getOrderDetail(): void
	{
		global $db_local;

		$status  = 'success';
		$message = '';
		$data    = [];

		try {
			if (!login_check(true)) {
				$status  = 'fail';
				throw new Exception('로그인 후 이용해주세요.');
			}

			$json      = json_decode(file_get_contents('php://input'), true);
			$order_uid = (int)($json['order_uid'] ?? 0);

			if (!$order_uid) {
				throw new Exception('잘못된 요청입니다.');
			}

			// 주문 마스터
			$r = $db_local->query("SELECT * FROM tndnjstl_order WHERE uid = {$order_uid} LIMIT 1");
			$order = $r ? $r->fetch_assoc() : null;
			if (!$order) {
				throw new Exception('주문을 찾을 수 없습니다.');
			}

			// 주문 상품 목록
			$r2    = $db_local->query("SELECT * FROM tndnjstl_order_item WHERE order_uid = {$order_uid} ORDER BY uid ASC");
			$items = [];
			while ($row = $r2->fetch_assoc()) {
				$items[] = $row;
			}

			$data['order'] = $order;
			$data['items'] = $items;

		} catch (Exception $e) {
			$status  = 'fail';
			$message = $e->getMessage();
		}

		$data['status']  = $status;
		$data['message'] = $message;
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	//주문 저장
	public function saveOrder(): void
	{
		global $db_local;
		$data = [];

		$status  = 'success';
		$message = '';

		try {
			if (!login_check(true)) {
				$status  = 'fail';
				$message = '로그인 후 이용해주세요.';
				throw new Exception($message);
			}

			$json = json_decode(file_get_contents('php://input'), true);

			$customer_type  = trim($json['customer_type']  ?? '');
			$customer_name  = trim($json['customer_name']  ?? '');
			$customer_phone = trim($json['customer_phone'] ?? '');
			$memo           = trim($json['memo']           ?? '');
			$items          = $json['items'] ?? [];
			$member_id      = $_SESSION['member_id'] ?? '';

			// 필수값 검증
			if (!in_array($customer_type, ['P', 'B', 'C'])) {
				throw new Exception('고객구분을 선택해주세요.');
			}
			if ($customer_name === '') {
				throw new Exception('고객명을 입력해주세요.');
			}
			if ($customer_phone === '') {
				throw new Exception('휴대폰 번호를 입력해주세요.');
			}
			if (empty($items)) {
				throw new Exception('상품을 1개 이상 선택해주세요.');
			}

			$customer_type  = $db_local->real_escape_string($customer_type);
			$customer_name  = $db_local->real_escape_string($customer_name);
			$customer_phone = $db_local->real_escape_string($customer_phone);
			$memo           = $db_local->real_escape_string($memo);
			$member_id      = $db_local->real_escape_string($member_id);

			// 주문 마스터 INSERT
			$db_local->query("
				INSERT INTO tndnjstl_order SET
					customer_type  = '{$customer_type}',
					customer_name  = '{$customer_name}',
					customer_phone = '{$customer_phone}',
					member_id      = '{$member_id}',
					memo           = '{$memo}',
					register_date  = NOW()
			");

			$order_uid = $db_local->insert_id;
			if (!$order_uid) {
				throw new Exception('주문 저장에 실패했습니다.');
			}

			// 주문 상품 상세 INSERT
			foreach ($items as $item) {
				$model_uid         = $db_local->real_escape_string($item['model_uid']         ?? '');
				$model_name        = $db_local->real_escape_string($item['model_name']        ?? '');
				$model_no          = $db_local->real_escape_string($item['model_no']          ?? '');
				$model_color       = $db_local->real_escape_string($item['model_color']       ?? '');
				$category          = $db_local->real_escape_string($item['category']          ?? '');
				$payment_type      = $db_local->real_escape_string($item['payment_type']      ?? 'rent');
				$visit_cycle       = (int)($item['visit_cycle']       ?? 0);
				$duty_year         = (int)($item['duty_year']         ?? 0);
				$promo_a141        = (int)($item['promo_a141']        ?? 0);
				$promo_a142        = (int)($item['promo_a142']        ?? 0);
				$promo_a143        = (int)($item['promo_a143']        ?? 0);
				$promo_a144        = (int)($item['promo_a144']        ?? 0);
				$base_setup_price  = (int)($item['base_setup_price']  ?? 0);
				$base_rent_price   = (int)($item['base_rent_price']   ?? 0);
				$final_setup_price = (int)($item['final_setup_price'] ?? 0);
				$final_rent_price  = (int)($item['final_rent_price']  ?? 0);
				$normal_price      = (int)($item['normal_price']      ?? 0);
				$total_pay         = (int)($item['total_pay']         ?? 0);

				$db_local->query("
					INSERT INTO tndnjstl_order_item SET
						order_uid         = {$order_uid},
						model_uid         = '{$model_uid}',
						model_name        = '{$model_name}',
						model_no          = '{$model_no}',
						model_color       = '{$model_color}',
						category          = '{$category}',
						payment_type      = '{$payment_type}',
						visit_cycle       = {$visit_cycle},
						duty_year         = {$duty_year},
						promo_a141        = {$promo_a141},
						promo_a142        = {$promo_a142},
						promo_a143        = {$promo_a143},
						promo_a144        = {$promo_a144},
						base_setup_price  = {$base_setup_price},
						base_rent_price   = {$base_rent_price},
						final_setup_price = {$final_setup_price},
						final_rent_price  = {$final_rent_price},
						normal_price      = {$normal_price},
						total_pay         = {$total_pay},
						register_date     = NOW()
				");
			}

			$data['order_uid'] = $order_uid;

		} catch (Exception $e) {
			$status  = 'fail';
			$message = $e->getMessage();
		}

		$data['status']  = $status;
		$data['message'] = $message;
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
}
