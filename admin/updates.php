<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Updater.php';

Auth::requireLogin();

$flash   = '';
$error   = '';
$result  = null;
$step    = 'check'; // check | download | apply

// POST: trigger update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::checkCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'refresh') {
        // Force re-fetch from remote
        Updater::fetchRemote(true);
        header('Location: ' . ADMIN_URL . '/updates.php');
        exit;
    }

    if ($action === 'update') {
        $latest = Updater::getLatest();
        if (!$latest || empty($latest['download_url'])) {
            $error = 'Посилання на завантаження не знайдено';
        } else {
            // Step 1: download (use direct file path if on same server)
            $zipPath = Updater::download($latest['download_url'], $latest['archive_path'] ?? '');
            if ($zipPath === false) {
                $error = 'Не вдалося завантажити архів оновлення. Перевірте URL та доступність сервера.';
            } else {
                // Step 2: apply
                $result = Updater::apply($zipPath, $latest['version'] ?? '');
                if ($result['success']) {
                    // Track download count on community server
                    if (!empty($latest['download_url'])) {
                        Updater::ping($latest['download_url'] . '&track=1');
                    }
                    $flash = 'Оновлення успішно встановлено. Скопійовано файлів: ' . $result['copied'];
                } else {
                    $error = 'Помилка при встановленні: ' . $result['error'];
                }
            }
        }
    }
}

