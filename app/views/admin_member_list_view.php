<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold">사용자 관리</h5>
					<button class="btn btn-primary btn-sm" id="btn-add-member">
						<i class="fas fa-plus me-1"></i> 신규 등록
					</button>
				</div>

				<div class="card border-0 shadow-sm">
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm table-hover mb-0">
								<thead style="background:#1e40af;color:#fff;">
									<tr>
										<th class="ps-3" style="width:36px;">#</th>
										<th>아이디</th>
										<th>이름</th>
										<th class="text-center">권한(role)</th>
										<th class="text-center">등록일</th>
										<th class="text-center">관리</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($members as $i => $m): ?>
									<tr id="member-row-<?= $m['uid'] ?>">
										<td class="ps-3 text-muted small"><?= $i + 1 ?></td>
										<td class="fw-bold small"><?= htmlspecialchars($m['member_id']) ?></td>
										<td class="small"><?= htmlspecialchars($m['member_name']) ?></td>
										<td class="text-center">
											<?php
											$rc = ['staff' => 'secondary', 'manager' => 'primary', 'admin' => 'danger'];
											$rl = ['staff' => '일반', 'manager' => '관리자', 'admin' => '시스템관리자'];
											?>
											<span class="badge bg-<?= $rc[$m['role']] ?? 'secondary' ?> small">
												<?= $rl[$m['role']] ?? $m['role'] ?>
											</span>
										</td>
										<td class="text-center small text-muted"><?= substr($m['register_date'] ?? '', 0, 10) ?></td>
										<td class="text-center">
											<button class="btn btn-outline-secondary btn-sm py-0 px-2 btn-edit-member"
											        data-uid="<?= $m['uid'] ?>"
											        data-name="<?= htmlspecialchars($m['member_name']) ?>"
											        data-role="<?= htmlspecialchars($m['role']) ?>">
												<i class="fas fa-edit"></i>
											</button>
											<button class="btn btn-outline-danger btn-sm py-0 px-2 ms-1 btn-delete-member"
											        data-uid="<?= $m['uid'] ?>"
											        data-name="<?= htmlspecialchars($m['member_name']) ?>">
												<i class="fas fa-trash"></i>
											</button>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<!-- 등록/수정 모달 -->
<div class="modal fade" id="memberModal" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h6 class="modal-title fw-bold" id="memberModalTitle">사용자 등록</h6>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="m-uid" value="">
				<div class="mb-3" id="m-id-wrap">
					<label class="form-label fw-bold small">아이디 <span class="text-danger">*</span></label>
					<input type="text" id="m-id" class="form-control form-control-sm">
				</div>
				<div class="mb-3">
					<label class="form-label fw-bold small">이름 <span class="text-danger">*</span></label>
					<input type="text" id="m-name" class="form-control form-control-sm">
				</div>
				<div class="mb-3">
					<label class="form-label fw-bold small">비밀번호 <span id="pw-hint" class="text-muted">(비워두면 변경 없음)</span></label>
					<input type="password" id="m-pw" class="form-control form-control-sm" autocomplete="new-password">
				</div>
				<div class="mb-0">
					<label class="form-label fw-bold small">권한</label>
					<select id="m-role" class="form-select form-select-sm">
						<option value="staff">일반 (staff)</option>
						<option value="manager">관리자 (manager)</option>
						<option value="admin">시스템관리자 (admin)</option>
					</select>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
				<button class="btn btn-primary btn-sm" id="btn-save-member">
					<i class="fas fa-save me-1"></i> 저장
				</button>
			</div>
		</div>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<script>
var role_badge = {
	staff:   '<span class="badge bg-secondary small">일반</span>',
	manager: '<span class="badge bg-primary small">관리자</span>',
	admin:   '<span class="badge bg-danger small">시스템관리자</span>'
};

// 신규 등록 모달
$('#btn-add-member').on('click', function() {
	$('#memberModalTitle').text('사용자 등록');
	$('#m-uid').val('');
	$('#m-id').val('').prop('disabled', false);
	$('#m-id-wrap').show();
	$('#m-name').val('');
	$('#m-pw').val('');
	$('#m-role').val('staff');
	$('#pw-hint').text('');
	$('#memberModal').modal('show');
});

// 수정 모달
$(document).on('click', '.btn-edit-member', function() {
	var uid  = $(this).data('uid');
	var name = $(this).data('name');
	var role = $(this).data('role');
	$('#memberModalTitle').text('사용자 수정');
	$('#m-uid').val(uid);
	$('#m-id-wrap').hide();
	$('#m-name').val(name);
	$('#m-pw').val('');
	$('#m-role').val(role);
	$('#pw-hint').text('(비워두면 변경 없음)');
	$('#memberModal').modal('show');
});

// 저장
$('#btn-save-member').on('click', function() {
	var uid  = $('#m-uid').val();
	var name = $.trim($('#m-name').val());
	var pw   = $('#m-pw').val();
	var role = $('#m-role').val();

	if (!name) { alert('이름을 입력해주세요.'); return; }

	var payload = { uid: uid ? parseInt(uid) : 0, member_name: name, password: pw, role: role };
	if (!uid) payload.member_id = $.trim($('#m-id').val());

	var url = uid ? '/Admin/memberEditProc' : '/Admin/memberAddProc';

	$.ajax({
		url: url, method: 'POST', contentType: 'application/json', dataType: 'json',
		data: JSON.stringify(payload),
		success: function(res) {
			if (res.success) {
				$('#memberModal').modal('hide');
				location.reload();
			} else {
				alert('오류: ' + res.message);
			}
		},
		error: function() { alert('서버 오류가 발생했습니다.'); }
	});
});

// 삭제
$(document).on('click', '.btn-delete-member', function() {
	var uid  = $(this).data('uid');
	var name = $(this).data('name');
	if (!confirm('[' + name + '] 계정을 삭제하시겠습니까?')) return;

	$.ajax({
		url: '/Admin/memberDeleteProc', method: 'POST', contentType: 'application/json', dataType: 'json',
		data: JSON.stringify({ uid: uid }),
		success: function(res) {
			if (res.success) {
				$('#member-row-' + uid).fadeOut(300, function() { $(this).remove(); });
			} else {
				alert('오류: ' + res.message);
			}
		},
		error: function() { alert('서버 오류가 발생했습니다.'); }
	});
});
</script>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
