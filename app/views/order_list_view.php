<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

<?php
$customer_type_map   = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$customer_type_badge = ['P' => 'bg-secondary', 'B' => 'bg-info', 'C' => 'bg-warning text-dark'];
$status_map          = ['prospect' => '가망고객', 'contracted' => '계약완료', 'installed' => '설치완료'];
$status_badge        = ['prospect' => 'bg-light text-secondary border', 'contracted' => 'bg-primary', 'installed' => 'bg-success'];
$keyword             = htmlspecialchars($_GET['keyword'] ?? '');
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

				<!-- 검색창 -->
				<form method="GET" action="/Order/orderList" class="mb-3">
					<div class="input-group">
						<input type="text" name="keyword" class="form-control form-control-sm"
							placeholder="고객명 또는 전화번호 검색"
							value="<?= $keyword ?>">
						<button class="btn btn-outline-secondary btn-sm" type="submit">
							<i class="fas fa-search"></i>
						</button>
						<?php if ($keyword !== ''): ?>
						<a href="/Order/orderList" class="btn btn-outline-danger btn-sm">
							<i class="fas fa-times"></i>
						</a>
						<?php endif; ?>
					</div>
				</form>

				<?php if ($keyword !== '' && empty($orders)): ?>
					<div class="text-center text-muted py-5">
						<i class="fas fa-search fa-2x mb-2 d-block"></i>
						"<?= $keyword ?>"에 대한 검색 결과가 없습니다.
					</div>
				<?php elseif (empty($orders)): ?>
					<div class="text-center text-muted py-5">
						<i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
						등록된 주문이 없습니다.
					</div>
				<?php else: ?>
					<?php foreach ($orders as $order):
						$ct     = $customer_type_map[$order['customer_type']]   ?? $order['customer_type'];
						$badge  = $customer_type_badge[$order['customer_type']] ?? 'bg-secondary';
						$st     = $status_map[$order['status'] ?? 'prospect']   ?? ($order['status'] ?? '');
						$sbadge = $status_badge[$order['status'] ?? 'prospect'] ?? 'bg-secondary';
						$date   = date('m/d H:i', strtotime($order['register_date']));
					?>
					<div class="card mb-2 order-row" data-order-uid="<?= $order['uid'] ?>" style="cursor:pointer;">
						<div class="card-body p-3">
							<div class="d-flex align-items-start justify-content-between">
								<div class="flex-grow-1 min-w-0">
									<div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
										<span class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></span>
										<span class="badge <?= $badge ?> small"><?= $ct ?></span>
										<span class="badge <?= $sbadge ?> status-badge small"><?= $st ?></span>
									</div>
									<div class="text-muted small mb-1"><?= htmlspecialchars($order['customer_phone']) ?></div>
									<div class="d-flex align-items-center gap-3">
										<span class="small text-muted">상품 <?= (int)$order['item_count'] ?>개</span>
										<span class="small fw-bold text-primary"><?= number_format((int)$order['total_pay']) ?>원</span>
									</div>
								</div>
								<div class="text-end flex-shrink-0 ms-2">
									<div class="text-muted small mb-2"><?= $date ?></div>
									<i class="fas fa-chevron-right text-muted small"></i>
								</div>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php';?>
	</div>
</div>

<!-- 주문 상세 모달 -->
<div class="modal fade" id="modal-order-detail" tabindex="-1" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" style="max-width:560px;">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold">주문 상세</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body p-3" id="order-detail-body">
				<div class="text-center py-4">
					<div class="spinner-border spinner-border-sm me-2" role="status"></div>
					불러오는 중...
				</div>
			</div>
			<div class="modal-footer py-2" id="order-detail-footer">
				<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">닫기</button>
			</div>
		</div>
	</div>
</div>

<!-- 설치완료 처리 모달 -->
<div class="modal fade" id="modal-install" tabindex="-1" data-bs-backdrop="static">
	<div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold"><i class="fas fa-tools me-1 text-success"></i>설치완료 처리</h6>
			</div>
			<div class="modal-body p-3">
				<div class="mb-3">
					<label class="form-label small fw-bold">계약 시작일 <span class="text-danger">*</span></label>
					<input type="date" id="install_contract_start" class="form-control form-control-sm">
				</div>
				<div class="mb-1">
					<label class="form-label small fw-bold">의무기간 <span class="text-danger">*</span></label>
					<select id="install_duty_year" class="form-select form-select-sm">
						<option value="">선택</option>
						<option value="3">3년</option>
						<option value="5">5년</option>
						<option value="6">6년</option>
						<option value="7">7년</option>
						<option value="9">9년</option>
					</select>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-secondary btn-sm" id="btn-install-cancel">취소</button>
				<button type="button" class="btn btn-success btn-sm" id="btn-install-confirm">
					<i class="fas fa-check me-1"></i> 설치완료 확정
				</button>
			</div>
		</div>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php';?>
