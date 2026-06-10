<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Template.php';

Auth::requireLogin();

$types = Template::$SECTION_TYPES;
$templateManager = new Template();
$activeType = $_GET['type'] ?? '';
$templates = $templateManager->getAll($activeType ?: null);
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Шаблони — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Каталог шаблонів</h1>
      <label class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Завантажити ZIP
        <input type="file" accept=".zip" onchange="uploadTemplate(this)" style="display:none">
      </label>
    </div>

    <!-- Type Filter -->
    <div class="type-filter">
      <a href="templates.php" class="type-filter-btn <?= !$activeType ? 'active' : '' ?>">Всі</a>
      <?php foreach ($types as $typeId => $typeInfo): ?>
      <a href="templates.php?type=<?= urlencode($typeId) ?>" class="type-filter-btn <?= $activeType === $typeId ? 'active' : '' ?>" style="<?= $activeType === $typeId ? '--active-color:' . $typeInfo['color'] : '' ?>">
        <?= htmlspecialchars($typeInfo['label']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($templates)): ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><path d="M4 22h14a2 2 0 0 0 2-2V7.5L14.5 2H6a2 2 0 0 0-2 2v4"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <h3>Немає шаблонів</h3>
      <p>Завантажте ZIP архів шаблону або перейдіть у папку templates/ та додайте шаблони вручну</p>
    </div>
    <?php else: ?>
    <div class="templates-catalog">
      <?php foreach ($templates as $tmpl): ?>
      <?php $typeInfo = $types[$tmpl['type']] ?? ['label' => $tmpl['type'], 'color' => '#888']; ?>
      <div class="template-card">
        <div class="template-preview">
          <?php if ($tmpl['has_preview'] ?? false): ?>
          <img src="<?= BASE_URL ?>/templates/<?= urlencode($tmpl['type']) ?>/<?= urlencode($tmpl['id_dir']) ?>/preview.jpg" alt="Preview" loading="lazy">
          <?php else: ?>
          <div class="template-no-preview" style="background:<?= htmlspecialchars($typeInfo['color']) ?>15">
            <span style="color:<?= htmlspecialchars($typeInfo['color']) ?>"><?= htmlspecialchars($typeInfo['label']) ?></span>
          </div>
          <?php endif; ?>
        </div>
        <div class="template-info">
          <span class="template-type-badge" style="background:<?= htmlspecialchars($typeInfo['color']) ?>20;color:<?= htmlspecialchars($typeInfo['color']) ?>">
            <?= htmlspecialchars($typeInfo['label']) ?>
          </span>
          <h4 class="template-name"><?= htmlspecialchars($tmpl['name'] ?? $tmpl['id_dir']) ?></h4>
          <?php if (!empty($tmpl['description'])): ?>
          <p class="template-desc"><?= htmlspecialchars($tmpl['description']) ?></p>
          <?php endif; ?>
          <div class="template-meta">
            <?php if ($tmpl['has_php'] ?? false): ?>
            <span class="badge badge-php">PHP</span>
            <?php endif; ?>
            <?php if ($tmpl['has_js'] ?? false): ?>
            <span class="badge badge-js">JS</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
async function uploadTemplate(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('zip', file);
  const res = await fetch(ADMIN_URL + '/api.php?action=template_upload', { method: 'POST', headers: { 'X-CSRF-Token': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '') }, body: fd });
  const json = await res.json();
  if (json.success) { location.reload(); }
  else { alert(json.error || 'Помилка завантаження'); }
}
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;
</script>
</body>
</html>
