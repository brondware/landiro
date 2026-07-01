<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Analytics.php';
require_once dirname(__DIR__) . '/core/OrderLog.php';

Auth::requireLogin();

$landingMgr = new Landing();
$landings   = $landingMgr->getAll();
$allStats   = (new Analytics())->getAllStats();

// Recent orders across all landings (last 8)
$recentOrders = [];
$orderLog = new OrderLog();
foreach ($landings as $l) {
    foreach ($orderLog->getAll($l['slug'], 3) as $o) {
        $o['_landing_title'] = $l['title'];
        $o['_landing_slug']  = $l['slug'];
        $recentOrders[] = $o;
    }
}
usort($recentOrders, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });
$recentOrders = array_slice($recentOrders, 0, 8);
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Landiro CMS — Дашборд</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Лендинги</h1>
      <div style="display:flex;gap:8px;align-items:center">
        <button class="btn btn-ghost btn-sm" id="selectModeBtn" onclick="toggleSelectMode()" title="Масовий вибір">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
          Вибір
        </button>
        <button class="btn btn-ghost btn-sm" onclick="openLibrary()" style="gap:6px">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          З бібліотеки
        </button>
        <button class="btn btn-primary" onclick="showCreateModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Новий лендинг
        </button>
      </div>
    </div>

    <!-- Bulk actions bar (hidden by default) -->
    <div id="bulkBar" style="display:none;background:#1e293b;border-radius:10px;padding:10px 16px;margin-bottom:16px;display:none;align-items:center;gap:10px;flex-wrap:wrap">
      <span id="bulkCount" style="color:#94a3b8;font-size:13px">0 вибрано</span>
      <button class="btn btn-sm" style="background:#22c55e;color:#fff" onclick="bulkAction('publish')">Опублікувати</button>
      <button class="btn btn-sm" style="background:#f59e0b;color:#fff" onclick="bulkAction('unpublish')">Зняти</button>
      <button class="btn btn-sm" style="background:#ef4444;color:#fff" onclick="bulkAction('delete')">Видалити</button>
      <button class="btn btn-sm btn-ghost" style="color:#94a3b8;margin-left:auto" onclick="toggleSelectMode()">Скасувати</button>
    </div>

    <div class="dashboard-layout" style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
    <div>
    <?php if (empty($landings)): ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
      </div>
      <h3>Немає лендингів</h3>
      <p>Створіть перший лендинг та починайте збирати секції</p>
      <button class="btn btn-primary" onclick="showCreateModal()">Створити перший лендинг</button>
    </div>
    <?php else: ?>
    <div class="landing-grid">
      <?php foreach ($landings as $l): ?>
      <div class="landing-card" data-slug="<?= htmlspecialchars($l['slug']) ?>">
        <input type="checkbox" class="landing-select-cb" data-slug="<?= htmlspecialchars($l['slug']) ?>" style="display:none;position:absolute;top:12px;left:12px;width:18px;height:18px;cursor:pointer;z-index:2" onchange="updateBulkCount()">
        <div class="landing-card-header">
          <span class="badge <?= $l['published'] ? 'badge-success' : 'badge-draft' ?>">
            <?= $l['published'] ? 'Опублікований' : 'Чернетка' ?>
          </span>
          <span class="landing-date"><?= date('d.m.Y', strtotime($l['updated_at'])) ?></span>
        </div>
        <h3 class="landing-title"><?= htmlspecialchars($l['title']) ?></h3>
        <p class="landing-slug"><?= htmlspecialchars($l['slug']) ?></p>
        <div class="landing-meta">
          <span><?= $l['sections_count'] ?> секцій</span>
          <?php $st = $allStats[$l['slug']] ?? []; ?>
          <?php if (!empty($st)): ?>
          <span class="stat-pill">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <?= number_format($st['views'] ?? 0) ?>
          </span>
          <span class="stat-pill stat-pill-green">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            <?= number_format($st['orders'] ?? 0) ?>
          </span>
          <?php endif; ?>
        </div>
        <div class="landing-actions">
          <a href="landing.php?slug=<?= urlencode($l['slug']) ?>" class="btn btn-sm btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Редагувати
          </a>
          <button class="btn btn-sm btn-ghost" onclick="quickPreview(<?= json_encode(LANDINGS_URL . '/' . $l['slug'] . '/') ?>, <?= json_encode($l['title']) ?>)" title="Швидкий перегляд">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <a href="<?= LANDINGS_URL ?>/<?= urlencode($l['slug']) ?>/" target="_blank" class="btn btn-sm btn-ghost" title="Відкрити в новій вкладці">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          </a>
          <div class="dropdown">
            <button class="btn btn-sm btn-ghost btn-icon" onclick="toggleDropdown(this)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
            </button>
            <div class="dropdown-menu">
              <a href="#" onclick="showQr('<?= htmlspecialchars($l['slug']) ?>', '<?= htmlspecialchars(addslashes($l['title'])) ?>')">QR-код</a>
              <a href="#" onclick="cloneLanding('<?= $l['slug'] ?>')">Дублювати</a>
              <a href="#" onclick="deleteLanding('<?= $l['slug'] ?>', '<?= htmlspecialchars(addslashes($l['title'])) ?>')" class="danger">Видалити</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    </div><!-- /landings col -->

    <!-- Recent Orders Widget -->
    <aside>
      <div style="background:#fff;border:1.5px solid var(--c-border);border-radius:14px;padding:18px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <h3 style="font-size:14px;font-weight:700;margin:0">Останні замовлення</h3>
          <a href="orders.php" style="font-size:12px;color:var(--c-primary);text-decoration:none">Всі →</a>
        </div>
        <?php if (empty($recentOrders)): ?>
        <p style="font-size:13px;color:var(--c-muted);text-align:center;padding:20px 0">Замовлень ще немає</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($recentOrders as $o):
            $fields = array_filter($o['data'] ?? [], function($v, $k) { return $v && !str_starts_with($k, '_'); }, ARRAY_FILTER_USE_BOTH);
            $preview = implode(', ', array_slice(array_values($fields), 0, 2));
          ?>
          <div style="border-bottom:1px solid var(--c-border);padding-bottom:10px;last-child{border:none}">
            <div style="font-size:12px;font-weight:600;color:var(--c-text);margin-bottom:2px"><?= htmlspecialchars(mb_strimwidth($preview ?: '—', 0, 36, '...')) ?></div>
            <div style="font-size:11px;color:var(--c-muted);display:flex;justify-content:space-between">
              <a href="orders.php?slug=<?= urlencode($o['_landing_slug']) ?>" style="color:var(--c-primary);text-decoration:none;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['_landing_title']) ?></a>
              <span><?= date('d.m H:i', strtotime($o['created_at'])) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </aside>
    </div><!-- /dashboard-layout -->
  </main>
