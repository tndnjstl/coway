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
					<h5 class="mb-0 fw-bold">제품 관리</h5>
					<button class="btn btn-warning btn-sm fw-bold" id="btn-crawl">
						<i class="fas fa-sync me-1"></i> 제품 크롤링 실행
					</button>
				</div>

				<!-- 카테고리 탭 -->
				<ul class="nav nav-pills mb-3 flex-wrap gap-1">
					<li class="nav-item">
						<a class="nav-link <?= $category === '' ? 'active' : '' ?> py-1 px-3 small"
						   href="/Admin/productList">전체</a>
					</li>
					<?php foreach ($categories as $cat): ?>
					<li class="nav-item">
						<a class="nav-link <?= $category === $cat ? 'active' : '' ?> py-1 px-3 small"
						   href="/Admin/productList?category=<?= urlencode($cat) ?>"><?= htmlspecialchars($cat) ?></a>
					</li>
					<?php endforeach; ?>
				</ul>

				<!-- 제품 목록 -->
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-box me-1 text-primary"></i>
							<?= $category !== '' ? htmlspecialchars($category) : '전체' ?> 제품 (<?= count($products) ?>건)
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($products)): ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-box-open fa-2x mb-2 d-block"></i>
							제품이 없습니다. 크롤링을 실행해주세요.
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:60px;">이미지</th>
										<th>모델명</th>
										<th>모델번호</th>
										<th>카테고리</th>
										<th class="text-end">렌탈가/월</th>
										<th class="text-end pe-3">정상가</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($products as $p): ?>
									<tr>
										<td class="ps-3">
											<?php if (!empty($p['model_image'])): ?>
											<img src="<?= htmlspecialchars($p['model_image']) ?>" alt=""
											     style="width:40px;height:40px;object-fit:contain;">
											<?php else: ?>
											<div style="width:40px;height:40px;background:#f1f5f9;border-radius:4px;"></div>
											<?php endif; ?>
										</td>
										<td>
											<div class="fw-bold small"><?= htmlspecialchars($p['model_name']) ?></div>
											<div class="small text-muted"><?= htmlspecialchars($p['model_uid']) ?></div>
										</td>
										<td class="small text-muted"><?= htmlspecialchars($p['model_no']) ?></td>
										<td><span class="badge bg-light text-secondary border small"><?= htmlspecialchars($p['category']) ?></span></td>
										<td class="text-end fw-bold text-primary small"><?= number_format((int)$p['rent_price']) ?>원</td>
										<td class="text-end pe-3 small text-muted"><?= number_format((int)$p['normal_price']) ?>원</td>
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
$('#btn-crawl').on('click', function() {
	if (!confirm('제품 크롤링을 실행하시겠습니까?\n시간이 다소 걸릴 수 있습니다.')) return;
	var $btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> 크롤링 중...');
	// 크롤링은 별도 페이지로 이동해서 실행
	window.location.href = '/Admin/productCrawlProc';
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
