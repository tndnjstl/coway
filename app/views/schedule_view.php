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
					<h5 class="mb-0 fw-bold">일정 관리</h5>
					<div class="d-flex gap-2 align-items-center">
						<!-- 범례 -->
						<span class="badge" style="background:#1e40af;">방문</span>
						<span class="badge" style="background:#f97316;">AS</span>
						<span class="badge" style="background:#16a34a;">설치</span>
						<span class="badge" style="background:#7c3aed;">상담</span>
						<button class="btn btn-primary btn-sm ms-2" id="btn-add-schedule">
							<i class="fas fa-plus me-1"></i> 일정 등록
						</button>
					</div>
				</div>

				<!-- 달력 -->
				<div class="card border-0 shadow-sm">
					<div class="card-body p-3">
						<div id="calendar"></div>
					</div>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<!-- 일정 등록/수정 모달 -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold" id="scheduleModalTitle">일정 등록</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="sch-uid" value="">
				<div class="mb-3">
					<label class="form-label fw-bold small">일정 유형 <span class="text-danger">*</span></label>
					<select id="sch-type" class="form-select form-select-sm">
						<option value="visit">방문</option>
						<option value="as">AS</option>
						<option value="install">설치</option>
						<option value="consult">상담</option>
					</select>
				</div>
				<div class="mb-3">
					<label class="form-label fw-bold small">제목 <span class="text-danger">*</span></label>
					<input type="text" id="sch-title" class="form-control form-control-sm" placeholder="일정 제목 입력">
				</div>
				<div class="row g-2 mb-3">
					<div class="col-7">
						<label class="form-label fw-bold small">날짜 <span class="text-danger">*</span></label>
						<input type="date" id="sch-date" class="form-control form-control-sm">
					</div>
					<div class="col-5">
						<label class="form-label fw-bold small">시간</label>
						<input type="time" id="sch-time" class="form-control form-control-sm">
					</div>
				</div>
				<div class="mb-3" id="sch-status-wrap" style="display:none;">
					<label class="form-label fw-bold small">상태</label>
					<select id="sch-status" class="form-select form-select-sm">
						<option value="pending">예정</option>
						<option value="done">완료</option>
						<option value="cancel">취소</option>
					</select>
				</div>
				<div class="mb-0">
					<label class="form-label fw-bold small">메모</label>
					<textarea id="sch-memo" class="form-control form-control-sm" rows="2" placeholder="메모 (선택)"></textarea>
				</div>
			</div>
			<div class="modal-footer py-2 d-flex justify-content-between">
				<button class="btn btn-outline-danger btn-sm" id="btn-delete-schedule" style="display:none;">
					<i class="fas fa-trash me-1"></i> 삭제
				</button>
				<div class="d-flex gap-2 ms-auto">
					<button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
					<button class="btn btn-primary btn-sm" id="btn-save-schedule">
						<i class="fas fa-save me-1"></i> 저장
					</button>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/ko.min.js"></script>
<script>
var current_member_id = '<?= htmlspecialchars($_SESSION['member_id'] ?? '') ?>';
var is_manager = <?= is_manager() ? 'true' : 'false' ?>;

$(function() {

	// ── FullCalendar 초기화 ──
	var calendarEl = document.getElementById('calendar');
	var calendar = new FullCalendar.Calendar(calendarEl, {
		locale: 'ko',
		initialView: 'dayGridMonth',
		headerToolbar: {
			left:   'prev,next today',
			center: 'title',
			right:  'dayGridMonth,timeGridWeek,listWeek'
		},
		height: 'auto',
		events: {
			url: '/Schedule/getEvents',
			method: 'GET'
		},
		eventClick: function(info) {
			open_edit_modal(info.event);
		},
		dateClick: function(info) {
			open_add_modal(info.dateStr);
		}
	});
	calendar.render();

	// ── 신규 등록 ──
	$('#btn-add-schedule').on('click', function() {
		open_add_modal(new Date().toISOString().slice(0, 10));
	});

	function open_add_modal(date) {
		$('#scheduleModalTitle').text('일정 등록');
		$('#sch-uid').val('');
		$('#sch-type').val('visit');
		$('#sch-title').val('');
		$('#sch-date').val(date);
		$('#sch-time').val('');
		$('#sch-memo').val('');
		$('#sch-status').val('pending');
		$('#sch-status-wrap').hide();
		$('#btn-delete-schedule').hide();
		$('#scheduleModal').modal('show');
	}

	function open_edit_modal(event) {
		var p = event.extendedProps;
		$('#scheduleModalTitle').text('일정 수정');
		$('#sch-uid').val(event.id);
		$('#sch-type').val(p.schedule_type);
		$('#sch-title').val(event.title);
		$('#sch-date').val(event.startStr.slice(0, 10));
		$('#sch-time').val(p.schedule_time || '');
		$('#sch-memo').val(p.memo || '');
		$('#sch-status').val(p.status);
		$('#sch-status-wrap').show();
		$('#btn-delete-schedule').show();
		$('#scheduleModal').modal('show');
	}

	// ── 저장 ──
	$('#btn-save-schedule').on('click', function() {
		var uid   = $('#sch-uid').val();
		var title = $.trim($('#sch-title').val());
		var date  = $('#sch-date').val();

		if (!title || !date) { alert('제목과 날짜를 입력해주세요.'); return; }

		var payload = {
			uid:           uid ? parseInt(uid) : 0,
			schedule_type: $('#sch-type').val(),
			title:         title,
			schedule_date: date,
			schedule_time: $('#sch-time').val(),
			memo:          $('#sch-memo').val(),
			status:        $('#sch-status').val(),
			customer_uid:  0
		};

		var url = uid ? '/Schedule/editProc' : '/Schedule/addProc';

		$.ajax({
			url: url,
			method: 'POST',
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify(payload),
			success: function(res) {
				if (res.success) {
					$('#scheduleModal').modal('hide');
					calendar.refetchEvents();
				} else {
					alert('오류: ' + res.message);
				}
			},
			error: function() { alert('서버 오류가 발생했습니다.'); }
		});
	});

	// ── 삭제 ──
	$('#btn-delete-schedule').on('click', function() {
		var uid = $('#sch-uid').val();
		if (!uid) return;
		if (!confirm('이 일정을 삭제하시겠습니까?')) return;

		$.ajax({
			url: '/Schedule/deleteProc',
			method: 'POST',
			contentType: 'application/json',
			dataType: 'json',
			data: JSON.stringify({ uid: parseInt(uid) }),
			success: function(res) {
				if (res.success) {
					$('#scheduleModal').modal('hide');
					calendar.refetchEvents();
				} else {
					alert('오류: ' + res.message);
				}
			},
			error: function() { alert('서버 오류가 발생했습니다.'); }
		});
	});

});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