</div>

<!-- Quick Preview Modal -->
<div class="modal" id="previewModal">
  <div class="modal-overlay" onclick="closePreview()"></div>
  <div class="modal-content modal-preview">
    <div class="modal-header">
      <h2 id="previewTitle">Перегляд</h2>
      <div style="display:flex;gap:8px;align-items:center">
        <div class="preview-device-btns">
          <button class="device-btn active" onclick="setPreviewDevice('mobile')" id="previewMobileBtn" title="Мобільний">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
          </button>
          <button class="device-btn" onclick="setPreviewDevice('desktop')" id="previewDesktopBtn" title="Десктоп">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
          </button>
        </div>
        <a id="previewOpenLink" href="#" target="_blank" class="btn btn-ghost btn-sm">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Відкрити
        </a>
        <button class="modal-close" onclick="closePreview()">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
    <div class="preview-modal-body" id="previewModalBody">
      <iframe id="previewIframe" src="about:blank" frameborder="0"></iframe>
    </div>
  </div>
</div>

<!-- Create Modal -->
<div class="modal" id="createModal">
  <div class="modal-overlay" onclick="hideCreateModal()"></div>
  <div class="modal-content">
    <div class="modal-header">
      <h2>Новий лендинг</h2>
      <button class="modal-close" onclick="hideCreateModal()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <form id="createForm" onsubmit="createLanding(event)">
      <div class="form-field">
        <label>Назва лендингу</label>
        <input type="text" id="newTitle" placeholder="Наприклад: Крем для обличчя" required autofocus>
      </div>
      <div class="form-field">
        <label>URL (slug) <span class="muted">— залиште порожнім для авто</span></label>
        <input type="text" id="newSlug" placeholder="cream-for-face">
      </div>
      <div class="form-field">
        <label>Шаблон</label>
        <div id="tplGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:4px">
          <div class="loading-spinner" style="grid-column:span 2;font-size:13px;color:var(--c-muted)">Завантаження...</div>
        </div>
        <input type="hidden" id="newTemplate" value="blank">
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="hideCreateModal()">Скасувати</button>
        <button type="submit" class="btn btn-primary">Створити</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL  = <?= json_encode(ADMIN_URL) ?>;
