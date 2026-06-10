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
usort($recentOrders, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
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
        <?php
          $dlFile = DATA_PATH . '/downloads.json';
          $dlData = file_exists($dlFile) ? json_decode(file_get_contents($dlFile), true) : [];
          $dlCount = (int)($dlData['count'] ?? 0);
          $dlLast  = $dlData['last'] ?? null;
        ?>
        <div style="background:linear-gradient(135deg,#6366f1,#818cf8);border-radius:12px;padding:16px 18px;margin-bottom:16px;color:#fff">
          <div style="font-size:11px;font-weight:600;opacity:.8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">Завантажень CMS</div>
          <div style="font-size:2rem;font-weight:800;line-height:1;margin-bottom:4px"><?= number_format($dlCount) ?></div>
          <?php if ($dlLast): ?>
          <div style="font-size:11px;opacity:.7">Останнє: <?= date('d.m.Y H:i', strtotime($dlLast)) ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
          <h3 style="font-size:14px;font-weight:700;margin:0">Останні замовлення</h3>
          <a href="orders.php" style="font-size:12px;color:var(--c-primary);text-decoration:none">Всі →</a>
        </div>
        <?php if (empty($recentOrders)): ?>
        <p style="font-size:13px;color:var(--c-muted);text-align:center;padding:20px 0">Замовлень ще немає</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($recentOrders as $o):
            $fields = array_filter($o['data'] ?? [], fn($v, $k) => $v && !str_starts_with($k, '_'), ARRAY_FILTER_USE_BOTH);
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
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;
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
