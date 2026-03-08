<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>코웨이 영업관리</title>
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

	<?php if (!empty($load_kakao_maps) && defined('KAKAO_MAP_KEY') && KAKAO_MAP_KEY): ?>
	<!-- 카카오맵 SDK (지도 페이지에서만 로드) -->
	<script type="text/javascript" src="//dapi.kakao.com/v2/maps/sdk.js?appkey=<?= htmlspecialchars(KAKAO_MAP_KEY) ?>&autoload=false&libraries=services"></script>
	<?php endif; ?>
</head>
<body>