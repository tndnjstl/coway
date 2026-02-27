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
	//로그인 실패시
	if(!isset($_SESSION['is_login']) || !$_SESSION['is_login'])
	{
		if($return)
		{
			return false;
		}

		alert('로그인 후 이용해주세요.');
		exit;
	}

	return true;
}

/****************************************************************************************
 * 입력시간 : 2026-01-31 19:19:19                                                        *
 * 입력자 : 한진희                                                                      *
 ****************************************************************************************
* 상품 크롤링
***************************************************************************************/
function crawl_product()
{
	global $db_local;

	/****************************************************************************************
	 * 입력시간 : 2026-01-31 19:20:46                                                        *
	 * 입력자 : 한진희                                                                      *
	 ****************************************************************************************
	* 상품 크롤링
	***************************************************************************************/
	/**
	 * 카테고리별 제품리스트 JSON 저장
	 * 1 : 정수기
	 * 2 : 공기청정기 
	 * 3 : 비데 => 비데, 연수기
	 * 4 : 안마의자 => 안마의자, 마사지셋
	 * 5 : 주방가전 => 전기레인지
	 * 55 : 매트리스 => 매트리스, 프레임
	 * 58 : 헬스케어 => 요실금 치료기
	 */
	$category_list = [1,2,3,4,5,55,58];

	foreach($category_list as $category_no)
	{
		//하나씩 브라우저에 로그 찍기
		

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://www.coway.com/core/fproduct/list?schctgno={$category_no}&pageSize=100&pageNumber=1&sortkey=NEW");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36");
		curl_setopt($ch, CURLOPT_REFERER, "https://www.coway.com/product/list?ctgry=1");

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"accept: application/json",
			"accept-language: ko-KR,ko;q=0.8",
			"x-requested-with: XMLHttpRequest"
		));

		curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array(
			'_kmpid=km|www.coway.com|1764412641056|239f90b2-bb32-4976-9f53-2a1f18781451',
			'_kmpid=km|coway.com|1764412641056|239f90b2-bb32-4976-9f53-2a1f18781451',
			'cwAuthRememberMe=true',
			'XSRF-TOKEN=c1e920b9-14c1-435c-b5d6-295d3ebf28d3',
			'language_coway_front=ko-KR',
			'c1e920b9-14c1-435c-b5d6-295d3ebf28d3',
			'FSESSIONID=NWEwODVlMDYtYzAxYy00NmU5LThjMTUtODdlNzc0ZmQ1ODUy',
			'_rct_prd=1370/2026-01-23'
		)));

		$json = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		//실패시 출력...
		if( $status_code != 200 ) {
			print_m($category_no, 'category_no :: ' . __FILE__ . ' :: ' . __LINE__, '');
			print_m($status_code, 'status :: ' . __FILE__ . ' :: ' . __LINE__, '');
			print_m($error, 'error :: ' . __FILE__ . ' :: ' . __LINE__, '');
			continue;
		}

		//json 저장
		file_put_contents(BASE_PATH . "/json/category_{$category_no}.json", $json);

		// echo $json;
		// exit;
	}



	/****************************************************************************************
	 * 입력시간 : 2026-01-31 19:20:33                                                        *
	 * 입력자 : 한진희                                                                      *
	 ****************************************************************************************
	* 상품 파싱 등록
	***************************************************************************************/
	foreach($category_list as $category_no)
	{
		$json = file_get_contents(BASE_PATH . '/json/category_'.$category_no.'.json');

		$data = json_decode($json, true);

		$product_rows = array();

		if (
			!isset($data['obj']) ||
			!isset($data['obj']['content']) ||
			!is_array($data['obj']['content'])
		) {
			$product_rows = array();
		} else {
			foreach ($data['obj']['content'] as $content_row)
			{
				if (!isset($content_row['prdno'])) {
					continue;
				}
				if (!isset($content_row['prdOptList']) || !is_array($content_row['prdOptList'])) {
					continue;
				}

				foreach ($content_row['prdOptList'] as $type_cd => $color_map)
				{
					if (!is_array($color_map)) {
						continue;
					}

					foreach ($color_map as $color_cd => $row)
					{
						if (!is_array($row)) {
							continue;
						}
						if (!isset($row['goodscd']) || !isset($row['modelno'])) {
							continue;
						}

						$model_uid = (string)$row['goodscd'];

						$model_name = (string)($row['modelnm'] ?? ($row['prdnm'] ?? ''));
						$model_no = (string)($row['modelno'] ?? '');
						$model_image = (string)($row['saveimgloc'] ?? '');
						$category = (string)($row['ctgnm'] ?? '');

						$model_color = 'ice_white';
						$optn_attr_func = (string)($row['optnattrfunc'] ?? '');
						if ($optn_attr_func != '') {
							$tmp = array_map('trim', explode(',', $optn_attr_func));
							if (isset($tmp[1]) && $tmp[1] != '') {
								$model_color = $tmp[1];
							}
						}

						$normal_price = 0;
						if (isset($row['finalprice']) && (int)$row['finalprice'] > 0) {
							$normal_price = (int)$row['finalprice'];
						} else if (isset($row['price'])) {
							$normal_price = (int)$row['price'];
						}

						$rent_price = 0;
						if (isset($row['finalmonthamt']) && (int)$row['finalmonthamt'] > 0) {
							$rent_price = (int)$row['finalmonthamt'];
						} else if (isset($row['monthamt'])) {
							$rent_price = (int)$row['monthamt'];
						}

						$setup_price = 0;

						$product_rows[] = array(
							'prdno' => (int)$content_row['prdno'],
							'model_uid' => $model_uid,
							'model_name' => $model_name,
							'model_no' => $model_no,
							'model_color' => $model_color,
							'model_image' => $model_image,
							'category' => $category,
							'normal_price' => $normal_price,
							'rent_price' => $rent_price,
							'setup_price' => $setup_price,
							'optnattrfunc' => $optn_attr_func,
							'type_cd' => (string)$type_cd,
							'color_cd' => (string)$color_cd
						);
					}
				}
			}
		}

		foreach ($product_rows as $row)
		{
			$model_uid = addslashes($row['model_uid']);
			$model_name = addslashes($row['model_name']);
			$model_no = addslashes($row['model_no']);
			$model_color = addslashes($row['model_color']);
			$model_image = addslashes($row['model_image']);
			$category = addslashes($row['category']);

			$rent_price = (int)$row['rent_price'];
			$normal_price = (int)$row['normal_price'];
			$setup_price = (int)$row['setup_price'];

			$prdno = (int)$row['prdno'];
			$model_detail_url = "https://www.coway.com/product/detail?prdno={$prdno}";

			/* model 존재 여부 체크 */
			$sql_check = "
				SELECT uid
				FROM tndnjstl_model
				WHERE model_uid = '{$model_uid}'
				LIMIT 1
			";
			$check = $db_local->query($sql_check);

			if ($check && $check->num_rows > 0) {
				/* update */
				$sql_model = "
					UPDATE tndnjstl_model
					SET
						model_name = '{$model_name}',
						model_no = '{$model_no}',
						model_color = '{$model_color}',
						model_image = '{$model_image}',
						model_detail_url = '{$model_detail_url}',
						category = '{$category}',
						update_date = NOW()
					WHERE model_uid = '{$model_uid}'
				";
			} else {
				/* insert */
				$sql_model = "
					INSERT INTO tndnjstl_model
					SET
						model_uid = '{$model_uid}',
						model_name = '{$model_name}',
						model_no = '{$model_no}',
						model_color = '{$model_color}',
						model_image = '{$model_image}',
						model_detail_url = '{$model_detail_url}',
						category = '{$category}',
						register_date = NOW()
				";
			}
			$db_local->query($sql_model);

			/* price는 upsert */
			$sql_price = "
				INSERT INTO tndnjstl_price
				SET
					model_uid = '{$model_uid}',
					rent_price = {$rent_price},
					normal_price = {$normal_price},
					setup_price = {$setup_price},
					register_date = NOW()
				ON DUPLICATE KEY UPDATE
					rent_price = {$rent_price},
					normal_price = {$normal_price},
					setup_price = {$setup_price}
			";
			$db_local->query($sql_price);
		}

	}
}