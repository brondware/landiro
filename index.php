<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Settings.php';

$_homepage_slug = Settings::get('homepage_slug', '');

if ($_homepage_slug) {
    require __DIR__ . '/landings/index.php';
    exit;
}

if (Auth::check()) {
    header('Location: ' . ADMIN_URL . '/');
} else {
    header('Location: ' . BASE_URL . '/login.php');
}
exit;
