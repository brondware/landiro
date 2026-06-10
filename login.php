<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Auth.php';

if (Auth::check()) {
    header('Location: ' . ADMIN_URL . '/');
    exit;
}

// Brute-force protection (file-based per IP, 5 attempts → 5 min lockout)
define('LOGIN_ATTEMPTS_DIR', DATA_PATH . '/login_attempts');

function _loginAttemptsFile(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return LOGIN_ATTEMPTS_DIR . '/' . hash('sha256', $ip) . '.json';
}

function _getAttempts(): array {
    $file = _loginAttemptsFile();
    if (!file_exists($file)) return ['count' => 0, 'until' => 0];
    return json_decode(file_get_contents($file), true) ?: ['count' => 0, 'until' => 0];
}

function _recordFailure(): void {
    if (!is_dir(LOGIN_ATTEMPTS_DIR)) mkdir(LOGIN_ATTEMPTS_DIR, 0755, true);
    $data = _getAttempts();
    $data['count']++;
    if ($data['count'] >= 5) {
        $data['until'] = time() + 300;
    }
    file_put_contents(_loginAttemptsFile(), json_encode($data));
}

function _clearAttempts(): void {
    $file = _loginAttemptsFile();
    if (file_exists($file)) @unlink($file);
}

$attempts = _getAttempts();
$locked = ($attempts['count'] >= 5 && $attempts['until'] > time());

$error = '';

if (isset($_GET['setup'])) {
    $error = ''; // just clear, will show success message below
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($locked) {
        $remaining = $attempts['until'] - time();
        $error = 'Забагато спроб. Зачекайте ' . ceil($remaining / 60) . ' хв.';
    } elseif (Auth::login($login, $password)) {
        _clearAttempts();
        header('Location: ' . ADMIN_URL . '/');
        exit;
    } else {
        _recordFailure();
        $attempts = _getAttempts();
        $locked = ($attempts['count'] >= 5 && $attempts['until'] > time());
        if ($locked) {
            $error = 'Забагато невдалих спроб. Аккаунт заблоковано на 5 хвилин.';
        } else {
            $remaining_tries = 5 - $attempts['count'];
            $error = 'Невірний логін або пароль' . ($remaining_tries <= 2 ? ' (залишилось спроб: ' . $remaining_tries . ')' : '');
        }
    }
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Вхід — Landiro CMS</title>
<style>
:root{--c-bg:#f8fafc;--c-card:#fff;--c-primary:#6366f1;--c-text:#0f172a;--c-muted:#64748b;--c-border:#e2e8f0;--radius:12px}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--c-bg);min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;padding:16px}
.card{background:var(--c-card);border-radius:var(--radius);box-shadow:0 4px 24px rgba(0,0,0,.08);padding:40px 32px;width:100%;max-width:380px}
.logo{text-align:center;margin-bottom:32px}
.logo h1{font-size:22px;color:var(--c-primary);font-weight:700;letter-spacing:-0.5px}
.logo p{font-size:13px;color:var(--c-muted);margin-top:4px}
.field{margin-bottom:16px}
.field label{display:block;font-size:13px;font-weight:500;color:var(--c-text);margin-bottom:6px}
.field input{width:100%;border:1.5px solid var(--c-border);border-radius:8px;padding:11px 14px;font-size:15px;outline:none;transition:border-color .2s;color:var(--c-text);background:#fff}
.field input:focus{border-color:var(--c-primary)}
.btn{width:100%;background:var(--c-primary);color:#fff;border:none;border-radius:8px;padding:13px;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .2s;margin-top:8px}
.btn:hover{opacity:.88}
.btn:disabled{opacity:.5;cursor:not-allowed}
.error{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px;text-align:center}
.success{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px;text-align:center}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>Landiro CMS</h1>
        <p>Конструктор лендингів</p>
    </div>
    <?php if (isset($_GET['setup'])): ?>
    <div class="success">Пароль встановлено. Увійдіть в адмін-панель.</div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" autocomplete="on">
        <div class="field">
            <label>Логін</label>
            <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" autocomplete="username" autofocus <?= $locked ? 'disabled' : '' ?>>
        </div>
        <div class="field">
            <label>Пароль</label>
            <input type="password" name="password" autocomplete="current-password" <?= $locked ? 'disabled' : '' ?>>
        </div>
        <button type="submit" class="btn" <?= $locked ? 'disabled' : '' ?>>Увійти</button>
    </form>
</div>
</body>
</html>
