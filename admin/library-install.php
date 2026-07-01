<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

header('Content-Type: application/json');

function lib_error(string $msg): never {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function lib_rmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
        $p = $dir . '/' . $f;
        is_dir($p) ? lib_rmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

function lib_copydir(string $src, string $dst): void {
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    foreach (array_diff(scandir($src) ?: [], ['.', '..']) as $f) {
        $s = $src . '/' . $f;
        $d = $dst . '/' . $f;
        is_dir($s) ? lib_copydir($s, $d) : @copy($s, $d);
    }
}

function lib_http(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body && $status >= 200 && $status < 300) ? $body : null;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 10, 'ignore_errors' => true],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $r = @file_get_contents($url, false, $ctx);
    return $r !== false ? $r : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') lib_error('Method not allowed');

$token = $_POST['_csrf'] ?? '';
if (!Auth::verifyCsrf($token)) lib_error('Forbidden');

$itemId = (int) ($_POST['item_id'] ?? 0);
$title  = trim($_POST['title'] ?? '');

if (!$itemId)   lib_error('item_id відсутній');
if (!$title)    $title = 'Новий лендинг';

// ── 1. Fetch item info from community API ──────────────────────────────
$apiUrl  = defined('LIBRARY_API_URL') ? LIBRARY_API_URL : '';
if (!$apiUrl) lib_error('LIBRARY_API_URL не налаштовано в config.php');

$json = lib_http($apiUrl . '?id=' . $itemId);
if (!$json) lib_error('Не вдалося отримати інформацію про шаблон');

$apiData = json_decode($json, true);
if (!isset($apiData['item'])) lib_error('Невідповідь від API');

$item        = $apiData['item'];
$archivePath = $apiData['archive_path'] ?? '';

if (!$archivePath || !file_exists($archivePath)) {
    lib_error('Файл архіву не знайдено на сервері');
}
if (substr($archivePath, 0, 2) === 'PK') {
    // already bytes check – skip
}
if (!is_readable($archivePath)) lib_error('Архів недоступний для читання');

// ── 2. Extract ZIP ─────────────────────────────────────────────────────
if (!extension_loaded('zip')) lib_error('PHP zip розширення не встановлено');

$tmpDir = DATA_PATH . '/install_tmp_' . time();
@mkdir($tmpDir, 0755, true);

$zip = new ZipArchive();
if ($zip->open($archivePath) !== true) {
    lib_rmdir($tmpDir);
    lib_error('Не вдалося відкрити ZIP архів');
}
$zip->extractTo($tmpDir);
$zip->close();

// Find actual root (may have a top-level subdir)
$entries = array_diff(scandir($tmpDir) ?: [], ['.', '..']);
$subdirs = array_filter($entries, function($e) use ($tmpDir) { return is_dir($tmpDir . '/' . $e); });
$srcRoot = (count($entries) === 1 && count($subdirs) === 1)
    ? $tmpDir . '/' . reset($subdirs)
    : $tmpDir;

// ── 3. Parse meta.json + landing.json ─────────────────────────────────
$meta = [];
if (file_exists($srcRoot . '/meta.json')) {
    $meta = json_decode(file_get_contents($srcRoot . '/meta.json'), true) ?? [];
}

if (!file_exists($srcRoot . '/landing.json')) {
    lib_rmdir($tmpDir);
    lib_error('Невірний формат пакету: відсутній landing.json. Архів має містити meta.json та landing.json.');
}

$landingData = json_decode(file_get_contents($srcRoot . '/landing.json'), true);
if (!$landingData || !isset($landingData['sections'])) {
    lib_rmdir($tmpDir);
    lib_error('Невірний формат landing.json');
}

// ── 4. Copy uploads/ and img/ to UPLOADS_PATH ─────────────────────────
$pkgUploads = $srcRoot . '/uploads';
if (is_dir($pkgUploads)) {
    lib_copydir($pkgUploads, UPLOADS_PATH);
}
// Templates may ship images in img/ — copy those too
$pkgImg = $srcRoot . '/img';
if (is_dir($pkgImg)) {
    lib_copydir($pkgImg, UPLOADS_PATH);
}

// ── 5. Copy custom templates/ ──────────────────────────────────────────
$pkgTemplates = $srcRoot . '/templates';
if (is_dir($pkgTemplates)) {
    lib_copydir($pkgTemplates, TEMPLATES_PATH);
}

// ── 6. Create landing ──────────────────────────────────────────────────
$landingMgr = new Landing();
$landing    = $landingMgr->create($title);

// Apply global styles & seo from package
if (!empty($landingData['global_styles'])) {
    $landing['global_styles'] = array_merge($landing['global_styles'], $landingData['global_styles']);
}
if (!empty($landingData['seo'])) {
    $landing['seo'] = array_merge($landing['seo'], $landingData['seo']);
    $landing['seo']['title'] = $title; // use user-provided title
}
if (!empty($landingData['scripts'])) {
    $landing['scripts'] = array_merge($landing['scripts'], $landingData['scripts']);
}

// Fix img/ paths in data.vars — replace with UPLOADS_URL
array_walk_recursive($landingData, function (&$val) {
    if (!is_string($val)) return;
    // Direct path: img/xxx.jpg → UPLOADS_URL/xxx.jpg
    if (strncmp($val, 'img/', 4) === 0) {
        $val = UPLOADS_URL . '/' . substr($val, 4);
        return;
    }
    // CSS url(): url(img/xxx) or url('img/xxx') or url("img/xxx") → url(UPLOADS_URL/xxx)
    $val = preg_replace_callback(
        '/url\([\'"]?img\/([^\'")\s]+)[\'"]?\)/',
        function ($m) { return "url('" . UPLOADS_URL . '/' . $m[1] . "')"; },
        $val
    );
});

// Copy sections with fresh IDs
$landing['sections'] = [];
foreach ($landingData['sections'] as $s) {
    $s['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff)|0x4000, mt_rand(0, 0x3fff)|0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    $landing['sections'][] = $s;
}

$landingMgr->save($landing);
lib_rmdir($tmpDir);

// Notify community API to increment download counter (best-effort)
$incUrl = str_replace('/api/library.php', '/api/library-inc.php', $apiUrl) . '?id=' . $itemId;
@lib_http($incUrl);

echo json_encode([
    'success'  => true,
    'slug'     => $landing['slug'],
    'edit_url' => ADMIN_URL . '/landing.php?slug=' . urlencode($landing['slug']),
]);
