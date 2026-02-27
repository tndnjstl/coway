<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

<?php
$customer_type_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
?>

<div class="wrapper">

	<?php include APP_PATH . '/views/layouts/side_menu.php';?>

	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php';?>
		<div class="container">
			<div class="page-inner pb-5">

				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">주문 현황</h5>
					<a href="/Order/addOrder" class="btn btn-primary btn-sm">
						<i class="fas fa-plus me-1"></i> 주문 등록
					</a>
				</div>

				<div class="card">
					<div class="card-body p-0">
						<?php if (empty($orders)): ?>
							<div class="text-center text-muted py-5">
								<i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
								등록된 주문이 없습니다.
							</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-hover mb-0">
								<thead class="table-light">
									<tr>
										<th class="text-center" style="width:60px;">번호</th>
										<th style="width:90px;">고객구분</th>
										<th>고객명</th>
										<th>전화번호</th>
										<th class="text-center" style="width:70px;">상품수</th>
										<th class="text-end">총납부예상액</th>
										<th style="width:90px;">담당자</th>
										<th style="width:140px;">등록일시</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $order): ?>
									<tr class="order-row" data-order-uid="<?= $order['uid'] ?>" style="cursor:pointer;">
										<td class="text-center text-muted small"><?= $order['uid'] ?></td>
										<td>
											<?php
											$ct = $customer_type_map[$order['customer_type']] ?? $order['customer_type'];
											$badge = $order['customer_type'] === 'P' ? 'bg-secondary' : ($order['customer_type'] === 'B' ? 'bg-info' : 'bg-warning text-dark');
											?>
											<span class="badge <?= $badge ?>"><?= htmlspecialchars($ct) ?></span>
										</td>
										<td class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></td>
										<td><?= htmlspecialchars($order['customer_phone']) ?></td>
										<td class="text-center"><?= (int)$order['item_count'] ?>개</td>
										<td class="text-end fw-bold text-primary"><?= number_format((int)$order['total_pay']) ?>원</td>
										<td class="text-muted small"><?= htmlspecialchars($order['member_id']) ?></td>
										<td class="text-muted small"><?= date('Y-m-d H:i', strtotime($order['register_date'])) ?></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php';?>
	</div>
</div>

<!-- 주문 상세 모달 -->
<div class="modal fade" id="modal-order-detail" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold">주문 상세</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body" id="order-detail-body">
				<div class="text-center py-4">
					<div class="spinner-border spinner-border-sm me-2" role="status"></div>
					불러오는 중...
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">닫기</button>
			</div>
		</div>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php';?>
<script>
var order_detail_modal = new bootstrap.Modal(document.getElementById('modal-order-detail'));

var customer_type_map = { P: '개인', B: '개인사업자', C: '법인사업자' };
var payment_type_map  = { rent: '렌탈', buy: '일시불' };

$(document).on('click', '.order-row', function() {
	var order_uid = $(this).data('order-uid');
	$('#order-detail-body').html(
		'<div class="text-center py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>불러오는 중...</div>'
	);
	order_detail_modal.show();

	$.ajax({
		url: '/Order/getOrderDetail',
		method: 'POST',
		contentType: 'application/json',
		dataType: 'json',
		data: JSON.stringify({ order_uid: order_uid }),
		success: function(res) {
			if (res.status !== 'success') {
				$('#order-detail-body').html('<div class="text-danger text-center py-4">' + (res.message || '오류가 발생했습니다.') + '</div>');
				return;
			}
			render_order_detail(res.order, res.items);
		},
		error: function() {
			$('#order-detail-body').html('<div class="text-danger text-center py-4">서버 오류가 발생했습니다.</div>');
		}
	});
});

function number_fmt(n) { return Number(n).toLocaleString('ko-KR'); }

