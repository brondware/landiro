<?php
// Публічний ендпоінт — повертає лічильник завантажень без авторизації
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$countFile = DATA_PATH . '/downloads.json';
$data = file_exists($countFile) ? json_decode(file_get_contents($countFile), true) : [];
echo json_encode(['count' => (int)($data['count'] ?? 0)]);
