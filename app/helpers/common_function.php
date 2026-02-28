<?php
function print_m($text, $title="", $mode="") {
	if($mode == "json_de") $text = json_decode($text, TRUE);
	else if($mode == "json_en") $text = json_encode($text);

	$br = "<br>";
	$xmp = "<xmp>";
	$xmp2 = "</xmp>";

	if($mode == "console") {
		$br = "\n";
		$xmp = "";
		$xmp2 = "";
	}

	if(is_array($text) or is_object($text)) {

		echo $br."=======================".$title."=======================".$br;

		echo $xmp;
		echo print_r($text);
		echo $xmp2;

		echo "=======================/".$title."=======================".$br.$br;
	}

	else {

		echo $br."=======================".$title."=======================".$br;
		echo $xmp;
		echo $text;
		echo $xmp2;
		echo $br;

		echo "=======================/".$title."=======================".$br.$br;
	}
}

// 로그처리 (에러 로그)
function error_log_m($data, $title = '')
{
	if (!is_string($data)) {
		$logData = print_r($data, true);
	} else {
		$logData = $data;
	}

	// 줄바꿈 통일
	$logData = str_replace("\r\n", "\n", $logData);
	$logData = str_replace("\n", PHP_EOL, $logData);

	// 로그 구분선
	error_log(PHP_EOL . str_repeat('=', 30) . " {$title} " . str_repeat('=', 30));
	error_log($logData);
	error_log(str_repeat('=', 70) . PHP_EOL);
}

function alert($msg)
{
	echo "<script>alert('{$msg}');location.href = '/Auth/login';</script>";
	exit;
}

function date_name_kr()
{
	$days = ['일', '월', '화', '수', '목', '금', '토'];
	return $days[date('w')];	
}

function login_check($return=false)
{
	// 세션이 있으면 통과
	if (isset($_SESSION['is_login']) && $_SESSION['is_login']) {
		return true;
	}

	// 세션 없으면 remember-me 쿠키로 자동 복원 시도
	if (!empty($_COOKIE['coway_remember'])) {
		global $db_local;
		$parts = explode('|', $_COOKIE['coway_remember'], 2);
		if (count($parts) === 2) {
			[$cookie_id, $cookie_token] = $parts;
			$esc = $db_local->real_escape_string($cookie_id);
			$r   = $db_local->query("SELECT * FROM tndnjstl_member WHERE member_id = '{$esc}' LIMIT 1");
			if ($r && $r->num_rows > 0) {
				$row   = $r->fetch_assoc();
				$valid = hash('sha256', $row['member_id'] . '|' . $row['password'] . '|' . COOKIE_SECRET);
				if (hash_equals($valid, $cookie_token)) {
					// 토큰 유효 → 세션 복원
					$_SESSION['is_login']  = true;
					$_SESSION['member_id'] = $row['member_id'];
					$_SESSION['info']      = $row;
					return true;
				}
			}
		}
		// 유효하지 않은 쿠키 삭제
		setcookie('coway_remember', '', ['expires' => time() - 3600, 'path' => '/']);
	}

	if ($return) {
		return false;
	}

	alert('로그인 후 이용해주세요.');
	exit;
}

/**
 * 상품 크롤링
 *
 * 카테고리별 제품리스트 JSON 저장 후 DB에 INSERT/UPDATE
 *   1  : 정수기
 *   2  : 공기청정기
 *   3  : 비데 (비데, 연수기)
 *   4  : 안마의자 (안마의자, 마사지셋)
 *   5  : 주방가전 (전기레인지)
 *   55 : 매트리스 (매트리스, 프레임)
 *   58 : 헬스케어 (요실금 치료기)
 */
