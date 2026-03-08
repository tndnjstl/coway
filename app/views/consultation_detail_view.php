<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$type_map   = ['visit' => '방문', 'phone' => '전화', 'online' => '화상'];
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

				<!-- 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">상담 상세</h5>
					<div class="d-flex gap-2">
						<?php if (is_manager()): ?>
						<button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete">
							<i class="fas fa-trash me-1"></i> 삭제
						</button>
						<?php endif; ?>
						<a href="/Consultation/list" class="btn btn-outline-secondary btn-sm">
							<i class="fas fa-arrow-left me-1"></i> 목록
						</a>
					</div>
				</div>

				<!-- 기본 정보 카드 -->
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small"><i class="fas fa-info-circle me-1 text-primary"></i> 상담 정보</span>
					</div>
					<div class="card-body py-3">
						<div class="row g-3">
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">상담 제목</div>
								<div class="fw-bold"><?= htmlspecialchars($consultation['title']) ?></div>
							</div>
							<div class="col-6 col-sm-2">
								<div class="small text-muted mb-1">상담 유형</div>
								<span class="badge bg-<?= $type_color[$consultation['consult_type']] ?? 'secondary' ?>">
									<?= $type_map[$consultation['consult_type']] ?? $consultation['consult_type'] ?>
								</span>
							</div>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">상담 일시</div>
								<div class="small"><?= substr($consultation['consult_date'], 0, 16) ?></div>
							</div>
							<div class="col-6 col-sm-2">
								<div class="small text-muted mb-1">상태</div>
								<span class="badge bg-<?= $status_color[$consultation['status']] ?? 'secondary' ?>">
									<?= $status_map[$consultation['status']] ?? $consultation['status'] ?>
								</span>
							</div>
							<div class="col-6 col-sm-2">
								<div class="small text-muted mb-1">녹음 시간</div>
								<div class="small">
									<?php if ($consultation['audio_duration']): ?>
									<?= sprintf('%02d:%02d', floor($consultation['audio_duration'] / 60), $consultation['audio_duration'] % 60) ?>
									<?php else: ?>-<?php endif; ?>
								</div>
							</div>
							<?php if (!empty($consultation['customer_name'])): ?>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">고객명</div>
								<div class="fw-bold"><?= htmlspecialchars($consultation['customer_name']) ?></div>
							</div>
							<?php endif; ?>
							<div class="col-6 col-sm-3">
								<div class="small text-muted mb-1">담당자</div>
								<div class="small"><?= htmlspecialchars($consultation['member_id']) ?></div>
							</div>
						</div>
					</div>
				</div>

				<!-- 오디오 플레이어 -->
				<?php if (!empty($consultation['audio_file'])): ?>
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
						<span class="fw-bold small"><i class="fas fa-volume-up me-1 text-primary"></i> 녹음 파일</span>
						<a href="/Consultation/downloadAudio?uid=<?= $consultation['uid'] ?>" class="btn btn-outline-secondary btn-sm py-0">
							<i class="fas fa-download me-1"></i> 다운로드
						</a>
					</div>
					<div class="card-body py-3">
						<audio controls style="width:100%;height:40px;">
							<source src="<?= htmlspecialchars($consultation['audio_file']) ?>" type="audio/webm">
							브라우저가 오디오 재생을 지원하지 않습니다.
						</audio>
					</div>
				</div>
				<?php endif; ?>

				<!-- STT 전문 텍스트 + 요약 (편집 폼) -->
				<form method="POST" action="/Consultation/editProc">
					<input type="hidden" name="uid" value="<?= $consultation['uid'] ?>">
					<input type="hidden" name="consult_type" value="<?= htmlspecialchars($consultation['consult_type']) ?>">
					<input type="hidden" name="consult_date" value="<?= htmlspecialchars($consultation['consult_date']) ?>">

					<div class="card border-0 shadow-sm mb-3">
						<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
							<span class="fw-bold small"><i class="fas fa-align-left me-1 text-primary"></i> STT 변환 텍스트</span>
							<span class="small text-muted">직접 편집 가능</span>
						</div>
						<div class="card-body py-3">
							<input type="text" name="title" class="form-control form-control-sm mb-2"
							       value="<?= htmlspecialchars($consultation['title']) ?>" placeholder="상담 제목">
							<textarea name="stt_text" class="form-control form-control-sm" rows="10"
							          style="resize:vertical;font-size:14px;line-height:1.7;"
							          placeholder="음성 변환 텍스트가 여기에 표시됩니다."><?= htmlspecialchars($consultation['stt_text'] ?? '') ?></textarea>
						</div>
					</div>

					<div class="card border-0 shadow-sm mb-3">
						<div class="card-header bg-white border-bottom py-2">
							<span class="fw-bold small"><i class="fas fa-sticky-note me-1 text-warning"></i> 상담 요약 메모</span>
						</div>
						<div class="card-body py-3">
							<textarea name="stt_summary" class="form-control form-control-sm" rows="4"
							          style="resize:vertical;"
							          placeholder="핵심 내용을 요약해주세요."><?= htmlspecialchars($consultation['stt_summary'] ?? '') ?></textarea>
						</div>
					</div>

					<div class="d-flex justify-content-end">
						<button type="submit" class="btn btn-primary btn-sm">
							<i class="fas fa-save me-1"></i> 저장
						</button>
					</div>
				</form>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script>
$(function () {
	<?php if (is_manager()): ?>
	$('#btn-delete').on('click', function () {
		if (!confirm('이 상담 내역을 삭제하시겠습니까? 녹음 파일도 함께 삭제됩니다.')) return;
		$.post('/Consultation/deleteProc', { uid: <?= $consultation['uid'] ?> }, function (res) {
			if (res.success) {
				location.href = '/Consultation/list';
			} else {
				alert(res.message || '삭제 실패');
			}
		}, 'json');
	});
	<?php endif; ?>
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
