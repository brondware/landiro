<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Template.php';
require_once dirname(__DIR__) . '/core/Renderer.php';

Auth::requireLogin();

$slug      = $_GET['slug']    ?? '';
$sectionId = $_GET['id']      ?? '';
$isVariantB = ($_GET['variant'] ?? '') === 'b';

$landingManager = new Landing();
$landing = $landingManager->get($slug);

if (!$landing) { header('Location: ' . ADMIN_URL . '/'); exit; }

$section = null;
foreach ($landing['sections'] as $s) {
    if ($s['id'] === $sectionId) { $section = $s; break; }
}
if (!$section) { header('Location: ' . ADMIN_URL . '/landing.php?slug=' . urlencode($slug)); exit; }

// In variant B mode, the HTML tab shows ab_html (fallback to html on first time)
$editHtml = $isVariantB ? ($section['ab_html'] ?? $section['html'] ?? '') : ($section['html'] ?? '');

$templateManager = new Template();
$types = Template::$SECTION_TYPES;
$typeInfo = $types[$section['type']] ?? ['label' => $section['type'], 'color' => '#888'];

// Завантажуємо мету шаблону для отримання CSS змінних та fallback-контенту
$tmplMeta = $templateManager->get($section['type'], $section['template'] ?? '') ?? [];

// Template reference mode: section stores html/css/js="" and reads from template files at render time.
// Load template file content into the code editor as fallback so the user can see and edit the code.
$editCss = $section['css'] ?? '';
$editJs  = $section['js']  ?? '';
$isTemplateMode = !empty($section['template'])
    && $editHtml === ''
    && $editCss  === ''
    && $editJs   === '';
if ($isTemplateMode) {
    $editHtml = $tmplMeta['html'] ?? '';
    $editCss  = $tmplMeta['css']  ?? '';
    $editJs   = $tmplMeta['js']   ?? '';
}

