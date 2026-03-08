<?php
// 쿠키 서명용 시크릿 키 (변경 시 모든 remember-me 쿠키 무효화)
define('COOKIE_SECRET', 'coway_rm_2024!@#$%^secure');

// 지도 API 설정
define('KAKAO_MAP_KEY', 'eaac31d68923d85c124e197c0bb4c187');    // 카카오맵 JavaScript API 키
define('NAVER_MAP_CLIENT', ''); // 네이버맵 Client ID

// 파일 업로드 경로
define('AUDIO_UPLOAD_PATH', BASE_PATH . '/uploads/audio/');
define('AUDIO_UPLOAD_URL', '/uploads/audio/');

// 위치 추적 설정
define('LOCATION_LOG_INTERVAL', 30); // 서버 전송 간격(초)
