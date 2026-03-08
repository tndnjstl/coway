<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php $ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자']; ?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">주문 승인 관리</h5>
					<span class="badge bg-warning text-dark">승인 대기 <?= count($orders) ?>건</span>
				</div>

				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-check-circle me-1 text-primary"></i> 가망고객 주문 (승인 대기)
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($orders)): ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-check fa-2x mb-2 d-block text-success"></i>
							승인 대기 주문이 없습니다.
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:36px;">#</th>
										<th>고객명</th>
										<th>구분</th>
										<th>전화번호</th>
										<th class="text-center">담당자</th>
										<th class="text-center">상품수</th>
										<th class="text-end">금액</th>
										<th class="text-center">등록일</th>
										<th class="text-center">처리</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $i => $o): ?>
									<tr id="row-<?= $o['uid'] ?>">
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td class="fw-bold small"><?= htmlspecialchars($o['customer_name']) ?></td>
										<td><span class="badge bg-light text-secondary border small"><?= $ct_map[$o['customer_type']] ?? '' ?></span></td>
										<td class="small"><?= htmlspecialchars($o['customer_phone']) ?></td>
										<td class="text-center small text-muted"><?= htmlspecialchars($o['member_id']) ?></td>
										<td class="text-center small"><?= (int)$o['item_count'] ?>개</td>
										<td class="text-end fw-bold text-primary small"><?= number_format((int)$o['total_pay']) ?>원</td>
										<td class="text-center small text-muted"><?= substr($o['register_date'], 0, 10) ?></td>
										<td class="text-center">
											<button class="btn btn-success btn-sm py-0 px-2 btn-approve" data-uid="<?= $o['uid'] ?>">
												<i class="fas fa-check"></i> 승인
											</button>
											<button class="btn btn-outline-danger btn-sm py-0 px-2 btn-reject ms-1" data-uid="<?= $o['uid'] ?>">
												<i class="fas fa-times"></i> 반려
											</button>
										</td>
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
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script src="/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<script>
function handle_approval(uid, action) {
	var label = action === 'approve' ? '승인' : '반려';
	swal({
		title: label + ' 하시겠습니까?',
		text: action === 'approve' ? '계약 상태로 변경됩니다.' : '반려 처리됩니다.',
		icon: action === 'approve' ? 'success' : 'warning',
		buttons: ['취소', label],
		dangerMode: action === 'reject'
	}).then(function(ok) {
		if (!ok) return;
		$.ajax({
			url: '/Manage/approvalProc',
			method: 'POST',
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({ order_uid: uid, action: action }),
			success: function(res) {
				if (res.success) {
					$('#row-' + uid).fadeOut(300, function() { $(this).remove(); });
					var cnt = parseInt($('.badge.bg-warning').text()) - 1;
					$('.badge.bg-warning').text('승인 대기 ' + cnt + '건');
				} else {
					alert('오류: ' + res.message);
				}
			},
			error: function() { alert('서버 오류가 발생했습니다.'); }
		});
	});
}

$(document).on('click', '.btn-approve', function() {
	handle_approval(parseInt($(this).data('uid')), 'approve');
});
$(document).on('click', '.btn-reject', function() {
	handle_approval(parseInt($(this).data('uid')), 'reject');
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
