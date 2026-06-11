<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Settings.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_pass'] ?? '';
        $new1    = $_POST['new_pass'] ?? '';
        $new2    = $_POST['new_pass2'] ?? '';

        if (!$current || !$new1 || !$new2) {
            $error = 'Заповніть усі поля';
        } elseif (!password_verify($current, ADMIN_PASSWORD)) {
            $error = 'Невірний поточний пароль';
        } elseif ($new1 !== $new2) {
            $error = 'Паролі не збігаються';
        } elseif (strlen($new1) < 6) {
            $error = 'Новий пароль повинен мати мінімум 6 символів';
        } else {
            $hash = password_hash($new1, PASSWORD_BCRYPT, ['cost' => 12]);
            $configFile = dirname(__DIR__) . '/config.php';
            $lines = file($configFile, FILE_IGNORE_NEW_LINES);
            foreach ($lines as &$line) {
                if (strpos($line, "define('ADMIN_PASSWORD'") !== false) {
                    $line = "define('ADMIN_PASSWORD', '" . $hash . "'); // оновлено " . date('Y-m-d H:i');
                }
            }
            unset($line);
            if (file_put_contents($configFile, implode("\n", $lines)) !== false) {
                $success = 'Пароль успішно змінено';
            } else {
                $error = 'Не вдалося записати config.php. Перевірте права доступу.';
            }
        }
    }

    if ($action === 'change_login') {
        $current = $_POST['current_pass'] ?? '';
        $newLogin = trim($_POST['new_login'] ?? '');

        if (!$current || !$newLogin) {
            $error = 'Заповніть усі поля';
        } elseif (!password_verify($current, ADMIN_PASSWORD)) {
            $error = 'Невірний поточний пароль';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $newLogin)) {
            $error = 'Логін: тільки латиниця, цифри, підкреслення (3–32 символи)';
        } else {
            $configFile = dirname(__DIR__) . '/config.php';
            $lines = file($configFile, FILE_IGNORE_NEW_LINES);
            foreach ($lines as &$line) {
                if (strpos($line, "define('ADMIN_LOGIN'") !== false) {
                    $line = "define('ADMIN_LOGIN', '" . $newLogin . "');";
                }
            }
            unset($line);
            if (file_put_contents($configFile, implode("\n", $lines)) !== false) {
                $success = 'Логін змінено на "' . htmlspecialchars($newLogin) . '"';
            } else {
                $error = 'Не вдалося записати config.php. Перевірте права доступу.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Налаштування — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.settings-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 14px; padding: 28px; max-width: 480px; margin-bottom: 20px; }
.settings-card h2 { font-size: 15px; font-weight: 700; margin: 0 0 20px; }
.alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.info-row { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: var(--c-bg); border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
.info-row strong { color: var(--c-text); }
.info-row span { color: var(--c-muted); }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Налаштування</h1>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="info-row">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <span>Поточний логін:</span>
      <strong><?= htmlspecialchars(ADMIN_LOGIN) ?></strong>
    </div>

    <!-- Change Password -->
    <div class="settings-card">
      <h2>Змінити пароль</h2>
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-field">
          <label>Поточний пароль</label>
          <input type="password" name="current_pass" required autocomplete="current-password">
        </div>
        <div class="form-field">
          <label>Новий пароль</label>
          <input type="password" name="new_pass" required minlength="6" autocomplete="new-password">
        </div>
        <div class="form-field">
          <label>Підтвердіть новий пароль</label>
          <input type="password" name="new_pass2" required minlength="6" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Змінити пароль</button>
      </form>
    </div>

    <!-- Change Login -->
    <div class="settings-card">
      <h2>Змінити логін</h2>
      <form method="POST">
        <input type="hidden" name="action" value="change_login">
        <div class="form-field">
          <label>Поточний пароль (для підтвердження)</label>
          <input type="password" name="current_pass" required autocomplete="current-password">
        </div>
        <div class="form-field">
          <label>Новий логін</label>
          <input type="text" name="new_login" pattern="[a-zA-Z0-9_]{3,32}" required placeholder="admin" value="<?= htmlspecialchars(ADMIN_LOGIN) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Змінити логін</button>
      </form>
    </div>

    <!-- Global Webhook -->
    <div class="settings-card">
      <h2>Глобальний Webhook</h2>
      <p style="font-size:13px;color:var(--c-muted);margin:-8px 0 18px">
        Надсилається при кожному замовленні з будь-якого лендингу (якщо у лендингу не вказано власний URL).
        Сумісний з <strong>Zapier</strong>, <strong>Make</strong>, <strong>n8n</strong> та будь-яким POST-ендпоінтом.
      </p>
      <div class="form-field">
        <label>Webhook URL</label>
        <input type="url" id="global_webhook_url" placeholder="https://hooks.zapier.com/hooks/catch/..." value="<?= htmlspecialchars(Settings::get('webhook_url', '')) ?>">
      </div>
      <div style="display:flex;gap:8px;margin-top:4px">
        <button class="btn btn-primary" onclick="saveWebhook()">Зберегти</button>
        <button class="btn btn-ghost" onclick="testGlobalWebhook()">Надіслати тест</button>
      </div>
      <p id="webhook-status" style="font-size:13px;margin-top:10px;display:none"></p>
    </div>

    <!-- Email Notifications -->
    <div class="settings-card">
      <h2>Email-сповіщення</h2>
      <p style="font-size:13px;color:var(--c-muted);margin:-8px 0 18px">
        Сповіщення надсилаються через PHP <code>mail()</code>. Переконайтесь що на сервері налаштований sendmail.
      </p>
      <div class="form-field">
        <label>Email отримувача</label>
        <input type="email" id="email_to" placeholder="you@example.com" value="<?= htmlspecialchars(Settings::get('email_to', '')) ?>">
      </div>
      <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="email_enabled" <?= Settings::get('email_enabled') ? 'checked' : '' ?> style="width:16px;height:16px">
          Увімкнути email-сповіщення
        </label>
      </div>
      <button class="btn btn-primary" onclick="saveEmail()" style="margin-top:4px">Зберегти</button>
      <p id="email-status" style="font-size:13px;margin-top:10px;display:none"></p>
    </div>

    <!-- Telegram Notifications -->
    <div class="settings-card">
      <h2>Telegram-сповіщення</h2>
      <p style="font-size:13px;color:var(--c-muted);margin:-8px 0 18px">
        При кожному новому замовленні буде надходити повідомлення у Telegram.
        <a href="https://t.me/BotFather" target="_blank" style="color:var(--c-primary)">Створити бота →</a>
      </p>
      <div class="form-field">
        <label>Bot Token</label>
        <input type="text" id="tg_token" placeholder="1234567890:AAF..." value="<?= htmlspecialchars(Settings::get('telegram_token', '')) ?>">
      </div>
      <div class="form-field">
        <label>Chat ID <span style="color:var(--c-muted);font-weight:400">(ваш або групи — отримайте через @userinfobot)</span></label>
        <input type="text" id="tg_chat" placeholder="-100123456789" value="<?= htmlspecialchars(Settings::get('telegram_chat_id', '')) ?>">
      </div>
      <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="checkbox" id="tg_enabled" <?= Settings::get('telegram_enabled') ? 'checked' : '' ?> style="width:16px;height:16px">
          Увімкнути сповіщення
        </label>
      </div>
      <div style="display:flex;gap:10px;margin-top:4px">
        <button class="btn btn-primary" onclick="saveTelegram()">Зберегти</button>
        <button class="btn btn-ghost" onclick="testTelegram()">Надіслати тест</button>
      </div>
      <p id="tg-status" style="font-size:13px;margin-top:10px;display:none"></p>
    </div>

    <!-- System Info -->
    <div class="settings-card">
      <h2>Інформація</h2>
      <table style="width:100%;font-size:13px;border-collapse:collapse">
        <?php foreach ([
            'CMS версія'    => CMS_VERSION,
            'PHP версія'    => PHP_VERSION,
            'Базовий URL'   => BASE_URL,
            'Папка даних'   => DATA_PATH,
        ] as $label => $val): ?>
        <tr>
          <td style="padding:6px 0;color:var(--c-muted);width:140px"><?= $label ?></td>
          <td style="padding:6px 0;font-family:monospace;font-size:12px"><?= htmlspecialchars($val) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>

    <!-- Homepage Landing -->
    <?php
    $homepageManager = new Landing();
    $allLandings     = $homepageManager->getAll();
    $currentHome     = Settings::get('homepage_slug', '');
    ?>
    <div class="settings-card">
      <h2>Головна сторінка</h2>
      <p style="font-size:13px;color:var(--c-muted);margin:-8px 0 18px">
        Обраний лендинг відображатиметься замість стандартної головної сторінки сайту
        (<code><?= htmlspecialchars(BASE_URL) ?>/</code>).
        Якщо не обрано — відкривається адмін-панель або сторінка входу.
      </p>
      <div class="form-field">
        <label>Лендинг для головної сторінки</label>
        <select id="homepage_slug" style="width:100%">
          <option value="">— не встановлено —</option>
          <?php foreach ($allLandings as $lp): ?>
          <option value="<?= htmlspecialchars($lp['slug']) ?>"
            <?= $lp['slug'] === $currentHome ? 'selected' : '' ?>>
            <?= htmlspecialchars($lp['title'] ?: $lp['slug']) ?>
            <?= $lp['published'] ? '' : ' (не опубліковано)' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($currentHome): ?>
      <p style="font-size:12px;color:var(--c-muted);margin:-4px 0 14px">
        Поточна головна:
        <a href="<?= htmlspecialchars(BASE_URL) ?>/" target="_blank" style="color:#6366f1"><?= htmlspecialchars(BASE_URL) ?>/</a>
      </p>
      <?php endif; ?>
      <div style="display:flex;gap:8px">
        <button class="btn btn-primary" onclick="saveHomepage()">Зберегти</button>
        <?php if ($currentHome): ?>
        <button class="btn btn-ghost" onclick="clearHomepage()">Скинути</button>
        <?php endif; ?>
      </div>
      <p id="homepage-status" style="font-size:13px;margin-top:10px;display:none"></p>
    </div>

  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;

async function saveWebhook() {
  const url = document.getElementById('global_webhook_url').value.trim();
  const res = await api('settings_save', { webhook_url: url });
  const el = document.getElementById('webhook-status');
  el.style.display = 'block';
  el.style.color = res.success ? '#15803d' : '#dc2626';
  el.textContent  = res.success ? '✓ Збережено' : '✗ Помилка';
}

async function testGlobalWebhook() {
  const url = document.getElementById('global_webhook_url').value.trim();
  const el  = document.getElementById('webhook-status');
  el.style.display = 'block'; el.style.color = '#64748b'; el.textContent = 'Надсилаємо...';
  const res = await api('webhook_test', { url });
  el.style.color = res.success ? '#15803d' : '#dc2626';
  el.textContent  = res.success ? '✓ Тест надіслано!' : '✗ ' + (res.error || 'Помилка');
}

async function saveEmail() {
  const res = await api('settings_save', {
    email_to:      document.getElementById('email_to').value.trim(),
    email_enabled: document.getElementById('email_enabled').checked,
  });
  const el = document.getElementById('email-status');
  el.style.display = 'block';
  el.style.color = res.success ? '#15803d' : '#dc2626';
  el.textContent  = res.success ? '✓ Налаштування збережено' : '✗ Помилка збереження';
}

async function saveTelegram() {
  const res = await api('settings_save', {
    telegram_token:  document.getElementById('tg_token').value.trim(),
    telegram_chat_id: document.getElementById('tg_chat').value.trim(),
    telegram_enabled: document.getElementById('tg_enabled').checked,
  });
  const el = document.getElementById('tg-status');
  el.style.display = 'block';
  if (res.success) {
    el.style.color = '#15803d';
    el.textContent = '✓ Налаштування збережено';
  } else {
    el.style.color = '#dc2626';
    el.textContent = '✗ Помилка збереження';
  }
}

async function saveHomepage() {
  const slug = document.getElementById('homepage_slug').value;
  const res  = await api('settings_save', { homepage_slug: slug });
  const el   = document.getElementById('homepage-status');
  el.style.display = 'block';
  if (res.success) {
    el.style.color = '#15803d';
    el.textContent = slug ? '✓ Збережено. Оновіть сторінку щоб побачити кнопку «Скинути».' : '✓ Головна сторінка скинута.';
  } else {
    el.style.color = '#dc2626';
    el.textContent = '✗ Помилка збереження';
  }
}

async function clearHomepage() {
  document.getElementById('homepage_slug').value = '';
  await saveHomepage();
}

async function testTelegram() {
  const el = document.getElementById('tg-status');
  el.style.display = 'block';
  el.style.color = '#64748b';
  el.textContent = 'Надсилаємо тест...';
  const res = await api('telegram_test', {});
  if (res.success) {
    el.style.color = '#15803d';
    el.textContent = '✓ Повідомлення надіслано! Перевірте Telegram.';
  } else {
    el.style.color = '#dc2626';
    el.textContent = '✗ ' + (res.error || 'Не вдалося надіслати');
  }
}
</script>
</body>
</html>
