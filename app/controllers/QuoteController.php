<?php
require_once APP_PATH . '/lib/fpdf/fpdf.php';

class QuoteController
{
    private function require_login(): void { login_check(); }

    // 견적서 조회
    public function view(): void
    {
        $this->require_login();
        global $db_local;

        $order_uid = (int)($_GET['id'] ?? 0);
        if ($order_uid <= 0) { http_response_code(400); exit('잘못된 요청입니다.'); }

        $order = $db_local->query("
            SELECT o.*, c.customer_email AS c_email, c.address AS c_address
            FROM tndnjstl_order AS o
            LEFT JOIN tndnjstl_customer AS c ON c.uid = o.customer_uid
            WHERE o.uid = {$order_uid} LIMIT 1
        ")->fetch_assoc();
        if (!$order) { http_response_code(404); exit('주문을 찾을 수 없습니다.'); }

        $result_items = $db_local->query(
            "SELECT * FROM tndnjstl_order_item WHERE order_uid = {$order_uid} ORDER BY uid ASC"
        );
        $items = [];
        while ($row = $result_items->fetch_assoc()) $items[] = $row;

        $total_rent  = array_sum(array_column(array_filter($items, fn($i) => $i['payment_type'] === 'rent'), 'final_rent_price'));
        $total_buy   = array_sum(array_column(array_filter($items, fn($i) => $i['payment_type'] === 'buy'),  'normal_price'));
        $total_setup = array_sum(array_column($items, 'final_setup_price'));

        include VIEW_PATH . '/quote_view.php';
    }

    // 이메일 발송 (PDF 첨부)
    public function sendEmail(): void
    {
        $this->require_login();
        global $db_local;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

        $order_uid   = (int)($_POST['order_uid']  ?? 0);
        $to_name     = trim($_POST['to_name']      ?? '');
        $to_email    = trim($_POST['to_email']      ?? '');
        $sender_name = trim($_POST['sender_name']   ?? 'Coway 영업관리');
        $extra_msg   = trim($_POST['extra_msg']     ?? '');

        if ($order_uid <= 0 || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '수신 이메일을 확인해 주세요.']);
            exit;
        }

        $order = $db_local->query("
            SELECT o.*, c.address AS c_address
            FROM tndnjstl_order AS o
            LEFT JOIN tndnjstl_customer AS c ON c.uid = o.customer_uid
            WHERE o.uid = {$order_uid} LIMIT 1
        ")->fetch_assoc();

        $result_items = $db_local->query(
            "SELECT * FROM tndnjstl_order_item WHERE order_uid = {$order_uid} ORDER BY uid ASC"
        );
        $items = [];
        while ($row = $result_items->fetch_assoc()) $items[] = $row;

        $total_rent  = array_sum(array_column(array_filter($items, fn($i) => $i['payment_type'] === 'rent'), 'final_rent_price'));
        $total_buy   = array_sum(array_column(array_filter($items, fn($i) => $i['payment_type'] === 'buy'),  'normal_price'));
        $total_setup = array_sum(array_column($items, 'final_setup_price'));

        // PDF 생성
        $pdf_content = $this->build_pdf($order, $items, $total_rent, $total_buy, $total_setup, $sender_name);
        $quote_no    = 'CW-' . date('Ymd') . '-' . str_pad($order_uid, 4, '0', STR_PAD_LEFT);
        $pdf_name    = "견적서_{$quote_no}.pdf";

        // 이메일 본문 (HTML)
        $body = $this->build_email_body($order, $items, $total_rent, $total_buy, $total_setup, $extra_msg, $sender_name, $quote_no);

        // MIME multipart 메일 발송
        $boundary = '----=_Part_' . md5(uniqid());
        $to       = $to_name ? "{$to_name} <{$to_email}>" : $to_email;
        $subject  = '=?UTF-8?B?' . base64_encode("[Coway] {$order['customer_name']}고객님 견적서 ({$quote_no})") . '?=';
        $headers  = implode("\r\n", [
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"{$boundary}\"",
            'From: ' . $sender_name . ' <' . REPORT_SENDER_EMAIL . '>',
            'X-Mailer: PHP/' . phpversion(),
        ]);

        $message  = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($body)) . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: application/pdf; name=\"{$pdf_name}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$pdf_name}\"\r\n\r\n";
        $message .= chunk_split(base64_encode($pdf_content)) . "\r\n";
        $message .= "--{$boundary}--";

