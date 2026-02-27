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
					<div class="row">
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
												<h4 class="card-category text-dark">주문현황</h>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php include APP_PATH . '/views/layouts/footer.php';?>
		</div>
	</div>
	

	<?php include APP_PATH . '/views/layouts/script.php';?>

	<script>
		function add_order()
		{
			location.href = '/Order/addOrder';
		}
	</script>
<?php include APP_PATH . '/views/layouts/tail.php';?>