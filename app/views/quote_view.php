<?php include APP_PATH . '/views/layouts/head.php';?>
<?php include APP_PATH . '/views/layouts/header.php';?>

<?php
$ct_map    = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$ct_badge  = ['P' => 'bg-secondary', 'B' => 'bg-info', 'C' => 'bg-warning text-dark'];
$quote_no  = 'CW-' . date('Ymd') . '-' . str_pad($order['uid'], 4, '0', STR_PAD_LEFT);
$today     = date('Y년 m월 d일');
$cust_type = $ct_map[$order['customer_type']] ?? '-';
?>

<div class="wrapper">
    <?php include APP_PATH . '/views/layouts/side_menu.php';?>
    <div class="main-panel">
        <?php include APP_PATH . '/views/layouts/nav.php';?>
        <div class="container">
            <div class="page-inner pb-5">

                <!-- 상단 헤더 -->
                <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
                    <div class="d-flex align-items-center gap-2">
                        <a href="/Order/orderList" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> 목록
                        </a>
                        <h5 class="mb-0 fw-bold">견적서</h5>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> 인쇄
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="fas fa-envelope me-1"></i> 이메일 발송
                        </button>
                    </div>
                </div>

                <!-- 견적서 본문 -->
                <div class="card border-0 shadow-sm" id="quoteDoc">
                    <!-- 견적서 타이틀 -->
                    <div class="card-header text-white py-4" style="background: linear-gradient(135deg,#1e40af,#3b82f6); border-radius: 8px 8px 0 0;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="mb-1 fw-bold letter-spacing-2">견 적 서</h3>
                                <div class="opacity-75 small">QUOTATION</div>
                            </div>
                            <div class="text-end small opacity-90">
                                <div>견적번호 : <strong><?= htmlspecialchars($quote_no) ?></strong></div>
                                <div class="mt-1">견적일자 : <?= $today ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- 수신 / 공급자 -->
                        <div class="row g-0 border-bottom">
                            <div class="col-6 p-4 border-end">
                                <div class="text-muted small fw-bold mb-2" style="letter-spacing:2px;">수 신</div>
                                <h5 class="fw-bold mb-3">
                                    <?= htmlspecialchars($order['customer_name']) ?> 귀하
                                    <span class="badge <?= $ct_badge[$order['customer_type']] ?? 'bg-secondary' ?> ms-1" style="font-size:11px;"><?= $cust_type ?></span>
                                </h5>
                                <table class="table table-sm table-borderless mb-0 small text-muted">
                                    <tr>
                                        <td class="ps-0" style="width:70px;">연&nbsp;&nbsp;락&nbsp;&nbsp;처</td>
                                        <td class="text-dark fw-semibold"><?= htmlspecialchars($order['customer_phone']) ?></td>
                                    </tr>
                                    <?php if (!empty($order['c_email'])): ?>
                                    <tr>
                                        <td class="ps-0">이&nbsp;&nbsp;메&nbsp;&nbsp;일</td>
                                        <td class="text-dark"><?= htmlspecialchars($order['c_email']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($order['c_address'])): ?>
                                    <tr>
                                        <td class="ps-0" style="vertical-align:top;">주&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;소</td>
                                        <td class="text-dark"><?= htmlspecialchars($order['c_address']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-6 p-4 bg-light">
                                <div class="text-muted small fw-bold mb-2" style="letter-spacing:2px;">공 급 자</div>
                                <h5 class="fw-bold mb-3">코웨이(주)</h5>
                                <table class="table table-sm table-borderless mb-0 small text-muted">
                                    <tr>
                                        <td class="ps-0" style="width:70px;">담&nbsp;&nbsp;당&nbsp;&nbsp;자</td>
                                        <td class="text-dark fw-semibold"><?= htmlspecialchars($order['member_id']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="ps-0">유효기간</td>
                                        <td class="text-dark">견적일로부터 30일</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-0">등록일</td>
                                        <td class="text-muted"><?= date('Y-m-d', strtotime($order['register_date'])) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- 견적 내역 -->
                        <div class="p-4">
                            <div class="text-muted small fw-bold mb-3" style="letter-spacing:2px;">견 적 내 역</div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0" style="font-size:14px;">
                                    <thead style="background:#1e40af; color:#fff;">
                                        <tr>
                                            <th class="text-center" style="width:50px;">No</th>
                                            <th>제품명</th>
                                            <th class="text-center" style="width:80px;">유형</th>
                                            <th class="text-end" style="width:130px;">금액</th>
                                            <th class="text-end" style="width:100px;">설치비</th>
                                            <th class="text-center" style="width:170px;">렌탈 조건</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($items as $idx => $item): ?>
                                    <?php
                                        $is_rent   = $item['payment_type'] === 'rent';
                                        $pay_label = $is_rent ? '렌탈' : '일시불';
                                        $pay_badge = $is_rent ? 'bg-primary' : 'bg-success';
                                        $price     = $is_rent
                                            ? number_format($item['final_rent_price']) . '원<span class="text-muted">/월</span>'
                                            : number_format($item['normal_price']) . '원';
                                        $setup     = $item['final_setup_price'] > 0
                                            ? number_format($item['final_setup_price']) . '원'
                                            : '<span class="text-muted">-</span>';
                                        $condition = $is_rent
                                            ? "방문 {$item['visit_cycle']}개월 / 의무 {$item['duty_year']}년"
                                            : '<span class="text-muted">-</span>';
                                    ?>
                                    <tr class="<?= $idx % 2 === 0 ? '' : 'table-light' ?>">
                                        <td class="text-center text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($item['model_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($item['model_no']) ?><?= $item['model_color'] ? ' · ' . htmlspecialchars($item['model_color']) : '' ?></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $pay_badge ?> small"><?= $pay_label ?></span>
                                        </td>
                                        <td class="text-end fw-semibold"><?= $price ?></td>
                                        <td class="text-end"><?= $setup ?></td>
                                        <td class="text-center small text-muted"><?= $condition ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- 합계 -->
                            <div class="border-top border-2 mt-0 pt-3 text-end" style="border-color:#1e40af !important;">
                                <?php if ($total_rent > 0): ?>
                                <div class="mb-1">
                                    렌탈 월 납부액 합계 :
                                    <strong class="text-primary fs-5 ms-2"><?= number_format($total_rent) ?>원<span class="fs-6 text-muted">/월</span></strong>
                                </div>
                                <?php endif; ?>
                                <?php if ($total_buy > 0): ?>
                                <div class="mb-1">
                                    일시불 구매 합계 :
                                    <strong class="text-success fs-5 ms-2"><?= number_format($total_buy) ?>원</strong>
                                </div>
                                <?php endif; ?>
                                <?php if ($total_setup > 0): ?>
                                <div class="mb-1 text-muted small">
                                    설치비 합계 : <?= number_format($total_setup) ?>원
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- 특이사항 -->
                            <?php if (!empty($order['memo'])): ?>
                            <div class="mt-3 p-3 rounded small text-muted" style="background:#f8fafc;border-left:4px solid #cbd5e1;">
                                <strong class="text-dark">특이사항 :</strong>
                                <?= nl2br(htmlspecialchars($order['memo'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- 안내 문구 -->
                        <div class="p-3 bg-light border-top text-center text-muted small" style="border-radius:0 0 8px 8px;">
                            본 견적서는 Coway 영업관리시스템에서 발행되었습니다. &nbsp;|&nbsp; 유효기간 : 견적일로부터 30일
                        </div>
                    </div>
                </div>
                <!-- // 견적서 본문 -->

            </div>
        </div>
    </div>
</div>

<!-- ① 이메일 작성 모달 (1단계) -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-envelope me-2 text-primary"></i>견적서 이메일 발송</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="emailForm">
                    <input type="hidden" name="order_uid" value="<?= $order['uid'] ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">수신자 이름</label>
                        <input type="text" name="to_name" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($order['customer_name']) ?>" placeholder="고객명">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">수신 이메일 <span class="text-danger">*</span></label>
                        <input type="email" name="to_email" id="toEmailInput" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($order['quote_email'] ?? $order['c_email'] ?? '') ?>"
                            placeholder="example@email.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">발신자명</label>
                        <input type="text" name="sender_name" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($order['member_id']) ?>" placeholder="담당자명">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">추가 메시지 <span class="text-muted">(선택)</span></label>
                        <textarea name="extra_msg" class="form-control form-control-sm" rows="3"
                            placeholder="고객에게 전달할 추가 안내 문구를 입력하세요."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary btn-sm" id="goConfirmBtn">
                    <i class="fas fa-arrow-right me-1"></i> 다음 (확인)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ② 최종 확인 모달 (2단계) -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold"><i class="fas fa-paper-plane me-2 text-primary"></i>발송 확인</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="p-3 bg-light rounded mb-3 small">
                    <div class="text-muted mb-1">수신 이메일</div>
                    <div class="fw-bold text-primary fs-6" id="confirmEmail"></div>
                </div>
                <p class="small text-muted mb-0">위 주소로 <strong>PDF 견적서</strong>를 첨부하여 발송합니다.<br>이메일 주소를 다시 확인해 주세요.</p>
                <div id="emailResult" class="d-none alert py-2 small mt-3 mb-0"></div>
            </div>
            <div class="modal-footer border-0 pt-0 gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="backToEditBtn">
                    <i class="fas fa-arrow-left me-1"></i> 수정
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="sendEmailBtn">
                    <i class="fas fa-paper-plane me-1"></i> 발송
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 인쇄 스타일 -->
<style>
@media print {
    .wrapper .main-panel > nav,
    .wrapper .sidebar,
    .page-inner > .d-flex:first-child,
    #emailModal, #confirmModal { display: none !important; }
    .page-inner { padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>

<script>
// 1단계 → 2단계
document.getElementById('goConfirmBtn').addEventListener('click', function () {
    const email = document.getElementById('toEmailInput').value.trim();
    if (!email) {
        document.getElementById('toEmailInput').focus();
        return;
    }
    bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide();
    document.getElementById('confirmEmail').textContent = email;
    document.getElementById('emailResult').classList.add('d-none');
    document.getElementById('sendEmailBtn').disabled = false;
    document.getElementById('sendEmailBtn').innerHTML = '<i class="fas fa-paper-plane me-1"></i> 발송';
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
});

// 2단계 → 1단계 (수정)
document.getElementById('backToEditBtn').addEventListener('click', function () {
    bootstrap.Modal.getInstance(document.getElementById('confirmModal')).hide();
    new bootstrap.Modal(document.getElementById('emailModal')).show();
});

// 최종 발송
document.getElementById('sendEmailBtn').addEventListener('click', function () {
    const result = document.getElementById('emailResult');
    const btn    = this;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> 발송 중...';
    result.classList.add('d-none');

    const data = new FormData(document.getElementById('emailForm'));

    fetch('/Quote/sendEmail', { method: 'POST', body: data })
        .then(r => r.json())
        .then(res => {
            result.className = 'alert py-2 small mt-3 mb-0 ' + (res.success ? 'alert-success' : 'alert-danger');
            result.textContent = res.message;
            result.classList.remove('d-none');
            if (res.success) {
                btn.innerHTML = '<i class="fas fa-check me-1"></i> 발송 완료';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> 다시 발송';
            }
        })
        .catch(() => {
            result.className = 'alert alert-danger py-2 small mb-0';
            result.textContent = '네트워크 오류가 발생했습니다.';
            result.classList.remove('d-none');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> 발송';
        });
});
</script>

<?php include APP_PATH . '/views/layouts/tail.php';?>
