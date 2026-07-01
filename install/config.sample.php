<?php
define('CMS_VERSION', '%%CMS_VERSION%%');
define('CMS_NAME', 'Landiro CMS');

// Updates
define('UPDATE_CHECK_URL', '%%UPDATE_CHECK_URL%%');
define('UPDATE_ENABLED',   true);

// Community
define('NEWS_API_URL',    '%%NEWS_API_URL%%');
define('LIBRARY_API_URL', '%%LIBRARY_API_URL%%');

// Admin credentials
define('ADMIN_LOGIN',    '%%ADMIN_LOGIN%%');
define('ADMIN_PASSWORD', '%%ADMIN_PASSWORD%%'); // bcrypt

// Paths
define('ROOT_PATH', __DIR__);
define('DATA_PATH', ROOT_PATH . '/data');
define('LANDINGS_DATA_PATH', DATA_PATH . '/landings');
define('UPLOADS_PATH', DATA_PATH . '/uploads');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('LANDINGS_PUBLIC_PATH', ROOT_PATH . '/landings');

// URL (auto-detect — works in root, subdir, any hosting)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$cmsRoot = realpath(ROOT_PATH);

if ($docRoot && $cmsRoot) {
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
if ($relPath === '') {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $cmsRootNorm = str_replace('\\', '/', ROOT_PATH);
    if ($scriptFile && $scriptName) {
        $fileRelToCms = ltrim(str_replace($cmsRootNorm, '', str_replace('\\', '/', $scriptFile)), '/');
        $relPath = substr($scriptName, 0, strlen($scriptName) - strlen($fileRelToCms) - 1);
    }
}
$relPath = rtrim($relPath, '/');

define('BASE_URL',     $protocol . '://' . $host . $relPath);
define('ADMIN_URL',    BASE_URL . '/admin');
define('LANDINGS_URL', BASE_URL . '/landings');
define('UPLOADS_URL',  BASE_URL . '/data/uploads');

// Upload limits
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml']);

// Security
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/core/Settings.php';
Settings::init();