<script>
var order_detail_modal = new bootstrap.Modal(document.getElementById('modal-order-detail'));
var install_modal      = new bootstrap.Modal(document.getElementById('modal-install'));
var customer_type_map  = { P: '개인', B: '개인사업자', C: '법인사업자' };
var status_map         = { prospect: '가망고객', contracted: '계약완료', installed: '설치완료' };
var status_badge_map   = { prospect: 'bg-light text-secondary border', contracted: 'bg-primary', installed: 'bg-success' };
var current_order_uid  = null;

/* ─── 주문 행 클릭 ─── */
$(document).on('click', '.order-row', function() {
	current_order_uid = $(this).data('order-uid');
	reset_detail_modal();
	order_detail_modal.show();
	load_order_detail(current_order_uid);
});

function reset_detail_modal() {
	$('#order-detail-body').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>불러오는 중...</div>');
	$('#order-detail-footer').html('<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">닫기</button>');
}

function load_order_detail(order_uid) {
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
			render_order_detail(res.order, res.items, res.contract);
		},
		error: function() {
			$('#order-detail-body').html('<div class="text-danger text-center py-4">서버 오류가 발생했습니다.</div>');
		}
	});
}

/* ─── 상태 변경 버튼 핸들러 ─── */
$(document).on('click', '#btn-status-contracted', function() {
	if (!confirm('계약완료로 변경하시겠습니까?')) return;
	call_update_status(current_order_uid, 'contracted', null, null);
});

$(document).on('click', '#btn-status-prospect', function() {
	if (!confirm('가망고객으로 되돌리시겠습니까?')) return;
	call_update_status(current_order_uid, 'prospect', null, null);
});

$(document).on('click', '#btn-status-installed', function() {
	$('#install_contract_start').val(new Date().toISOString().slice(0, 10));
	$('#install_duty_year').val('');
	order_detail_modal.hide();
	install_modal.show();
});

/* ─── 설치완료 확정 ─── */
$('#btn-install-confirm').on('click', function() {
	var cs = $('#install_contract_start').val();
	var dy = $('#install_duty_year').val();
	if (!cs) { alert('계약 시작일을 입력해주세요.'); return; }
	if (!dy)  { alert('의무기간을 선택해주세요.'); return; }
	install_modal.hide();
	call_update_status(current_order_uid, 'installed', cs, parseInt(dy));
});

$('#btn-install-cancel').on('click', function() {
	install_modal.hide();
	order_detail_modal.show();
});

/* ─── 상태 변경 AJAX ─── */
function call_update_status(order_uid, new_status, contract_start, duty_year) {
	$.ajax({
		url: '/Order/updateOrderStatus',
		method: 'POST',
		contentType: 'application/json',
		dataType: 'json',
		data: JSON.stringify({
			order_uid:      order_uid,
			new_status:     new_status,
			contract_start: contract_start || '',
			duty_year:      duty_year || 0
		}),
		success: function(res) {
			if (res.status !== 'success') {
				alert(res.message || '오류가 발생했습니다.');
				return;
			}
			// 목록 행 뱃지 즉시 갱신
			$('.order-row[data-order-uid="' + order_uid + '"] .status-badge')
				.attr('class', 'badge status-badge small ' + (status_badge_map[new_status] || 'bg-secondary'))
				.text(status_map[new_status] || new_status);
			// 모달 새로고침
			order_detail_modal.show();
			reset_detail_modal();
			load_order_detail(order_uid);
		},
		error: function() { alert('서버 오류가 발생했습니다.'); }
	});
}

/* ─── 유틸 ─── */
function fmt(n) { return Number(n).toLocaleString('ko-KR'); }

