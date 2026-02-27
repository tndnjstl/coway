<!DOCTYPE html>
<html lang="ko">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>로그인 - Coway 영업관리</title>
	<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
	<link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon"/>

	<script src="/assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Public Sans:300,400,500,600,700"]},
			custom: {"families":["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['/assets/css/fonts.min.css']},
			active: function() { sessionStorage.fonts = true; }
		});
	</script>

	<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/css/plugins.min.css">
	<link rel="stylesheet" href="/assets/css/kaiadmin.min.css">
</head>
<body class="login bg-primary">

<div class="wrapper wrapper-login">
	<div class="container container-login animated fadeIn">

		<div class="text-center mb-4">
			<span class="fw-bold text-white" style="font-size:26px;letter-spacing:-0.5px;">COWAY</span>
			<div class="text-white-50 small mt-1">영업관리시스템</div>
		</div>

		<form id="login_form" method="post" action="/Auth/loginProc">
			<div class="login-form">
				<div class="form-sub">
					<div class="form-floating form-floating-custom mb-3">
						<input id="member_id" name="member_id" type="text" class="form-control" placeholder="아이디" required />
						<label for="member_id">아이디</label>
					</div>
					<div class="form-floating form-floating-custom mb-3">
						<input id="password" name="password" type="password" class="form-control" placeholder="비밀번호" required />
						<label for="password">비밀번호</label>
						<div class="show-password">
							<i class="icon-eye"></i>
						</div>
					</div>
				</div>
				<div class="form-action mb-3">
					<button type="submit" class="btn btn-primary w-100 btn-login">로그인</button>
				</div>
			</div>
		</form>

	</div>
</div>

<?php include APP_PATH . '/views/layouts/script.php';?>
</body>
</html>
