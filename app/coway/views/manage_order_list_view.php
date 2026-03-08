<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$status_map   = ['prospect' => '가망', 'contracted' => '계약', 'installed' => '설치완료'];
$status_color = ['prospect' => 'warning', 'contracted' => 'primary', 'installed' => 'success'];
?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<div class="mb-3 mt-2">
					<h5 class="mb-0 fw-bold">팀 주문 현황</h5>
				</div>

				<!-- 요약 카드 -->
				<div class="row g-3 mb-3">
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#1e40af;"><?= number_format((int)($summary['total'] ?? 0)) ?></div>
							<div class="small text-muted mt-1">전체 주문</div>
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#1e40af;"><?= number_format((int)($summary['contracted'] ?? 0)) ?></div>
							<div class="small text-muted mt-1">계약 완료</div>
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:22px;color:#16a34a;"><?= number_format((int)($summary['installed'] ?? 0)) ?></div>
							<div class="small text-muted mt-1">설치 완료</div>
						</div>
					</div>
					<div class="col-6 col-sm-3">
						<div class="card border-0 shadow-sm text-center py-3">
							<div class="fw-bold" style="font-size:20px;color:#1e40af;"><?= number_format((int)($summary['total_pay'] ?? 0)) ?>원</div>
							<div class="small text-muted mt-1">총 매출</div>
						</div>
					</div>
				</div>

				<!-- 검색 필터 -->
				<form method="GET" action="/Manage/orderList" class="mb-3">
					<div class="card border-0 shadow-sm">
						<div class="card-body p-3">
							<div class="row g-2 align-items-end">
								<div class="col-12 col-sm-3">
									<label class="form-label small fw-bold mb-1">검색</label>
									<input type="text" name="keyword" class="form-control form-control-sm"
									       placeholder="고객명 또는 전화번호"
									       value="<?= htmlspecialchars($keyword ?? '') ?>">
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">상태</label>
									<select name="status" class="form-select form-select-sm">
										<option value="">전체</option>
										<option value="prospect"   <?= ($filter_status ?? '') === 'prospect'   ? 'selected' : '' ?>>가망</option>
										<option value="contracted" <?= ($filter_status ?? '') === 'contracted' ? 'selected' : '' ?>>계약</option>
										<option value="installed"  <?= ($filter_status ?? '') === 'installed'  ? 'selected' : '' ?>>설치완료</option>
									</select>
								</div>
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
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">등록일(시작)</label>
									<input type="date" name="date_from" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($date_from ?? '') ?>">
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">등록일(종료)</label>
									<input type="date" name="date_to" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($date_to ?? '') ?>">
								</div>
								<div class="col-6 col-sm-1">
									<button type="submit" class="btn btn-primary btn-sm w-100">조회</button>
								</div>
							</div>
						</div>
					</div>
				</form>

				<!-- 목록 -->
				<div class="card border-0 shadow-sm">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small">
							<i class="fas fa-list-alt me-1 text-primary"></i> 전체 주문 (<?= count($orders) ?>건)
						</span>
					</div>
					<div class="card-body p-0">
						<?php if (empty($orders)): ?>
						<div class="text-center py-5 text-muted"><i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i> 주문이 없습니다.</div>
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
										<th class="text-center">상태</th>
										<th class="text-center">상품수</th>
										<th class="text-end">금액</th>
										<th class="text-center">등록일</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($orders as $i => $o): ?>
									<tr>
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td class="fw-bold small"><?= htmlspecialchars($o['customer_name']) ?></td>
										<td><span class="badge bg-light text-secondary border small"><?= $ct_map[$o['customer_type']] ?? '' ?></span></td>
										<td class="small"><?= htmlspecialchars($o['customer_phone']) ?></td>
										<td class="text-center small text-muted"><?= htmlspecialchars($o['member_id']) ?></td>
										<td class="text-center">
											<span class="badge bg-<?= $status_color[$o['status']] ?? 'secondary' ?> small">
												<?= $status_map[$o['status']] ?? $o['status'] ?>
											</span>
										</td>
										<td class="text-center small"><?= (int)$o['item_count'] ?>개</td>
										<td class="text-end fw-bold text-primary small"><?= number_format((int)$o['total_pay']) ?>원</td>
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
