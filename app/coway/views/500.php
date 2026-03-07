<!DOCTYPE html>
<html lang="ko">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>500 - 코웨이 영업관리</title>
	<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
	<link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon"/>
	<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
	<div class="d-flex align-items-center justify-content-center" style="min-height:100vh;">
		<div class="text-center">
			<div class="fw-bold text-danger mb-2" style="font-size:80px;line-height:1;">500</div>
			<h4 class="fw-bold mb-2">서버 오류가 발생했습니다.</h4>
			<p class="text-muted mb-4">일시적인 문제가 발생했습니다. 잠시 후 다시 시도해주세요.</p>
			<?php if (!empty($error_message) && (defined('APP_ENV') && APP_ENV !== 'production')): ?>
			<pre class="text-start text-danger small bg-white border rounded p-3 mb-4" style="max-width:600px;margin:0 auto;overflow:auto;"><?= htmlspecialchars($error_message) ?></pre>
			<?php endif; ?>
			<a href="/" class="btn btn-primary me-2">
				<i class="fas fa-home me-1"></i> 홈으로 돌아가기
			</a>
			<button onclick="history.back()" class="btn btn-outline-secondary">
				<i class="fas fa-arrow-left me-1"></i> 뒤로
			</button>
		</div>
	</div>
	<link rel="stylesheet" href="/assets/css/fonts.min.css">
</body>
</html>
