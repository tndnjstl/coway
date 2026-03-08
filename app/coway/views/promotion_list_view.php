<?php login_check(); ?>
<?php include VIEW_PATH . '/layouts/header.php'; ?>

<div class="wrapper">
  <?php include VIEW_PATH . '/layouts/side_menu.php'; ?>

  <div class="main-panel">
    <?php include VIEW_PATH . '/layouts/nav.php'; ?>

    <div class="container">
      <div class="page-inner pb-5">

        <div class="d-flex align-items-center justify-content-between mb-3 mt-2 flex-wrap gap-2">
          <h5 class="mb-0 fw-bold">프로모션 관리</h5>
          <?php if (is_manager()): ?>
          <a href="/Promotion/add" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>프로모션 등록</a>
          <?php endif; ?>
        </div>

        <ul class="nav nav-tabs mb-3">
          <?php foreach (['active' => '진행중', 'all' => '전체', 'expired' => '만료'] as $key => $label): ?>
          <li class="nav-item">
            <a class="nav-link <?= ($filter ?? 'active') === $key ? 'active' : '' ?>" href="/Promotion/index?filter=<?= $key ?>">
              <?= $label ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>

        <?php if (empty($promos)): ?>
        <div class="alert alert-light text-center py-5"><i class="fas fa-bullhorn fa-2x mb-2 text-muted d-block"></i>프로모션이 없습니다.</div>
        <?php else: ?>
        <div class="row g-3">
          <?php foreach ($promos as $p):
            $badge = match($p['status_key']) {
              'active'    => ['success', '진행중'],
              'scheduled' => ['info', '예정'],
              'expired'   => ['secondary', '만료'],
              default     => ['warning', '비활성'],
            };
            $days_left = (int)ceil((strtotime($p['end_date']) - time()) / 86400);
          ?>
          <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm <?= $p['status_key'] === 'expired' ? 'opacity-75' : '' ?>">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge bg-<?= $badge[0] ?>"><?= $badge[1] ?></span>
                  <?php if (is_manager()): ?>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li><a class="dropdown-item" href="/Promotion/edit?id=<?= $p['uid'] ?>"><i class="fas fa-edit me-2"></i>수정</a></li>
                      <li><button class="dropdown-item btn-toggle-promo" data-id="<?= $p['uid'] ?>" data-active="<?= $p['is_active'] ?>">
                        <i class="fas fa-<?= $p['is_active'] ? 'ban' : 'check' ?> me-2"></i><?= $p['is_active'] ? '비활성화' : '활성화' ?>
                      </button></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><button class="dropdown-item text-danger btn-delete-promo" data-id="<?= $p['uid'] ?>"><i class="fas fa-trash me-2"></i>삭제</button></li>
                    </ul>
                  </div>
                  <?php endif; ?>
                </div>

                <h6 class="fw-bold mb-1"><?= htmlspecialchars($p['promo_name']) ?></h6>
                <?php if ($p['target_category']): ?>
                <div class="small text-muted mb-2"><i class="fas fa-tag me-1"></i><?= htmlspecialchars($p['target_category']) ?></div>
                <?php endif; ?>

                <div class="row g-2 my-2">
                  <div class="col-6">
                    <div class="bg-light rounded p-2 text-center">
                      <div class="small text-muted">기본 영업비</div>
                      <div class="fw-bold text-primary"><?= number_format($p['base_fee']) ?>원</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="bg-light rounded p-2 text-center">
                      <div class="small text-muted">특별 영업비</div>
                      <div class="fw-bold text-success"><?= number_format($p['special_fee']) ?>원</div>
                    </div>
                  </div>
                  <?php if ($p['discount_value'] > 0): ?>
                  <div class="col-12">
                    <div class="bg-warning bg-opacity-10 rounded p-2 text-center">
                      <div class="small text-muted">고객 할인</div>
                      <div class="fw-bold text-warning">
                        <?= $p['discount_type'] === 'percent' ? $p['discount_value'] . '%' : number_format($p['discount_value']) . '원' ?> 할인
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>
                </div>

                <?php if ($p['description']): ?>
                <p class="small text-muted mb-2" style="white-space:pre-line;"><?= htmlspecialchars(mb_substr($p['description'], 0, 80)) ?></p>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                  <span class="small text-muted"><i class="fas fa-calendar me-1"></i><?= $p['start_date'] ?> ~ <?= $p['end_date'] ?></span>
                  <?php if ($p['status_key'] === 'active' && $days_left <= 7): ?>
                  <span class="badge bg-danger">D-<?= $days_left ?></span>
                  <?php elseif ($p['status_key'] === 'active'): ?>
                  <span class="badge bg-outline-secondary text-muted">D-<?= $days_left ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>

    <?php include VIEW_PATH . '/layouts/footer.php'; ?>
  </div>
</div>

<?php include VIEW_PATH . '/layouts/script.php'; ?>
<script>
document.querySelectorAll('.btn-toggle-promo').forEach((btn) => {
  btn.addEventListener('click', async () => {
    if (!confirm('활성/비활성 상태를 변경하시겠습니까?')) return;
    const res = await fetch('/Promotion/toggle', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'uid=' + btn.dataset.id
    });
    const d = await res.json();
    if (d.success) location.reload();
  });
});

document.querySelectorAll('.btn-delete-promo').forEach((btn) => {
  btn.addEventListener('click', async () => {
    if (!confirm('프로모션을 삭제하시겠습니까?')) return;
    const res = await fetch('/Promotion/delete', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'uid=' + btn.dataset.id
    });
    const d = await res.json();
    if (d.success) location.reload();
  });
});
</script>
<?php include VIEW_PATH . '/layouts/tail.php'; ?>
