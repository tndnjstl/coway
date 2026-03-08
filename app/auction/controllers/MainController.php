<?php
declare(strict_types=1);

class MainController
{
    public function main(): void
    {
        include VIEW_PATH . '/layouts/header.php';
        include VIEW_PATH . '/main_view.php';
        include VIEW_PATH . '/layouts/footer.php';
        include VIEW_PATH . '/layouts/script.php';
        include VIEW_PATH . '/layouts/tail.php';
    }
}
