<?php
/**
 * Landiro CMS — Installer
 * After installation delete the /install/ folder or this file.
 */

define('SETUP_ROOT', dirname(__DIR__));
define('SETUP_VERSION', '1.0.2');

// ── Already installed? ────────────────────────────────────────────────────────
$configExists = file_exists(SETUP_ROOT . '/config.php');

// ── Requirements check ────────────────────────────────────────────────────────
$reqs = [
    'PHP ≥ 8.1'        => version_compare(PHP_VERSION, '8.1.0', '>='),
    'ZipArchive'        => extension_loaded('zip'),
    'JSON'              => extension_loaded('json'),
    'Writable root'     => is_writable(SETUP_ROOT),
    'Writable data/'    => is_writable(SETUP_ROOT . '/data') || !is_dir(SETUP_ROOT . '/data'),
];
$reqsFail = array_filter($reqs, function($v) { return !$v; });

// ── Handle POST ───────────────────────────────────────────────────────────────
$errors  = [];
$success = false;
$values  = [
    'admin_login'       => 'admin',
    'update_check_url'  => 'https://landiro.com/community/api/releases.php',
    'news_api_url'      => 'https://landiro.com/community/api/news.php',
    'library_api_url'   => 'https://landiro.com/community/api/library.php',
];

if (!$configExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['admin_login']    ?? '');
    $pass     = $_POST['admin_password']      ?? '';
    $pass2    = $_POST['admin_password2']     ?? '';
    $updUrl   = trim($_POST['update_check_url']  ?? '');
    $newsUrl  = trim($_POST['news_api_url']       ?? '');
    $libUrl   = trim($_POST['library_api_url']    ?? '');

    $values = compact('login', 'updUrl', 'newsUrl', 'libUrl');
    $values['admin_login']      = $login;
    $values['update_check_url'] = $updUrl;
    $values['news_api_url']     = $newsUrl;
    $values['library_api_url']  = $libUrl;

    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $login)) {
        $errors['admin_login'] = 'Логін: 3–32 символи, лише латиниця, цифри, _';
    }
    if (strlen($pass) < 8) {
        $errors['admin_password'] = 'Пароль мінімум 8 символів';
    } elseif ($pass !== $pass2) {
        $errors['admin_password2'] = 'Паролі не збігаються';
    }

    if (!$errors) {
        $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

        // Build config.php from sample
        $sample = file_get_contents(__DIR__ . '/config.sample.php');
        $config = str_replace(
            ['%%CMS_VERSION%%', '%%UPDATE_CHECK_URL%%', '%%NEWS_API_URL%%', '%%LIBRARY_API_URL%%', '%%ADMIN_LOGIN%%', '%%ADMIN_PASSWORD%%'],
            [SETUP_VERSION,     $updUrl,                $newsUrl,            $libUrl,               $login,           $hash],
            $sample
        );

        if (file_put_contents(SETUP_ROOT . '/config.php', $config) === false) {
            $errors['_'] = 'Не вдалося записати config.php — перевірте права на запис у кореневій папці';
        } else {
            // Create required directories
            $dirs = [
                SETUP_ROOT . '/data',
                SETUP_ROOT . '/data/landings',
                SETUP_ROOT . '/data/uploads',
                SETUP_ROOT . '/data/orders',
                SETUP_ROOT . '/data/analytics',
                SETUP_ROOT . '/data/presets',
            ];
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
            }
            // Place .htaccess to protect data/
            $dataHtaccess = SETUP_ROOT . '/data/.htaccess';
            if (!file_exists($dataHtaccess)) {
                file_put_contents($dataHtaccess, "Options -Indexes\nDeny from all\n");
            }
            $uploadsHtaccess = SETUP_ROOT . '/data/uploads/.htaccess';
            if (!file_exists($uploadsHtaccess)) {
                file_put_contents($uploadsHtaccess, "Options -Indexes\nAllow from all\n");
            }
            $success = true;
        }
    }
}

