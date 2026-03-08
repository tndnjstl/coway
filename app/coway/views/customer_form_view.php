<?php include APP_PATH . '/views/layouts/head.php'; ?>
<?php include APP_PATH . '/views/layouts/header.php'; ?>

<?php
$is_edit = isset($customer);
$action  = $is_edit ? '/Customer/editProc' : '/Customer/addProc';
$title   = $is_edit ? '고객 수정' : '고객 등록';
?>

<div class="wrapper">
	<?php include APP_PATH . '/views/layouts/side_menu.php'; ?>
	<div class="main-panel">
		<?php include APP_PATH . '/views/layouts/nav.php'; ?>
		<div class="container">
			<div class="page-inner pb-5">

				<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
					<h5 class="mb-0 fw-bold"><?= $title ?></h5>
					<a href="/Customer/list" class="btn btn-outline-secondary btn-sm">
						<i class="fas fa-arrow-left me-1"></i> 목록
					</a>
				</div>

				<div class="card border-0 shadow-sm" style="max-width:600px;">
					<div class="card-body p-4">
						<form method="POST" action="<?= $action ?>">
							<?php if ($is_edit): ?>
							<input type="hidden" name="uid" value="<?= $customer['uid'] ?>">
							<?php endif; ?>

							<div class="mb-3">
								<label class="form-label fw-bold small">고객 구분 <span class="text-danger">*</span></label>
								<select name="customer_type" class="form-select form-select-sm" required>
									<option value="P" <?= ($customer['customer_type'] ?? '') === 'P' ? 'selected' : '' ?>>개인</option>
									<option value="B" <?= ($customer['customer_type'] ?? '') === 'B' ? 'selected' : '' ?>>개인사업자</option>
									<option value="C" <?= ($customer['customer_type'] ?? '') === 'C' ? 'selected' : '' ?>>법인사업자</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold small">고객명 <span class="text-danger">*</span></label>
								<input type="text" name="customer_name" class="form-control form-control-sm" required
								       value="<?= htmlspecialchars($customer['customer_name'] ?? '') ?>">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold small">전화번호 <span class="text-danger">*</span></label>
								<input type="text" name="customer_phone" class="form-control form-control-sm" required
								       value="<?= htmlspecialchars($customer['customer_phone'] ?? '') ?>">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold small">이메일</label>
								<input type="email" name="customer_email" class="form-control form-control-sm"
								       value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold small">주소</label>
								<input type="text" name="address" class="form-control form-control-sm"
								       value="<?= htmlspecialchars($customer['address'] ?? '') ?>">
							</div>
							<div class="mb-4">
								<label class="form-label fw-bold small">메모</label>
								<textarea name="memo" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($customer['memo'] ?? '') ?></textarea>
							</div>
							<button type="submit" class="btn btn-primary btn-sm w-100">
								<i class="fas fa-save me-1"></i> <?= $is_edit ? '수정 완료' : '등록 완료' ?>
							</button>
						</form>
					</div>
				</div>

			</div>
		</div>
		<?php include APP_PATH . '/views/layouts/footer.php'; ?>
	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php'; ?>
<?php include APP_PATH . '/views/layouts/tail.php'; ?>
