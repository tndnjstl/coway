<?php
class MainController
{
	//메인
	public function main(): void
	{
		login_check();
		
		include VIEW_PATH . '/main_view.php';
	}
}
