<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">영업자 실적</h5>
					<form method="GET" action="/Manage/performance" class="d-flex gap-2 align-items-center">
						<input type="month" name="month" class="form-control form-control-sm" style="width:150px;"
						       value="<?= htmlspecialchars($sel_month) ?>">
						<button type="submit" class="btn btn-primary btn-sm">조회</button>
					</form>
				</div>

				<!-- 실적 테이블 -->
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-chart-bar me-1 text-primary"></i>
							<?= htmlspecialchars($sel_month) ?> 기준 영업자별 실적
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($performance)): ?>
						<div class="text-center py-5 text-muted">데이터가 없습니다.</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3">#</th>
										<th>영업자</th>
										<th class="text-center">이번 달 계약</th>
										<th class="text-center">전달 계약</th>
										<th class="text-center">누적 계약</th>
										<th class="text-end pe-3">이번 달 매출</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$max_this = max(array_column($performance, 'this_month') ?: [1]);
									foreach ($performance as $i => $p):
									$rate = $max_this > 0 ? round($p['this_month'] / $max_this * 100) : 0;
									?>
									<tr>
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td>
											<div class="fw-bold small"><?= htmlspecialchars($p['member_name']) ?></div>
											<div class="small text-muted"><?= htmlspecialchars($p['member_id']) ?></div>
										</td>
										<td class="text-center">
											<div class="fw-bold text-primary"><?= (int)$p['this_month'] ?>건</div>
											<div class="progress mt-1" style="height:4px;width:80px;margin:auto;">
												<div class="progress-bar bg-primary" style="width:<?= $rate ?>%;"></div>
											</div>
										</td>
										<td class="text-center small text-muted"><?= (int)$p['prev_month'] ?>건</td>
										<td class="text-center small"><?= (int)$p['total'] ?>건</td>
										<td class="text-end pe-3 fw-bold text-primary small"><?= number_format((int)$p['this_pay']) ?>원</td>
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
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
