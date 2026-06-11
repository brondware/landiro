<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Renderer.php';
require_once dirname(__DIR__) . '/core/Analytics.php';
require_once dirname(__DIR__) . '/core/OrderLog.php';
require_once dirname(__DIR__) . '/core/Telegram.php';
require_once dirname(__DIR__) . '/core/Mailer.php';
require_once dirname(__DIR__) . '/core/Webhook.php';

// Slug може бути переданий від index.php (homepage) або розпарсений з URL
if (!empty($_homepage_slug)) {
    $slug = $_homepage_slug;
} else {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath   = parse_url(LANDINGS_URL, PHP_URL_PATH);
    $path       = substr($requestUri, strlen($basePath));
    $path       = trim($path, '/');
    $slug       = strtok($path, '/') ?: '';
}

if (!$slug) {
    http_response_code(404);
    echo '<h1>404 — Лендинг не знайдено</h1>';
    exit;
}

$landingManager = new Landing();
$landing = $landingManager->get($slug);

if (!$landing) {
    http_response_code(404);
    echo '<h1>404 — Лендинг не знайдено</h1>';
    exit;
}

// Перевірка пароля
if (!empty($landing['password'])) {
    $sessionKey = 'landing_pass_' . $slug;
    if (!isset($_SESSION[$sessionKey])) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
            if ($_POST['pass'] === $landing['password']) {
                $_SESSION[$sessionKey] = true;
            } else {
                $passError = 'Невірний пароль';
            }
        }
        if (!isset($_SESSION[$sessionKey])) {
            showPasswordForm($landing['title'], $passError ?? '');
            exit;
        }
    }
}

if (!$landing['published']) {
    http_response_code(403);
    echo '<h1>Лендинг ще не опубліковано</h1>';
    exit;
}

$analytics = new Analytics();
$orderLog  = new OrderLog();

// Обробка AJAX замовлень з форм секцій
$isCmsForm = isset($_SERVER['HTTP_X_CMS_FORM']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isCmsForm) {
    header('Content-Type: application/json; charset=utf-8');
    $rawInput = file_get_contents('php://input');
    $postData = json_decode($rawInput, true) ?? $_POST;

    // Honeypot: якщо поле _hp заповнене — це бот, повертаємо фейковий успіх
    if (!empty($postData['_hp'])) {
        echo json_encode(['success' => true, 'message' => 'Дякуємо! Ми зв\'яжемося з вами.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Rate limiting: не більше 1 замовлення на 60 секунд з одного сеансу
    $rateKey = 'cms_rate_' . $slug;
    $lastOrder = $_SESSION[$rateKey] ?? 0;
    if (time() - $lastOrder < 60) {
        echo json_encode(['success' => false, 'message' => 'Зачекайте хвилину перед повторним відправленням.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $response = ['success' => false, 'message' => 'Обробник не налаштований'];

    foreach ($landing['sections'] as $section) {
        if ($section['type'] !== 'order-form' || empty($section['php'])) continue;

        $phpCode = $section['php'];

        // Перевірка на заборонені функції
        $forbidden = ['exec', 'system', 'shell_exec', 'passthru', 'popen', 'proc_open',
                      'eval', 'assert', 'preg_replace_callback', 'create_function',
                      'pcntl_', 'posix_', 'dl('];
        foreach ($forbidden as $fn) {
            if (stripos($phpCode, $fn) !== false) {
                $response = ['success' => false, 'message' => 'Заборонені функції в обробнику'];
                break 2;
            }
        }

        // Записуємо у тимчасовий файл і підключаємо — безпечніше ніж eval()
        $tmpFile = tempnam(sys_get_temp_dir(), 'cms_handler_') . '.php';
        try {
            file_put_contents($tmpFile, "<?php\n" . $phpCode . "\n");
            require $tmpFile;
            if (function_exists('handleOrderForm')) {
                $response = handleOrderForm($postData);
            }
        } catch (\Throwable $e) {
            $response = ['success' => false, 'message' => 'Помилка в обробнику форми'];
        } finally {
            if (file_exists($tmpFile)) @unlink($tmpFile);
        }

        if (!empty($response['success'])) {
            $_SESSION[$rateKey] = time();
            $analytics->order($slug);
            // Determine which A/B variant the user saw
            $abVariantSeen = '';
            if (!empty($postData['_ab_variants']) && is_array($postData['_ab_variants'])) {
                $abVariantSeen = implode(',', array_map(
                    fn($k, $v) => $k . ':' . $v,
                    array_keys($postData['_ab_variants']),
                    array_values($postData['_ab_variants'])
                ));
            }
            // Log the order (always, before handler failures)
            $savedOrder = $orderLog->save($slug, $postData, $abVariantSeen);
            // Notifications (non-blocking)
            if ($savedOrder) {
                if (Settings::get('telegram_enabled')) {
                    @(Telegram::fromSettings())->sendOrder($landing['title'] ?? $slug, $slug, $savedOrder);
                }
                @Mailer::sendOrder($landing['title'] ?? $slug, $savedOrder);
                @Webhook::sendOrder($landing['title'] ?? $slug, $slug, $savedOrder, $landing['webhook_url'] ?? '');
            }
            // Track A/B conversion if variant info is present
            if (!empty($postData['_ab_variants']) && is_array($postData['_ab_variants'])) {
                foreach ($postData['_ab_variants'] as $shortId => $v) {
                    // Match short ID back to full section ID
                    foreach ($landing['sections'] as $s) {
                        if (!empty($s['ab_html']) && substr(str_replace('-', '', $s['id']), 0, 8) === $shortId) {
                            $analytics->abConvert($slug, $s['id'], $v === 'b' ? 'b' : 'a');
                            break;
                        }
                    }
                }
            }
        }
        break;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Лічильник переглядів (тільки для реальних GET запитів)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $analytics->hit($slug);

    // A/B variant split: для секцій з ab_html робимо 50/50 через cookie
    foreach ($landing['sections'] as &$sec) {
        if (empty($sec['ab_html'])) continue;
        $cKey  = '_ab_' . substr(str_replace('-', '', $sec['id']), 0, 8);
        $variant = isset($_COOKIE[$cKey]) && $_COOKIE[$cKey] === 'b' ? 'b' : 'a';
        if (!isset($_COOKIE[$cKey])) {
            $variant = mt_rand(0, 1) ? 'b' : 'a';
            setcookie($cKey, $variant, time() + 7 * 86400, '/');
        }
        if ($variant === 'b') {
            $sec['html'] = $sec['ab_html'];
        }
        $analytics->abHit($slug, $sec['id'], $variant);
    }
    unset($sec);
}

$renderer = new Renderer();
echo $renderer->renderLanding($landing);

function showPasswordForm(string $title, string $error = ''): void {
    echo '<!DOCTYPE html><html lang="uk"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . htmlspecialchars($title) . '</title>
    <style>body{background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;margin:0}
    .card{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:360px;width:90%}
    h2{margin:0 0 20px;font-size:20px}
    input{width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:11px 14px;font-size:15px;box-sizing:border-box;margin-bottom:12px}
    button{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;cursor:pointer}
    .err{color:#dc2626;font-size:13px;margin-bottom:12px}</style>
    </head><body><div class="card"><h2>🔒 Захищений лендинг</h2>';
    if ($error) echo '<p class="err">' . htmlspecialchars($error) . '</p>';
    echo '<form method="POST"><input type="password" name="pass" placeholder="Введіть пароль" autofocus required><button type="submit">Відкрити</button></form></div></body></html>';
}
