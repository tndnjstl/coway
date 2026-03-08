<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

<?php
$ct_map       = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$report_date  = date('Y년 m월 d일');
$total_count  = count($prospects);
$total_pay    = array_sum(array_column($prospects, 'total_pay'));
$period       = $period ?? 'week';
$period_label = $period_label ?? '이번 주';
$date_from    = $date_from ?? '';
$date_to      = $date_to   ?? '';
?>
<style>
@media print {
	.sidebar, .main-header, .footer, .filter-card, .action-bar { display: none !important; }
	.main-panel { margin-left: 0 !important; }
	.page-inner { padding: 0 !important; }
	.print-table { font-size: 11px; }
	.print-table th, .print-table td { padding: 6px 8px !important; }
	.consult-item { font-size: 10px; }
	.no-print { display: none !important; }
}
</style>

<div class="wrapper">

	<?php include APP_PATH . '/views/layouts/side_menu.php';?>

	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php';?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 페이지 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2 action-bar">
					<h5 class="mb-0 fw-bold">가망고객 보고서</h5>
					<div class="d-flex gap-2">
						<button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
							<i class="fas fa-print me-1"></i> 인쇄 / PDF
						</button>
						<button class="btn btn-warning btn-sm fw-bold no-print" id="btn-send-email">
							<i class="fas fa-paper-plane me-1"></i> 이메일 발송
						</button>
					</div>
				</div>

				<!-- 필터 카드 -->
				<form method="GET" action="/Order/prospectReport" class="mb-3 filter-card">
					<div class="card border-0 shadow-sm">
						<div class="card-body p-3">
							<div class="row g-2 align-items-end">
								<div class="col-6 col-sm-3">
									<label class="form-label small fw-bold mb-1">상담내역 기간</label>
									<select name="period" class="form-select form-select-sm" id="period-select">
										<option value="week"   <?= $period === 'week'   ? 'selected' : '' ?>>이번 주</option>
										<option value="month"  <?= $period === 'month'  ? 'selected' : '' ?>>이번 달</option>
										<option value="all"    <?= $period === 'all'    ? 'selected' : '' ?>>전체</option>
										<option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>직접 입력</option>
									</select>
								</div>
								<div class="col-6 col-sm-2" id="custom-from" style="<?= $period !== 'custom' ? 'display:none;' : '' ?>">
									<label class="form-label small fw-bold mb-1">시작일</label>
									<input type="date" name="date_from" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($date_from) ?>">
								</div>
								<div class="col-6 col-sm-2" id="custom-to" style="<?= $period !== 'custom' ? 'display:none;' : '' ?>">
									<label class="form-label small fw-bold mb-1">종료일</label>
									<input type="date" name="date_to" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($date_to) ?>">
								</div>
								<div class="col-6 col-sm-2">
									<button type="submit" class="btn btn-primary btn-sm w-100">
										<i class="fas fa-search me-1"></i> 조회
									</button>
								</div>
							</div>
						</div>
					</div>
				</form>

				<!-- 요약 카드 -->
				<div class="row g-3 mb-3">
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#1e40af;"><?= $total_count ?></div>
							<div class="small text-muted mt-1">전체 가망고객</div>
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#ef4444;"><?= $new_count ?></div>
							<div class="small text-muted mt-1">이번 주 신규</div>
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#1e40af;"><?= number_format($total_pay) ?>원</div>
							<div class="small text-muted mt-1">총 예상 금액</div>
						</div>
					</div>
					<?php if (!empty($by_member)): ?>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#1e40af;"><?= count($by_member) ?></div>
							<div class="small text-muted mt-1">담당 영업자</div>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<!-- 가망고객 테이블 -->
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
						<span class="fw-bold small">
							<i class="fas fa-users me-1 text-primary"></i> 가망고객 목록
						</span>
						<span class="small text-muted">
							상담내역 기간: <strong><?= htmlspecialchars($period_label) ?></strong>
							<?php if ($period === 'custom' && $date_from && $date_to): ?>
							(<?= date('Y.m.d', strtotime($date_from)) ?> ~ <?= date('Y.m.d', strtotime($date_to)) ?>)
							<?php endif; ?>
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($prospects)): ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
							가망고객이 없습니다.
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0 print-table">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:36px;">#</th>
										<th>고객명</th>
										<th>구분</th>
										<th>전화번호</th>
										<th class="text-center">상품수</th>
										<th class="text-end">예상금액</th>
										<th class="text-center">담당자</th>
										<th class="text-center">등록일</th>
										<th>상담내역 (<?= htmlspecialchars($period_label) ?>)</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($prospects as $i => $p):
										$is_new = substr($p['register_date'], 0, 10) >= $week_start;
										$ct     = $ct_map[$p['customer_type']] ?? $p['customer_type'];
									?>
									<tr>
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td>
											<span class="fw-bold"><?= htmlspecialchars($p['customer_name']) ?></span>
											<?php if ($is_new): ?>
											<span class="badge bg-danger ms-1" style="font-size:9px;">NEW</span>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge bg-light text-secondary border small"><?= $ct ?></span>
										</td>
										<td class="small"><?= htmlspecialchars($p['customer_phone']) ?></td>
										<td class="text-center small"><?= (int)$p['item_count'] ?>개</td>
										<td class="text-end fw-bold text-primary small"><?= number_format((int)$p['total_pay']) ?>원</td>
										<td class="text-center small text-muted"><?= htmlspecialchars($p['member_id']) ?></td>
										<td class="text-center small text-muted"><?= substr($p['register_date'], 0, 10) ?></td>
										<td>
											<?php if (empty($p['consultations'])): ?>
											<span class="text-muted" style="font-size:11px;">-</span>
											<?php else: ?>
											<?php foreach ($p['consultations'] as $c): ?>
											<div class="consult-item mb-1 p-1 rounded" style="background:#f8f9fa;font-size:11px;">
												<span class="text-muted me-1"><?= substr($c['consult_date'], 0, 10) ?></span>
												<span class="text-muted me-1">[<?= htmlspecialchars($c['member_id']) ?>]</span>
												<?= htmlspecialchars($c['content']) ?>
											</div>
											<?php endforeach; ?>
											<?php endif; ?>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- 담당자별 현황 -->
				<?php if (!empty($by_member)): ?>
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-user-tie me-1 text-primary"></i> 담당자별 현황
						</span>
					</div>
					<div class="card-body p-0">
						<table class="table table-sm mb-0" style="max-width:500px;">
							<thead class="table-light">
								<tr>
									<th class="ps-3">담당자</th>
									<th class="text-center">건수</th>
									<th class="text-end pe-3">예상금액 합계</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($by_member as $mid => $ms): ?>
								<tr>
									<td class="ps-3"><?= htmlspecialchars($mid) ?></td>
									<td class="text-center"><?= $ms['count'] ?>건</td>
									<td class="text-end pe-3 fw-bold text-primary"><?= number_format($ms['total_pay']) ?>원</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php';?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php';?>
