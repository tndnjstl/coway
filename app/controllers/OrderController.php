<?php
class OrderController
{
	//주문접수
	public function addOrder(): void
	{
		//로그인 체크
		login_check();

		include VIEW_PATH . '/order_form_view.php';
	}
}