$renderer = new Renderer();
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Редактор — <?= htmlspecialchars($typeInfo['label']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/editor.css">
<!-- CodeMirror -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
</head>
<body class="editor-body">

<div class="editor-layout">
  <!-- Top Bar -->
  <div class="editor-topbar">
    <div class="editor-topbar-left">
      <a href="<?= ADMIN_URL ?>/landing.php?slug=<?= urlencode($slug) ?>" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <span class="editor-section-badge" style="background:<?= htmlspecialchars($typeInfo['color']) ?>20;color:<?= htmlspecialchars($typeInfo['color']) ?>">
        <?= htmlspecialchars($typeInfo['label']) ?>
      </span>
      <span class="editor-landing-name"><?= htmlspecialchars($landing['title']) ?></span>
    </div>
    <div class="editor-topbar-center">
      <div class="editor-mode-tabs">
        <button class="mode-tab active" data-mode="visual" onclick="setMode('visual')">Візуальний</button>
        <button class="mode-tab" data-mode="code" onclick="setMode('code')">Код</button>
        <?php if (!empty($section['php']) || true): ?>
        <button class="mode-tab" data-mode="php" onclick="setMode('php')">PHP</button>
        <?php endif; ?>
      </div>
      <?php if ($isVariantB): ?>
      <span class="ab-variant-badge">A/B — Варіант B</span>
      <?php elseif (!empty($section['ab_html'])): ?>
      <a href="?slug=<?= urlencode($slug) ?>&id=<?= urlencode($sectionId) ?>&variant=b" class="ab-variant-link">A/B: редагувати B →</a>
      <?php endif; ?>
    </div>
    <div class="editor-topbar-right">
      <div class="preview-device-btns">
        <button class="device-btn active" data-device="mobile" onclick="setDevice('mobile')" title="Мобільний (390px)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        </button>
        <button class="device-btn" data-device="tablet" onclick="setDevice('tablet')" title="Планшет (768px)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
        </button>
        <button class="device-btn" data-device="desktop" onclick="setDevice('desktop')" title="Десктоп">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </button>
      </div>
      <span class="save-status" id="editorSaveStatus">Збережено</span>
      <button class="btn btn-primary btn-sm" onclick="saveSection()">Зберегти</button>
    </div>
  </div>

  <!-- Editor Content -->
  <div class="editor-content">

    <!-- Visual Mode Panel -->
    <div id="mode-visual" class="editor-panel">
      <div class="visual-editor">
        <!-- Preview -->
        <div class="preview-pane" id="previewPane">
          <div class="preview-frame-wrap" id="previewFrameWrap">
            <iframe id="previewFrame" class="preview-frame mobile" src="<?= ADMIN_URL ?>/preview.php?slug=<?= urlencode($slug) ?>&id=<?= urlencode($sectionId) ?><?= $isVariantB ? '&variant=b' : '' ?>" frameborder="0"></iframe>
          </div>
        </div>

        <!-- Controls Panel -->
        <div class="controls-pane" id="controlsPane">
          <div class="controls-scroll">

            <!-- CSS Variables -->
            <?php if (!empty($tmplMeta['vars'])): ?>
            <div class="control-group">
              <div class="control-group-title">Кольори та розміри</div>
              <?php foreach ($tmplMeta['vars'] as $varName => $varDef): ?>
              <?php $currentVal = $section['data']['vars'][$varName] ?? $varDef['default'] ?? ''; ?>
              <div class="control-item">
                <label class="control-label"><?= htmlspecialchars($varDef['label'] ?? $varName) ?></label>
                <?php if ($varDef['type'] === 'color'): ?>
                <div class="color-input-wrap">
                  <input type="color" class="css-var-input" data-var="<?= htmlspecialchars($varName) ?>" value="<?= htmlspecialchars($currentVal) ?>" onchange="updateCssVar(this)" oninput="updateCssVar(this)">
                  <input type="text" class="color-text css-var-text" data-var="<?= htmlspecialchars($varName) ?>" value="<?= htmlspecialchars($currentVal) ?>">
                </div>
                <?php elseif ($varDef['type'] === 'range'): ?>
                <div class="range-wrap">
                  <input type="range" class="css-var-input range-input" data-var="<?= htmlspecialchars($varName) ?>" data-unit="px" value="<?= (int)$currentVal ?>" min="<?= (int)($varDef['min'] ?? 0) ?>" max="<?= (int)($varDef['max'] ?? 200) ?>" oninput="updateCssVar(this)">
                  <span class="range-val"><?= htmlspecialchars($currentVal) ?></span>
                </div>
                <?php elseif ($varDef['type'] === 'select'): ?>
                <select class="css-var-input select-input" data-var="<?= htmlspecialchars($varName) ?>" onchange="updateCssVar(this)">
                  <?php foreach ($varDef['options'] ?? [] as $opt): ?>
                  <option value="<?= htmlspecialchars($opt) ?>" <?= $currentVal === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                  <?php endforeach; ?>
                </select>
                <?php else: ?>
                <input type="text" class="css-var-input text-input" data-var="<?= htmlspecialchars($varName) ?>" value="<?= htmlspecialchars($currentVal) ?>" oninput="updateCssVar(this)">
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Image Upload -->
            <div class="control-group">
              <div class="control-group-title">Зображення</div>
              <div class="image-upload-area" id="imageUploadArea">
                <p class="image-upload-hint">Клікніть на зображення у превью щоб замінити, або:</p>
                <label class="btn btn-outline btn-sm upload-label">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  Завантажити зображення
                  <input type="file" accept="image/*" onchange="uploadImage(this)" style="display:none">
                </label>
              </div>
            </div>

            <!-- PHP Handler (if template has PHP) -->
            <?php if ($section['type'] === 'order-form' || !empty($section['php'])): ?>
            <div class="control-group">
              <div class="control-group-title">PHP Обробник</div>
              <div class="form-field">
                <label>Тип обробника</label>
                <select id="phpHandlerType" class="select-input" onchange="showPhpHandlerConfig(this.value)">
                  <option value="">— Не підключений —</option>
                  <option value="email">Email</option>
                  <option value="telegram">Telegram Bot</option>
                  <option value="webhook">Webhook (CRM)</option>
                  <option value="custom">Кастомний PHP</option>
                </select>
              </div>
              <div id="phpHandlerConfig" class="php-config"></div>
            </div>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

    <!-- Code Mode Panel -->
    <div id="mode-code" class="editor-panel hidden">
      <div class="code-editor-layout">
        <div class="code-tabs">
          <button class="code-tab active" data-lang="html" onclick="setCodeLang('html')">HTML</button>
          <button class="code-tab" data-lang="css" onclick="setCodeLang('css')">CSS</button>
          <button class="code-tab" data-lang="js" onclick="setCodeLang('js')">JavaScript</button>
          <?php if ($isTemplateMode): ?>
          <span class="code-tab-badge" title="Код завантажений з файлів шаблону <?= htmlspecialchars($section['template'] ?? '') ?>. Після збереження стане власним кодом секції.">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Шаблон: <?= htmlspecialchars($section['template'] ?? '') ?>
          </span>
          <?php endif; ?>
        </div>
        <div id="code-html" class="code-panel">
          <textarea id="editor-html"><?= htmlspecialchars($editHtml) ?></textarea>
        </div>
        <div id="code-css" class="code-panel hidden">
          <textarea id="editor-css"><?= htmlspecialchars($editCss) ?></textarea>
        </div>
        <div id="code-js" class="code-panel hidden">
          <textarea id="editor-js"><?= htmlspecialchars($editJs) ?></textarea>
        </div>
        <div class="code-actions">
          <span style="font-size:12px;color:#64748b;">Ctrl+S — зберегти</span>
          <button class="btn btn-outline btn-sm" onclick="applyCodeToPreview()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Зберегти і застосувати
          </button>
        </div>
      </div>
    </div>

    <!-- PHP Mode Panel -->
    <div id="mode-php" class="editor-panel hidden">
      <div class="code-editor-layout">
        <div class="php-warning">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          Тільки для розробників. PHP код виконується на сервері. Уникайте небезпечних функцій.
        </div>
        <div id="code-php" class="code-panel">
          <textarea id="editor-php"><?= htmlspecialchars($section['php'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
const LANDING_SLUG  = <?= json_encode($slug) ?>;
const SECTION_ID    = <?= json_encode($sectionId) ?>;
const ADMIN_URL     = <?= json_encode(ADMIN_URL) ?>;
const BASE_URL      = <?= json_encode(BASE_URL) ?>;
const SECTION_VARS  = <?= json_encode($section['data']['vars'] ?? []) ?>;
const AB_VARIANT    = <?= json_encode($isVariantB ? 'b' : 'a') ?>;
const CSRF_TOKEN    = <?= json_encode(Auth::csrf()) ?>;

/* Auto-resize iframe to fit content */
(function() {
  let resizeTimer;
  function resizeIframe() {
    const frame = document.getElementById('previewFrame');
    if (!frame) return;
    try {
      const body = frame.contentDocument?.body;
      if (body) {
        const h = Math.max(200, body.scrollHeight + 2);
        frame.style.height = h + 'px';
      }
    } catch(e) {}
  }
  window.addEventListener('message', e => {
    if (e.data?.type === 'html-response' || e.data?.type === 'content-changed') {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(resizeIframe, 100);
    }
  });
  document.addEventListener('DOMContentLoaded', () => {
    const frame = document.getElementById('previewFrame');
    if (frame) frame.addEventListener('load', () => setTimeout(resizeIframe, 200));
  });
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script src="<?= BASE_URL ?>/assets/js/editor.js"></script>
</body>
</html>
