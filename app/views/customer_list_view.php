<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 페이지 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">고객 관리</h5>
					<a href="/Customer/add" class="btn btn-primary btn-sm">
						<i class="fas fa-plus me-1"></i> 고객 등록
					</a>
				</div>

				<!-- 검색 필터 -->
				<form method="GET" action="/Customer/list" class="mb-3">
					<div class="card border-0 shadow-sm">
						<div class="card-body p-3">
							<div class="row g-2 align-items-end">
								<div class="col-12 col-sm-4">
									<label class="form-label small fw-bold mb-1">검색</label>
									<input type="text" name="keyword" class="form-control form-control-sm"
									       placeholder="고객명 또는 전화번호"
									       value="<?= htmlspecialchars($keyword ?? '') ?>">
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">구분</label>
									<select name="type" class="form-select form-select-sm">
										<option value="">전체</option>
										<option value="P" <?= ($filter_type ?? '') === 'P' ? 'selected' : '' ?>>개인</option>
										<option value="B" <?= ($filter_type ?? '') === 'B' ? 'selected' : '' ?>>개인사업자</option>
										<option value="C" <?= ($filter_type ?? '') === 'C' ? 'selected' : '' ?>>법인사업자</option>
									</select>
								</div>
								<?php if (is_manager() && !empty($members)): ?>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">담당자</label>
									<select name="member_id" class="form-select form-select-sm">
										<option value="">전체</option>
										<?php foreach ($members as $m): ?>
										<option value="<?= htmlspecialchars($m['member_id']) ?>"
										        <?= ($filter_mid ?? '') === $m['member_id'] ? 'selected' : '' ?>>
											<?= htmlspecialchars($m['member_name']) ?>
										</option>
										<?php endforeach; ?>
									</select>
								</div>
								<?php endif; ?>
								<div class="col-6 col-sm-2">
									<button type="submit" class="btn btn-primary btn-sm w-100">
										<i class="fas fa-search me-1"></i> 조회
									</button>
								</div>
							</div>
						</div>
					</div>
				</form>

				<!-- 결과 요약 -->
				<div class="d-flex align-items-center justify-content-between mb-2">
					<span class="small text-muted">총 <strong><?= count($customers) ?></strong>명</span>
				</div>

				<!-- 고객 목록 -->
				<div class="card border-0 shadow-sm">
					<div class="card-body p-0">
						<?php if (empty($customers)): ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-users fa-2x mb-2 d-block"></i>
							등록된 고객이 없습니다.
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0" id="customer-table">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:36px;">#</th>
										<th>고객명</th>
										<th>구분</th>
										<th>전화번호</th>
										<?php if (is_manager()): ?>
										<th class="text-center">담당자</th>
										<?php endif; ?>
										<th class="text-center">주문수</th>
										<th class="text-center">등록일</th>
										<th class="text-center">상세</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($customers as $i => $c): ?>
									<tr>
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td class="fw-bold"><?= htmlspecialchars($c['customer_name']) ?></td>
										<td>
											<span class="badge bg-light text-secondary border small">
												<?= $ct_map[$c['customer_type']] ?? $c['customer_type'] ?>
											</span>
										</td>
										<td class="small">
											<a href="tel:<?= preg_replace('/[^0-9]/', '', $c['customer_phone']) ?>" class="text-decoration-none">
												<?= htmlspecialchars($c['customer_phone']) ?>
											</a>
										</td>
										<?php if (is_manager()): ?>
										<td class="text-center small text-muted"><?= htmlspecialchars($c['member_id']) ?></td>
										<?php endif; ?>
										<td class="text-center small"><?= (int)$c['order_count'] ?>건</td>
										<td class="text-center small text-muted"><?= substr($c['register_date'], 0, 10) ?></td>
										<td class="text-center">
											<a href="/Customer/detail?uid=<?= $c['uid'] ?>" class="btn btn-outline-primary btn-sm py-0 px-2">
												<i class="fas fa-eye"></i>
											</a>
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
<script>
$(function() {
	if ($('#customer-table tbody tr').length > 10) {
		$('#customer-table').DataTable({
			language: { url: '' },
			pageLength: 25,
			order: [],
			columnDefs: [{ orderable: false, targets: [-1] }]
		});
	}
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
