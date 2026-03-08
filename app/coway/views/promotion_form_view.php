<?php login_check(); is_manager() ?: (header('Location:/Promotion/index') & exit()); ?>
<?php include VIEW_PATH . '/layouts/header.php'; ?>
<?php $is_edit = !empty($promo); ?>

<div class="wrapper">
  <?php include VIEW_PATH . '/layouts/side_menu.php'; ?>

  <div class="main-panel">
    <?php include VIEW_PATH . '/layouts/nav.php'; ?>

    <div class="container">
      <div class="page-inner pb-5">

        <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
          <h5 class="mb-0 fw-bold"><?= $is_edit ? '프로모션 수정' : '프로모션 등록' ?></h5>
          <a href="/Promotion/index" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> 목록
          </a>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card shadow-sm border-0">
              <div class="card-body p-4">
                <form method="POST" action="/Promotion/<?= $is_edit ? 'update' : 'store' ?>">
                  <?php if ($is_edit): ?><input type="hidden" name="uid" value="<?= $promo['uid'] ?>"><?php endif; ?>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">프로모션명 <span class="text-danger">*</span></label>
                    <input type="text" name="promo_name" class="form-control" required maxlength="200"
                      value="<?= htmlspecialchars($promo['promo_name'] ?? '') ?>" placeholder="예: 봄맞이 정수기 특가">
                  </div>

                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">시작일 <span class="text-danger">*</span></label>
                      <input type="date" name="start_date" class="form-control" required
                        value="<?= $promo['start_date'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">종료일 <span class="text-danger">*</span></label>
                      <input type="date" name="end_date" class="form-control" required
                        value="<?= $promo['end_date'] ?? date('Y-m-d') ?>">
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">적용 카테고리</label>
                    <input type="text" name="target_category" class="form-control" maxlength="200"
                      value="<?= htmlspecialchars($promo['target_category'] ?? '') ?>"
                      placeholder="예: 정수기, 공기청정기 (비워두면 전체 적용)">
                    <div class="form-text">비워두면 전 제품 적용</div>
                  </div>

                  <div class="card bg-light border-0 mb-3">
                    <div class="card-body">
                      <h6 class="fw-bold mb-3"><i class="fas fa-tag me-1 text-success"></i>구매자 할인 설정</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">적용 단위 <span class="text-danger">*</span></label>
                          <select name="apply_unit" class="form-select" id="apply_unit_select" onchange="toggle_min_items()">
                            <option value="per_item"  <?= ($promo['apply_unit'] ?? 'per_item') === 'per_item'  ? 'selected' : '' ?>>건당 적용 (상품 1개당 각각)</option>
                            <option value="per_order" <?= ($promo['apply_unit'] ?? '') === 'per_order' ? 'selected' : '' ?>>주문 전체 1회 (패키지 등)</option>
                          </select>
                          <div class="form-text">건당: 각 상품마다 적용 / 주문 전체: 선택 상품 합계에 1회 적용</div>
                        </div>
                        <div class="col-md-6" id="min_items_wrap" style="<?= ($promo['apply_unit'] ?? 'per_item') === 'per_order' ? '' : 'opacity:0.4;pointer-events:none;' ?>">
                          <label class="form-label fw-semibold">최소 상품 수</label>
                          <div class="input-group">
                            <input type="number" name="min_items" class="form-control" min="1" max="99"
                              value="<?= $promo['min_items'] ?? 1 ?>">
                            <span class="input-group-text">대 이상</span>
                          </div>
                          <div class="form-text">패키지 적용 시 최소 수량 (기본 1)</div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">할인 대상 <span class="text-danger">*</span></label>
                          <select name="discount_target" class="form-select">
                            <option value="rent_amount"  <?= ($promo['discount_target'] ?? 'rent_amount') === 'rent_amount'  ? 'selected' : '' ?>>월 렌탈료 할인</option>
                            <option value="setup_amount" <?= ($promo['discount_target'] ?? '') === 'setup_amount' ? 'selected' : '' ?>>등록비 할인</option>
                            <option value="free_months"  <?= ($promo['discount_target'] ?? '') === 'free_months'  ? 'selected' : '' ?>>N개월 무료 (할인 값 = 개월 수)</option>
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="card bg-light border-0 mb-3">
                    <div class="card-body">
                      <h6 class="fw-bold mb-3"><i class="fas fa-coins me-1 text-warning"></i>영업비 설정</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">기본 영업비 <span class="text-danger">*</span></label>
                          <div class="input-group">
                            <input type="number" name="base_fee" class="form-control" required min="0" step="1000"
                              value="<?= $promo['base_fee'] ?? 200000 ?>">
                            <span class="input-group-text">원</span>
                          </div>
                          <div class="form-text">기본 200,000원</div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">특별 영업비</label>
                          <div class="input-group">
                            <input type="number" name="special_fee" class="form-control" min="0" step="1000"
                              value="<?= $promo['special_fee'] ?? 0 ?>">
                            <span class="input-group-text">원</span>
                          </div>
                        </div>
                      </div>
                      <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>해지방어비</strong>: 기본비+특별비의 50%가 자동 적립 후 1년 뒤 지급됩니다.
                      </div>
                    </div>
                  </div>

                  <div class="card bg-light border-0 mb-3">
                    <div class="card-body">
                      <h6 class="fw-bold mb-3"><i class="fas fa-percent me-1 text-primary"></i>고객 할인 설정</h6>
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">할인 유형</label>
                          <select name="discount_type" class="form-select">
                            <option value="amount"  <?= ($promo['discount_type'] ?? 'amount') === 'amount' ? 'selected' : '' ?>>금액 할인</option>
                            <option value="percent" <?= ($promo['discount_type'] ?? '') === 'percent' ? 'selected' : '' ?>>% 할인</option>
                          </select>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label fw-semibold">할인 값</label>
                          <input type="number" name="discount_value" class="form-control" min="0"
                            value="<?= $promo['discount_value'] ?? 0 ?>" placeholder="0이면 할인 없음">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">프로모션 설명/조건</label>
                    <textarea name="description" class="form-control" rows="4" maxlength="1000"
                      placeholder="프로모션 상세 조건, 대상 제품, 특이사항 등"><?= htmlspecialchars($promo['description'] ?? '') ?></textarea>
                  </div>

                  <div class="d-flex gap-2 justify-content-end">
                    <a href="/Promotion/index" class="btn btn-outline-secondary">취소</a>
                    <button type="submit" class="btn btn-primary">
                      <i class="fas fa-save me-1"></i><?= $is_edit ? '저장' : '등록' ?>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <?php include VIEW_PATH . '/layouts/footer.php'; ?>
  </div>
</div>

<?php include VIEW_PATH . '/layouts/script.php'; ?>
<script>
function toggle_min_items() {
  var val = document.getElementById('apply_unit_select').value;
  var wrap = document.getElementById('min_items_wrap');
  wrap.style.opacity = (val === 'per_order') ? '1' : '0.4';
  wrap.style.pointerEvents = (val === 'per_order') ? 'auto' : 'none';
}
</script>
<?php include VIEW_PATH . '/layouts/tail.php'; ?>
