<!DOCTYPE html>
<html lang="ko">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>가망고객 보고서 - Coway 영업관리</title>
	<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/css/fonts.min.css">
	<style>
		body { font-family: "Apple SD Gothic Neo", "Malgun Gothic", sans-serif; background: #f0f2f5; }

		/* 화면용 */
		.report-toolbar {
			position: sticky; top: 0; z-index: 100;
			background: #1e40af; padding: 12px 20px;
			display: flex; align-items: center; justify-content: space-between;
			gap: 10px;
		}
		.report-page {
			max-width: 860px; margin: 30px auto 60px;
			background: #fff; border-radius: 8px;
			box-shadow: 0 2px 16px rgba(0,0,0,0.12);
			overflow: hidden;
		}
		.report-header {
			background: #1e40af; color: #fff;
			padding: 30px 36px 22px;
		}
		.report-summary {
			display: flex; gap: 0;
			background: #eff6ff; border-bottom: 2px solid #dbeafe;
		}
		.summary-item {
			flex: 1; text-align: center; padding: 16px 10px;
			border-right: 1px solid #dbeafe;
		}
		.summary-item:last-child { border-right: none; }
		.summary-num { font-size: 26px; font-weight: 800; color: #1e40af; line-height: 1.2; }
		.summary-label { font-size: 12px; color: #64748b; margin-top: 2px; }
		.report-body { padding: 24px 36px 36px; }
		.report-table { width: 100%; border-collapse: collapse; font-size: 13px; }
		.report-table thead tr { background: #1e40af; color: #fff; }
		.report-table thead th { padding: 10px 12px; font-weight: 600; white-space: nowrap; }
		.report-table tbody tr:nth-child(even) { background: #f8fafc; }
		.report-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
		.report-table tbody tr:hover { background: #eff6ff; }
		.badge-new { background: #ef4444; color: #fff; font-size: 10px; padding: 1px 5px; border-radius: 3px; margin-left: 4px; vertical-align: middle; }
		.report-footer-note { text-align: center; color: #94a3b8; font-size: 11px; margin-top: 24px; }
		.consult-list { margin-top: 4px; padding-left: 0; list-style: none; }
		.consult-list li { font-size: 11px; color: #475569; padding: 2px 0; border-top: 1px dashed #e2e8f0; margin-top: 2px; }
		.consult-list li:first-child { border-top: none; margin-top: 0; }
		.consult-date { color: #94a3b8; margin-right: 4px; }

		/* ─── 기간 선택 툴바 ─── */
		.period-bar {
			background: #fff; border-bottom: 1px solid #e2e8f0;
			padding: 10px 20px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
		}

		/* ─── 인쇄 전용 ─── */
		@media print {
			@page { size: A4; margin: 14mm 12mm; }
			body { background: #fff !important; font-size: 12px; }
			.report-toolbar { display: none !important; }
			.period-bar { display: none !important; }
			.report-page { margin: 0; box-shadow: none; border-radius: 0; }
			.report-header { padding: 16px 20px 12px; }
			.report-body { padding: 12px 20px 20px; }
			.report-summary { border-bottom: 1.5pt solid #1e40af; }
			.summary-item { padding: 10px 6px; }
			.summary-num { font-size: 18px; }
			.report-table { font-size: 11px; }
			.report-table thead th { padding: 7px 8px; }
			.report-table tbody td { padding: 6px 8px; }
			.report-table tbody tr:hover { background: transparent; }
			a { color: inherit !important; text-decoration: none !important; }
		}
	</style>
</head>
<body>

<?php
$ct_map          = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$report_date     = date('Y년 m월 d일');
$total_count     = count($prospects);
$total_pay       = array_sum(array_column($prospects, 'total_pay'));
$period_label    = $period_label ?? '이번 주';
$date_from_fmt   = isset($date_from) ? date('Y년 m월 d일', strtotime($date_from)) : '';
$date_to_fmt     = isset($date_to)   ? date('Y년 m월 d일', strtotime($date_to))   : '';
?>

<!-- 툴바 (화면에서만 보임) -->
<div class="report-toolbar">
	<div class="d-flex align-items-center gap-3">
		<a href="/Order/orderList" class="btn btn-sm btn-outline-light">
			<i class="fas fa-arrow-left me-1"></i> 돌아가기
		</a>
		<span class="text-white fw-bold">가망고객 보고서</span>
	</div>
	<div class="d-flex gap-2">
		<button class="btn btn-sm btn-light" onclick="window.print()">
			<i class="fas fa-print me-1"></i> PDF 저장 / 인쇄
		</button>
		<button class="btn btn-sm btn-warning fw-bold" id="btn-send-email">
			<i class="fas fa-paper-plane me-1"></i> 이메일 발송
			<span class="ms-1 small text-muted"><?= htmlspecialchars(REPORT_RECIPIENT_EMAIL) ?></span>
		</button>
	</div>
</div>

<!-- 기간 선택 바 -->
<div class="period-bar">
	<span class="text-muted small fw-bold me-2">상담내역 기간:</span>
	<a href="/Order/prospectReport?period=week"
	   class="btn btn-sm <?= ($period ?? 'week') === 'week' ? 'btn-primary' : 'btn-outline-secondary' ?>">이번 주</a>
	<a href="/Order/prospectReport?period=month"
	   class="btn btn-sm <?= ($period ?? '') === 'month' ? 'btn-primary' : 'btn-outline-secondary' ?>">이번 달</a>
	<a href="/Order/prospectReport?period=all"
	   class="btn btn-sm <?= ($period ?? '') === 'all' ? 'btn-primary' : 'btn-outline-secondary' ?>">전체</a>

	<form method="get" action="/Order/prospectReport" class="d-flex align-items-center gap-1 ms-2">
		<input type="hidden" name="period" value="custom">
		<input type="date" name="date_from" class="form-control form-control-sm" style="width:140px;"
		       value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
		<span class="text-muted small">~</span>
		<input type="date" name="date_to" class="form-control form-control-sm" style="width:140px;"
		       value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
		<button type="submit" class="btn btn-sm btn-outline-primary">조회</button>
	</form>

	<span class="ms-auto text-muted small">
		상담내역 조회: <strong><?= htmlspecialchars($period_label) ?></strong>
		<?php if (($period ?? '') === 'custom'): ?>
		(<?= $date_from_fmt ?> ~ <?= $date_to_fmt ?>)
		<?php endif; ?>
	</span>
</div>

<!-- 보고서 본문 -->
<div class="report-page">

	<!-- 헤더 -->
	<div class="report-header">
		<div style="color:#93c5fd;font-size:12px;margin-bottom:4px;">COWAY 영업관리시스템</div>
		<h1 style="color:#fff;font-size:20px;font-weight:800;margin:0 0 6px;">가망고객 현황 보고</h1>
		<div style="color:#bfdbfe;font-size:13px;">
			<?= $report_date ?> 기준
			&nbsp;·&nbsp;
			상담내역: <strong style="color:#fff;"><?= htmlspecialchars($period_label) ?></strong>
			<?php if (($period ?? '') === 'custom'): ?>
			(<?= $date_from_fmt ?> ~ <?= $date_to_fmt ?>)
			<?php endif; ?>
			&nbsp;·&nbsp;
			이번 주 신규 <strong style="color:#fff;"><?= $new_count ?>건</strong>
		</div>
	</div>

	<!-- 요약 -->
	<div class="report-summary">
		<div class="summary-item">
			<div class="summary-num"><?= $total_count ?></div>
			<div class="summary-label">전체 가망고객</div>
		</div>
		<div class="summary-item">
			<div class="summary-num"><?= $new_count ?></div>
			<div class="summary-label">이번 주 신규</div>
		</div>
		<div class="summary-item">
			<div class="summary-num"><?= number_format($total_pay) ?>원</div>
			<div class="summary-label">총 예상 금액</div>
		</div>
		<?php if (!empty($by_member)): ?>
		<div class="summary-item">
			<div class="summary-num"><?= count($by_member) ?></div>
			<div class="summary-label">담당 영업자</div>
		</div>
		<?php endif; ?>
	</div>

	<!-- 가망고객 테이블 -->
	<div class="report-body">
		<div class="fw-bold mb-3" style="font-size:14px;color:#1e40af;">
			<i class="fas fa-users me-1"></i> 가망고객 목록
		</div>

		<?php if (empty($prospects)): ?>
		<div class="text-center py-5 text-muted">
			<i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i>
			가망고객이 없습니다.
		</div>
		<?php else: ?>
		<table class="report-table">
			<thead>
				<tr>
					<th style="width:36px;text-align:center;">No</th>
					<th>고객명</th>
					<th>구분</th>
					<th>전화번호</th>
					<th style="text-align:center;">상품수</th>
					<th style="text-align:right;">예상금액</th>
					<th style="text-align:center;">담당자</th>
					<th style="text-align:center;">등록일</th>
					<th>상담내역 (<?= htmlspecialchars($period_label) ?>)</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($prospects as $i => $p):
					$is_new = substr($p['register_date'], 0, 10) >= $week_start;
					$ct     = $ct_map[$p['customer_type']] ?? $p['customer_type'];
				?>
				<tr>
					<td style="text-align:center;color:#999;"><?= $i + 1 ?></td>
					<td>
						<span class="fw-bold"><?= htmlspecialchars($p['customer_name']) ?></span>
						<?php if ($is_new): ?><span class="badge-new">NEW</span><?php endif; ?>
					</td>
					<td style="color:#666;"><?= $ct ?></td>
					<td><?= htmlspecialchars($p['customer_phone']) ?></td>
					<td style="text-align:center;"><?= (int)$p['item_count'] ?>개</td>
					<td style="text-align:right;color:#1d4ed8;font-weight:700;"><?= number_format((int)$p['total_pay']) ?>원</td>
					<td style="text-align:center;color:#666;"><?= htmlspecialchars($p['member_id']) ?></td>
					<td style="text-align:center;color:#999;"><?= substr($p['register_date'], 0, 10) ?></td>
					<td>
						<?php if (empty($p['consultations'])): ?>
						<span class="text-muted" style="font-size:11px;">-</span>
						<?php else: ?>
						<ul class="consult-list">
							<?php foreach ($p['consultations'] as $c): ?>
							<li>
								<span class="consult-date"><?= substr($c['consult_date'], 0, 10) ?></span>
								<span class="text-muted" style="font-size:10px;">[<?= htmlspecialchars($c['member_id']) ?>]</span>
								<?= htmlspecialchars($c['content']) ?>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<!-- 담당자별 요약 -->
		<?php if (!empty($by_member)): ?>
		<div class="mt-4">
			<div class="fw-bold mb-2" style="font-size:13px;color:#475569;">담당자별 현황</div>
			<table class="report-table" style="max-width:400px;">
				<thead>
					<tr>
						<th>담당자</th>
						<th style="text-align:center;">건수</th>
						<th style="text-align:right;">예상금액 합계</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($by_member as $mid => $ms): ?>
					<tr>
						<td><?= htmlspecialchars($mid) ?></td>
						<td style="text-align:center;"><?= $ms['count'] ?>건</td>
						<td style="text-align:right;color:#1d4ed8;font-weight:700;"><?= number_format($ms['total_pay']) ?>원</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>

		<div class="report-footer-note mt-4">
			본 보고서는 <?= $report_date ?> Coway 영업관리시스템에서 생성되었습니다.
		</div>
	</div>
</div>

<script src="/assets/js/core/jquery-3.7.1.min.js"></script>
<script src="/assets/js/core/bootstrap.min.js"></script>
<script>
var current_period    = '<?= htmlspecialchars($period ?? 'week') ?>';
var current_date_from = '<?= htmlspecialchars($date_from ?? '') ?>';
var current_date_to   = '<?= htmlspecialchars($date_to   ?? '') ?>';

$('#btn-send-email').on('click', function() {
	if (!confirm('국장님께 이메일을 발송하시겠습니까?')) return;

	var $btn = $(this);
	$btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> 발송 중...');

	$.ajax({
		url: '/Order/sendProspectReport',
		method: 'POST',
		contentType: 'application/json',
		dataType: 'json',
		data: JSON.stringify({
			period:    current_period,
			date_from: current_date_from,
			date_to:   current_date_to
		}),
		success: function(res) {
			if (res.status === 'success') {
				$btn.removeClass('btn-warning').addClass('btn-success')
					.html('<i class="fas fa-check me-1"></i> 발송 완료 (' + res.recipient + ')');
			} else {
				alert('발송 실패: ' + (res.message || '오류가 발생했습니다.'));
				$btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> 이메일 발송');
			}
		},
		error: function() {
			alert('서버 오류가 발생했습니다.');
			$btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> 이메일 발송');
		}
	});
});
</script>
</body>
</html>