function render_order_detail(order, items) {
	var ct = customer_type_map[order.customer_type] || order.customer_type;
	var html = '';

	// 고객 정보
	html += '<div class="mb-3 p-3 rounded" style="background:#f8f9fa;">';
	html += '  <div class="row g-2">';
	html += '    <div class="col-6"><span class="text-muted small">주문번호</span><div class="fw-bold">#' + order.uid + '</div></div>';
	html += '    <div class="col-6"><span class="text-muted small">고객구분</span><div class="fw-bold">' + ct + '</div></div>';
	html += '    <div class="col-6"><span class="text-muted small">고객명</span><div class="fw-bold">' + esc(order.customer_name) + '</div></div>';
	html += '    <div class="col-6"><span class="text-muted small">전화번호</span><div class="fw-bold">' + esc(order.customer_phone) + '</div></div>';
	html += '    <div class="col-6"><span class="text-muted small">담당자</span><div>' + esc(order.member_id) + '</div></div>';
	html += '    <div class="col-6"><span class="text-muted small">등록일시</span><div>' + order.register_date + '</div></div>';
	if (order.memo) {
		html += '  <div class="col-12"><span class="text-muted small">메모</span><div>' + esc(order.memo) + '</div></div>';
	}
	html += '  </div>';
	html += '</div>';

	// 상품 목록
	$.each(items, function(_, item) {
		var is_rent = item.payment_type === 'rent';
		html += '<div class="card mb-2">';
		html += '  <div class="card-body p-3">';
		html += '    <div class="d-flex justify-content-between align-items-start mb-2">';
		html += '      <div>';
		html += '        <div class="fw-bold">' + esc(item.model_name) + '</div>';
		html += '        <div class="text-muted small">' + esc(item.model_no) + ' · ' + esc(item.model_color) + '</div>';
		html += '        <span class="badge bg-light text-secondary border me-1 mt-1">' + esc(item.category) + '</span>';
		html += '        <span class="badge ' + (is_rent ? 'bg-primary' : 'bg-success') + ' mt-1">' + (is_rent ? '렌탈' : '일시불') + '</span>';
		html += '      </div>';
		html += '      <div class="text-end">';
		html += '        <div class="fw-bold text-primary">' + number_fmt(item.total_pay) + '원</div>';
		html += '        <div class="text-muted small">총 납부예상</div>';
		html += '      </div>';
		html += '    </div>';

		if (is_rent) {
			html += '    <div class="small rounded p-2" style="background:#eef2ff;">';
			html += '      <div class="d-flex justify-content-between mb-1"><span class="text-muted">방문주기 / 의무기간</span><span>' + item.visit_cycle + '개월 / ' + item.duty_year + '년</span></div>';
			html += '      <div class="d-flex justify-content-between mb-1"><span class="text-muted">등록비 / 월렌탈료</span><span>' + number_fmt(item.base_setup_price) + '원 / ' + number_fmt(item.base_rent_price) + '원</span></div>';
			html += '      <div class="d-flex justify-content-between mb-1"><span class="text-muted">최종 등록비 / 월렌탈료</span><span class="fw-bold">' + number_fmt(item.final_setup_price) + '원 / ' + number_fmt(item.final_rent_price) + '원</span></div>';

			var promos = [];
			if (item.promo_a141 == 1) promos.push('월 -6,000원');
			if (item.promo_a142 == 1) promos.push('렌탈 10% 할인');
			if (item.promo_a143 == 1) promos.push('설치비 면제');
			if (item.promo_a144 == 1) promos.push('3개월 무료');
			if (promos.length > 0) {
				html += '      <div class="d-flex justify-content-between"><span class="text-muted">프로모션</span><span class="text-danger">' + promos.join(', ') + '</span></div>';
			}
			html += '    </div>';
		} else {
			html += '    <div class="small rounded p-2" style="background:#eef2ff;">';
			html += '      <div class="d-flex justify-content-between"><span class="text-muted">일시불 금액</span><span class="fw-bold">' + number_fmt(item.normal_price) + '원</span></div>';
			html += '    </div>';
		}

		html += '  </div>';
		html += '</div>';
	});

	$('#order-detail-body').html(html);
}

function esc(str) {
	return String(str || '')
		.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
</script>
<?php include APP_PATH . '/views/layouts/tail.php';?>
