<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Renderer.php';

Auth::requireLogin();

$slug = $_GET['slug'] ?? '';
if (!$slug) { http_response_code(400); exit('Не вказано slug'); }

$landingManager = new Landing();
$landing = $landingManager->get($slug);
if (!$landing) { http_response_code(404); exit('Лендинг не знайдено'); }

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('Розширення ZipArchive не підтримується на цьому сервері. Зверніться до хостера.');
}

$renderer = new Renderer();
$html = $renderer->renderLanding($landing);

$zipTmp = tempnam(sys_get_temp_dir(), 'Landiro CMS_') . '.zip';

$zip = new ZipArchive();
if ($zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('Не вдалося створити ZIP-архів');
}

// Rendered HTML page
$zip->addFromString('index.html', $html);

// Raw JSON data
$zip->addFromString('landing.json', json_encode($landing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

// Readme
$zip->addFromString('README.txt',
    "Експорт лендингу: " . $landing['title'] . "\n" .
    "Slug: " . $landing['slug'] . "\n" .
    "Дата: " . date('Y-m-d H:i') . "\n\n" .
    "Файли:\n" .
    "  index.html  — готовий HTML для хостингу\n" .
    "  landing.json — дані для імпорту назад до Landiro CMS\n" .
    "  uploads/    — завантажені зображення\n"
);

// Uploaded media
$uploadsDir = UPLOADS_PATH . '/' . $slug;
if (is_dir($uploadsDir)) {
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iter as $file) {
        if (!$file->isFile()) continue;
        $abs = str_replace('\\', '/', $file->getRealPath());
        $base = rtrim(str_replace('\\', '/', realpath($uploadsDir)), '/');
        $rel = 'uploads/' . ltrim(substr($abs, strlen($base)), '/');
        $zip->addFile($file->getRealPath(), $rel);
    }
}

$zip->close();

$filename = $slug . '-export-' . date('Ymd') . '.zip';
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipTmp));
header('Cache-Control: no-cache, no-store, must-revalidate');
readfile($zipTmp);
@unlink($zipTmp);
exit;
