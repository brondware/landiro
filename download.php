<?php
// Відстежує завантаження і редиректить на GitHub ZIP
require_once __DIR__ . '/config.php';

$countFile = DATA_PATH . '/downloads.json';
$data = [];
if (file_exists($countFile)) {
    $data = json_decode(file_get_contents($countFile), true) ?: [];
}
$data['count'] = ($data['count'] ?? 0) + 1;
$data['last']  = date('Y-m-d H:i:s');
file_put_contents($countFile, json_encode($data, JSON_UNESCAPED_UNICODE));

header('Location: https://github.com/brondware/landiro/archive/refs/heads/main.zip');
exit;