function crawl_product(): void
{
	global $db_local;

	$is_cli = (php_sapi_name() === 'cli');
	$nl = $is_cli ? PHP_EOL : '<br>';

	$category_list = [1, 2, 3, 4, 5, 55, 58];

	/* ----------------------------------------------------------------
	 * 1단계: Coway API → JSON 파일 저장
	 * ---------------------------------------------------------------- */
	echo "[1단계] Coway API 크롤링 시작{$nl}";

	foreach ($category_list as $category_no) {
		$url = "https://www.coway.com/core/fproduct/list?schctgno={$category_no}&pageSize=100&pageNumber=1&sortkey=NEW";

		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => 'GET',
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36',
			CURLOPT_REFERER        => 'https://www.coway.com/product/list?ctgry=' . $category_no,
			CURLOPT_HTTPHEADER     => [
				'accept: application/json',
				'accept-language: ko-KR,ko;q=0.8',
				'x-requested-with: XMLHttpRequest',
			],
			CURLOPT_TIMEOUT        => 30,
		]);

		$json        = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error  = curl_error($ch);
		curl_close($ch);

		if ($status_code !== 200 || $json === false) {
			echo "  [FAIL] category {$category_no} → HTTP {$status_code} {$curl_error}{$nl}";
			continue;
		}

		$data = json_decode($json, true);
		if (!isset($data['obj']['content'])) {
			echo "  [FAIL] category {$category_no} → 응답 형식 오류{$nl}";
			continue;
		}

		$save_path = BASE_PATH . "/json/category_{$category_no}.json";
		file_put_contents($save_path, $json);

		$count = count($data['obj']['content']);
		echo "  [OK] category {$category_no} → 상품 {$count}건 저장{$nl}";
	}

	/* ----------------------------------------------------------------
	 * 2단계: JSON 파싱 → DB INSERT/UPDATE
	 * ---------------------------------------------------------------- */
	echo "{$nl}[2단계] DB 반영 시작{$nl}";

	$total_insert = 0;
	$total_update = 0;

	foreach ($category_list as $category_no) {
		$file_path = BASE_PATH . "/json/category_{$category_no}.json";

		if (!file_exists($file_path)) {
			echo "  [SKIP] category {$category_no} → JSON 파일 없음{$nl}";
			continue;
		}

		$data = json_decode(file_get_contents($file_path), true);

		if (!isset($data['obj']['content']) || !is_array($data['obj']['content'])) {
			echo "  [SKIP] category {$category_no} → 파싱 실패{$nl}";
			continue;
		}

		$product_rows = [];

		foreach ($data['obj']['content'] as $content_row) {
			if (!isset($content_row['prdno'], $content_row['prdOptList']) || !is_array($content_row['prdOptList'])) {
				continue;
			}

			foreach ($content_row['prdOptList'] as $type_cd => $color_map) {
				if (!is_array($color_map)) continue;

				foreach ($color_map as $color_cd => $row) {
					if (!is_array($row) || !isset($row['goodscd'], $row['modelno'])) continue;

					// 색상 추출 (optnattrfunc 두 번째 값)
					$model_color  = 'ice_white';
					$optn_attr    = (string)($row['optnattrfunc'] ?? '');
					if ($optn_attr !== '') {
						$parts = array_map('trim', explode(',', $optn_attr));
						if (!empty($parts[1])) $model_color = $parts[1];
					}

					// 가격: final 값 우선, 없으면 기본값
					$normal_price = (int)(($row['finalprice'] ?? 0) ?: ($row['price'] ?? 0));
					$rent_price   = (int)(($row['finalmonthamt'] ?? 0) ?: ($row['monthamt'] ?? 0));

					$product_rows[] = [
						'prdno'       => (int)$content_row['prdno'],
						'model_uid'   => (string)$row['goodscd'],
						'model_name'  => (string)($row['modelnm'] ?? $row['prdnm'] ?? ''),
						'model_no'    => (string)$row['modelno'],
						'model_color' => $model_color,
						'model_image' => (string)($row['saveimgloc'] ?? ''),
						'category'    => (string)($row['ctgnm'] ?? ''),
						'normal_price'=> $normal_price,
						'rent_price'  => $rent_price,
						'setup_price' => 0,
					];
				}
			}
		}

		$cat_insert = 0;
		$cat_update = 0;

		foreach ($product_rows as $row) {
			$model_uid        = $db_local->real_escape_string($row['model_uid']);
			$model_name       = $db_local->real_escape_string($row['model_name']);
			$model_no         = $db_local->real_escape_string($row['model_no']);
			$model_color      = $db_local->real_escape_string($row['model_color']);
			$model_image      = $db_local->real_escape_string($row['model_image']);
			$category         = $db_local->real_escape_string($row['category']);
			$rent_price       = $row['rent_price'];
			$normal_price     = $row['normal_price'];
			$setup_price      = $row['setup_price'];
			$model_detail_url = "https://www.coway.com/product/detail?prdno={$row['prdno']}";

			// model 존재 여부 확인
			$check = $db_local->query("SELECT uid FROM tndnjstl_model WHERE model_uid = '{$model_uid}' LIMIT 1");

			if ($check && $check->num_rows > 0) {
				$db_local->query("
					UPDATE tndnjstl_model SET
						model_name       = '{$model_name}',
						model_no         = '{$model_no}',
						model_color      = '{$model_color}',
						model_image      = '{$model_image}',
						model_detail_url = '{$model_detail_url}',
						category         = '{$category}',
						update_date      = NOW()
					WHERE model_uid = '{$model_uid}'
				");
				$cat_update++;
			} else {
				$db_local->query("
					INSERT INTO tndnjstl_model SET
						model_uid        = '{$model_uid}',
						model_name       = '{$model_name}',
						model_no         = '{$model_no}',
						model_color      = '{$model_color}',
						model_image      = '{$model_image}',
						model_detail_url = '{$model_detail_url}',
						category         = '{$category}',
						register_date    = NOW()
				");
				$cat_insert++;
			}

			// price upsert
			$db_local->query("
				INSERT INTO tndnjstl_price SET
					model_uid     = '{$model_uid}',
					rent_price    = {$rent_price},
					normal_price  = {$normal_price},
					setup_price   = {$setup_price},
					register_date = NOW()
				ON DUPLICATE KEY UPDATE
					rent_price    = {$rent_price},
					normal_price  = {$normal_price},
					setup_price   = {$setup_price}
			");
		}

		echo "  [OK] category {$category_no} → INSERT {$cat_insert}건 / UPDATE {$cat_update}건{$nl}";
		$total_insert += $cat_insert;
		$total_update += $cat_update;
	}

	echo "{$nl}완료: 총 INSERT {$total_insert}건 / UPDATE {$total_update}건{$nl}";
}