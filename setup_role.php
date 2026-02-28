#!/usr/bin/env php
<?php
/**
 * 계정 역할 설정 스크립트 (1회 실행 후 자동 삭제)
 * 접속: https://도메인/setup_role.php?key=coway2024
 */
define('BASE_PATH', __DIR__);
define('APP_PATH',  BASE_PATH . '/app');
define('HELPER_PATH', APP_PATH . '/helpers');

$secret = 'coway2024';
if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    exit('403 Forbidden');
}

ini_set('default_charset', 'UTF-8');
require_once HELPER_PATH . '/common_db.php';

$msg = '';
$error = '';

// 1. is_admin 컬럼 추가 (없을 경우)
$check_col = $db_local->query("SHOW COLUMNS FROM tndnjstl_member LIKE 'is_admin'");
if ($check_col && $check_col->num_rows === 0) {
    $db_local->query("ALTER TABLE tndnjstl_member ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=관리자'");
    $msg .= "✅ is_admin 컬럼 추가 완료<br>";
} else {
    $msg .= "ℹ️ is_admin 컬럼 이미 존재<br>";
}

// 2. 관리자 설정 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_id'])) {
    $aid = $db_local->real_escape_string(trim($_POST['admin_id']));
    $db_local->query("UPDATE tndnjstl_member SET is_admin = 0");
    $db_local->query("UPDATE tndnjstl_member SET is_admin = 1 WHERE member_id = '{$aid}'");
    $msg .= "✅ [{$aid}] 계정을 관리자로 설정 완료<br>";

    // 스크립트 자동 삭제
    @unlink(__FILE__);
    echo "<html><head><meta charset='UTF-8'></head><body style='font-family:sans-serif;padding:40px;'>";
    echo "<h2>✅ 설정 완료</h2>";
    echo $msg;
    echo "<p style='color:green;font-weight:bold;'>설정 파일이 삭제되었습니다. 이 페이지를 닫으세요.</p>";
    echo "</body></html>";
    exit;
}

// 3. 현재 계정 목록 출력
$members = [];
$r = $db_local->query("SELECT uid, member_id, member_name, IF(is_admin IS NULL, 0, is_admin) AS is_admin FROM tndnjstl_member ORDER BY uid ASC");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $members[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head><meta charset="UTF-8"><title>계정 역할 설정</title>
<style>
  body { font-family: 'Apple SD Gothic Neo', sans-serif; padding: 40px; background: #f0f2f5; }
  .card { background: #fff; border-radius: 8px; padding: 30px; max-width: 500px; margin: auto; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
  h2 { color: #1e40af; margin-bottom: 8px; }
  .msg { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px; margin-bottom: 20px; font-size: 13px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #1e40af; color: #fff; padding: 8px 12px; text-align: left; font-size: 13px; }
  td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
  label { cursor: pointer; display: flex; align-items: center; gap: 8px; }
  .btn { background: #1e40af; color: #fff; border: none; padding: 10px 24px; border-radius: 6px; font-size: 14px; cursor: pointer; }
  .badge-admin { background: #dcfce7; color: #166534; font-size: 11px; padding: 2px 6px; border-radius: 4px; }
</style>
</head>
<body>
<div class="card">
  <h2>계정 역할 설정</h2>
  <p style="color:#64748b;font-size:13px;margin-bottom:20px;">관리자(Admin)는 모든 계정의 데이터를 볼 수 있습니다.</p>
  <?php if ($msg): ?>
  <div class="msg"><?= $msg ?></div>
  <?php endif; ?>

  <form method="POST" action="?key=<?= htmlspecialchars($secret) ?>">
    <table>
      <thead><tr><th style="width:40px;">선택</th><th>아이디</th><th>이름</th><th>현재 역할</th></tr></thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><input type="radio" name="admin_id" value="<?= htmlspecialchars($m['member_id']) ?>" <?= $m['is_admin'] ? 'checked' : '' ?>></td>
          <td><?= htmlspecialchars($m['member_id']) ?></td>
          <td><?= htmlspecialchars($m['member_name']) ?></td>
          <td><?= $m['is_admin'] ? '<span class="badge-admin">관리자</span>' : '<span style="color:#94a3b8;">일반</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p style="font-size:12px;color:#ef4444;margin-bottom:16px;">⚠️ 관리자로 설정하면 설정 파일이 자동 삭제됩니다.</p>
    <button type="submit" class="btn">관리자 설정 완료</button>
  </form>
</div>
</body>
</html>