const CSRF_TOKEN = <?= json_encode(Auth::csrf()) ?>;
async function showCreateModal() {
  document.getElementById('createModal').classList.add('active');
  setTimeout(() => document.getElementById('newTitle').focus(), 100);
  // Load templates
  const grid = document.getElementById('tplGrid');
  const res  = await api('landing_templates', {});
  if (res.success) {
    grid.innerHTML = res.templates.map(t => `
      <label style="border:1.5px solid var(--c-border);border-radius:8px;padding:10px 12px;cursor:pointer;display:flex;gap:8px;align-items:flex-start;transition:.15s" id="tpl_lbl_${t.id}">
        <input type="radio" name="landing_tpl" value="${t.id}" ${t.id==='blank'?'checked':''} style="margin-top:3px" onchange="selectTpl('${t.id}')">
        <div>
          <div style="font-size:13px;font-weight:600">${t.icon||''} ${t.name}</div>
          <div style="font-size:11px;color:var(--c-muted)">${t.desc}</div>
        </div>
      </label>`).join('');
    selectTpl('blank');
  }
}
function selectTpl(id) {
  document.getElementById('newTemplate').value = id;
  document.querySelectorAll('[id^=tpl_lbl_]').forEach(el => {
    el.style.borderColor = el.id === 'tpl_lbl_' + id ? 'var(--c-primary)' : 'var(--c-border)';
    el.style.background  = el.id === 'tpl_lbl_' + id ? 'var(--c-primary-light)' : '';
  });
}
function hideCreateModal() {
  document.getElementById('createModal').classList.remove('active');
}
async function createLanding(e) {
  e.preventDefault();
  const title    = document.getElementById('newTitle').value.trim();
  const slug     = document.getElementById('newSlug').value.trim();
  const template = document.getElementById('newTemplate').value || 'blank';
  if (!title) return;
  const res = await api('landing_create', { title, slug, template });
  if (res.success) {
    window.location.href = 'landing.php?slug=' + encodeURIComponent(res.slug);
  } else {
    alert(res.error || 'Помилка');
  }
}
async function cloneLanding(slug) {
  if (!confirm('Дублювати лендинг?')) return;
  const res = await api('landing_clone', { slug });
  if (res.success) location.reload();
  else alert(res.error || 'Помилка');
}
async function deleteLanding(slug, title) {
  if (!confirm('Видалити лендинг "' + title + '"? Цю дію не можна скасувати.')) return;
  const res = await api('landing_delete', { slug });
  if (res.success) location.reload();
  else alert(res.error || 'Помилка');
}
function toggleDropdown(btn) {
  const menu = btn.nextElementSibling;
  const all = document.querySelectorAll('.dropdown-menu.active');
  all.forEach(m => { if (m !== menu) m.classList.remove('active'); });
  menu.classList.toggle('active');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu.active').forEach(m => m.classList.remove('active'));
  }
});

