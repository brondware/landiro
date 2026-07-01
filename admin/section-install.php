<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Template.php';

Auth::requireLogin();
header('Content-Type: application/json');

function sec_error(string $msg): never {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function sec_http(string $url): ?string {
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sec_error('Method not allowed');

$token = $_POST['_csrf'] ?? '';
if (!Auth::verifyCsrf($token)) sec_error('Forbidden');

$itemId = (int)($_POST['item_id'] ?? 0);
if (!$itemId) sec_error('item_id відсутній');

$apiUrl = defined('LIBRARY_API_URL') ? LIBRARY_API_URL : '';
if (!$apiUrl) sec_error('LIBRARY_API_URL не налаштовано в config.php');

// ── 1. Fetch item info ─────────────────────────────────────────────────────
$json = sec_http($apiUrl . '?id=' . $itemId);
if (!$json) sec_error('Не вдалося отримати інформацію про секцію');

$apiData = json_decode($json, true);
if (!isset($apiData['item'])) sec_error('Невідповідь від API');

$item        = $apiData['item'];
$archivePath = $apiData['archive_path'] ?? '';

if ($item['type'] !== 'section') sec_error('Цей елемент не є секцією');
if (!$archivePath || !file_exists($archivePath)) sec_error('Файл архіву не знайдено на сервері');
if (!is_readable($archivePath)) sec_error('Архів недоступний для читання');

// ── 2. Copy to tmp ──────────────────────────────────────────────────────────
$tmpFile = DATA_PATH . '/section_tmp_' . time() . '.zip';
if (!@copy($archivePath, $tmpFile)) sec_error('Не вдалося скопіювати архів');

// ── 3. Install via Template::installFromZip ────────────────────────────────
$tmpl   = new Template();
$result = $tmpl->installFromZip($tmpFile);
@unlink($tmpFile);

if (!$result['success']) sec_error($result['error']);

// ── 4. Notify community (best-effort) ─────────────────────────────────────
$incUrl = str_replace('/api/library.php', '/api/library-inc.php', $apiUrl) . '?id=' . $itemId;
@sec_http($incUrl);

echo json_encode([
    'success' => true,
    'type'    => $result['type'],
    'id'      => $result['id'],
    'name'    => $item['title'],
]);
