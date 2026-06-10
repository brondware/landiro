<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

$error   = '';
$success = '';
$newSlug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['zip'])) {
    if (!Auth::verifyCsrf($_POST['_csrf'] ?? '')) {
        $error = 'Невалідний запит';
    }
    $file = $_FILES['zip'];
    if ($error) {
        // csrf failed, skip file processing
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Помилка завантаження файлу';
    } elseif (!in_array(mime_content_type($file['tmp_name']), ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
        $error = 'Дозволені тільки ZIP-архіви';
    } elseif ($file['size'] > 50 * 1024 * 1024) {
        $error = 'Максимальний розмір: 50 МБ';
    } elseif (!class_exists('ZipArchive')) {
        $error = 'ZipArchive не підтримується на цьому сервері';
    } else {
        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            $error = 'Не вдалося відкрити ZIP';
        } else {
            // Find landing.json in the archive
            $jsonContent = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (basename($name) === 'landing.json') {
                    $jsonContent = $zip->getFromIndex($i);
                    break;
                }
            }
            if (!$jsonContent) {
                $error = 'Файл landing.json не знайдено в архіві';
                $zip->close();
            } else {
                $data = json_decode($jsonContent, true);
                if (!$data || empty($data['title'])) {
                    $error = 'Невалідний landing.json';
                    $zip->close();
                } else {
                    $landingManager = new Landing();
                    // Determine slug: use override if provided, else from JSON, ensure unique
                    $slugOverride = trim($_POST['slug'] ?? '');
                    $baseSlug = $slugOverride ?: ($data['slug'] ?? 'imported-landing');
                    // Create a fresh landing with imported data
                    $data['id'] = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
                        mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000,
                        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
                    $data['created_at'] = date('c');
                    $data['updated_at'] = date('c');
                    $data['published']  = false; // Always import as draft

                    // Ensure unique slug
                    $slug = $baseSlug;
                    $i = 1;
                    while (file_exists(LANDINGS_DATA_PATH . '/' . $slug . '.json')) {
                        $slug = $baseSlug . '-' . $i++;
                    }
                    $data['slug'] = $slug;

                    $landingManager->save($data);

                    // Import media files (uploads/ directory inside ZIP)
                    $uploadsDir = UPLOADS_PATH . '/' . $slug;
                    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                    for ($j = 0; $j < $zip->numFiles; $j++) {
                        $name = $zip->getNameIndex($j);
                        if (str_starts_with($name, 'uploads/') && !str_ends_with($name, '/')) {
                            $basename = basename($name);
                            // Security: only safe extensions
                            $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
                                $content = $zip->getFromIndex($j);
                                if ($content !== false) {
                                    file_put_contents($uploadsDir . '/' . $basename, $content);
                                }
                            }
                        }
                    }
                    $zip->close();
                    $newSlug = $slug;
                    $success = 'Лендинг "' . htmlspecialchars($data['title']) . '" успішно імпортовано як чернетку.';
                }
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Імпорт — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.import-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 14px; padding: 32px; max-width: 520px; }
.import-zone { border: 2px dashed var(--c-border); border-radius: 10px; padding: 36px 24px; text-align: center; cursor: pointer; transition: border-color .15s, background .15s; margin-bottom: 20px; }
.import-zone:hover, .import-zone.drag { border-color: var(--c-primary); background: #eef2ff; }
.import-zone svg { color: #94a3b8; margin-bottom: 10px; }
.import-zone p { font-size: 14px; color: var(--c-muted); margin: 4px 0; }
.import-zone strong { color: var(--c-text); }
.import-zone input { display: none; }
.alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
.alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.alert-success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Імпорт лендингу</h1>
    </div>

    <div class="import-card">
      <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success">
        <?= $success ?>
        <br><br>
        <a href="landing.php?slug=<?= urlencode($newSlug) ?>" class="btn btn-primary btn-sm">Відкрити редактор →</a>
      </div>
      <?php else: ?>
      <form method="POST" enctype="multipart/form-data" id="importForm">
        <input type="hidden" name="_csrf" value="<?= Auth::csrf() ?>">
        <div class="import-zone" id="dropZone" onclick="document.getElementById('zipInput').click()">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <p><strong>Клікніть або перетягніть ZIP-файл</strong></p>
          <p>Файл повинен містити <code>landing.json</code> (з експорту Landiro CMS)</p>
          <p id="dropFileName" style="font-size:13px;color:#6366f1;margin-top:8px"></p>
          <input type="file" id="zipInput" name="zip" accept=".zip" required>
        </div>

        <div class="form-field" style="margin-bottom:20px">
          <label>URL (slug) <span class="muted">— залиште порожнім, щоб використати з файлу</span></label>
          <input type="text" name="slug" placeholder="my-new-landing" pattern="[a-z0-9\-]+">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">Імпортувати</button>
      </form>
      <?php endif; ?>

      <p style="font-size:12px;color:var(--c-muted);margin-top:16px">
        Підтримуються ZIP-архіви, створені функцією "Експорт" Landiro CMS.<br>
        Лендинг буде імпортовано як <strong>чернетка</strong> (не опублікований).
      </p>
    </div>
  </main>
</div>
<script>
const dropZone = document.getElementById('dropZone');
const zipInput = document.getElementById('zipInput');

zipInput.addEventListener('change', () => {
  if (zipInput.files[0]) {
    document.getElementById('dropFileName').textContent = zipInput.files[0].name;
  }
});
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('drag');
  const file = e.dataTransfer.files[0];
  if (file && file.name.endsWith('.zip')) {
    const dt = new DataTransfer();
    dt.items.add(file);
    zipInput.files = dt.files;
    document.getElementById('dropFileName').textContent = file.name;
  }
});
</script>
</body>
</html>