function quickPreview(url, title) {
  document.getElementById('previewTitle').textContent = title;
  document.getElementById('previewIframe').src = url;
  document.getElementById('previewOpenLink').href = url;
  document.getElementById('previewModal').classList.add('active');
  setPreviewDevice('mobile');
}
function closePreview() {
  document.getElementById('previewModal').classList.remove('active');
  document.getElementById('previewIframe').src = 'about:blank';
}
function setPreviewDevice(d) {
  const iframe = document.getElementById('previewIframe');
  const body   = document.getElementById('previewModalBody');
  document.getElementById('previewMobileBtn').classList.toggle('active', d === 'mobile');
  document.getElementById('previewDesktopBtn').classList.toggle('active', d === 'desktop');
  if (d === 'mobile') {
    body.style.padding = '16px';
    iframe.style.width = '390px';
    iframe.style.maxWidth = '100%';
    iframe.style.borderRadius = '16px';
    iframe.style.boxShadow = '0 8px 32px rgba(0,0,0,.2)';
    iframe.style.margin = '0 auto';
    iframe.style.display = 'block';
  } else {
    body.style.padding = '0';
    iframe.style.width = '100%';
    iframe.style.maxWidth = '';
    iframe.style.borderRadius = '0';
    iframe.style.boxShadow = 'none';
    iframe.style.margin = '';
  }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePreview(); });

// ── Bulk operations ─────────────────────────────────────
let selectMode = false;
function toggleSelectMode() {
  selectMode = !selectMode;
  const cbs  = document.querySelectorAll('.landing-select-cb');
  const bar  = document.getElementById('bulkBar');
  const btn  = document.getElementById('selectModeBtn');
  cbs.forEach(cb => {
    cb.style.display = selectMode ? 'block' : 'none';
    cb.checked = false;
  });
  bar.style.display  = selectMode ? 'flex' : 'none';
  btn.style.background = selectMode ? 'var(--c-primary)' : '';
  btn.style.color      = selectMode ? '#fff' : '';
  if (!selectMode) updateBulkCount();
}
function updateBulkCount() {
  const count = document.querySelectorAll('.landing-select-cb:checked').length;
  document.getElementById('bulkCount').textContent = count + ' вибрано';
}
async function bulkAction(action) {
  const slugs = Array.from(document.querySelectorAll('.landing-select-cb:checked')).map(cb => cb.dataset.slug);
  if (!slugs.length) return alert('Нічого не вибрано');
  const labels = { publish: 'опублікувати', unpublish: 'зняти з публікації', delete: 'видалити' };
  if (!confirm(`${labels[action]} ${slugs.length} лендинг(ів)?`)) return;
  const res = await api('landing_bulk', { bulk_action: action, slugs });
  if (res.success) location.reload();
  else alert(res.error || 'Помилка');
}

// ── QR Code ──────────────────────────────────────────────────────────
const LANDINGS_URL_JS = <?= json_encode(LANDINGS_URL) ?>;

function showQr(slug, title) {
  const url = LANDINGS_URL_JS + '/' + slug + '/';
  const qrSrc = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(url);
  document.getElementById('qrTitle').textContent = title;
  document.getElementById('qrUrl').textContent = url;
  document.getElementById('qrImg').src = qrSrc;
  document.getElementById('qrDownload').href = 'https://api.qrserver.com/v1/create-qr-code/?size=512x512&format=png&data=' + encodeURIComponent(url);
  document.getElementById('qrDownload').download = 'qr-' + slug + '.png';
  document.getElementById('qrModal').style.display = 'flex';
}
function closeQr() { document.getElementById('qrModal').style.display = 'none'; }
</script>

