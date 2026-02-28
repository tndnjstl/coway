<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

	<div class="wrapper">

		<?php include APP_PATH . '/views/layouts/side_menu.php' ?>

		<div class="main-panel">
			<?php include APP_PATH . '/views/layouts/nav.php' ?>
			<div class="container">
				<div class="page-inner">
					<div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
						<div>
							<h3 class="fw-bold mb-3">영업관리</h3>
							<h6 class="op-7 mb-2"><?php echo date('Y-m-d') ?> <?php echo date_name_kr() ?></h6>
						</div>
					</div>

					<!-- 바로가기 카드 -->
					<div class="row mb-4">
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-round">
								<div class="card-body" onclick="add_order()" style="cursor: pointer !important;">
									<div class="row align-items-center">
										<div class="col-icon">
											<div class="icon-big text-center icon-primary bubble-shadow-small">
												<i class="fas fa-cart-plus"></i>
											</div>
										</div>
										<div class="col col-stats ms-3 ms-sm-0">
											<div class="numbers">
												<h4 class="card-category text-dark">주문접수</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-round">
								<div class="card-body" onclick="order_list()" style="cursor: pointer !important;">
									<div class="row align-items-center">
										<div class="col-icon">
											<div class="icon-big text-center icon-primary bubble-shadow-small">
												<i class="fas fa-shopping-cart"></i>
											</div>
										</div>
										<div class="col col-stats ms-3 ms-sm-0">
											<div class="numbers">
												<h4 class="card-category text-dark">주문현황</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- 계약만료 임박 알림 -->
					<?php if (!empty($expire_contracts)): ?>
					<div class="mb-2">
						<div class="d-flex align-items-center gap-2 mb-2">
							<i class="fas fa-bell text-warning"></i>
							<h6 class="fw-bold mb-0">재계약 안내 (90일 이내 만료)</h6>
							<span class="badge bg-warning text-dark"><?= count($expire_contracts) ?>건</span>
						</div>
						<?php foreach ($expire_contracts as $ec):
							$days = (int)$ec['days_left'];
							if ($days <= 30) {
								$alert_class = 'border-danger';
								$badge_class = 'bg-danger';
								$label = 'D-' . $days;
							} else {
								$alert_class = 'border-warning';
								$badge_class = 'bg-warning text-dark';
								$label = 'D-' . $days;
							}
							$ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
							$ct     = $ct_map[$ec['customer_type']] ?? $ec['customer_type'];
						?>
						<div class="card mb-2 border <?= $alert_class ?>" style="border-width:2px!important;">
							<div class="card-body p-3">
								<div class="d-flex align-items-center justify-content-between">
									<div class="flex-grow-1 min-w-0">
										<div class="d-flex align-items-center gap-2 mb-1">
											<span class="fw-bold"><?= htmlspecialchars($ec['customer_name']) ?></span>
											<span class="badge bg-secondary small"><?= $ct ?></span>
										</div>
										<div class="text-muted small"><?= htmlspecialchars($ec['customer_phone']) ?></div>
										<div class="small mt-1">
											만료일: <span class="fw-bold"><?= htmlspecialchars($ec['contract_end']) ?></span>
											<span class="text-muted ms-1">(의무 <?= (int)$ec['duty_year'] ?>년)</span>
										</div>
									</div>
									<div class="text-end ms-2 flex-shrink-0">
										<span class="badge <?= $badge_class ?> fs-6"><?= $label ?></span>
										<div class="text-muted small mt-1"><?= htmlspecialchars($ec['member_id']) ?></div>
									</div>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
					<?php else: ?>
					<div class="card mb-3" style="border:1px dashed #dee2e6;">
						<div class="card-body p-3 text-center text-muted small">
							<i class="fas fa-check-circle text-success me-1"></i>
							90일 이내 만료 예정 계약이 없습니다.
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
		function add_order()  { location.href = '/Order/addOrder'; }
		function order_list() { location.href = '/Order/orderList'; }
	</script>
<?php include APP_PATH . '/views/layouts/tail.php';?>
