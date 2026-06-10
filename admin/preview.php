<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Renderer.php';

Auth::requireLogin();

$slug      = $_GET['slug']    ?? '';
$sectionId = $_GET['id']      ?? '';
$variantB  = ($_GET['variant'] ?? '') === 'b';

$landingManager = new Landing();
$landing = $landingManager->get($slug);
if (!$landing) { http_response_code(404); exit; }

$section = null;
foreach ($landing['sections'] as $s) {
    if ($s['id'] === $sectionId) { $section = $s; break; }
}
if (!$section) { http_response_code(404); exit; }

// In variant B mode, swap HTML to ab_html for preview
if ($variantB && !empty($section['ab_html'])) {
    $section['html'] = $section['ab_html'];
} elseif ($variantB && empty($section['ab_html'])) {
    // First time creating variant B — start with current HTML
}

$renderer = new Renderer();
$gs = $landing['global_styles'] ?? [];

$globalVars = ':root{';
foreach ([
    'primary_color'   => '--primary-color',
    'secondary_color' => '--secondary-color',
    'accent_color'    => '--accent-color',
    'text_color'      => '--text-color',
    'font_family'     => '--font-family',
] as $key => $var) {
    if (!empty($gs[$key])) $globalVars .= $var . ':' . $gs[$key] . ';';
}
$globalVars .= '}body{margin:0;padding:0;font-family:var(--font-family,sans-serif);}';

$sectionHtml = $renderer->renderSection($section, false);
$safeId = json_encode($sectionId);
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style><?= $globalVars ?></style>
<?php if (!empty($gs['custom_css'])): ?>
<style id="cms-global-css"><?= htmlspecialchars($gs['custom_css']) ?></style>
<?php endif; ?>
<style id="cms-section-vars"></style>
<style id="cms-custom-css-override"></style>
<style>
/* Editing indicators */
[contenteditable]:focus { outline: 2px solid rgba(99,102,241,.6) !important; outline-offset: 2px; border-radius: 3px; }
[contenteditable]:hover { outline: 1px dashed rgba(99,102,241,.35) !important; outline-offset: 2px; border-radius: 3px; }
img.cms-img-hover { outline: 2px dashed rgba(99,102,241,.5) !important; cursor: pointer !important; }
img.cms-img-hover::after { content: '📷'; }
</style>
</head>
<body>
<?= $sectionHtml ?>
<script>
const SECTION_ID = <?= $safeId ?>;

/* ── Message handler ────────────────────────────────────── */
window.addEventListener('message', function(e) {
  if (!e.data || !e.data.type) return;
  switch (e.data.type) {
    case 'update-var':    applyVar(e.data.var, e.data.value);   break;
    case 'update-css':    applyOverrideCss(e.data.css);         break;
    case 'update-image':  replaceImage(e.data.index, e.data.src); break;
    case 'enable-edit':   enableInlineEdit();                   break;
    case 'get-html':      sendHtml();                           break;
    case 'reload':        location.reload();                    break;
  }
});

/* ── CSS variable injection ─────────────────────────────── */
function applyVar(varName, value) {
  const sheet = document.getElementById('cms-section-vars');
  const sectionEl = document.getElementById('section-' + SECTION_ID);
  if (!sectionEl) return;
  // Rebuild all vars style (simpler than per-var elements)
  _varCache[varName] = value;
  let css = '#section-' + SECTION_ID + '{';
  for (const [k, v] of Object.entries(_varCache)) css += k + ':' + v + ';';
  css += '}';
  sheet.textContent = css;
}
const _varCache = {};

function applyOverrideCss(css) {
  document.getElementById('cms-custom-css-override').textContent = css;
}

/* ── Image replacement ──────────────────────────────────── */
function replaceImage(index, src) {
  const imgs = document.querySelectorAll('#section-' + SECTION_ID + ' img');
  if (imgs[index]) {
    imgs[index].src = src;
    imgs[index].removeAttribute('srcset');
    notifyChange();
  }
}

/* ── Send section HTML to parent ───────────────────────── */
function sendHtml() {
  const sectionEl = document.getElementById('section-' + SECTION_ID);
  const html = sectionEl ? sectionEl.innerHTML.trim() : '';
  window.parent.postMessage({ type: 'html-response', html }, '*');
}

/* ── Inline edit ────────────────────────────────────────── */
let editEnabled = false;
function enableInlineEdit() {
  if (editEnabled) return;
  editEnabled = true;

  const sectionEl = document.getElementById('section-' + SECTION_ID);
  if (!sectionEl) return;

  /* Text elements: make leaf text nodes editable */
  const TEXT_TAGS = ['H1','H2','H3','H4','H5','H6','P','SPAN','A','BUTTON','LI','TD','TH','LABEL','STRONG','EM','B','I'];
  sectionEl.querySelectorAll(TEXT_TAGS.map(t => t.toLowerCase()).join(',')).forEach(el => {
    /* Skip if already editable or contains block children */
    if (el.isContentEditable) return;
    const hasBlock = [...el.children].some(c =>
      ['DIV','P','SECTION','ARTICLE','H1','H2','H3','H4','H5','H6','UL','OL','TABLE'].includes(c.tagName)
    );
    if (hasBlock) return;

    el.setAttribute('contenteditable', 'true');
    el.setAttribute('spellcheck', 'false');

    /* Prevent Enter from adding <div> blocks in headings/spans */
    el.addEventListener('keydown', ev => {
      if (ev.key === 'Enter' && !ev.shiftKey &&
          ['H1','H2','H3','H4','H5','H6','SPAN','A','BUTTON'].includes(el.tagName)) {
        ev.preventDefault();
        el.blur();
      }
    });

    el.addEventListener('blur', () => notifyChange());
    el.addEventListener('input', () => {
      clearTimeout(_notifyTimer);
      _notifyTimer = setTimeout(notifyChange, 800);
    });
  });

  /* Images: show hover outline and click-to-replace */
  sectionEl.querySelectorAll('img').forEach((img, index) => {
    img.addEventListener('mouseenter', () => img.classList.add('cms-img-hover'));
    img.addEventListener('mouseleave', () => img.classList.remove('cms-img-hover'));
    img.addEventListener('click', ev => {
      ev.preventDefault();
      ev.stopPropagation();
      window.parent.postMessage({ type: 'image-click', index, src: img.src }, '*');
    });
  });
}

let _notifyTimer;
function notifyChange() {
  clearTimeout(_notifyTimer);
  sendHtml();
  window.parent.postMessage({ type: 'content-changed' }, '*');
}

/* Auto-enable edit on load */
window.addEventListener('load', () => {
  setTimeout(enableInlineEdit, 200);
});
</script>
</body>
</html>
