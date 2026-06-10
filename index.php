<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Auth.php';

if (Auth::check()) {
    header('Location: ' . ADMIN_URL . '/');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