// Always load latest info for display
$latest    = Updater::getLatest();
$hasUpdate = $latest && version_compare($latest['version'] ?? '0', CMS_VERSION, '>');
$isSame    = $latest && version_compare($latest['version'] ?? '0', CMS_VERSION, '=');
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Оновлення — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.update-card {
  background: var(--c-card, #fff);
  border: 1px solid var(--c-border, #e2e8f0);
  border-radius: 12px;
  padding: 28px;
  max-width: 680px;
}
.update-status {
  display: flex; align-items: center; gap: 14px;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 24px;
}
.update-status.available { background: #f0fdf4; border: 1px solid #bbf7d0; }
.update-status.current   { background: var(--c-primary-light, #eef2ff); border: 1px solid #c7d2fe; }
.update-status.unknown   { background: var(--c-hover, #f1f5f9); border: 1px solid var(--c-border, #e2e8f0); }
.update-icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; font-size: 22px;
}
.update-icon.green  { background: #dcfce7; }
.update-icon.indigo { background: var(--c-primary-light, #eef2ff); }
.update-icon.gray   { background: var(--c-hover, #f1f5f9); }
.update-title { font-size: 17px; font-weight: 700; margin-bottom: 3px; }
.update-sub   { font-size: 13px; color: var(--c-muted, #64748b); }
.changelog {
  background: var(--c-hover, #f1f5f9);
  border: 1px solid var(--c-border, #e2e8f0);
  border-radius: 8px;
  padding: 16px 18px;
  font-size: 13px;
  line-height: 1.7;
  white-space: pre-wrap;
  max-height: 220px;
  overflow-y: auto;
  margin-bottom: 20px;
  color: var(--c-text, #0f172a);
}
.version-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 0;
  border-bottom: 1px solid var(--c-border, #e2e8f0);
  font-size: 14px;
}
.version-row:last-child { border: none; }
.version-label { color: var(--c-muted, #64748b); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
.version-val   { font-weight: 600; color: var(--c-text, #0f172a); }
.version-url   { font-size: 11px; color: var(--c-muted, #64748b); word-break: break-all; }
.badge-new     { background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-left: 8px; }
.badge-cur     { background: var(--c-primary-light, #eef2ff); color: var(--c-primary, #6366f1); padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; margin-left: 8px; }
.alert-box {
  padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 20px;
  display: flex; gap: 10px; align-items: flex-start;
}
.alert-box.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
.alert-box.error   { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.warning-note {
  background: #fffbeb;
  border: 1px solid #fde68a;
  border-radius: 8px; padding: 12px 16px;
  font-size: 13px; color: #92400e;
  margin-bottom: 20px;
  display: flex; gap: 8px;
}
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Оновлення системи</h1>
      <form method="POST" style="display:inline">
        <?= Auth::csrfField() ?>
        <button name="action" value="refresh" class="btn btn-ghost btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Перевірити знову
        </button>
      </form>
    </div>

    <?php if ($flash): ?>
    <div class="alert-box success">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-box error">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="update-card">

      <!-- Status block -->
      <?php if ($hasUpdate): ?>
      <div class="update-status available">
        <div class="update-icon green">🚀</div>
        <div>
          <div class="update-title" style="color:#16a34a">Доступне оновлення!</div>
          <div class="update-sub">Нова версія <?= htmlspecialchars($latest['version']) ?> готова до встановлення</div>
        </div>
      </div>
      <?php elseif ($isSame): ?>
      <div class="update-status current">
        <div class="update-icon indigo">✓</div>
        <div>
          <div class="update-title" style="color:var(--c-primary,#6366f1)">Система актуальна</div>
          <div class="update-sub">Ви використовуєте останню версію Landiro CMS</div>
        </div>
      </div>
      <?php else: ?>
      <div class="update-status unknown">
        <div class="update-icon gray">?</div>
        <div>
          <div class="update-title" style="color:var(--c-muted,#64748b)">Не вдалося перевірити</div>
          <div class="update-sub">Сервер оновлень недоступний або URL не налаштовано</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Version info -->
      <div style="margin-bottom:24px">
        <div class="version-row">
          <span class="version-label">Поточна версія</span>
          <span class="version-val"><?= CMS_VERSION ?> <span class="badge-cur">Встановлена</span></span>
        </div>
        <?php if ($latest): ?>
        <div class="version-row">
          <span class="version-label">Остання версія</span>
          <span class="version-val">
            <?= htmlspecialchars($latest['version']) ?>
            <?php if ($hasUpdate): ?><span class="badge-new">Нова</span><?php endif; ?>
          </span>
        </div>
        <?php if (!empty($latest['title'])): ?>
        <div class="version-row">
          <span class="version-label">Назва релізу</span>
          <span class="version-val" style="font-weight:400;font-size:13px"><?= htmlspecialchars($latest['title']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($latest['released_at'])): ?>
        <div class="version-row">
          <span class="version-label">Дата релізу</span>
          <span class="version-val" style="font-weight:400;font-size:13px"><?= htmlspecialchars($latest['released_at']) ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="version-row">
          <span class="version-label">URL перевірки</span>
          <span class="version-url"><?= htmlspecialchars(UPDATE_CHECK_URL ?: '— не налаштовано') ?></span>
        </div>
      </div>

      <!-- Changelog -->
      <?php if ($hasUpdate && !empty($latest['changelog'])): ?>
      <div style="margin-bottom:4px">
        <div style="font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--c-muted,#64748b);margin-bottom:8px">Що нового</div>
        <div class="changelog"><?= htmlspecialchars($latest['changelog']) ?></div>
      </div>
      <?php endif; ?>

      <!-- Update action -->
      <?php if ($hasUpdate && !empty($latest['download_url'])): ?>
      <div class="warning-note">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>Перед оновленням рекомендується зробити резервну копію папки <code>data/</code>. Файл <code>config.php</code> і дані лендингів не торкаються.</span>
      </div>

      <form method="POST" onsubmit="return confirm('Запустити оновлення до версії <?= htmlspecialchars($latest['version']) ?>?')">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="download_url" value="<?= htmlspecialchars($latest['download_url']) ?>">
        <button type="submit" class="btn btn-primary" style="background:#16a34a;border-color:#16a34a;padding:10px 24px;font-size:15px">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Встановити версію <?= htmlspecialchars($latest['version']) ?>
        </button>
      </form>
      <?php endif; ?>

    </div><!-- .update-card -->
  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
