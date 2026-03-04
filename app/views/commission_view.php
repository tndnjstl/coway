<?php login_check(); ?>
<?php include VIEW_PATH . '/layouts/header.php'; ?>
<?php include VIEW_PATH . '/layouts/nav.php'; ?>
<?php include VIEW_PATH . '/layouts/side_menu.php'; ?>

<?php
$pos_label = ['staff'=>'영업사원','team_leader'=>'팀장','director'=>'국장','branch_manager'=>'지점장'];
$my_position = get_position();
$my_pos_label = $pos_label[$my_position] ?? '영업사원';
?>

<div class="main-panel">
  <div class="content-wrapper">
    <div class="page-inner">

      <!-- 헤더 -->
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="fw-bold mb-0">수수료 대시보드</h4>
          <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active"><?= $year ?>년 <?= $month ?>월 | <?= $my_pos_label ?></li>
          </ol></nav>
        </div>
        <!-- 월 이동 -->
        <form class="d-flex gap-2 align-items-center" method="GET" action="/Commission/index">
          <select name="year" class="form-select form-select-sm" style="width:90px;">
            <?php for ($y = date('Y'); $y >= date('Y')-2; $y--): ?>
            <option value="<?= $y ?>" <?= $y===$year?'selected':'' ?>><?= $y ?>년</option>
            <?php endfor; ?>
          </select>
          <select name="month" class="form-select form-select-sm" style="width:80px;">
            <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m===$month?'selected':'' ?>><?= $m ?>월</option>
            <?php endfor; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-primary">조회</button>
        </form>
      </div>

      <!-- 요약 카드 -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
              <div class="small text-muted mb-1">계약 건수</div>
              <div class="fs-4 fw-bold text-primary"><?= count($items) ?>건</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
              <div class="small text-muted mb-1">총 영업비</div>
              <div class="fs-5 fw-bold"><?= number_format($total_gross) ?>원</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
              <div class="small text-muted mb-1">해지방어비 적립</div>
              <div class="fs-5 fw-bold text-danger">-<?= number_format($total_hold) ?>원</div>
              <div class="small text-muted">1년 후 지급</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center bg-success bg-opacity-10">
            <div class="card-body py-3">
              <div class="small text-muted mb-1">이달 예상 수령액</div>
              <div class="fs-4 fw-bold text-success">
                <?= number_format($total_net + $total_team_fee) ?>원
              </div>
              <?php if ($total_team_fee > 0): ?>
              <div class="small text-muted">팀비 <?= number_format($total_team_fee) ?>원 포함</div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- 팀원별 실적 (팀장 이상) -->
      <?php if (!empty($member_summary)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-users me-2 text-primary"></i>
          <?= match($my_position) {
            'team_leader'    => '팀원별 실적',
            'director'       => '팀별 영업자 실적',
            'branch_manager' => '지점 전체 실적',
            default          => '실적 현황',
          } ?>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>영업자</th>
                  <th>팀명</th>
                  <th>직급</th>
                  <th class="text-end">계약건수</th>
                  <th class="text-end">총 영업비</th>
                  <th class="text-end">해지방어비</th>
                  <th class="text-end">즉시 수령액</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($member_summary as $ms):
                  $ms_hold = round($ms['gross'] * 0.5);
                  $ms_net  = $ms['gross'] - $ms_hold;
                  $ms_pos  = $pos_label[$ms['member_position']] ?? '-';
                ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($ms['member_name'] ?: $ms['member_id']) ?></td>
                  <td><?= htmlspecialchars($ms['member_team'] ?? '-') ?></td>
                  <td><span class="badge bg-secondary"><?= $ms_pos ?></span></td>
                  <td class="text-end"><?= number_format($ms['cnt']) ?>건</td>
                  <td class="text-end"><?= number_format($ms['gross']) ?>원</td>
                  <td class="text-end text-danger">-<?= number_format($ms_hold) ?>원</td>
                  <td class="text-end fw-bold text-success"><?= number_format($ms_net) ?>원</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- 계약별 상세 -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>이달 계약 상세
        </div>
        <?php if (empty($items)): ?>
        <div class="card-body text-center py-5 text-muted">
          <i class="fas fa-inbox fa-2x mb-2 d-block"></i>이달 계약 완료 건이 없습니다.
        </div>
        <?php else: ?>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>계약일</th>
                  <th>고객</th>
                  <th>제품</th>
                  <?php if ($my_position !== 'staff'): ?>
                  <th>영업자</th>
                  <?php endif; ?>
                  <th>프로모션</th>
                  <th class="text-end">기본비</th>
                  <th class="text-end">특별비</th>
                  <th class="text-end text-danger">해지방어비</th>
                  <th class="text-end text-success">수령액</th>
                  <?php if ($my_position === 'team_leader'): ?>
                  <th class="text-end text-info">팀비</th>
                  <?php endif; ?>
                  <th>해방 예정일</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                  <td class="small"><?= date('m/d', strtotime($item['register_date'])) ?></td>
                  <td><?= htmlspecialchars($item['customer_name']) ?></td>
                  <td class="small"><?= htmlspecialchars(mb_substr($item['model_name'],0,20)) ?></td>
                  <?php if ($my_position !== 'staff'): ?>
                  <td><?= htmlspecialchars($item['member_name'] ?: $item['member_id']) ?></td>
                  <?php endif; ?>
                  <td class="small"><?= $item['promo_name'] ? '<span class="badge bg-info text-dark">'.htmlspecialchars($item['promo_name']).'</span>' : '<span class="text-muted">기본</span>' ?></td>
                  <td class="text-end"><?= number_format($item['base_fee']) ?></td>
                  <td class="text-end"><?= $item['special_fee']>0?number_format($item['special_fee']):'<span class="text-muted">-</span>' ?></td>
                  <td class="text-end text-danger">-<?= number_format($item['calc_hold']) ?></td>
                  <td class="text-end fw-bold text-success"><?= number_format($item['calc_net']) ?></td>
                  <?php if ($my_position === 'team_leader'): ?>
                  <td class="text-end text-info"><?= $item['calc_team_fee']>0?number_format($item['calc_team_fee']):'<span class="text-muted">-</span>' ?></td>
                  <?php endif; ?>
                  <td class="small text-muted"><?= $item['hold_release'] ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- 해지방어비 지급 예정 -->
      <?php if (!empty($hold_pending)): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-piggy-bank me-2 text-warning"></i>해지방어비 지급 예정 (향후 1년)
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>지급 예정일</th>
                  <th>고객</th>
                  <th>제품</th>
                  <?php if ($my_position !== 'staff'): ?>
                  <th>영업자</th>
                  <?php endif; ?>
                  <th class="text-end">총 영업비</th>
                  <th class="text-end text-warning fw-bold">지급 예정액</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($hold_pending as $hp):
                  $d_day = (int)ceil((strtotime($hp['release_date']) - time()) / 86400);
                ?>
                <tr>
                  <td>
                    <?= $hp['release_date'] ?>
                    <?php if ($d_day <= 30): ?>
                    <span class="badge bg-danger ms-1">D-<?= $d_day ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($hp['customer_name']) ?></td>
                  <td class="small"><?= htmlspecialchars(mb_substr($hp['model_name'],0,20)) ?></td>
                  <?php if ($my_position !== 'staff'): ?>
                  <td><?= htmlspecialchars($hp['member_name'] ?: $hp['member_id']) ?></td>
                  <?php endif; ?>
                  <td class="text-end"><?= number_format($hp['gross']) ?>원</td>
                  <td class="text-end fw-bold text-warning"><?= number_format($hp['hold']) ?>원</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
  <?php include VIEW_PATH . '/layouts/footer.php'; ?>
</div>

<?php include VIEW_PATH . '/layouts/tail.php'; ?>