<script>
var current_period    = '<?= htmlspecialchars($period) ?>';
var current_date_from = '<?= htmlspecialchars($date_from) ?>';
var current_date_to   = '<?= htmlspecialchars($date_to) ?>';

// 기간 선택 변경 시 직접 입력 필드 토글
$('#period-select').on('change', function() {
	var val = $(this).val();
	if (val === 'custom') {
		$('#custom-from, #custom-to').show();
	} else {
		$('#custom-from, #custom-to').hide();
	}
});

// 이메일 발송
$('#btn-send-email').on('click', function() {
	if (!confirm('<?= htmlspecialchars(REPORT_RECIPIENT_NAME) ?>님께 이메일을 발송하시겠습니까?')) return;

	var $btn = $(this);
	$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> 발송 중...');

	$.ajax({
		url: '/Order/sendProspectReport',
		method: 'POST',
		contentType: 'application/json',
		dataType: 'json',
		data: JSON.stringify({
			period:    current_period,
			date_from: current_date_from,
			date_to:   current_date_to
		}),
		success: function(res) {
			if (res.status === 'success') {
				$btn.removeClass('btn-warning').addClass('btn-success')
					.html('<i class="fas fa-check me-1"></i> 발송 완료 (' + res.recipient + ')');
			} else {
				alert('발송 실패: ' + (res.message || '오류가 발생했습니다.'));
				$btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> 이메일 발송');
			}
		},
		error: function() {
			alert('서버 오류가 발생했습니다.');
			$btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> 이메일 발송');
		}
	});
});
</script>