function esc(str) {
	return String(str || '')
		.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* ─── 상세 렌더 ─── */
function render_order_detail(order, items, contract) {
	var ct   = customer_type_map[order.customer_type] || order.customer_type;
	var html = '';

	/* 고객 정보 */
	html += '<div class="rounded p-3 mb-3" style="background:#f8f9fa;">';
	html += '  <div class="row g-2 small">';
	html += '    <div class="col-6"><div class="text-muted">주문번호</div><div class="fw-bold">#' + order.uid + '</div></div>';
	html += '    <div class="col-6"><div class="text-muted">고객구분</div><div class="fw-bold">' + ct + '</div></div>';
	html += '    <div class="col-6"><div class="text-muted">고객명</div><div class="fw-bold">' + esc(order.customer_name) + '</div></div>';
	html += '    <div class="col-6"><div class="text-muted">전화번호</div><div class="fw-bold">' + esc(order.customer_phone) + '</div></div>';
	html += '    <div class="col-6"><div class="text-muted">담당자</div><div>' + esc(order.member_id) + '</div></div>';
	html += '    <div class="col-6"><div class="text-muted">등록일시</div><div>' + order.register_date + '</div></div>';
	if (order.memo) {
		html += '  <div class="col-12"><div class="text-muted">메모</div><div>' + esc(order.memo) + '</div></div>';
	}
	html += '  </div>';
	html += '</div>';

	/* 계약 정보 (설치완료인 경우) */
	if (order.status === 'installed' && contract) {
		html += '<div class="rounded p-3 mb-3" style="background:#e8f5e9;">';
		html += '  <div class="fw-bold small mb-2 text-success"><i class="fas fa-file-contract me-1"></i>계약 정보</div>';
		html += '  <div class="row g-2 small">';
		html += '    <div class="col-6"><div class="text-muted">계약시작일</div><div class="fw-bold">' + esc(contract.contract_start) + '</div></div>';
		html += '    <div class="col-6"><div class="text-muted">계약만료일</div><div class="fw-bold">' + esc(contract.contract_end) + '</div></div>';
		html += '    <div class="col-12"><div class="text-muted">의무기간</div><div class="fw-bold">' + contract.duty_year + '년</div></div>';
		html += '  </div>';
		html += '</div>';
	}

	/* 상품 목록 */
	$.each(items, function(_, item) {
		var is_rent = item.payment_type === 'rent';
		html += '<div class="card mb-2 border">';
		html += '  <div class="card-body p-3">';
		html += '  <div class="d-flex justify-content-between align-items-start mb-2">';
		html += '    <div class="flex-grow-1 min-w-0">';
		html += '      <div class="fw-bold small text-truncate">' + esc(item.model_name) + '</div>';
		html += '      <div class="text-muted" style="font-size:11px;">' + esc(item.model_no) + ' · ' + esc(item.model_color) + '</div>';
		html += '      <div class="mt-1">';
		html += '        <span class="badge bg-light text-secondary border me-1" style="font-size:11px;">' + esc(item.category) + '</span>';
		html += '        <span class="badge ' + (is_rent ? 'bg-primary' : 'bg-success') + '" style="font-size:11px;">' + (is_rent ? '렌탈' : '일시불') + '</span>';
		html += '      </div>';
		html += '    </div>';
		html += '    <div class="text-end ms-2 flex-shrink-0">';
		html += '      <div class="fw-bold text-primary small">' + fmt(item.total_pay) + '원</div>';
		html += '      <div class="text-muted" style="font-size:11px;">총납부예상</div>';
		html += '    </div>';
		html += '  </div>';
		html += '  <div class="rounded p-2" style="background:#eef2ff;font-size:12px;">';
		if (is_rent) {
			html += '    <div class="d-flex justify-content-between mb-1"><span class="text-muted">방문주기 / 의무기간</span><span>' + item.visit_cycle + '개월 / ' + item.duty_year + '년</span></div>';
			html += '    <div class="d-flex justify-content-between mb-1"><span class="text-muted">최종 등록비</span><span class="fw-bold">' + fmt(item.final_setup_price) + '원</span></div>';
			html += '    <div class="d-flex justify-content-between mb-1"><span class="text-muted">최종 월렌탈료</span><span class="fw-bold">' + fmt(item.final_rent_price) + '원</span></div>';
			var promos = [];
			if (item.promo_a141 == 1) promos.push('월 -6,000원');
			if (item.promo_a142 == 1) promos.push('렌탈 10%↓');
			if (item.promo_a143 == 1) promos.push('설치비 면제');
			if (item.promo_a144 == 1) promos.push('3개월 무료');
			if (promos.length > 0) {
				html += '    <div class="d-flex justify-content-between"><span class="text-muted">프로모션</span><span class="text-danger">' + promos.join(' · ') + '</span></div>';
			}
		} else {
			html += '    <div class="d-flex justify-content-between"><span class="text-muted">일시불 금액</span><span class="fw-bold">' + fmt(item.normal_price) + '원</span></div>';
		}
		html += '  </div>';
		html += '  </div>';
		html += '</div>';
	});

	$('#order-detail-body').html(html);

	/* 상태 변경 버튼 */
	var footer_html = '<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">닫기</button>';
	if (order.status === 'prospect') {
		footer_html += ' <button type="button" id="btn-status-contracted" class="btn btn-primary btn-sm"><i class="fas fa-handshake me-1"></i>계약완료</button>';
	} else if (order.status === 'contracted') {
		footer_html += ' <button type="button" id="btn-status-prospect" class="btn btn-outline-secondary btn-sm">가망고객으로</button>';
		footer_html += ' <button type="button" id="btn-status-installed" class="btn btn-success btn-sm"><i class="fas fa-tools me-1"></i>설치완료</button>';
	} else if (order.status === 'installed') {
		footer_html += ' <button type="button" id="btn-status-contracted" class="btn btn-outline-primary btn-sm">계약완료로</button>';
	}
	$('#order-detail-footer').html(footer_html);
}
</script>
<?php include APP_PATH . '/views/layouts/tail.php';?>
