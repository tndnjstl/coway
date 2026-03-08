<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<!-- 페이지 헤더 -->
				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold"><i class="fas fa-microphone text-danger me-2"></i>상담 녹음</h5>
					<a href="/Consultation/list" class="btn btn-outline-secondary btn-sm">
						<i class="fas fa-arrow-left me-1"></i> 목록
					</a>
				</div>

				<!-- 고객 정보 선택 -->
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small"><i class="fas fa-user me-1 text-primary"></i> 상담 정보</span>
					</div>
					<div class="card-body py-3">
						<div class="row g-2">
							<div class="col-12 col-sm-5">
								<label class="form-label small fw-bold mb-1">고객 선택</label>
								<select id="customer_uid" class="form-select form-select-sm">
									<option value="">-- 고객 선택 (선택 사항) --</option>
									<?php foreach ($customers as $c): ?>
									<option value="<?= $c['uid'] ?>">
										<?= htmlspecialchars($c['customer_name']) ?>
										<?= $c['customer_phone'] ? ' (' . htmlspecialchars($c['customer_phone']) . ')' : '' ?>
									</option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="col-6 col-sm-3">
								<label class="form-label small fw-bold mb-1">상담 유형</label>
								<select id="consult_type" class="form-select form-select-sm">
									<option value="visit">방문</option>
									<option value="phone">전화</option>
									<option value="online">화상</option>
								</select>
							</div>
							<div class="col-6 col-sm-4">
								<label class="form-label small fw-bold mb-1">상담 일시</label>
								<input type="datetime-local" id="consult_date" class="form-control form-control-sm"
								       value="<?= date('Y-m-d\TH:i') ?>">
							</div>
						</div>
					</div>
				</div>

				<!-- STT 텍스트 영역 -->
				<div class="card border-0 shadow-sm mb-3">
					<div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
						<span class="fw-bold small"><i class="fas fa-align-left me-1 text-primary"></i> 실시간 음성 변환</span>
						<span class="small text-muted" id="stt-status">대기 중</span>
					</div>
					<div class="card-body p-0">
						<!-- 실시간 텍스트 표시 -->
						<div id="stt-live-area" style="min-height:180px;max-height:300px;overflow-y:auto;padding:16px;font-size:15px;line-height:1.7;background:#fafafa;border-bottom:1px solid #eee;">
							<span id="stt-final-text" style="color:#222;"></span>
							<span id="stt-interim-text" style="color:#aaa;font-style:italic;"></span>
						</div>
						<!-- 녹음 컨트롤 바 -->
						<div class="d-flex align-items-center gap-3 px-3 py-2" style="background:#fff;">
							<!-- 녹음 시작/중지 버튼 -->
							<button type="button" id="btn-record" class="btn btn-danger rounded-circle" style="width:50px;height:50px;font-size:18px;" title="녹음 시작">
								<i class="fas fa-microphone"></i>
							</button>
							<button type="button" id="btn-pause" class="btn btn-outline-secondary btn-sm d-none" title="일시정지">
								<i class="fas fa-pause"></i>
							</button>
							<button type="button" id="btn-stop" class="btn btn-outline-dark btn-sm d-none" title="녹음 완료">
								<i class="fas fa-stop me-1"></i> 완료
							</button>

							<!-- 타이머 -->
							<span class="fw-bold text-danger" id="stt-timer" style="font-size:20px;font-family:monospace;min-width:60px;">00:00</span>

							<!-- 상태 표시 -->
							<span id="rec-indicator" class="d-none">
								<span class="badge bg-danger">
									<i class="fas fa-circle me-1"></i> 녹음 중
								</span>
							</span>

							<!-- 경고: HTTPS 필요 -->
							<span id="https-warn" class="small text-warning d-none">
								<i class="fas fa-exclamation-triangle me-1"></i>HTTPS 환경에서만 녹음이 가능합니다.
							</span>
						</div>
					</div>
				</div>

				<!-- 저장 폼 (녹음 완료 후 표시) -->
				<div id="save-section" class="card border-0 shadow-sm d-none">
					<div class="card-header bg-white border-bottom py-2">
						<span class="fw-bold small"><i class="fas fa-save me-1 text-primary"></i> 상담 저장</span>
					</div>
					<div class="card-body py-3">
						<div class="row g-3">
							<div class="col-12">
								<label class="form-label small fw-bold mb-1">상담 제목 <span class="text-danger">*</span></label>
								<input type="text" id="save-title" class="form-control form-control-sm" placeholder="상담 제목을 입력하세요">
							</div>
							<div class="col-12">
								<label class="form-label small fw-bold mb-1">음성 변환 텍스트</label>
								<textarea id="save-stt-text" class="form-control form-control-sm" rows="5" style="resize:vertical;"></textarea>
							</div>
							<div class="col-12">
								<label class="form-label small fw-bold mb-1">상담 요약 메모</label>
								<textarea id="save-summary" class="form-control form-control-sm" rows="3" placeholder="핵심 내용을 요약해주세요"></textarea>
							</div>
							<div class="col-12">
								<div class="d-flex align-items-center gap-2">
									<audio id="preview-audio" controls class="d-none" style="height:36px;flex:1;"></audio>
									<span id="duration-label" class="small text-muted"></span>
								</div>
							</div>
							<div class="col-12 d-flex justify-content-end gap-2">
								<button type="button" id="btn-discard" class="btn btn-outline-secondary btn-sm">
									<i class="fas fa-trash me-1"></i> 버리기
								</button>
								<button type="button" id="btn-save" class="btn btn-primary btn-sm">
									<i class="fas fa-save me-1"></i> 저장
								</button>
							</div>
						</div>
					</div>
				</div>

				<!-- Chrome 미지원 안내 -->
				<div id="no-support-msg" class="alert alert-warning d-none" role="alert">
					<i class="fas fa-exclamation-triangle me-2"></i>
					이 기능은 <strong>Chrome 또는 Edge</strong> 브라우저에서 사용해주세요.<br>
					다른 브라우저에서는 음성 인식이 지원되지 않습니다.
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script src="/assets/js/stt-recorder.js"></script>
<script>
$(function () {

	// 브라우저 지원 확인
	if (!SttRecorder.isSupported()) {
		$('#no-support-msg').removeClass('d-none');
		$('#btn-record').prop('disabled', true);
		return;
	}

	// HTTPS 확인
	if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
		$('#https-warn').removeClass('d-none');
		$('#btn-record').prop('disabled', true);
		return;
	}

	let currentUid  = null;   // 자동저장 uid
	let autoSaveTimer = null; // 30초 자동저장 타이머
	let recordedBlob = null;
	let recordedDuration = 0;
	let isRecording = false;

	// 텍스트 업데이트 콜백
	SttRecorder.onTextUpdate(function (final, interim) {
		$('#stt-final-text').text(final);
		$('#stt-interim-text').text(interim);
		$('#save-stt-text').val(final);
	});

	// 오류 콜백
	SttRecorder.onError(function (msg) {
		alert('오류: ' + msg);
	});

	// 완료 콜백
	SttRecorder.onComplete(function (result) {
		recordedBlob = result.audioBlob;
		recordedDuration = result.duration;

		$('#btn-stop, #btn-pause').addClass('d-none');
		$('#btn-record').removeClass('d-none').html('<i class="fas fa-microphone"></i>').addClass('btn-danger').removeClass('btn-secondary');
		$('#rec-indicator').addClass('d-none');
		$('#stt-status').text('녹음 완료');
		$('#stt-timer').removeClass('text-danger').addClass('text-muted');

		// 저장 폼 표시
		$('#save-stt-text').val(result.text);
		$('#save-section').removeClass('d-none');
		$('#duration-label').text('녹음 시간: ' + formatDuration(result.duration));

		// 오디오 미리보기
		if (result.audioBlob) {
			const url = URL.createObjectURL(result.audioBlob);
			$('#preview-audio').attr('src', url).removeClass('d-none');
		}

		// 자동저장 타이머 해제
		clearInterval(autoSaveTimer);
		autoSaveTimer = null;

		// 최종 텍스트 자동저장
		autoSaveText(result.text);
	});

	// 페이지 이탈 경고
	window.addEventListener('beforeunload', function (e) {
		if (isRecording) {
			e.preventDefault();
			e.returnValue = '녹음이 진행 중입니다. 페이지를 나가면 녹음 내용이 손실될 수 있습니다.';
		}
	});

	// ── 버튼 이벤트 ──

	$('#btn-record').on('click', function () {
		SttRecorder.start();
		isRecording = true;

		$(this).addClass('d-none');
		$('#btn-stop').removeClass('d-none');
		$('#btn-pause').removeClass('d-none');
		$('#rec-indicator').removeClass('d-none');
		$('#stt-status').text('녹음 중...');
		$('#stt-timer').removeClass('text-muted').addClass('text-danger');
		$('#save-section').addClass('d-none');

		// 맥동 애니메이션
		$(this).css('animation', 'pulse 1.5s infinite');

		// 30초마다 자동저장
		autoSaveTimer = setInterval(function () {
			autoSaveText(SttRecorder.getText());
		}, 30000);
	});

	$('#btn-pause').on('click', function () {
		if ($(this).data('paused')) {
			SttRecorder.resume();
			$(this).html('<i class="fas fa-pause"></i>').data('paused', false);
			$('#stt-status').text('녹음 중...');
		} else {
			SttRecorder.pause();
			$(this).html('<i class="fas fa-play"></i>').data('paused', true);
			$('#stt-status').text('일시정지');
		}
	});

	$('#btn-stop').on('click', function () {
		isRecording = false;
		SttRecorder.stop();
	});

	$('#btn-save').on('click', function () {
		const title = $('#save-title').val().trim();
		if (!title) { alert('상담 제목을 입력해주세요.'); $('#save-title').focus(); return; }

		const $btn = $(this).prop('disabled', true).text('저장 중...');

		const fd = new FormData();
		fd.append('ajax', '1');
		fd.append('uid', currentUid || '');
		fd.append('customer_uid', $('#customer_uid').val());
		fd.append('consult_type', $('#consult_type').val());
		fd.append('consult_date', $('#consult_date').val().replace('T', ' '));
		fd.append('title', title);
		fd.append('stt_text', $('#save-stt-text').val());
		fd.append('stt_summary', $('#save-summary').val());
		fd.append('audio_duration', recordedDuration);
		if (recordedBlob) fd.append('audio_file', recordedBlob, 'recording.webm');

		$.ajax({
			url: '/Consultation/addProc',
			method: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			success: function (res) {
				if (res.success) {
					location.href = '/Consultation/detail?uid=' + res.uid;
				} else {
					alert(res.message || '저장 실패');
					$btn.prop('disabled', false).text('저장');
				}
			},
			error: function () {
				alert('서버 오류가 발생했습니다.');
				$btn.prop('disabled', false).text('저장');
			},
		});
	});

	$('#btn-discard').on('click', function () {
		if (!confirm('녹음 내용을 버리시겠습니까?')) return;
		location.reload();
	});

	// 30초마다 텍스트 자동저장
	function autoSaveText(text) {
		$.post('/Consultation/updateTextProc', {
			uid: currentUid || 0,
			stt_text: text,
			customer_uid: $('#customer_uid').val(),
			title: '녹음 중 임시저장',
			consult_type: $('#consult_type').val(),
		}, function (res) {
			if (res.success && !currentUid) currentUid = res.uid;
		}, 'json');
	}

	function formatDuration(sec) {
		const m = Math.floor(sec / 60);
		const s = sec % 60;
		return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
	}
});
</script>
<style>
@keyframes pulse {
	0%   { box-shadow: 0 0 0 0 rgba(220,53,69,.7); }
	70%  { box-shadow: 0 0 0 12px rgba(220,53,69,0); }
	100% { box-shadow: 0 0 0 0 rgba(220,53,69,0); }
}
</style>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