<!-- ═══════════════════════════════════════════════════════════ LIBRARY MODAL -->
<div id="libModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.55);backdrop-filter:blur(2px)">
  <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:16px">
  <div style="background:var(--c-bg,#f8fafc);border-radius:16px;width:100%;max-width:1080px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.22);overflow:hidden">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:14px;padding:18px 24px;border-bottom:1px solid var(--c-border,#e2e8f0);flex-shrink:0;background:var(--c-card,#fff)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--c-primary,#6366f1)" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <h2 style="font-size:18px;font-weight:700;margin:0;flex:1">Бібліотека шаблонів</h2>
      <div style="display:flex;align-items:center;gap:8px">
        <div style="position:relative">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--c-muted,#64748b)" stroke-width="2" style="position:absolute;left:10px;top:50%;transform:translateY(-50%)"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input id="libSearchInput" type="search" placeholder="Пошук шаблону..."
                 style="border:1px solid var(--c-border,#e2e8f0);border-radius:8px;padding:7px 12px 7px 32px;font-size:13px;width:220px;background:var(--c-bg,#f8fafc);color:var(--c-text,#0f172a);outline:none"
                 oninput="libDebounce()">
        </div>
        <button onclick="closeLibrary()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;color:var(--c-muted,#64748b);display:flex" title="Закрити">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>

    <!-- Topics filter -->
    <div id="libTopicsBar" style="display:flex;gap:6px;flex-wrap:nowrap;overflow-x:auto;padding:12px 24px 10px;border-bottom:1px solid var(--c-border,#e2e8f0);flex-shrink:0;background:var(--c-card,#fff);scrollbar-width:thin;scrollbar-color:var(--c-border,#e2e8f0) transparent;cursor:grab;user-select:none">
      <button onclick="libSetTopic('')" id="lib-topic-" class="lib-topic-btn lib-topic-active" style="white-space:nowrap;padding:5px 14px;border-radius:20px;border:1px solid var(--c-primary,#6366f1);background:var(--c-primary,#6366f1);color:#fff;font-size:12px;font-weight:600;cursor:pointer">Всі</button>
    </div>

    <!-- Grid -->
    <div id="libGrid" style="flex:1;min-height:0;overflow-y:auto;padding:20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;align-content:start">
    </div>

    <!-- Loader -->
    <div id="libLoader" style="display:flex;justify-content:center;padding:24px;flex-shrink:0">
      <div style="width:26px;height:26px;border:3px solid var(--c-border,#e2e8f0);border-top-color:var(--c-primary,#6366f1);border-radius:50%;animation:spin .7s linear infinite"></div>
    </div>
    <div id="libEnd" style="display:none;text-align:center;padding:16px;font-size:13px;color:var(--c-muted,#64748b);flex-shrink:0">Всі шаблони завантажено</div>
    <div id="libEmpty" style="display:none;text-align:center;padding:40px 20px;flex-shrink:0">
      <div style="font-size:36px;margin-bottom:10px">📦</div>
      <div style="font-size:15px;font-weight:600;color:var(--c-text,#0f172a);margin-bottom:6px">Шаблонів не знайдено</div>
      <div style="font-size:13px;color:var(--c-muted,#64748b)">Спробуйте змінити фільтр або пошуковий запит</div>
    </div>
    <div id="libScrollAnchor" style="height:1px"></div>

  </div>
  </div>
</div>

<!-- Install dialog -->
<div id="libInstallDlg" style="display:none;position:fixed;inset:0;z-index:9100;background:rgba(15,23,42,.6);align-items:center;justify-content:center">
  <div style="background:var(--c-card,#fff);border-radius:14px;padding:28px;width:90vw;max-width:420px;box-shadow:0 16px 48px rgba(0,0,0,.22)">
    <h3 style="font-size:17px;font-weight:700;margin:0 0 6px">Встановити шаблон</h3>
    <div id="libInstallItemName" style="font-size:13px;color:var(--c-muted,#64748b);margin-bottom:18px"></div>
    <label style="font-size:13px;font-weight:600;display:block;margin-bottom:6px">Назва лендингу</label>
    <input id="libInstallTitle" type="text" class="form-control"
           placeholder="Мій лендинг" style="width:100%;box-sizing:border-box;margin-bottom:18px">
    <div style="display:flex;gap:8px">
      <button id="libInstallBtn" onclick="doInstall()" class="btn btn-primary" style="flex:1">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Встановити
      </button>
      <button onclick="closeInstallDlg()" class="btn btn-ghost">Скасувати</button>
    </div>
  </div>
</div>

<style>
.lib-card {
  background: var(--c-card,#fff);
  border: 1px solid var(--c-border,#e2e8f0);
  border-radius: var(--radius,10px);
  display: flex;
  flex-direction: column;
  transition: box-shadow .15s, transform .15s;
}
.lib-card:hover {
  box-shadow: var(--shadow-md,0 4px 16px rgba(0,0,0,.10));
  transform: translateY(-2px);
}
.lib-card-img {
  height: 140px;
  background: var(--c-hover,#f1f5f9);
  overflow: hidden;
  border-radius: var(--radius,10px) var(--radius,10px) 0 0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.lib-card-img img { width:100%;height:100%;object-fit:cover; }
.lib-card-img-ph {
  font-size: 32px;
  color: var(--c-border,#e2e8f0);
}
.lib-card-body {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.lib-card-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--c-text,#0f172a);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.lib-card-desc {
  font-size: 12px;
  color: var(--c-muted,#64748b);
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.lib-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
  color: var(--c-muted,#64748b);
}
.lib-topic-badge {
  display: inline-block;
  background: var(--c-primary-light,#eef2ff);
  color: var(--c-primary,#6366f1);
  padding: 2px 8px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 600;
}
#libTopicsBar::-webkit-scrollbar { height: 4px; }
#libTopicsBar::-webkit-scrollbar-track { background: transparent; }
#libTopicsBar::-webkit-scrollbar-thumb { background: var(--c-border,#e2e8f0); border-radius: 2px; }
.lib-topic-btn {
  transition: .15s;
  flex-shrink: 0;
}
.lib-topic-btn:not(.lib-topic-active) {
  background: var(--c-bg,#f8fafc) !important;
  color: var(--c-muted,#64748b) !important;
  border-color: var(--c-border,#e2e8f0) !important;
}
.lib-topic-btn:not(.lib-topic-active):hover {
  background: var(--c-hover,#f1f5f9) !important;
  color: var(--c-text,#0f172a) !important;
}
</style>

<script>
const LIB_API  = <?= json_encode(defined('LIBRARY_API_URL') ? LIBRARY_API_URL : '') ?>;
const LIB_CSRF = <?= json_encode(Auth::csrf()) ?>;

let libPage = 1, libLoading = false, libDone = false;

document.getElementById('libGrid').addEventListener('click', e => {
  const btn = e.target.closest('.lib-install-btn');
  if (btn) openInstallDlg(+btn.dataset.id, btn.dataset.title, btn.dataset.title);
});
let libTopic = '', libSearch = '';
let libInstallItemId = 0, libInstallItemTitle = '';
let libSearchTimer = null;
let libTopicsLoaded = false;
let libTopics = [];
let libObserver = null;

function openLibrary() {
  document.getElementById('libModal').style.display = 'block';
  if (!libTopicsLoaded) {
    libReset();
    libLoad();
  }
}
function closeLibrary() {
  document.getElementById('libModal').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLibrary(); closeInstallDlg(); } });

// Drag-to-scroll topics bar
(function(){
  const bar = document.getElementById('libTopicsBar');
  let isDragging = false, startX = 0, scrollLeft = 0, moved = false;
  bar.addEventListener('mousedown', e => {
    isDragging = true; moved = false;
    startX = e.pageX - bar.offsetLeft;
    scrollLeft = bar.scrollLeft;
    bar.style.cursor = 'grabbing';
  });
  document.addEventListener('mouseup', () => {
    isDragging = false;
    bar.style.cursor = 'grab';
  });
  bar.addEventListener('mousemove', e => {
    if (!isDragging) return;
    const dx = e.pageX - bar.offsetLeft - startX;
    if (Math.abs(dx) > 4) moved = true;
    bar.scrollLeft = scrollLeft - dx;
  });
  // Prevent click-firing on buttons after drag
  bar.addEventListener('click', e => { if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; } }, true);
})();

function libReset() {
  libPage = 1; libLoading = false; libDone = false;
  document.getElementById('libGrid').innerHTML = '';
  document.getElementById('libEnd').style.display = 'none';
  document.getElementById('libEmpty').style.display = 'none';
  document.getElementById('libLoader').style.display = 'flex';
  if (libObserver) { libObserver.disconnect(); libObserver = null; }
  setupLibObserver();
}

function setupLibObserver() {
  const anchor = document.getElementById('libScrollAnchor');
  libObserver = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) libLoad();
  }, { root: document.getElementById('libGrid').parentElement, rootMargin: '200px' });
  libObserver.observe(anchor);
}

async function libLoad() {
  if (libLoading || libDone || !LIB_API) return;
  libLoading = true;
  document.getElementById('libLoader').style.display = 'flex';

  const url = `${LIB_API}?type=landing&page=${libPage}&limit=12${libTopic ? '&topic=' + encodeURIComponent(libTopic) : ''}${libSearch ? '&q=' + encodeURIComponent(libSearch) : ''}`;
  try {
    const r = await fetch(url);
    const d = await r.json();

    // Load topics once
    if (!libTopicsLoaded && d.topics) {
      renderTopics(d.topics);
      libTopicsLoaded = true;
    }

    const grid = document.getElementById('libGrid');
    (d.items || []).forEach(item => grid.insertAdjacentHTML('beforeend', renderLibCard(item)));

    if (!d.items?.length || libPage >= (d.pages || 1)) {
      libDone = true;
      document.getElementById('libLoader').style.display = 'none';
      document.getElementById(grid.children.length === 0 ? 'libEmpty' : 'libEnd').style.display = 'block';
    } else {
      libPage++;
      document.getElementById('libLoader').style.display = 'none';
    }
  } catch(e) {
    document.getElementById('libLoader').style.display = 'none';
    if (!document.getElementById('libGrid').children.length)
      document.getElementById('libEmpty').style.display = 'block';
  }
  libLoading = false;
}

function renderTopics(topics) {
  libTopics = topics;
  const bar = document.getElementById('libTopicsBar');
  topics.forEach(t => {
    const btn = document.createElement('button');
    btn.id = 'lib-topic-' + t.slug;
    btn.className = 'lib-topic-btn';
    btn.style.cssText = 'white-space:nowrap;padding:5px 14px;border-radius:20px;border:1px solid var(--c-border,#e2e8f0);background:var(--c-bg,#f8fafc);color:var(--c-muted,#64748b);font-size:12px;font-weight:600;cursor:pointer';
    btn.innerHTML = (t.icon ? t.icon + ' ' : '') + t.label;
    btn.onclick = () => libSetTopic(t.slug);
    bar.appendChild(btn);
  });
}

function libSetTopic(slug) {
  libTopic = slug;
  document.querySelectorAll('.lib-topic-btn').forEach(b => b.classList.remove('lib-topic-active'));
  const active = document.getElementById('lib-topic-' + slug);
  if (active) active.classList.add('lib-topic-active');
  libTopicsLoaded = true;
  libReset(); libLoad();
}

function libDebounce() {
  clearTimeout(libSearchTimer);
  libSearchTimer = setTimeout(() => {
    libSearch = document.getElementById('libSearchInput').value.trim();
    libTopicsLoaded = true;
    libReset(); libLoad();
  }, 350);
}

function renderLibCard(item) {
  const safeTitle = (item.title || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
  const preview   = item.preview_url
    ? '<img src="' + item.preview_url + '" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">'
    : '<div class="lib-card-img-ph">🖼</div>';

  const topicObj  = item.topic ? libTopics.find(function(t){ return t.slug === item.topic; }) : null;
  const badge     = topicObj
    ? '<span class="lib-topic-badge">' + (topicObj.icon ? topicObj.icon + ' ' : '') + topicObj.label + '</span>'
    : (item.topic ? '<span class="lib-topic-badge">' + item.topic + '</span>' : '');

  const desc = item.description ? item.description.slice(0, 80) + (item.description.length > 80 ? '…' : '') : '';

  var html = '<div class="lib-card">';
  html += '<div class="lib-card-img">' + preview + '</div>';
  html += '<div class="lib-card-body">';
  if (badge) html += '<div style="margin-bottom:4px">' + badge + '</div>';
  html += '<div class="lib-card-title">' + safeTitle + '</div>';
  if (desc)  html += '<div class="lib-card-desc">' + desc + '</div>';
  html += '<div class="lib-card-meta">';
  html += '<span>@' + item.author + '</span>';
  html += '<span>⬇ ' + item.downloads + '</span>';
  html += '</div>';
  html += '<div style="display:flex;gap:6px;margin-top:10px">';
  html += '<button class="btn btn-primary btn-sm lib-install-btn" style="flex:1;justify-content:center" data-id="' + item.id + '" data-title="' + safeTitle + '">Встановити</button>';
  if (item.demo_url) {
    html += '<a href="' + item.demo_url + '" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="flex-shrink:0" title="Переглянути демо">Демо</a>';
  }
  if (item.view_url) {
    html += '<a href="' + item.view_url + '" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="flex-shrink:0">Детально</a>';
  }
  html += '</div>';
  html += '</div></div>';
  return html;
}

function openInstallDlg(id, apiTitle, suggestTitle) {
  libInstallItemId    = id;
  libInstallItemTitle = apiTitle;
  document.getElementById('libInstallItemName').textContent = '📦 ' + apiTitle;
  document.getElementById('libInstallTitle').value = suggestTitle || apiTitle;
  const dlg = document.getElementById('libInstallDlg');
  dlg.style.display = 'flex';
  setTimeout(() => document.getElementById('libInstallTitle').focus(), 50);
}
function closeInstallDlg() {
  document.getElementById('libInstallDlg').style.display = 'none';
}

async function doInstall() {
  const title = document.getElementById('libInstallTitle').value.trim();
  if (!title) { document.getElementById('libInstallTitle').focus(); return; }

  const btn = document.getElementById('libInstallBtn');
  btn.disabled = true;
  btn.textContent = 'Встановлення…';

  const fd = new FormData();
  fd.append('_csrf',   LIB_CSRF);
  fd.append('item_id', libInstallItemId);
  fd.append('title',   title);

  try {
    const r   = await fetch(ADMIN_URL + '/library-install.php', { method: 'POST', body: fd });
    const res = await r.json();
    if (res.success) {
      window.location.href = res.edit_url;
    } else {
      alert('Помилка: ' + (res.error || 'Невідома помилка'));
      btn.disabled = false;
      btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Встановити';
    }
  } catch(e) {
    alert('Помилка з\'єднання');
    btn.disabled = false;
    btn.textContent = 'Встановити';
  }
}
</script>

<!-- QR Modal -->
<div id="qrModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center" onclick="if(event.target===this)closeQr()">
  <div style="background:#fff;border-radius:16px;padding:28px;max-width:340px;width:90vw;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.25)">
    <h3 id="qrTitle" style="margin:0 0 6px;font-size:16px;font-weight:700;color:#1e293b"></h3>
    <p id="qrUrl" style="font-size:11px;color:#94a3b8;margin:0 0 20px;word-break:break-all"></p>
    <img id="qrImg" src="" alt="QR Code" style="width:200px;height:200px;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:16px">
    <div style="display:flex;gap:10px;justify-content:center">
      <a id="qrDownload" href="#" target="_blank" class="btn btn-primary btn-sm">Завантажити PNG</a>
      <button onclick="closeQr()" class="btn btn-ghost btn-sm">Закрити</button>
    </div>
  </div>
</div>
</body>
</html>
