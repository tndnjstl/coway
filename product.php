<?php
/**
 * 상품 크롤링 실행 스크립트
 *
 * 웹: /product.php 접근 시 html/index.php를 통해 실행
 * CLI: php product.php
 */

// CLI 직접 실행 시 부트스트랩
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
    define('APP_PATH', BASE_PATH . '/app');
    define('HELPER_PATH', APP_PATH . '/helpers');

    ini_set('default_charset', 'UTF-8');
    date_default_timezone_set('Asia/Seoul');

    require_once APP_PATH . '/helpers/common_db.php';
    require_once APP_PATH . '/helpers/common_function.php';
}

crawl_product();
