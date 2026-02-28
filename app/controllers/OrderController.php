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

		$keyword = trim($_GET['keyword'] ?? '');
		$where   = '';
		if ($keyword !== '') {
			$kw    = $db_local->real_escape_string($keyword);
			$where = "WHERE o.customer_name LIKE '%{$kw}%' OR o.customer_phone LIKE '%{$kw}%'";
		}

		$sql = "
			SELECT
				o.uid,
				o.customer_type,
				o.customer_name,
				o.customer_phone,
				o.member_id,
				o.memo,
				o.status,
				o.register_date,
				COUNT(oi.uid)     AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			{$where}
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
			$r     = $db_local->query("SELECT * FROM tndnjstl_order WHERE uid = {$order_uid} LIMIT 1");
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

			// 계약 정보 (설치완료인 경우)
			$contract = null;
			if ($order['status'] === 'installed') {
				$rc = $db_local->query("SELECT * FROM tndnjstl_contract WHERE order_uid = {$order_uid} ORDER BY uid DESC LIMIT 1");
				if ($rc && $rc->num_rows > 0) {
					$contract = $rc->fetch_assoc();
				}
			}

			$data['order']    = $order;
			$data['items']    = $items;
			$data['contract'] = $contract;

		} catch (Exception $e) {
			$status  = 'fail';
			$message = $e->getMessage();
		}

		$data['status']  = $status;
		$data['message'] = $message;
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	//주문 상태 변경 (AJAX)
	public function updateOrderStatus(): void
	{
		global $db_local;

		$status  = 'success';
		$message = '';
		$data    = [];

		try {
			if (!login_check(true)) {
				throw new Exception('로그인 후 이용해주세요.');
			}

			$json           = json_decode(file_get_contents('php://input'), true);
			$order_uid      = (int)($json['order_uid']      ?? 0);
			$new_status     = trim($json['new_status']      ?? '');
			$contract_start = trim($json['contract_start']  ?? '');
			$duty_year      = (int)($json['duty_year']      ?? 0);

			if (!$order_uid) {
				throw new Exception('잘못된 요청입니다.');
			}
			if (!in_array($new_status, ['prospect', 'contracted', 'installed'])) {
				throw new Exception('유효하지 않은 상태값입니다.');
			}

			// 기존 주문 확인
			$r   = $db_local->query("SELECT uid, status FROM tndnjstl_order WHERE uid = {$order_uid} LIMIT 1");
			$ord = $r ? $r->fetch_assoc() : null;
			if (!$ord) {
				throw new Exception('주문을 찾을 수 없습니다.');
			}

			// 설치완료 처리 시 계약 정보 필수
			if ($new_status === 'installed') {
				if ($contract_start === '') {
					throw new Exception('계약 시작일을 입력해주세요.');
				}
				if ($duty_year <= 0) {
					throw new Exception('의무기간을 선택해주세요.');
				}
				$cs = $db_local->real_escape_string($contract_start);
				// 기존 계약이 있으면 삭제 후 재등록
				$db_local->query("DELETE FROM tndnjstl_contract WHERE order_uid = {$order_uid}");
				$db_local->query("
					INSERT INTO tndnjstl_contract SET
						order_uid      = {$order_uid},
						contract_start = '{$cs}',
						contract_end   = DATE_ADD('{$cs}', INTERVAL {$duty_year} YEAR),
						duty_year      = {$duty_year},
						register_date  = NOW()
				");
			}

			// 상태 업데이트
			$ns = $db_local->real_escape_string($new_status);
			$db_local->query("UPDATE tndnjstl_order SET status = '{$ns}' WHERE uid = {$order_uid}");

			$data['new_status'] = $new_status;

		} catch (Exception $e) {
			$status  = 'fail';
			$message = $e->getMessage();
		}

		$data['status']  = $status;
		$data['message'] = $message;
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	//가망고객 보고서 페이지
	public function prospectReport(): void
	{
		global $db_local;
		login_check();

		// 이번 주 월요일 ~ 오늘
		$week_start = date('Y-m-d', strtotime('monday this week'));
		$today      = date('Y-m-d');

		// 전체 현재 가망고객 목록
		$sql = "
			SELECT
				o.uid,
				o.customer_type,
				o.customer_name,
				o.customer_phone,
				o.member_id,
				o.memo,
				o.register_date,
				COUNT(oi.uid)     AS item_count,
				SUM(oi.total_pay) AS total_pay
			FROM tndnjstl_order AS o
			LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
			WHERE o.status = 'prospect'
			GROUP BY o.uid
			ORDER BY o.register_date DESC
		";
		$result   = $db_local->query($sql);
		$prospects = [];
		while ($row = $result->fetch_assoc()) {
			$prospects[] = $row;
		}

		// 이번 주 신규 등록 건
		$new_count = 0;
		foreach ($prospects as $p) {
			if (substr($p['register_date'], 0, 10) >= $week_start) {
				$new_count++;
			}
		}

		// 담당자별 집계
		$by_member = [];
		foreach ($prospects as $p) {
			$m = $p['member_id'];
			if (!isset($by_member[$m])) {
				$by_member[$m] = ['count' => 0, 'total_pay' => 0];
			}
			$by_member[$m]['count']++;
			$by_member[$m]['total_pay'] += (int)$p['total_pay'];
		}

		require_once HELPER_PATH . '/email_config.php';
		include VIEW_PATH . '/prospect_report_view.php';
	}

	//가망고객 보고서 이메일 발송 (AJAX)
	public function sendProspectReport(): void
	{
		global $db_local;

		$status  = 'success';
		$message = '';
		$data    = [];

		try {
			if (!login_check(true)) {
				throw new Exception('로그인 후 이용해주세요.');
			}

			require_once HELPER_PATH . '/email_config.php';

			if (REPORT_RECIPIENT_EMAIL === 'director@example.com') {
				throw new Exception('email_config.php에서 수신자 이메일을 설정해주세요.');
			}

			// 가망고객 목록 조회
			$sql = "
				SELECT
					o.uid,
					o.customer_type,
					o.customer_name,
					o.customer_phone,
					o.member_id,
					o.register_date,
					COUNT(oi.uid)     AS item_count,
					SUM(oi.total_pay) AS total_pay
				FROM tndnjstl_order AS o
				LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
				WHERE o.status = 'prospect'
				GROUP BY o.uid
				ORDER BY o.register_date DESC
			";
			$result    = $db_local->query($sql);
			$prospects = [];
			while ($row = $result->fetch_assoc()) {
				$prospects[] = $row;
			}

			$ct_map     = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
			$report_date = date('Y년 m월 d일');
			$week_start  = date('Y-m-d', strtotime('monday this week'));
			$total_count = count($prospects);
			$total_pay   = array_sum(array_column($prospects, 'total_pay'));

			// HTML 이메일 본문 생성
			$rows_html = '';
			foreach ($prospects as $i => $p) {
				$is_new  = substr($p['register_date'], 0, 10) >= $week_start;
				$new_tag = $is_new ? ' <span style="background:#ff4757;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;margin-left:4px;">NEW</span>' : '';
				$ct      = $ct_map[$p['customer_type']] ?? $p['customer_type'];
				$bg      = ($i % 2 === 0) ? '#ffffff' : '#f8f9fa';
				$rows_html .= "
					<tr style='background:{$bg};'>
						<td style='padding:8px 10px;text-align:center;color:#666;font-size:13px;'>" . ($i + 1) . "</td>
						<td style='padding:8px 10px;font-weight:bold;'>" . htmlspecialchars($p['customer_name']) . $new_tag . "</td>
						<td style='padding:8px 10px;color:#666;'>" . $ct . "</td>
						<td style='padding:8px 10px;'>" . htmlspecialchars($p['customer_phone']) . "</td>
						<td style='padding:8px 10px;text-align:center;'>" . (int)$p['item_count'] . "개</td>
						<td style='padding:8px 10px;text-align:right;color:#2563eb;font-weight:bold;'>" . number_format((int)$p['total_pay']) . "원</td>
						<td style='padding:8px 10px;text-align:center;color:#666;'>" . htmlspecialchars($p['member_id']) . "</td>
						<td style='padding:8px 10px;text-align:center;color:#999;font-size:12px;'>" . substr($p['register_date'], 0, 10) . "</td>
					</tr>
				";
			}

			$body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f2f5;font-family:\"Apple SD Gothic Neo\",\"Malgun Gothic\",sans-serif;'>
<div style='max-width:800px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>

  <!-- 헤더 -->
  <div style='background:#1e40af;padding:30px 30px 20px;'>
    <div style='color:#93c5fd;font-size:13px;margin-bottom:4px;'>COWAY 영업관리시스템</div>
    <h1 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>가망고객 현황 보고</h1>
    <div style='color:#bfdbfe;font-size:13px;margin-top:8px;'>{$report_date} 기준</div>
  </div>

  <!-- 요약 -->
  <div style='padding:20px 30px;background:#eff6ff;border-bottom:1px solid #dbeafe;display:flex;gap:20px;'>
    <div style='flex:1;text-align:center;'>
      <div style='color:#1e40af;font-size:28px;font-weight:800;'>{$total_count}</div>
      <div style='color:#64748b;font-size:13px;margin-top:2px;'>전체 가망고객</div>
    </div>
    <div style='flex:1;text-align:center;'>
      <div style='color:#1e40af;font-size:28px;font-weight:800;'>" . number_format($total_pay) . "원</div>
      <div style='color:#64748b;font-size:13px;margin-top:2px;'>총 예상 금액</div>
    </div>
  </div>

  <!-- 테이블 -->
  <div style='padding:20px 30px 30px;'>
    <table style='width:100%;border-collapse:collapse;font-size:13px;'>
      <thead>
        <tr style='background:#1e40af;color:#fff;'>
          <th style='padding:10px;text-align:center;font-weight:600;width:40px;'>No</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>고객명</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>구분</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>전화번호</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>상품수</th>
          <th style='padding:10px;text-align:right;font-weight:600;'>예상금액</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>담당자</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>등록일</th>
        </tr>
      </thead>
      <tbody>
        {$rows_html}
      </tbody>
    </table>
    " . ($total_count === 0 ? "<div style='text-align:center;padding:30px;color:#999;'>가망고객이 없습니다.</div>" : "") . "
  </div>

  <!-- 푸터 -->
  <div style='padding:15px 30px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px;'>
    본 메일은 Coway 영업관리시스템에서 자동 발송되었습니다.
  </div>
</div>
</body>
</html>
			";

			// 메일 발송
			$to      = REPORT_RECIPIENT_NAME . ' <' . REPORT_RECIPIENT_EMAIL . '>';
			$subject = '=?UTF-8?B?' . base64_encode('[Coway] 가망고객 현황 보고 (' . $report_date . ')') . '?=';
			$headers = implode("\r\n", [
				'MIME-Version: 1.0',
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . REPORT_SENDER_NAME . ' <' . REPORT_SENDER_EMAIL . '>',
				'X-Mailer: PHP/' . phpversion(),
			]);

			$sent = mail($to, $subject, $body, $headers);
			if (!$sent) {
				throw new Exception('메일 발송에 실패했습니다. 서버 mail() 설정을 확인하세요.');
			}

			$data['recipient'] = REPORT_RECIPIENT_EMAIL;

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
					status         = 'prospect',
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
