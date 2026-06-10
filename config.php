<?php
define('CMS_VERSION', '1.0.0');
define('CMS_NAME', 'Landiro CMS');

// Admin credentials (password: admin123 — змініть після першого входу)
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', '$2y$12$Z0xHiQ507aZjOZsB/Jdl8euF.jUzJCqfnETXVrYcKPTxsbm3Hr2ra'); // оновлено 2026-06-10 10:03

// Paths
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('LANDINGS_DATA_PATH', DATA_PATH . '/landings');
define('UPLOADS_PATH', DATA_PATH . '/uploads');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('LANDINGS_PUBLIC_PATH', ROOT_PATH . '/landings');

// URL (автовизначення — працює в корені, підпапці, на будь-якому хостингу)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Нормалізуємо шляхи через realpath (вирішує проблеми зі слешами на Windows)
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$cmsRoot = realpath(ROOT_PATH);

if ($docRoot && $cmsRoot) {
    // Порівняння без урахування регістру (Windows нечутливий)
    $docRootNorm = rtrim(str_replace('\\', '/', $docRoot), '/');
    $cmsRootNorm = rtrim(str_replace('\\', '/', $cmsRoot), '/');
    if (stripos($cmsRootNorm, $docRootNorm) === 0) {
        $relPath = substr($cmsRootNorm, strlen($docRootNorm));
    } else {
        $relPath = '';
    }
} else {
    $relPath = '';
}
// Якщо не вдалось визначити — fallback через SCRIPT_NAME
if ($relPath === '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $cmsRootNorm = str_replace('\\', '/', ROOT_PATH);
    if ($scriptFile && $scriptName) {
        // Обчислюємо відносний шлях файлу відносно кореня CMS
        $fileRelToCms = ltrim(str_replace($cmsRootNorm, '', str_replace('\\', '/', $scriptFile)), '/');
        // Видаляємо цю ж частину з SCRIPT_NAME щоб отримати базовий URL
        $relPath = substr($scriptName, 0, strlen($scriptName) - strlen($fileRelToCms) - 1);
    }
}
$relPath = rtrim($relPath, '/');

define('BASE_URL', $protocol . '://' . $host . $relPath);
define('ADMIN_URL', BASE_URL . '/admin');
define('LANDINGS_URL', BASE_URL . '/landings');
define('UPLOADS_URL', BASE_URL . '/data/uploads');

// Upload limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

// Security
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Secure session cookies
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global settings (data/settings.json)
require_once ROOT_PATH . '/core/Settings.php';
Settings::init();