// ── HTML ──────────────────────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Встановлення Landiro CMS</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Inter, system-ui, sans-serif; background: #f1f5f9; color: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
.card { background: #fff; border-radius: 16px; padding: 40px; width: 100%; max-width: 560px; box-shadow: 0 4px 32px rgba(0,0,0,.10); }
.logo { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
.logo-icon { width: 44px; height: 44px; background: #6366f1; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.logo-icon svg { stroke: #fff; }
.logo-text { font-size: 20px; font-weight: 700; }
.logo-ver  { font-size: 12px; color: #64748b; margin-top: 1px; }
h2 { font-size: 17px; font-weight: 700; margin-bottom: 20px; }
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #374151; }
.field input { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 9px 12px; font-size: 14px; outline: none; transition: border-color .15s; color: #0f172a; background: #fff; }
.field input:focus { border-color: #6366f1; }
.field .hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.err  { font-size: 12px; color: #dc2626; margin-top: 4px; }
.sep  { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
.btn  { width: 100%; background: #6366f1; color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s; }
.btn:hover { background: #4f46e5; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.req-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 24px; }
.req-item { display: flex; align-items: center; gap: 10px; font-size: 13px; }
.req-ok   { color: #16a34a; font-size: 16px; }
.req-fail { color: #dc2626; font-size: 16px; }
.alert { border-radius: 10px; padding: 14px 18px; font-size: 14px; margin-bottom: 20px; }
.alert-err     { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.success-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }
.btn-outline { background: none; border: 1.5px solid #6366f1; color: #6366f1; border-radius: 8px; padding: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: .15s; text-decoration: none; display: block; text-align: center; }
.btn-outline:hover { background: #eef2ff; }
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke-width="2.5">
        <rect x="3" y="3" width="18" height="5" rx="1"/><rect x="3" y="11" width="8" height="10" rx="1"/><rect x="14" y="11" width="7" height="10" rx="1"/>
      </svg>
    </div>
    <div>
      <div class="logo-text">Landiro CMS</div>
      <div class="logo-ver">Версія <?= SETUP_VERSION ?> — Інсталятор</div>
    </div>
  </div>

<?php if ($success): ?>
  <!-- ── SUCCESS ── -->
  <div class="alert alert-success">
    <strong>✅ Встановлення завершено!</strong><br>
    CMS готова до роботи.
  </div>
  <div class="alert alert-info" style="font-size:13px">
    ⚠️ <strong>Видаліть папку <code>install/</code></strong> з сервера — вона більше не потрібна і може становити ризик безпеки.
  </div>
  <div class="success-actions">
    <a href="../admin/" class="btn" style="text-decoration:none;display:block;text-align:center">Відкрити адмін-панель →</a>
    <a href="../" class="btn-outline">На головну</a>
  </div>

<?php elseif ($configExists): ?>
  <!-- ── ALREADY INSTALLED ── -->
  <div class="alert alert-info">
    <strong>ℹ️ CMS вже встановлена.</strong><br>
    Файл <code>config.php</code> вже існує. Якщо хочете перевстановити — видаліть <code>config.php</code> вручну.
  </div>
  <div class="success-actions">
    <a href="../admin/" class="btn" style="text-decoration:none;display:block;text-align:center">Відкрити адмін-панель →</a>
  </div>

<?php elseif ($reqsFail): ?>
  <!-- ── REQUIREMENTS FAILED ── -->
  <h2>Перевірка вимог</h2>
  <div class="req-list">
    <?php foreach ($reqs as $name => $ok): ?>
    <div class="req-item">
      <span class="<?= $ok ? 'req-ok' : 'req-fail' ?>"><?= $ok ? '✓' : '✗' ?></span>
      <span style="<?= $ok ? '' : 'color:#dc2626;font-weight:600' ?>"><?= $name ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="alert alert-err">Виправте помилки вище та оновіть сторінку.</div>

<?php else: ?>
  <!-- ── INSTALL FORM ── -->
  <h2>Нова установка</h2>

  <?php if (!empty($errors['_'])): ?>
  <div class="alert alert-err"><?= htmlspecialchars($errors['_']) ?></div>
  <?php endif; ?>

  <div class="req-list" style="margin-bottom:20px">
    <?php foreach ($reqs as $name => $ok): ?>
    <div class="req-item">
      <span class="req-ok">✓</span>
      <span style="color:#16a34a"><?= $name ?></span>
    </div>
    <?php endforeach; ?>
  </div>

  <form method="POST" autocomplete="off">

    <h2 style="margin-bottom:16px">Адміністратор</h2>

    <div class="field">
      <label>Логін адміністратора</label>
      <input type="text" name="admin_login" value="<?= htmlspecialchars($values['admin_login']) ?>" required>
      <?php if ($e = $errors['admin_login'] ?? ''): ?><div class="err"><?= $e ?></div><?php endif; ?>
    </div>

    <div class="field">
      <label>Пароль</label>
      <input type="password" name="admin_password" required autocomplete="new-password">
      <?php if ($e = $errors['admin_password'] ?? ''): ?><div class="err"><?= $e ?></div><?php endif; ?>
      <div class="hint">Мінімум 8 символів</div>
    </div>

    <div class="field">
      <label>Повторіть пароль</label>
      <input type="password" name="admin_password2" required autocomplete="new-password">
      <?php if ($e = $errors['admin_password2'] ?? ''): ?><div class="err"><?= $e ?></div><?php endif; ?>
    </div>

    <hr class="sep">
    <h2 style="margin-bottom:4px">Community сервер <span style="font-size:12px;font-weight:400;color:#94a3b8">(необов'язково)</span></h2>
    <p style="font-size:12px;color:#64748b;margin-bottom:16px">Залиште порожніми якщо не використовуєте Landiro Community</p>

    <div class="field">
      <label>URL перевірки оновлень</label>
      <input type="url" name="update_check_url" value="<?= htmlspecialchars($values['update_check_url']) ?>" placeholder="https://...">
    </div>

    <div class="field">
      <label>API новин</label>
      <input type="url" name="news_api_url" value="<?= htmlspecialchars($values['news_api_url']) ?>" placeholder="https://...">
    </div>

    <div class="field">
      <label>API бібліотеки шаблонів</label>
      <input type="url" name="library_api_url" value="<?= htmlspecialchars($values['library_api_url']) ?>" placeholder="https://...">
    </div>

    <hr class="sep">
    <button type="submit" class="btn">Встановити Landiro CMS →</button>
  </form>

<?php endif; ?>
</div>
</body>
</html>
