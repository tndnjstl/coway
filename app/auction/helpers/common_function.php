<?php
declare(strict_types=1);

function print_m(mixed $data, bool $console = false): void
{
    if ($console) {
        echo '<script>console.log(' . json_encode($data) . ');</script>';
    } else {
        echo '<pre>' . htmlspecialchars(print_r($data, true)) . '</pre>';
    }
}

function alert(string $msg, string $url = ''): void
{
    $redirect = $url ? "location.href='{$url}';" : 'history.back();';
    echo "<script>alert('" . addslashes($msg) . "');{$redirect}</script>";
    exit;
}

function login_check(bool $return = false): bool|array
{
    if (!empty($_SESSION['auction_member'])) {
        return $return ? $_SESSION['auction_member'] : true;
    }

    if (!$return) {
        header('Location: /auction/Auth/login');
        exit;
    }

    return false;
}
