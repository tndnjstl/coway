#!/usr/bin/env php
<?php
/**
 * 가망고객 주간 보고 이메일 자동 발송 스크립트
 *
 * [닷홈 cron 설정]
 * - 제어판 → 서비스관리 → cron 설정
 * - 실행주기: 매주 월요일 오전 9시
 * - 명령: php /home/tndnjstl/public_html/cron_report.php
 * - 또는 분/시/일/월/요일: 0 9 * * 1
 */

// CLI에서만 실행 허용
if (php_sapi_name() !== 'cli') {
	http_response_code(403);
	exit('CLI only');
}

define('BASE_PATH', __DIR__);
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Asia/Seoul');

require_once HELPER_PATH . '/common_db.php';
require_once HELPER_PATH . '/email_config.php';

$now = date('Y-m-d H:i:s');
echo "[{$now}] 가망고객 보고 이메일 발송 시작\n";

// 가망고객 목록 조회
$sql = "
	SELECT
		o.uid,
		o.customer_type,
		o.customer_name,
		o.customer_phone,
		o.member_id,
		o.register_date,
		COUNT(oi.uid)     AS item_count,
		SUM(oi.total_pay) AS total_pay
	FROM tndnjstl_order AS o
	LEFT JOIN tndnjstl_order_item AS oi ON oi.order_uid = o.uid
	WHERE o.status = 'prospect'
	GROUP BY o.uid
	ORDER BY o.register_date DESC
";

$result    = $db_local->query($sql);
$prospects = [];
while ($row = $result->fetch_assoc()) {
	$prospects[] = $row;
}

$ct_map      = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
$report_date = date('Y년 m월 d일');
$week_start  = date('Y-m-d', strtotime('monday this week'));
$total_count = count($prospects);
$total_pay   = array_sum(array_column($prospects, 'total_pay'));

$rows_html = '';
foreach ($prospects as $i => $p) {
	$is_new  = substr($p['register_date'], 0, 10) >= $week_start;
	$new_tag = $is_new ? ' <span style="background:#ef4444;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;margin-left:4px;">NEW</span>' : '';
	$ct      = $ct_map[$p['customer_type']] ?? $p['customer_type'];
	$bg      = ($i % 2 === 0) ? '#ffffff' : '#f8f9fa';
	$rows_html .= "
		<tr style='background:{$bg};'>
			<td style='padding:8px 10px;text-align:center;color:#666;font-size:13px;'>" . ($i + 1) . "</td>
			<td style='padding:8px 10px;font-weight:bold;'>" . htmlspecialchars($p['customer_name']) . $new_tag . "</td>
			<td style='padding:8px 10px;color:#666;'>" . $ct . "</td>
			<td style='padding:8px 10px;'>" . htmlspecialchars($p['customer_phone']) . "</td>
			<td style='padding:8px 10px;text-align:center;'>" . (int)$p['item_count'] . "개</td>
			<td style='padding:8px 10px;text-align:right;color:#2563eb;font-weight:bold;'>" . number_format((int)$p['total_pay']) . "원</td>
			<td style='padding:8px 10px;text-align:center;color:#666;'>" . htmlspecialchars($p['member_id']) . "</td>
			<td style='padding:8px 10px;text-align:center;color:#999;font-size:12px;'>" . substr($p['register_date'], 0, 10) . "</td>
		</tr>
	";
}

$body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f2f5;font-family:\"Apple SD Gothic Neo\",\"Malgun Gothic\",sans-serif;'>
<div style='max-width:800px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
  <div style='background:#1e40af;padding:30px 30px 20px;'>
    <div style='color:#93c5fd;font-size:13px;margin-bottom:4px;'>COWAY 영업관리시스템 · 주간 자동 보고</div>
    <h1 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>가망고객 현황 보고</h1>
    <div style='color:#bfdbfe;font-size:13px;margin-top:8px;'>{$report_date} 기준</div>
  </div>
  <div style='padding:16px 30px;background:#eff6ff;border-bottom:1px solid #dbeafe;display:flex;gap:0;'>
    <div style='flex:1;text-align:center;'>
      <div style='color:#1e40af;font-size:26px;font-weight:800;'>{$total_count}</div>
      <div style='color:#64748b;font-size:13px;margin-top:2px;'>전체 가망고객</div>
    </div>
    <div style='flex:1;text-align:center;border-left:1px solid #dbeafe;'>
      <div style='color:#1e40af;font-size:26px;font-weight:800;'>" . number_format($total_pay) . "원</div>
      <div style='color:#64748b;font-size:13px;margin-top:2px;'>총 예상 금액</div>
    </div>
  </div>
  <div style='padding:20px 30px 30px;'>
    <table style='width:100%;border-collapse:collapse;font-size:13px;'>
      <thead>
        <tr style='background:#1e40af;color:#fff;'>
          <th style='padding:10px;text-align:center;font-weight:600;'>No</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>고객명</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>구분</th>
          <th style='padding:10px;text-align:left;font-weight:600;'>전화번호</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>상품수</th>
          <th style='padding:10px;text-align:right;font-weight:600;'>예상금액</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>담당자</th>
          <th style='padding:10px;text-align:center;font-weight:600;'>등록일</th>
        </tr>
      </thead>
      <tbody>{$rows_html}</tbody>
    </table>
    " . ($total_count === 0 ? "<div style='text-align:center;padding:30px;color:#999;'>가망고객이 없습니다.</div>" : "") . "
  </div>
  <div style='padding:15px 30px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px;'>
    본 메일은 Coway 영업관리시스템에서 매주 자동 발송됩니다.
  </div>
</div>
</body>
</html>
";

$to      = REPORT_RECIPIENT_NAME . ' <' . REPORT_RECIPIENT_EMAIL . '>';
$subject = '=?UTF-8?B?' . base64_encode('[Coway] 가망고객 현황 보고 (' . $report_date . ')') . '?=';
$headers = implode("\r\n", [
	'MIME-Version: 1.0',
	'Content-Type: text/html; charset=UTF-8',
	'From: ' . REPORT_SENDER_NAME . ' <' . REPORT_SENDER_EMAIL . '>',
	'X-Mailer: PHP/' . phpversion(),
]);

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
	echo "[{$now}] 발송 완료 → {$to} (가망고객 {$total_count}건)\n";
} else {
	echo "[{$now}] 발송 실패! mail() 함수 오류\n";
	exit(1);
}
