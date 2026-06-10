<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

$landingManager = new Landing();
$landings = $landingManager->getAll();
$selectedSlug = $_GET['slug'] ?? ($landings[0]['slug'] ?? '');

// Load media files
$files = [];
$uploadsDir = $selectedSlug ? UPLOADS_PATH . '/' . $selectedSlug : '';
if ($uploadsDir && is_dir($uploadsDir)) {
    foreach (glob($uploadsDir . '/*') ?: [] as $path) {
        if (!is_file($path)) continue;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) continue;
        $relPath = UPLOADS_URL . '/' . $selectedSlug . '/' . basename($path);
        $files[] = [
            'name'  => basename($path),
            'path'  => $path,
            'url'   => $relPath,
            'size'  => filesize($path),
            'mtime' => filemtime($path),
            'ext'   => $ext,
        ];
    }
    usort($files, fn($a, $b) => $b['mtime'] - $a['mtime']);
}

function humanSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' МБ';
    if ($bytes >= 1024)    return round($bytes / 1024, 0) . ' КБ';
    return $bytes . ' Б';
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Медіа — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.media-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.media-filter select { border: 1.5px solid var(--c-border); border-radius: 8px; padding: 7px 12px; font-size: 13px; outline: none; background: #fff; }
.media-count { font-size: 13px; color: var(--c-muted); margin-left: auto; }
.media-upload-area { background: #fff; border: 2px dashed var(--c-border); border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 20px; cursor: pointer; transition: .15s; }
.media-upload-area:hover { border-color: var(--c-primary); background: #eef2ff; }
.media-upload-area p { font-size: 13px; color: var(--c-muted); margin: 4px 0; }
.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
.media-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 10px; overflow: hidden; position: relative; }
.media-card:hover .media-card-actions { opacity: 1; }
.media-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; display: block; background: #f1f5f9; }
.media-thumb-svg { width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; background: #f8fafc; }
.media-info { padding: 8px 10px; border-top: 1px solid var(--c-border); }
.media-name { font-size: 11px; color: var(--c-text); font-weight: 500; word-break: break-all; line-height: 1.3; }
.media-size { font-size: 11px; color: var(--c-muted); margin-top: 2px; }
.media-card-actions { position: absolute; top: 4px; right: 4px; display: flex; gap: 4px; opacity: 0; transition: opacity .15s; }
.media-btn { width: 28px; height: 28px; border-radius: 6px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.media-btn-copy { background: rgba(255,255,255,.9); color: #6366f1; }
.media-btn-del { background: rgba(255,255,255,.9); color: #ef4444; }
.media-btn:hover { transform: scale(1.05); }
.no-media { text-align: center; padding: 60px 24px; color: var(--c-muted); }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Медіафайли</h1>
    </div>

    <div class="media-filter">
      <label style="font-size:13px;color:var(--c-muted)">Лендинг:</label>
      <select onchange="location.href='?slug='+this.value">
        <?php foreach ($landings as $l): ?>
        <option value="<?= htmlspecialchars($l['slug']) ?>" <?= $l['slug'] === $selectedSlug ? 'selected' : '' ?>>
          <?= htmlspecialchars($l['title']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <span class="media-count"><?= count($files) ?> файлів</span>
    </div>

    <!-- Upload area -->
    <?php if ($selectedSlug): ?>
    <div class="media-upload-area" id="uploadArea" onclick="document.getElementById('uploadInput').click()">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:8px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      <p><strong>Клікніть або перетягніть зображення</strong></p>
      <p>JPG, PNG, WebP, GIF, SVG · Максимум 5 МБ</p>
      <input type="file" id="uploadInput" accept="image/*" multiple style="display:none">
    </div>
    <?php endif; ?>

    <?php if (empty($files)): ?>
    <div class="no-media">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <p>Немає завантажених зображень для цього лендингу</p>
    </div>
    <?php else: ?>
    <div class="media-grid" id="mediaGrid">
      <?php foreach ($files as $f): ?>
      <div class="media-card" id="media-<?= htmlspecialchars($f['name']) ?>">
        <?php if ($f['ext'] === 'svg'): ?>
        <div class="media-thumb-svg">
          <img src="<?= htmlspecialchars($f['url']) ?>" style="max-width:80px;max-height:80px" alt="">
        </div>
        <?php else: ?>
        <img class="media-thumb" src="<?= htmlspecialchars($f['url']) ?>" alt="<?= htmlspecialchars($f['name']) ?>" loading="lazy">
        <?php endif; ?>
        <div class="media-card-actions">
          <button class="media-btn media-btn-copy" onclick="copyUrl('<?= htmlspecialchars($f['url']) ?>')" title="Копіювати URL">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          </button>
          <button class="media-btn media-btn-del" onclick="deleteFile('<?= htmlspecialchars($f['name']) ?>')" title="Видалити">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </div>
        <div class="media-info">
          <div class="media-name"><?= htmlspecialchars($f['name']) ?></div>
          <div class="media-size"><?= humanSize($f['size']) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;
const LANDING_SLUG = <?= json_encode($selectedSlug) ?>;
const BASE_URL = <?= json_encode(BASE_URL) ?>;

async function deleteFile(name) {
  if (!confirm('Видалити файл "' + name + '"?')) return;
  const res = await api('file_delete', { slug: LANDING_SLUG, file: name });
  if (res.success) {
    const card = document.getElementById('media-' + name);
    if (card) card.remove();
  } else {
    alert(res.error || 'Помилка');
  }
}

function copyUrl(url) {
  navigator.clipboard?.writeText(url).then(() => showToast('URL скопійовано: ' + url))
    .catch(() => prompt('URL зображення:', url));
}

function showToast(msg) {
  let t = document.getElementById('cms-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'cms-toast';
    t.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;z-index:9999;max-width:90vw;text-align:center';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.opacity = '0', 3000);
}

// Upload functionality
const uploadInput = document.getElementById('uploadInput');
const uploadArea  = document.getElementById('uploadArea');

async function uploadFiles(fileList) {
  for (const file of fileList) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('slug', LANDING_SLUG);
    try {
      const r = await fetch(ADMIN_URL + '/api.php?action=file_upload', { method: 'POST', headers: { 'X-CSRF-Token': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '') }, body: fd });
      const res = await r.json();
      if (res.success) {
        // Add card to grid without reload
        const grid = document.getElementById('mediaGrid');
        if (grid) {
          const div = document.createElement('div');
          div.className = 'media-card';
          div.id = 'media-' + res.filename;
          div.innerHTML = `<img class="media-thumb" src="${res.path}" loading="lazy">
            <div class="media-card-actions">
              <button class="media-btn media-btn-copy" onclick="copyUrl('${res.path}')" title="Копіювати URL"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg></button>
              <button class="media-btn media-btn-del" onclick="deleteFile('${res.filename}')" title="Видалити"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg></button>
            </div>
            <div class="media-info"><div class="media-name">${res.filename}</div></div>`;
          grid.insertBefore(div, grid.firstChild);
        }
        showToast('Завантажено: ' + file.name);
      } else {
        showToast('Помилка: ' + (res.error || file.name));
      }
    } catch(e) {
      showToast('Помилка мережі');
    }
  }
}

if (uploadInput) uploadInput.addEventListener('change', () => uploadFiles(uploadInput.files));
if (uploadArea) {
  uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.style.borderColor = '#6366f1'; });
  uploadArea.addEventListener('dragleave', () => uploadArea.style.borderColor = '');
  uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.style.borderColor = '';
    uploadFiles(e.dataTransfer.files);
  });
}
</script>
</body>
</html>
