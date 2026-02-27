<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>Coway 영업관리</title>
	<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
	<link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon"/>

	<!-- Fonts and icons -->
	<script src="/assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Public Sans:300,400,500,600,700"]},
			custom: {"families":["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['/assets/css/fonts.min.css']},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="/assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/css/plugins.min.css">
	<link rel="stylesheet" href="/assets/css/kaiadmin.min.css">

	<!-- CSS Just for demo purpose, don't include it in your project -->
	<link rel="stylesheet" href="/assets/css/demo.css">
</head>
  <body class="login bg-primary">
	<form id="login_form" method="post" action="/Auth/loginProc">
    <div class="wrapper wrapper-login">
      <div class="container container-login animated fadeIn">
        <h3 class="text-center">로그인</h3>
        <div class="login-form">
          <div class="form-sub">
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="member_id"
                name="member_id"
                type="text"
                class="form-control"
                placeholder="아이디를 입력해주세요."
                required
              />
              <label for="member_id">아이디</label>
            </div>
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="password"
                name="password"
                type="password"
                class="form-control"
                placeholder="비밀번호를 입력해주세요."
                required
              />
              <label for="password">비밀번호</label>
              <div class="show-password">
                <i class="icon-eye"></i>
              </div>
            </div>
          </div>
          <div class="row m-0">
            <div class="d-flex form-sub">
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="rememberme" />
                <label class="form-check-label" for="rememberme">자동로그인</label>
              </div>

            </div>
          </div>
          <div class="form-action mb-3">
            <button type="button" class="btn btn-primary w-100 btn-login" onclick="login();">로그인</button>
          </div>
        </div>
      </div>

      <div class="container container-signup animated fadeIn">
        <h3 class="text-center">Sign Up</h3>
        <div class="login-form">
          <div class="form-sub">
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="fullname"
                name="fullname"
                type="text"
                class="form-control"
				placeholder="fullname"
                required
              />
              <label for="fullname">Fullname</label>
            </div>
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="email"
                name="email"
                type="email"
                class="form-control"
				placeholder="email"
                required
              />
              <label for="email">Email</label>
            </div>
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="passwordsignin"
                name="passwordsignin"
                type="password"
                class="form-control"
				placeholder="passwordsignin"
                required
              />
              <label for="passwordsignin">Password</label>
              <div class="show-password">
                <i class="icon-eye"></i>
              </div>
            </div>
            <div class="form-floating form-floating-custom mb-3">
              <input
                id="confirmpassword"
                name="confirmpassword"
                type="password"
                class="form-control"
				placeholder="confirmpassword"
                required
              />
              <label for="confirmpassword">Confirm Password</label>
              <div class="show-password">
                <i class="icon-eye"></i>
              </div>
            </div>
          </div>
          <div class="row form-sub m-0">
            <div class="form-check">
              <input type="checkbox" class="form-check-input" name="agree" id="agree" />
              <label class="form-check-label" for="agree"
                >I Agree the terms and conditions.</label
              >
            </div>
          </div>
          <div class="form-action">
            <a href="#" id="show-signin" class="btn btn-danger btn-link btn-login me-3">Cancel</a>
            <a href="#" class="btn btn-primary btn-login">Sign Up</a>
          </div>
        </div>
      </div>
    </div>
	</form>

    <?php include APP_PATH . '/views/layouts/script.php';?>
	<script>
		function login()
		{
			var frm = $('#login_form');

			frm.submit();
		}
	</script>
  </body>
</html>
