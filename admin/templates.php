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
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-ghost btn-sm" onclick="openSecLibrary()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          З бібліотеки
        </button>
        <label class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          Завантажити ZIP
          <input type="file" accept=".zip" onchange="uploadTemplate(this)" style="display:none">
        </label>
      </div>
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
      <p>Завантажте ZIP архів або оберіть секцію з бібліотеки ком'юніті</p>
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

<!-- ═══════════════════════ SECTION LIBRARY MODAL ═══════════════════════ -->
<div id="secLibModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.55);backdrop-filter:blur(2px)">
  <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px">
  <div style="background:var(--c-bg,#f8fafc);border-radius:16px;width:100%;max-width:1080px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;padding:16px 24px;border-bottom:1px solid var(--c-border,#e2e8f0);flex-shrink:0;background:var(--c-card,#fff)">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--c-primary,#6366f1)" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <span style="font-size:16px;font-weight:700;color:var(--c-text,#0f172a)">Бібліотека секцій</span>
      <div style="flex:1;max-width:320px;position:relative;margin-left:8px">
        <input type="search" id="secSearchInput" placeholder="Пошук секцій…"
               oninput="secDebounce()"
               style="width:100%;padding:7px 32px 7px 11px;border:1px solid var(--c-border,#e2e8f0);border-radius:8px;background:var(--c-bg,#f8fafc);color:var(--c-text,#0f172a);font-size:13px;outline:none">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;right:9px;top:50%;transform:translateY(-50%);color:var(--c-muted,#94a3b8)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <button onclick="closeSecLibrary()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--c-muted,#64748b);padding:4px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

    <!-- Topics -->
    <div id="secTopicsBar" style="display:flex;gap:6px;flex-wrap:nowrap;overflow-x:auto;padding:10px 24px;border-bottom:1px solid var(--c-border,#e2e8f0);flex-shrink:0;background:var(--c-card,#fff);scrollbar-width:thin;scrollbar-color:var(--c-border,#e2e8f0) transparent;cursor:grab;user-select:none">
      <button onclick="secSetTopic('')" id="sec-topic-" class="sec-topic-btn sec-topic-active" style="white-space:nowrap;padding:5px 14px;border-radius:20px;border:1px solid var(--c-primary,#6366f1);background:var(--c-primary,#6366f1);color:#fff;font-size:12px;font-weight:600;cursor:pointer;flex-shrink:0">Всі</button>
    </div>

    <!-- Grid -->
    <div id="secGrid" style="flex:1;overflow-y:auto;padding:20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;align-content:start"></div>

    <!-- States -->
    <div id="secLoader" style="display:flex;justify-content:center;padding:24px;flex-shrink:0">
      <div style="width:26px;height:26px;border:3px solid var(--c-border,#e2e8f0);border-top-color:var(--c-primary,#6366f1);border-radius:50%;animation:spin .7s linear infinite"></div>
    </div>
    <div id="secEnd"   style="display:none;text-align:center;padding:16px;font-size:13px;color:var(--c-muted,#64748b);flex-shrink:0">Всі секції завантажено</div>
    <div id="secEmpty" style="display:none;text-align:center;padding:40px 20px;flex-shrink:0">
      <div style="font-size:36px;margin-bottom:10px">🧩</div>
      <div style="font-size:15px;font-weight:600;color:var(--c-text,#0f172a);margin-bottom:6px">Секцій не знайдено</div>
      <div style="font-size:13px;color:var(--c-muted,#64748b)">Спробуйте змінити фільтр або пошуковий запит</div>
    </div>
    <div id="secScrollAnchor" style="height:1px"></div>

  </div>
  </div>
</div>

<!-- ═══════════════════════ SUCCESS TOAST ═══════════════════════ -->
<div id="secToast" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9999;background:#0f172a;color:#fff;border-radius:10px;padding:12px 20px;font-size:14px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,.25);gap:10px;align-items:center">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="secToastMsg"></span>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#secTopicsBar::-webkit-scrollbar { height: 4px; }
#secTopicsBar::-webkit-scrollbar-thumb { background: var(--c-border,#e2e8f0); border-radius: 2px; }
.sec-topic-btn { transition: .15s; }
.sec-topic-btn:not(.sec-topic-active) {
  background: var(--c-bg,#f8fafc) !important;
  color: var(--c-muted,#64748b) !important;
  border-color: var(--c-border,#e2e8f0) !important;
}
.sec-topic-btn:not(.sec-topic-active):hover {
  background: var(--c-hover,#f1f5f9) !important;
  color: var(--c-text,#0f172a) !important;
}
.sec-card {
  background: var(--c-card,#fff);
  border: 1px solid var(--c-border,#e2e8f0);
  border-radius: var(--radius,10px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow .15s, transform .15s;
}
.sec-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10); transform: translateY(-2px); }
.sec-card-img {
  height: 130px;
  background: var(--c-hover,#f1f5f9);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  position: relative;
}
.sec-card-img img { width:100%;height:100%;object-fit:cover; }
.sec-card-img-ph { font-size: 32px; color: var(--c-border,#e2e8f0); }
.sec-card-body { padding: 12px; display: flex; flex-direction: column; gap: 5px; }
.sec-card-title { font-size: 13px; font-weight: 700; color: var(--c-text,#0f172a); line-height: 1.3; }
.sec-card-desc  { font-size: 11px; color: var(--c-muted,#64748b); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.sec-card-meta  { display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: var(--c-muted,#64748b); }
.sec-type-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 600;
  margin-bottom: 2px;
}
</style>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL   = <?= json_encode(ADMIN_URL) ?>;
const SEC_API     = <?= json_encode(defined('LIBRARY_API_URL') ? LIBRARY_API_URL : '') ?>;
const SEC_CSRF    = <?= json_encode(Auth::csrf()) ?>;
const CSRF_TOKEN  = <?= json_encode(Auth::csrf()) ?>;

// ── Local template upload ───────────────────────────────────────────────────
async function uploadTemplate(input) {
  const file = input.files[0];
  if (!file) return;
  const fd = new FormData();
  fd.append('zip', file);
  const res  = await fetch(ADMIN_URL + '/api.php?action=template_upload', { method: 'POST', headers: { 'X-CSRF-Token': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '') }, body: fd });
  const json = await res.json();
  if (json.success) location.reload();
  else alert(json.error || 'Помилка завантаження');
}

// ── Section library state ───────────────────────────────────────────────────
let secPage = 1, secLoading = false, secDone = false;
let secTopic = '', secSearch = '';
let secTopicsLoaded = false, secTopics = [];
let secObserver = null, secSearchTimer = null;

// ── Open / Close ────────────────────────────────────────────────────────────
function openSecLibrary() {
  document.getElementById('secLibModal').style.display = 'block';
  if (!secTopicsLoaded) { secReset(); secLoad(); }
}
function closeSecLibrary() {
  document.getElementById('secLibModal').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSecLibrary(); });

// ── Drag-to-scroll topics bar ───────────────────────────────────────────────
(function(){
  const bar = document.getElementById('secTopicsBar');
  let drag = false, startX = 0, scrollLeft = 0, moved = false;
  bar.addEventListener('mousedown', e => { drag = true; moved = false; startX = e.pageX - bar.offsetLeft; scrollLeft = bar.scrollLeft; bar.style.cursor = 'grabbing'; });
  document.addEventListener('mouseup', () => { drag = false; bar.style.cursor = 'grab'; });
  bar.addEventListener('mousemove', e => { if (!drag) return; const dx = e.pageX - bar.offsetLeft - startX; if (Math.abs(dx) > 4) moved = true; bar.scrollLeft = scrollLeft - dx; });
  bar.addEventListener('click', e => { if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; } }, true);
})();

// ── Load / Reset ────────────────────────────────────────────────────────────
function secReset() {
  secPage = 1; secDone = false; secLoading = false;
  document.getElementById('secGrid').innerHTML = '';
  document.getElementById('secEnd').style.display   = 'none';
  document.getElementById('secEmpty').style.display = 'none';
  document.getElementById('secLoader').style.display = 'flex';
  if (secObserver) { secObserver.disconnect(); secObserver = null; }
}

async function secLoad() {
  if (secLoading || secDone || !SEC_API) return;
  secLoading = true;

  const url = SEC_API + '?type=section&page=' + secPage + '&limit=12'
    + (secTopic  ? '&topic='  + encodeURIComponent(secTopic)  : '')
    + (secSearch ? '&q='      + encodeURIComponent(secSearch) : '');

  try {
    const r = await fetch(url);
    const d = await r.json();

    document.getElementById('secLoader').style.display = 'none';

    if (!secTopicsLoaded && d.topics) {
      secRenderTopics(d.topics);
      secTopicsLoaded = true;
    }

    const grid = document.getElementById('secGrid');
    (d.items || []).forEach(item => grid.insertAdjacentHTML('beforeend', secRenderCard(item)));

    if (!d.items?.length && secPage === 1) {
      document.getElementById('secEmpty').style.display = 'block';
    }
    if (!d.items?.length || secPage >= (d.pages || 1)) {
      secDone = true;
      if (secPage > 1) document.getElementById('secEnd').style.display = 'block';
    } else {
      secPage++;
      secSetupObserver();
    }
  } catch(e) {
    document.getElementById('secLoader').style.display = 'none';
    document.getElementById('secEmpty').style.display  = 'block';
  }
  secLoading = false;
}

function secSetupObserver() {
  const anchor = document.getElementById('secScrollAnchor');
  secObserver = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) secLoad();
  }, { root: document.getElementById('secGrid').parentElement, rootMargin: '200px' });
  secObserver.observe(anchor);
}

// ── Topics ──────────────────────────────────────────────────────────────────
function secRenderTopics(topics) {
  secTopics = topics;
  const bar = document.getElementById('secTopicsBar');
  topics.forEach(t => {
    const btn = document.createElement('button');
    btn.id        = 'sec-topic-' + t.slug;
    btn.className = 'sec-topic-btn';
    btn.style.cssText = 'white-space:nowrap;padding:5px 14px;border-radius:20px;border:1px solid var(--c-border,#e2e8f0);font-size:12px;font-weight:600;cursor:pointer;flex-shrink:0';
    btn.textContent = (t.icon ? t.icon + ' ' : '') + t.label;
    btn.onclick = () => secSetTopic(t.slug);
    bar.appendChild(btn);
  });
}

function secSetTopic(slug) {
  secTopic = slug;
  document.querySelectorAll('.sec-topic-btn').forEach(b => {
    b.classList.remove('sec-topic-active');
    b.style.background   = '';
    b.style.color        = '';
    b.style.borderColor  = '';
  });
  const active = document.getElementById('sec-topic-' + slug);
  if (active) {
    active.classList.add('sec-topic-active');
    active.style.background  = 'var(--c-primary,#6366f1)';
    active.style.color       = '#fff';
    active.style.borderColor = 'var(--c-primary,#6366f1)';
  }
  secTopicsLoaded = true;
  secReset(); secLoad();
}

// ── Search debounce ─────────────────────────────────────────────────────────
function secDebounce() {
  clearTimeout(secSearchTimer);
  secSearchTimer = setTimeout(() => {
    secSearch = document.getElementById('secSearchInput').value.trim();
    secTopicsLoaded = true;
    secReset(); secLoad();
  }, 350);
}

// ── Render card ─────────────────────────────────────────────────────────────
function secRenderCard(item) {
  const safeTitle = (item.title || '').replace(/&/g,'&amp;').replace(/</g,'&lt;');
  const preview   = item.preview_url
    ? '<img src="' + item.preview_url + '" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">'
    : '<div class="sec-card-img-ph">🧩</div>';

  const topicObj  = item.topic ? secTopics.find(function(t){ return t.slug === item.topic; }) : null;
  const badge     = topicObj
    ? '<span class="sec-type-badge" style="background:' + 'var(--c-primary-light,#eef2ff);color:var(--c-primary,#6366f1)">' + (topicObj.icon ? topicObj.icon + ' ' : '') + topicObj.label + '</span>'
    : (item.topic ? '<span class="sec-type-badge" style="background:var(--c-primary-light,#eef2ff);color:var(--c-primary,#6366f1)">' + item.topic + '</span>' : '');

  const desc = item.description ? item.description.slice(0, 70) + (item.description.length > 70 ? '…' : '') : '';

  var html = '<div class="sec-card">';
  html += '<div class="sec-card-img">' + preview + '</div>';
  html += '<div class="sec-card-body">';
  if (badge) html += badge;
  html += '<div class="sec-card-title">' + safeTitle + '</div>';
  if (desc) html += '<div class="sec-card-desc">' + desc + '</div>';
  html += '<div class="sec-card-meta"><span>@' + item.author + '</span><span>⬇ ' + item.downloads + '</span></div>';
  html += '<div style="display:flex;gap:6px;margin-top:8px">';
  html += '<button class="btn btn-primary btn-sm sec-install-btn" style="flex:1;justify-content:center" data-id="' + item.id + '" data-name="' + safeTitle + '">Встановити</button>';
  if (item.demo_url) {
    html += '<a href="' + item.demo_url + '" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="flex-shrink:0" title="Демо">Демо</a>';
  }
  if (item.view_url) {
    html += '<a href="' + item.view_url + '" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="flex-shrink:0">Детально</a>';
  }
  html += '</div>';
  html += '</div></div>';
  return html;
}

// ── Install click (delegated) ────────────────────────────────────────────────
document.getElementById('secGrid').addEventListener('click', async e => {
  const btn = e.target.closest('.sec-install-btn');
  if (!btn || btn.disabled) return;

  const itemId = btn.dataset.id;
  const name   = btn.dataset.name;
  btn.disabled    = true;
  btn.textContent = '…';

  const fd = new FormData();
  fd.append('_csrf',   SEC_CSRF);
  fd.append('item_id', itemId);

  try {
    const r   = await fetch(ADMIN_URL + '/section-install.php', { method: 'POST', body: fd });
    const res = await r.json();
    if (res.success) {
      btn.textContent = '✓ Встановлено';
      btn.style.background = '#16a34a';
      showSecToast('Секцію "' + (res.name || name) + '" встановлено');
    } else {
      alert('Помилка: ' + (res.error || 'Невідома помилка'));
      btn.disabled    = false;
      btn.textContent = 'Встановити';
    }
  } catch {
    alert('Помилка мережі');
    btn.disabled    = false;
    btn.textContent = 'Встановити';
  }
});

// ── Toast ───────────────────────────────────────────────────────────────────
function showSecToast(msg) {
  const t = document.getElementById('secToast');
  document.getElementById('secToastMsg').textContent = msg;
  t.style.display = 'flex';
  setTimeout(() => { t.style.display = 'none'; }, 3500);
}
</script>
</body>
</html>