        $sent = mail($to, $subject, $message, $headers);
        echo json_encode([
            'success' => $sent,
            'message' => $sent ? '견적서가 발송되었습니다.' : '발송에 실패했습니다. 서버 메일 설정을 확인해 주세요.',
        ]);
        exit;
    }

    private function build_pdf(array $order, array $items, int $tr, int $tb, int $ts, string $sender): string
    {
        $ct_map    = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
        $quote_no  = 'CW-' . date('Ymd') . '-' . str_pad($order['uid'], 4, '0', STR_PAD_LEFT);
        $today     = date('Y년 m월 d일');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // 유니코드 한글 대신 이미지/폰트 없이 영문+숫자만 사용하는 방식으로 처리
        // (닷홈 공유호스팅엔 별도 폰트 없으므로 핵심 내용을 영문 필드로)

        // ── 헤더 배경 ──
        $pdf->SetFillColor(30, 64, 175);
        $pdf->Rect(0, 0, 210, 35, 'F');

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 22);
        $pdf->SetXY(15, 8);
        $pdf->Cell(100, 10, 'QUOTATION', 0, 0, 'L');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(110, 8);
        $pdf->Cell(85, 6, 'Quote No : ' . $quote_no, 0, 2, 'R');
        $pdf->SetX(110);
        $pdf->Cell(85, 6, 'Date : ' . date('Y-m-d'), 0, 0, 'R');

        // ── 고객 / 공급자 ──
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(15, 40, 85, 45, 'FD');
        $pdf->Rect(110, 40, 85, 45, 'FD');

        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->SetXY(18, 43); $pdf->Cell(0, 5, 'BILL TO');
        $pdf->SetXY(113, 43); $pdf->Cell(0, 5, 'FROM');

        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY(18, 50);
        $pdf->Cell(79, 6, iconv('UTF-8', 'cp1252//TRANSLIT', $order['customer_name']) . ' (' . ($ct_map[$order['customer_type']] ?? '') . ')');

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(18, 57);
        $pdf->Cell(79, 5, 'Tel: ' . $order['customer_phone']);
        if (!empty($order['c_address'])) {
            $pdf->SetXY(18, 63);
            $pdf->MultiCell(79, 5, 'Addr: ' . mb_substr($order['c_address'], 0, 40));
        }

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetXY(113, 50); $pdf->Cell(79, 6, 'Coway Co., Ltd.');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(113, 57); $pdf->Cell(79, 5, 'Manager: ' . $sender);
        $pdf->SetXY(113, 63); $pdf->Cell(79, 5, 'Valid: 30 days from date');

        // ── 상품 테이블 헤더 ──
        $y = 92;
        $pdf->SetFillColor(30, 64, 175);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetXY(15, $y);
        $pdf->Cell(8,  7, 'No',      1, 0, 'C', true);
        $pdf->Cell(65, 7, 'Product', 1, 0, 'C', true);
        $pdf->Cell(18, 7, 'Type',    1, 0, 'C', true);
        $pdf->Cell(32, 7, 'Price',   1, 0, 'C', true);
        $pdf->Cell(22, 7, 'Setup',   1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Condition', 1, 1, 'C', true);

        // ── 상품 행 ──
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(30, 41, 59);
        foreach ($items as $i => $item) {
            $bg = ($i % 2 === 0);
            $pdf->SetFillColor($bg ? 255 : 248, $bg ? 255 : 250, $bg ? 255 : 252);
            $is_rent  = $item['payment_type'] === 'rent';
            $price    = $is_rent
                ? number_format($item['final_rent_price']) . '/mo'
                : number_format($item['normal_price']);
            $setup    = $item['final_setup_price'] > 0 ? number_format($item['final_setup_price']) : '-';
            $cond     = $is_rent ? "V:{$item['visit_cycle']}mo D:{$item['duty_year']}yr" : '-';
            $name     = mb_substr($item['model_name'], 0, 30);

            $pdf->SetX(15);
            $pdf->Cell(8,  7, $i + 1, 1, 0, 'C', $bg);
            $pdf->Cell(65, 7, $name,  1, 0, 'L', $bg);
            $pdf->Cell(18, 7, $is_rent ? 'Rental' : 'Buy', 1, 0, 'C', $bg);
            $pdf->Cell(32, 7, $price, 1, 0, 'R', $bg);
            $pdf->Cell(22, 7, $setup, 1, 0, 'R', $bg);
            $pdf->Cell(35, 7, $cond,  1, 1, 'C', $bg);
        }

        // ── 합계 ──
        $pdf->Ln(4);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetDrawColor(30, 64, 175);
        $pdf->SetLineWidth(0.5);

        if ($tr > 0) {
            $pdf->SetXY(110, $pdf->GetY());
            $pdf->Cell(55, 8, 'Monthly Rental Total', 0, 0, 'R');
            $pdf->SetTextColor(29, 78, 216);
            $pdf->Cell(25, 8, number_format($tr) . '/mo', 0, 1, 'R');
            $pdf->SetTextColor(30, 41, 59);
        }
        if ($tb > 0) {
            $pdf->SetXY(110, $pdf->GetY());
            $pdf->Cell(55, 8, 'Purchase Total', 0, 0, 'R');
            $pdf->SetTextColor(22, 101, 52);
            $pdf->Cell(25, 8, number_format($tb), 0, 1, 'R');
            $pdf->SetTextColor(30, 41, 59);
        }
        if ($ts > 0) {
            $pdf->SetXY(110, $pdf->GetY());
            $pdf->Cell(55, 8, 'Setup Fee Total', 0, 0, 'R');
            $pdf->Cell(25, 8, number_format($ts), 0, 1, 'R');
        }

        // ── 메모 ──
        if (!empty($order['memo'])) {
            $pdf->Ln(4);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetFillColor(254, 252, 232);
            $pdf->SetXY(15, $pdf->GetY());
            $pdf->MultiCell(180, 5, 'Note: ' . mb_substr($order['memo'], 0, 100), 1, 'L', true);
        }

        // ── 푸터 ──
        $pdf->SetY(-20);
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(0, 5, 'This quotation is valid for 30 days from the date of issue. | Coway Sales Management System', 0, 0, 'C');

        return $pdf->Output('S');
    }

    private function build_email_body(array $order, array $items, int $tr, int $tb, int $ts, string $extra, string $sender, string $quote_no): string
    {
        $ct_map = ['P' => '개인', 'B' => '개인사업자', 'C' => '법인사업자'];
        $today  = date('Y년 m월 d일');
        $rows   = '';

        foreach ($items as $i => $item) {
            $is_rent = $item['payment_type'] === 'rent';
            $price   = $is_rent ? number_format($item['final_rent_price']) . '원/월' : number_format($item['normal_price']) . '원';
            $setup   = $item['final_setup_price'] > 0 ? number_format($item['final_setup_price']) . '원' : '-';
            $cond    = $is_rent ? "방문 {$item['visit_cycle']}개월 / 의무 {$item['duty_year']}년" : '-';
            $bg      = ($i % 2 === 0) ? '#fff' : '#f8fafc';
            $rows   .= "<tr style='background:{$bg};'>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:13px;'>" . ($i + 1) . "</td>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;'>
                <div style='font-weight:600;color:#1e293b;'>" . htmlspecialchars($item['model_name']) . "</div>
                <div style='color:#94a3b8;font-size:11px;'>" . htmlspecialchars($item['model_no']) . " " . htmlspecialchars($item['model_color']) . "</div>
              </td>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:center;'>
                <span style='background:" . ($is_rent ? '#dbeafe' : '#dcfce7') . ";color:" . ($is_rent ? '#1d4ed8' : '#166534') . ";padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;'>" . ($is_rent ? '렌탈' : '일시불') . "</span>
              </td>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-weight:600;font-size:13px;'>{$price}</td>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:right;font-size:12px;color:#64748b;'>{$setup}</td>
              <td style='padding:9px 12px;border-bottom:1px solid #e2e8f0;text-align:center;font-size:11px;color:#64748b;'>{$cond}</td>
            </tr>";
        }

        $summary = '';
        if ($tr > 0) $summary .= "<div style='margin-bottom:5px;'>렌탈 월 납부액 합계 : <strong style='color:#1d4ed8;font-size:15px;'>" . number_format($tr) . "원/월</strong></div>";
        if ($tb > 0) $summary .= "<div style='margin-bottom:5px;'>일시불 구매 합계 : <strong style='color:#166534;font-size:15px;'>" . number_format($tb) . "원</strong></div>";
        if ($ts > 0) $summary .= "<div style='color:#92400e;font-size:13px;'>설치비 합계 : " . number_format($ts) . "원</div>";

        $extra_block = $extra
            ? "<div style='margin:16px 24px 0;padding:12px 16px;background:#fefce8;border-left:4px solid #facc15;border-radius:4px;font-size:13px;color:#713f12;line-height:1.6;'>" . nl2br(htmlspecialchars($extra)) . "</div>"
            : '';
        $memo_block = !empty($order['memo'])
            ? "<div style='margin:0 24px 16px;padding:10px 14px;background:#f8fafc;border-radius:6px;font-size:12px;color:#64748b;'><strong>특이사항 :</strong> " . nl2br(htmlspecialchars($order['memo'])) . "</div>"
            : '';

        return "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='margin:0;padding:20px;background:#f1f5f9;font-family:\"Apple SD Gothic Neo\",\"Malgun Gothic\",sans-serif;'>
<div style='max-width:700px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);'>
  <div style='background:linear-gradient(135deg,#1e40af,#3b82f6);padding:28px 30px;color:#fff;'>
    <div style='font-size:21px;font-weight:700;letter-spacing:2px;'>견 적 서</div>
    <div style='margin-top:6px;font-size:12px;opacity:.8;'>견적번호 : <strong>{$quote_no}</strong> &nbsp;|&nbsp; {$today}</div>
    <div style='margin-top:4px;font-size:12px;opacity:.8;'>※ PDF 견적서가 첨부되어 있습니다.</div>
  </div>
  <div style='display:flex;border-bottom:1px solid #e2e8f0;'>
    <div style='flex:1;padding:18px 24px;border-right:1px solid #e2e8f0;'>
      <div style='font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:1px;margin-bottom:8px;'>수 신</div>
      <div style='font-size:16px;font-weight:700;color:#1e293b;'>" . htmlspecialchars($order['customer_name']) . " 귀하</div>
      <div style='font-size:12px;color:#64748b;margin-top:4px;'>" . htmlspecialchars($order['customer_phone']) . " &nbsp;|&nbsp; " . ($ct_map[$order['customer_type']] ?? '') . "</div>
    </div>
    <div style='flex:1;padding:18px 24px;background:#f8fafc;'>
      <div style='font-size:10px;font-weight:700;color:#94a3b8;letter-spacing:1px;margin-bottom:8px;'>공 급 자</div>
      <div style='font-size:16px;font-weight:700;color:#1e293b;'>코웨이(주)</div>
      <div style='font-size:12px;color:#64748b;margin-top:4px;'>담당 : " . htmlspecialchars($sender) . " &nbsp;|&nbsp; 유효 30일</div>
    </div>
  </div>
  <div style='padding:20px 24px 0;'>
    <table style='width:100%;border-collapse:collapse;'>
      <thead><tr style='background:#1e40af;color:#fff;'>
        <th style='padding:9px 12px;text-align:center;font-size:12px;width:36px;'>No</th>
        <th style='padding:9px 12px;text-align:left;font-size:12px;'>제품명</th>
        <th style='padding:9px 12px;text-align:center;font-size:12px;width:60px;'>유형</th>
        <th style='padding:9px 12px;text-align:right;font-size:12px;width:110px;'>금액</th>
        <th style='padding:9px 12px;text-align:right;font-size:12px;width:80px;'>설치비</th>
        <th style='padding:9px 12px;text-align:center;font-size:12px;width:150px;'>렌탈 조건</th>
      </tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
  <div style='padding:16px 24px;text-align:right;border-top:2px solid #1e40af;margin:0 24px;'>{$summary}</div>
  {$extra_block}
  {$memo_block}
  <div style='padding:14px 24px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:11px;'>
    본 견적서는 Coway 영업관리시스템에서 발송되었습니다. | 유효기간 : 견적일로부터 30일
  </div>
</div></body></html>";
    }
}
