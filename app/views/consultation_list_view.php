<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$type_map  = ['visit' => '방문', 'phone' => '전화', 'online' => '화상'];
$type_color = ['visit' => 'primary', 'phone' => 'success', 'online' => 'info'];
$status_map   = ['recording' => '녹음 중', 'completed' => '완료', 'archived' => '보관'];
$status_color = ['recording' => 'danger', 'completed' => 'primary', 'archived' => 'secondary'];
?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 페이지 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">상담 내역</h5>
					<a href="/Consultation/form" class="btn btn-primary btn-sm">
						<i class="fas fa-microphone me-1"></i> 상담 녹음
					</a>
				</div>

				<!-- 검색 필터 -->
				<form method="GET" action="/Consultation/list" class="mb-3">
					<div class="card border-0 shadow-sm">
						<div class="card-body p-3">
							<div class="row g-2 align-items-end">
								<div class="col-12 col-sm-3">
									<label class="form-label small fw-bold mb-1">검색</label>
									<input type="text" name="keyword" class="form-control form-control-sm"
									       placeholder="상담제목 또는 고객명"
									       value="<?= htmlspecialchars($keyword ?? '') ?>">
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">유형</label>
									<select name="consult_type" class="form-select form-select-sm">
										<option value="">전체</option>
										<option value="visit"  <?= ($filter_type ?? '') === 'visit'  ? 'selected' : '' ?>>방문</option>
										<option value="phone"  <?= ($filter_type ?? '') === 'phone'  ? 'selected' : '' ?>>전화</option>
										<option value="online" <?= ($filter_type ?? '') === 'online' ? 'selected' : '' ?>>화상</option>
									</select>
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">시작일</label>
									<input type="date" name="date_from" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($filter_date_from ?? '') ?>">
								</div>
								<div class="col-6 col-sm-2">
									<label class="form-label small fw-bold mb-1">종료일</label>
									<input type="date" name="date_to" class="form-control form-control-sm"
									       value="<?= htmlspecialchars($filter_date_to ?? '') ?>">
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
								<div class="col-6 col-sm-1">
									<button type="submit" class="btn btn-primary btn-sm w-100">
										<i class="fas fa-search"></i>
									</button>
								</div>
							</div>
						</div>
					</div>
				</form>

				<!-- 결과 요약 -->
				<div class="d-flex align-items-center justify-content-between mb-2">
					<span class="small text-muted">총 <strong><?= count($consultations) ?></strong>건</span>
				</div>

				<!-- 목록 -->
				<div class="card border-0 shadow-sm">
					<div class="card-body p-0">
						<?php if (empty($consultations)): ?>
						<div class="text-center py-5 text-muted">
							<i class="fas fa-comments fa-2x mb-2 d-block"></i>
							등록된 상담 내역이 없습니다.
						</div>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0" id="consultation-table">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:36px;">#</th>
										<th>상담일시</th>
										<th>고객명</th>
										<th class="text-center">유형</th>
										<th>제목</th>
										<?php if (is_manager()): ?>
										<th class="text-center">담당자</th>
										<?php endif; ?>
										<th class="text-center">녹음시간</th>
										<th class="text-center">상태</th>
										<th class="text-center">상세</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($consultations as $i => $c): ?>
									<tr>
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td class="small"><?= substr($c['consult_date'], 0, 16) ?></td>
										<td class="fw-bold small"><?= htmlspecialchars($c['customer_name'] ?: '-') ?></td>
										<td class="text-center">
											<span class="badge bg-<?= $type_color[$c['consult_type']] ?? 'secondary' ?> small">
												<?= $type_map[$c['consult_type']] ?? $c['consult_type'] ?>
											</span>
										</td>
										<td class="small"><?= htmlspecialchars($c['title']) ?></td>
										<?php if (is_manager()): ?>
										<td class="text-center small text-muted"><?= htmlspecialchars($c['member_id']) ?></td>
										<?php endif; ?>
										<td class="text-center small text-muted">
											<?php if ($c['audio_duration']): ?>
											<?= sprintf('%02d:%02d', floor($c['audio_duration'] / 60), $c['audio_duration'] % 60) ?>
											<?php else: ?>-<?php endif; ?>
										</td>
										<td class="text-center">
											<span class="badge bg-<?= $status_color[$c['status']] ?? 'secondary' ?> small">
												<?= $status_map[$c['status']] ?? $c['status'] ?>
											</span>
										</td>
										<td class="text-center">
											<a href="/Consultation/detail?uid=<?= $c['uid'] ?>" class="btn btn-outline-primary btn-sm py-0 px-2">
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
$(function () {
	if ($('#consultation-table tbody tr').length > 10) {
		$('#consultation-table').DataTable({
			pageLength: 25,
			order: [[1, 'desc']],
			columnDefs: [{ orderable: false, targets: [-1] }],
		});
	}
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
