<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$status_map = ['prospect' => '가망', 'contracted' => '계약', 'installed' => '설치완료'];
$status_color = ['prospect' => 'warning', 'contracted' => 'primary', 'installed' => 'success'];
?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">고객 상세</h5>
					<div class="d-flex gap-2">
						<a href="/Customer/edit?uid=<?= $customer['uid'] ?>" class="btn btn-outline-secondary btn-sm">
							<i class="fas fa-edit me-1"></i> 수정
						</a>
						<a href="/Customer/list" class="btn btn-outline-secondary btn-sm">
							<i class="fas fa-arrow-left me-1"></i> 목록
						</a>
					</div>
				</div>

				<!-- 기본 정보 카드 -->
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-user me-1 text-primary"></i> 기본 정보
						</span>
					</div>
					<div class="card-body py-3">
						<div class="row g-3">
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">고객명</div>
								<div class="fw-bold"><?= htmlspecialchars($customer['customer_name']) ?></div>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">구분</div>
								<div>
									<span class="badge bg-light text-secondary border">
										<?= $ct_map[$customer['customer_type']] ?? $customer['customer_type'] ?>
									</span>
								</div>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">전화번호</div>
								<div>
									<a href="tel:<?= preg_replace('/[^0-9]/', '', $customer['customer_phone']) ?>" class="text-decoration-none fw-bold">
										<?= htmlspecialchars($customer['customer_phone']) ?>
									</a>
								</div>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">이메일</div>
								<div class="small"><?= htmlspecialchars($customer['customer_email'] ?: '-') ?></div>
							</div>
							<div class="col-12 col-sm-6">
								<div class="small text-muted mb-1">주소</div>
								<div class="small"><?= htmlspecialchars($customer['address'] ?: '-') ?></div>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">담당자</div>
								<div class="small"><?= htmlspecialchars($customer['member_id']) ?></div>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">등록일</div>
								<div class="small"><?= substr($customer['register_date'], 0, 10) ?></div>
							</div>
							<?php if (!empty($customer['memo'])): ?>
							<div class="col-12">
								<div class="small text-muted mb-1">메모</div>
								<div class="small p-2 rounded" style="background:#f8f9fa;"><?= nl2br(htmlspecialchars($customer['memo'])) ?></div>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- 주문 이력 -->
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
						<span class="fw-bold small">
							<i class="fas fa-clipboard-list me-1 text-primary"></i> 주문 이력
						</span>
						<span class="small text-muted">총 <?= count($orders) ?>건</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($orders)): ?>
						<div class="text-center py-4 text-muted small">주문 이력이 없습니다.</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead class="table-light">
									<tr>
										<th class="ps-3">주문번호</th>
										<th class="text-center">상태</th>
										<th class="text-center">상품수</th>
										<th class="text-end pe-3">총금액</th>
										<th class="text-center">등록일</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $o): ?>
									<tr>
										<td class="ps-3 small text-muted">#<?= $o['uid'] ?></td>
										<td class="text-center">
											<span class="badge bg-<?= $status_color[$o['status']] ?? 'secondary' ?> small">
												<?= $status_map[$o['status']] ?? $o['status'] ?>
											</span>
										</td>
										<td class="text-center small"><?= (int)$o['item_count'] ?>개</td>
										<td class="text-end pe-3 fw-bold text-primary small"><?= number_format((int)$o['total_pay']) ?>원</td>
										<td class="text-center small text-muted"><?= substr($o['register_date'], 0, 10) ?></td>
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
