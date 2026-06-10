<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

$slug = $_GET['slug'] ?? '';
$landingManager = new Landing();
$landing = $landingManager->get($slug);

if (!$landing) {
    header('Location: ' . ADMIN_URL . '/');
    exit;
}

$historyFiles = $landingManager->getHistory($slug);
$versions = [];
foreach (array_reverse($historyFiles) as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    $versions[] = [
        'file'     => basename($file),
        'ts'       => $data['updated_at'] ?? '',
        'sections' => count($data['sections'] ?? []),
        'published'=> $data['published'] ?? false,
        'title'    => $data['title'] ?? '',
    ];
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Версії — <?= htmlspecialchars($landing['title']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.version-list { display: flex; flex-direction: column; gap: 10px; max-width: 680px; }
.version-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 14px; }
.version-card.current { border-color: #6366f1; background: #eef2ff; }
.version-icon { width: 36px; height: 36px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #6366f1; }
.version-info { flex: 1; min-width: 0; }
.version-ts { font-size: 14px; font-weight: 600; color: var(--c-text); }
.version-meta { font-size: 12px; color: var(--c-muted); margin-top: 2px; }
.version-current-label { font-size: 11px; font-weight: 600; background: #6366f1; color: #fff; padding: 2px 8px; border-radius: 99px; }
.empty-history { text-align: center; padding: 48px 24px; color: var(--c-muted); }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div style="display:flex;align-items:center;gap:10px">
        <a href="landing.php?slug=<?= urlencode($slug) ?>" class="btn-back">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
          <h1 class="page-title">Версії лендингу</h1>
          <p class="page-subtitle"><?= htmlspecialchars($landing['title']) ?></p>
        </div>
      </div>
    </div>

    <?php if (empty($versions)): ?>
    <div class="empty-history">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
      <p>Немає збережених версій. Версії з'являться після редагування.</p>
    </div>
    <?php else: ?>
    <p style="font-size:13px;color:var(--c-muted);margin-bottom:20px">Зберігаються останні <?= count($versions) ?> версій. Відновлення замінить поточний лендинг (поточний стан збережеться як нова версія).</p>
    <div class="version-list">

      <!-- Current version -->
      <div class="version-card current">
        <div class="version-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="version-info">
          <div class="version-ts"><?= $landing['updated_at'] ? date('d.m.Y H:i:s', strtotime($landing['updated_at'])) : '—' ?></div>
          <div class="version-meta"><?= count($landing['sections']) ?> секцій · <?= $landing['published'] ? 'Опубліковано' : 'Чернетка' ?></div>
        </div>
        <span class="version-current-label">Поточна</span>
      </div>

      <?php foreach ($versions as $i => $v): ?>
      <div class="version-card">
        <div class="version-icon" style="background:#f8fafc;color:#94a3b8">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        </div>
        <div class="version-info">
          <div class="version-ts"><?= $v['ts'] ? date('d.m.Y H:i:s', strtotime($v['ts'])) : htmlspecialchars(basename($v['file'], '.json')) ?></div>
          <div class="version-meta"><?= $v['sections'] ?> секцій · <?= $v['published'] ? 'Опубліковано' : 'Чернетка' ?></div>
        </div>
        <button class="btn btn-sm btn-outline" onclick="restoreVersion('<?= htmlspecialchars($v['file']) ?>', '<?= $v['ts'] ? date('d.m.Y H:i', strtotime($v['ts'])) : $i ?>')">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.05"/></svg>
          Відновити
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;
const LANDING_SLUG = <?= json_encode($slug) ?>;

async function restoreVersion(file, label) {
  if (!confirm('Відновити версію від ' + label + '?\n\nПоточний стан збережеться як нова версія.')) return;
  const res = await api('landing_restore', { slug: LANDING_SLUG, version: file });
  if (res.success) {
    window.location.href = 'landing.php?slug=' + encodeURIComponent(LANDING_SLUG);
  } else {
    alert(res.error || 'Помилка відновлення');
  }
}
</script>
</body>
</html>
