<?php
class ProductController
{
	//모델 목록 조회
	public function getSearchProduct(): void
	{
		global $db_local;
		$data = array();

		$status = 'success';
		$message = '';

		try{
			//로그인 체크
			if(!login_check(true))
			{
				$status = 'fail';
				$message = '로그인 후 이용해주세요.';
				throw new Exception($message);
			}

			//변수
			$_REQUST_JSON = json_decode(file_get_contents('php://input'), true);

			$keyword = $_REQUST_JSON['keyword'];

			$where_sql = '';
			if ($keyword)
			{
				$where_sql = "
					AND
					(
						m.model_name LIKE '%{$keyword}%'
						OR m.model_no LIKE '%{$keyword}%'
					)
				";
			}

			$sql = "
				SELECT
					m.*
					, p.rent_price
					, p.setup_price
					, p.normal_price
				FROM tndnjstl_model AS m
				INNER JOIN tndnjstl_price AS p
					ON p.uid = (
						SELECT uid
						FROM tndnjstl_price
						WHERE model_uid = m.model_uid
						ORDER BY register_date DESC
						LIMIT 1
					)
				WHERE 1=1
					{$where_sql}
				ORDER BY m.model_name ASC
			";


			$result = $db_local->query("$sql");

			$products = array();
			while($row = $result->fetch_assoc()){
				$products[] = $row;
			}

			$data['status'] = $status;
			$data['data'] = $products;
		}
		catch(Exception $e){
			$data = array();
			$data['status'] = $status;
			$data['message'] = $e->getMessage();
		}

		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}

	//모델 건별 조회

	//상품조회
	public function getModelInfo(): void
	{
		global $db_local;
		$data = array();

		$status = 'success';
		$message = '';

		try{
			//로그인 체크
			if(!login_check(true))
			{
				$status = 'fail';
				$message = '로그인 후 이용해주세요.';
				throw new Exception($message);
			}

			//변수
			$_REQUST_JSON = json_decode(file_get_contents('php://input'), true);

			$model_uid = $_REQUST_JSON['model_uid'];

			$sql = "
				SELECT
					  tndnjstl_model.*
					, model_price.rent_price
					, model_price.setup_price
					, model_price.normal_price
				FROM tndnjstl_model
				INNER JOIN 
				(
					SELECT
						*
					FROM tndnjstl_price
					ORDER BY tndnjstl_price.register_date DESC
				) AS model_price
					ON tndnjstl_model.uid = model_price.model_uid
				WHERE 1=1
					AND tndnjstl_model.uid = '{$model_uid}'
				ORDER BY tndnjstl_model.model_name ASC
			";

			$result = $db_local->query("$sql");

			$products = array();
			while($row = $result->fetch_assoc()){
				$products[] = $row;
			}

			$data['status'] = $status;
			$data['data'] = $products;
		}
		catch(Exception $e){
			$data = array();
			$data['status'] = $status;
			$data['message'] = $e->getMessage();
		}

		echo json_encode($data, JSON_UNESCAPED_UNICODE);
	}
}
