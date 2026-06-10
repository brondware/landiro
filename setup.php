<?php
// Одноразова утиліта початкового налаштування.
// Доступна лише якщо пароль ще не змінено з дефолтного 'admin123'.
// Після успішного збереження — видаляється автоматично.

require_once __DIR__ . '/config.php';

// Guard: якщо пароль вже змінено — редирект на логін
if (!password_verify('admin123', ADMIN_PASSWORD)) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$configFile = __DIR__ . '/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login   = trim($_POST['login'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$login || !$pass) {
        $error = 'Заповніть логін і пароль';
    } elseif ($pass !== $confirm) {
        $error = 'Паролі не співпадають';
    } elseif (strlen($pass) < 6) {
        $error = 'Пароль мінімум 6 символів';
    } elseif (!is_writable($configFile)) {
        $error = 'Файл config.php недоступний для запису. Перевірте права (chmod 644 або 666 тимчасово).';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $lines = file($configFile, FILE_IGNORE_NEW_LINES);
        $found = ['login' => false, 'pass' => false];

        foreach ($lines as &$line) {
            if (strpos($line, "define('ADMIN_LOGIN'") !== false) {
                $line = "define('ADMIN_LOGIN', '" . $login . "');";
                $found['login'] = true;
            }
            if (strpos($line, "define('ADMIN_PASSWORD'") !== false) {
                $line = "define('ADMIN_PASSWORD', '" . $hash . "'); // оновлено " . date('Y-m-d H:i');
                $found['pass'] = true;
            }
        }

        if (!$found['login'] || !$found['pass']) {
            $error = 'Не знайдено рядки ADMIN_LOGIN/ADMIN_PASSWORD в config.php';
        } elseif (file_put_contents($configFile, implode("\n", $lines)) !== false) {
            @unlink(__FILE__);
            header('Location: ' . BASE_URL . '/login.php?setup=done');
            exit;
        } else {
            $error = 'Не вдалося записати config.php';
        }
    }
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Налаштування Landiro CMS</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f8fafc;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,sans-serif;padding:16px}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.08);padding:36px 32px;width:100%;max-width:400px}
h1{font-size:20px;font-weight:700;color:#0f172a;margin-bottom:6px}
.sub{font-size:13px;color:#64748b;margin-bottom:28px}
label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
input{width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:15px;outline:none;margin-bottom:16px;transition:border-color .15s}
input:focus{border-color:#6366f1}
button{width:100%;background:#6366f1;color:#fff;border:none;border-radius:8px;padding:12px;font-size:15px;font-weight:600;cursor:pointer}
.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px}
</style>
</head>
<body>
<div class="card">
  <h1>Налаштування Landiro CMS</h1>
  <p class="sub">Встановіть логін і пароль адміна</p>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST">
    <label>Логін</label>
    <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? 'admin') ?>" autocomplete="username" required>
    <label>Пароль</label>
    <input type="password" name="password" autocomplete="new-password" required>
    <label>Підтвердіть пароль</label>
    <input type="password" name="confirm" autocomplete="new-password" required>
    <button type="submit">Зберегти і увійти</button>
  </form>
</div>
</body>
</html>
