<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Template.php';
require_once dirname(__DIR__) . '/core/FileManager.php';
require_once dirname(__DIR__) . '/core/Analytics.php';
require_once dirname(__DIR__) . '/core/OrderLog.php';
require_once dirname(__DIR__) . '/core/Telegram.php';
require_once dirname(__DIR__) . '/core/Presets.php';
require_once dirname(__DIR__) . '/core/LandingTemplates.php';
require_once dirname(__DIR__) . '/core/Webhook.php';

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!Auth::verifyCsrf($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$landingManager = new Landing();
$templateManager = new Template();
$fileManager = new FileManager();
$analyticsManager = new Analytics();
$orderLogManager  = new OrderLog();
$presetsManager   = new Presets();

function respond(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($action) {
    // ── LANDINGS ──────────────────────────────────────────
    case 'landing_list':
        respond(['success' => true, 'landings' => $landingManager->getAll()]);

    case 'landing_get':
        $slug = $input['slug'] ?? '';
        $landing = $landingManager->get($slug);
        if (!$landing) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        respond(['success' => true, 'landing' => $landing]);

    case 'landing_create':
        $title   = trim($input['title'] ?? '');
        $slug    = trim($input['slug'] ?? '');
        $tplId   = $input['template'] ?? 'blank';
        if (!$title) respond(['success' => false, 'error' => 'Вкажіть назву']);
        $landing = $landingManager->create($title, $slug);
        if ($tplId && $tplId !== 'blank') {
            $landing['sections'] = LandingTemplates::buildSections($tplId);
            $landing['sections_count'] = count($landing['sections']);
            $landingManager->save($landing);
        }
        respond(['success' => true, 'slug' => $landing['slug'], 'landing' => $landing]);

    case 'landing_templates':
        respond(['success' => true, 'templates' => LandingTemplates::getAll()]);

    case 'landing_save':
        $slug = $input['slug'] ?? '';
        $landing = $landingManager->get($slug);
        if (!$landing) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        $allowed = ['title', 'published', 'password', 'seo', 'global_styles', 'scripts', 'sections', 'popup', 'sticky_bar', 'success_url', 'floating_widget', 'webhook_url', 'countdown', 'social_proof'];
        foreach ($allowed as $key) {
            if (isset($input[$key])) $landing[$key] = $input[$key];
        }
        $ok = $landingManager->save($landing);
        respond(['success' => $ok]);

    case 'landing_delete':
        $slug = $input['slug'] ?? '';
        $ok = $landingManager->delete($slug);
        respond(['success' => $ok]);

    case 'landing_clone':
        $slug = $input['slug'] ?? '';
        $result = $landingManager->clone($slug);
        if (!$result) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        respond(['success' => true, 'slug' => $result['slug']]);

    case 'landing_restore':
        $slug = $input['slug'] ?? '';
        $versionFile = $input['version'] ?? '';
        // Only allow safe filename: digits + .json
        if (!preg_match('/^\d{14}\.json$/', $versionFile)) {
            respond(['success' => false, 'error' => 'Невалідний файл версії']);
        }
        $historyDir = LANDINGS_DATA_PATH . '/' . $slug . '_history';
        $vFile = $historyDir . '/' . $versionFile;
        if (!file_exists($vFile)) respond(['success' => false, 'error' => 'Версію не знайдено']);
        $vData = json_decode(file_get_contents($vFile), true);
        if (!$vData) respond(['success' => false, 'error' => 'Пошкоджений файл версії']);
        $vData['slug'] = $slug; // safety: keep current slug
        $ok = $landingManager->save($vData);
        respond(['success' => $ok]);

    case 'landing_bulk':
        $bulkAction = $input['bulk_action'] ?? '';
        $slugs      = array_filter((array)($input['slugs'] ?? []), function($s) { return preg_match('/^[a-z0-9-]+$/', $s); });
        if (!$slugs) respond(['success' => false, 'error' => 'Не вказано лендинги']);
        $count = 0;
        foreach ($slugs as $s) {
            $lnd = $landingManager->get($s);
            if (!$lnd) continue;
            if ($bulkAction === 'publish')   { $lnd['published'] = true;  $landingManager->save($lnd); }
            elseif ($bulkAction === 'unpublish') { $lnd['published'] = false; $landingManager->save($lnd); }
            elseif ($bulkAction === 'delete') { $landingManager->delete($s); }
            $count++;
        }
        respond(['success' => true, 'count' => $count]);

    case 'landing_publish':
        $slug = $input['slug'] ?? '';
        $landing = $landingManager->get($slug);
        if (!$landing) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        $landing['published'] = (bool)($input['published'] ?? true);
        $ok = $landingManager->save($landing);
        respond(['success' => $ok]);

    // ── SECTIONS ──────────────────────────────────────────
    case 'section_add':
        $slug = $input['slug'] ?? '';
        $type = $input['type'] ?? 'custom';
        $templateId = $input['template'] ?? '';
        $tmpl = $templateManager->get($type, $templateId);
        $section = [
            'type'     => $type,
            'template' => $templateId,
            'visible'  => true,
            'html'     => $tmpl['html'] ?? '<div class="section-placeholder"><p>Новa секція: ' . htmlspecialchars($type) . '</p></div>',
            'css'      => $tmpl['css'] ?? '',
            'js'       => $tmpl['js'] ?? '',
            'php'      => $tmpl['php'] ?? '',
            'data'     => ['vars' => []],
        ];
        // Ініціалізуємо CSS змінні з мети шаблону
        if ($tmpl && !empty($tmpl['vars'])) {
            foreach ($tmpl['vars'] as $varName => $varDef) {
                $section['data']['vars'][$varName] = $varDef['default'] ?? '';
            }
        }
        $result = $landingManager->addSection($slug, $section);
        if (!$result) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        respond(['success' => true, 'section' => $result]);

    case 'section_update':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $data = $input['data'] ?? [];
        $allowed = ['html', 'css', 'js', 'php', 'visible', 'data'];
        $updateData = array_intersect_key($data, array_flip($allowed));
        // Strip editor-injected attributes before saving (browser may send stale iframe HTML)
        if (!empty($updateData['html'])) {
            $h = $updateData['html'];
            $h = preg_replace('/\s*contenteditable="[^"]*"/', '', $h);
            $h = preg_replace('/\s*spellcheck="[^"]*"/', '', $h);
            $h = preg_replace('/\s*style="[^"]*(?:translate|rotate|scale|transform|opacity)\s*:[^"]*"/', '', $h);
            $updateData['html'] = $h;
        }
        $ok = $landingManager->updateSection($slug, $sectionId, $updateData);
        respond(['success' => $ok]);

    case 'section_delete':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $ok = $landingManager->deleteSection($slug, $sectionId);
        respond(['success' => $ok]);

    case 'section_reorder':
        $slug = $input['slug'] ?? '';
        $order = $input['order'] ?? [];
        $ok = $landingManager->reorderSections($slug, $order);
        respond(['success' => $ok]);

    case 'section_set_ab':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $abHtml = $input['ab_html'] ?? '';
        $ok = $landingManager->updateSection($slug, $sectionId, ['ab_html' => $abHtml]);
        respond(['success' => $ok]);

    case 'section_clear_ab':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $abLanding = $landingManager->get($slug);
        if (!$abLanding) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        foreach ($abLanding['sections'] as &$abSec) {
            if ($abSec['id'] === $sectionId) { unset($abSec['ab_html']); break; }
        }
        unset($abSec);
        respond(['success' => $landingManager->save($abLanding)]);

    case 'section_clone':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $cloned = $landingManager->cloneSection($slug, $sectionId);
        if (!$cloned) respond(['success' => false, 'error' => 'Секцію не знайдено']);
        respond(['success' => true, 'section' => $cloned]);

    case 'section_toggle':
        $slug = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $landing = $landingManager->get($slug);
        if (!$landing) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        foreach ($landing['sections'] as &$s) {
            if ($s['id'] === $sectionId) {
                $s['visible'] = !($s['visible'] ?? true);
                $landingManager->save($landing);
                respond(['success' => true, 'visible' => $s['visible']]);
            }
        }
        respond(['success' => false, 'error' => 'Секцію не знайдено']);

    // ── TEMPLATES ─────────────────────────────────────────
    case 'templates_list':
        $type = $input['type'] ?? null;
        respond(['success' => true, 'templates' => $templateManager->getAll($type)]);

    case 'templates_by_type':
        $type = $input['type'] ?? '';
        respond(['success' => true, 'templates' => $templateManager->getAll($type), 'types' => Template::$SECTION_TYPES]);

    case 'template_get':
        $type = $input['type'] ?? '';
        $id = $input['id'] ?? '';
        $tmpl = $templateManager->get($type, $id);
        if (!$tmpl) respond(['success' => false, 'error' => 'Шаблон не знайдено']);
        respond(['success' => true, 'template' => $tmpl]);

    case 'template_upload':
        if (empty($_FILES['zip'])) respond(['success' => false, 'error' => 'Файл не завантажено']);
        $result = $templateManager->installFromZip($_FILES['zip']['tmp_name']);
        respond($result);

    // ── FILES ─────────────────────────────────────────────
    case 'file_upload':
        $slug = $input['slug'] ?? $_POST['slug'] ?? '';
        if (empty($_FILES['file'])) respond(['success' => false, 'error' => 'Файл не завантажено']);
        $result = $fileManager->upload($_FILES['file'], $slug);
        respond($result);

    // ── ANALYTICS ─────────────────────────────────────────
    case 'landing_stats':
        $slug = $input['slug'] ?? '';
        if ($slug) {
            respond(['success' => true, 'stats' => $analyticsManager->getStats($slug)]);
        }
        respond(['success' => true, 'stats' => $analyticsManager->getAllStats()]);

    // ── ORDERS ────────────────────────────────────────────
    case 'order_delete':
        $slug    = $input['slug'] ?? '';
        $orderId = $input['order_id'] ?? '';
        respond(['success' => $orderLogManager->delete($slug, $orderId)]);

    case 'order_status':
        $slug    = $input['slug'] ?? '';
        $orderId = $input['order_id'] ?? '';
        $status  = $input['status'] ?? '';
        respond(['success' => $orderLogManager->setStatus($slug, $orderId, $status)]);

    case 'order_note':
        $slug    = $input['slug'] ?? '';
        $orderId = $input['order_id'] ?? '';
        $note    = $input['note'] ?? '';
        respond(['success' => $orderLogManager->setNote($slug, $orderId, $note)]);

    case 'order_price':
        $slug    = $input['slug'] ?? '';
        $orderId = $input['order_id'] ?? '';
        $price   = (float)($input['price'] ?? 0);
        respond(['success' => $orderLogManager->setPrice($slug, $orderId, $price)]);

    case 'order_revenue':
        $slug = $input['slug'] ?? '';
        if ($slug) respond(['success' => true, 'revenue' => $orderLogManager->getRevenueSummary($slug)]);
        respond(['success' => true, 'revenue' => $orderLogManager->getAllRevenue()]);

    case 'order_counts':
        respond(['success' => true, 'counts' => $orderLogManager->getAllCounts()]);

    case 'file_delete':
        $slug = $input['slug'] ?? $_POST['slug'] ?? '';
        $file = $input['file'] ?? '';
        if (!$slug || !$file) respond(['success' => false, 'error' => 'Не вказано файл']);
        // Security: no path traversal
        $file = basename($file);
        $path = UPLOADS_PATH . '/' . $slug . '/' . $file;
        if (!file_exists($path)) respond(['success' => false, 'error' => 'Файл не знайдено']);
        respond(['success' => @unlink($path)]);

    case 'analytics_reset':
        $slug = $input['slug'] ?? '';
        if (!$slug) respond(['success' => false, 'error' => 'Не вказано slug']);
        // Reset by overwriting with zeroes
        $file = DATA_PATH . '/analytics/' . $slug . '.json';
        file_put_contents($file, json_encode(['views' => 0, 'orders' => 0, 'daily' => []], JSON_PRETTY_PRINT));
        respond(['success' => true]);

    // ── PRESETS ───────────────────────────────────────────────
    case 'preset_save':
        $slug      = $input['slug'] ?? '';
        $sectionId = $input['section_id'] ?? '';
        $name      = trim($input['name'] ?? '');
        if (!$name) respond(['success' => false, 'error' => 'Вкажіть назву пресету']);
        $lnd = $landingManager->get($slug);
        if (!$lnd) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        $sec = null;
        foreach ($lnd['sections'] as $s) { if ($s['id'] === $sectionId) { $sec = $s; break; } }
        if (!$sec) respond(['success' => false, 'error' => 'Секцію не знайдено']);
        $id = $presetsManager->save($name, $sec);
        respond(['success' => true, 'id' => $id]);

    case 'preset_list':
        respond(['success' => true, 'presets' => $presetsManager->getAll()]);

    case 'preset_delete':
        $id = $input['id'] ?? '';
        respond(['success' => $presetsManager->delete($id)]);

    case 'preset_use':
        $slug     = $input['slug'] ?? '';
        $presetId = $input['preset_id'] ?? '';
        $preset   = $presetsManager->get($presetId);
        if (!$preset) respond(['success' => false, 'error' => 'Пресет не знайдено']);
        $sec = $preset['section'];
        $sec['type']     = $preset['type'];
        $sec['template'] = $preset['template'] ?? '';
        $sec['visible']  = true;
        $result = $landingManager->addSection($slug, $sec);
        if (!$result) respond(['success' => false, 'error' => 'Лендинг не знайдено']);
        respond(['success' => true, 'section' => $result]);

    // ── SETTINGS ──────────────────────────────────────────────
    case 'settings_get':
        respond(['success' => true, 'settings' => Settings::all()]);

    case 'settings_save':
        $allowed = ['telegram_token', 'telegram_chat_id', 'telegram_enabled', 'email_to', 'email_enabled', 'webhook_url', 'homepage_slug'];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $input)) Settings::set($k, $input[$k]);
        }
        respond(['success' => Settings::save()]);

    case 'webhook_test':
        $url = $input['url'] ?? Settings::get('webhook_url', '');
        if (!$url) respond(['success' => false, 'error' => 'Webhook URL не вказано']);
        $ok = Webhook::fire($url, ['event' => 'test', 'source' => 'Landiro CMS', 'time' => date('c')]);
        respond(['success' => $ok, 'error' => $ok ? null : 'Не вдалося відправити. Перевірте URL.']);

    case 'telegram_test':
        $tg = Telegram::fromSettings();
        if (!$tg->isConfigured()) respond(['success' => false, 'error' => 'Telegram не налаштовано. Спочатку збережіть токен та Chat ID.']);
        $ok = $tg->testSend();
        respond(['success' => $ok, 'error' => $ok ? null : 'Не вдалося відправити. Перевірте токен та Chat ID.']);

    // ── SECTION TYPES ─────────────────────────────────────
    case 'section_types':
        respond(['success' => true, 'types' => Template::$SECTION_TYPES]);

    default:
        respond(['success' => false, 'error' => 'Невідома дія: ' . htmlspecialchars($action)]);
}
