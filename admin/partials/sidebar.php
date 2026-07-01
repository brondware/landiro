<?php
if (UPDATE_ENABLED && !isset($__updateChecked)) {
    require_once dirname(__DIR__, 2) . '/core/Updater.php';
    $__hasUpdate     = Updater::hasUpdate();
    $__updateVersion = Updater::getLatest()['version'] ?? '';
    $__updateChecked = true;
}
?>
<!-- Mobile top bar -->
<div class="mobile-topbar">
  <button class="hamburger" onclick="toggleSidebar()" aria-label="Меню">
    <span></span><span></span><span></span>
  </button>
  <div class="mobile-logo">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
    <span>Landiro CMS</span>
  </div>
</div>

<!-- Overlay -->
<div class="sidebar-overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="3"/><path d="M3 9h18M9 21V9"/></svg>
    <span>Landiro CMS</span>
    <button class="sidebar-close" onclick="closeSidebar()" aria-label="Закрити">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
  <nav class="sidebar-nav">
    <a href="<?= ADMIN_URL ?>/" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      <span>Лендинги</span>
    </a>
    <a href="<?= ADMIN_URL ?>/analytics.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      <span>Аналітика</span>
    </a>
    <a href="<?= ADMIN_URL ?>/orders.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <span>Замовлення</span>
    </a>
    <a href="<?= ADMIN_URL ?>/media.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'media.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
      <span>Медіа</span>
    </a>
    <a href="<?= ADMIN_URL ?>/utm.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'utm.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
      <span>UTM-генератор</span>
    </a>
    <a href="<?= ADMIN_URL ?>/templates.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'templates.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h14a2 2 0 0 0 2-2V7.5L14.5 2H6a2 2 0 0 0-2 2v4"/><polyline points="14 2 14 8 20 8"/><path d="M2 15s1-1 2-1 2 1 3 1 2-1 3-1 2 1 3 1"/><path d="M2 19s1-1 2-1 2 1 3 1 2-1 3-1 2 1 3 1"/></svg>
      <span>Шаблони</span>
    </a>
    <a href="<?= ADMIN_URL ?>/import.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'import.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      <span>Імпорт</span>
    </a>
    <a href="<?= ADMIN_URL ?>/settings.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span>Налаштування</span>
    </a>
    <a href="<?= ADMIN_URL ?>/news.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'news.php' ? 'active' : '' ?>">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>
      <span>Новини</span>
    </a>
    <?php if (defined('LIBRARY_API_URL') && LIBRARY_API_URL): ?>
    <?php $__docsUrl = preg_replace('#/api/library\.php.*$#', '', LIBRARY_API_URL) . '/docs/'; ?>
    <a href="<?= htmlspecialchars($__docsUrl) ?>" target="_blank" class="nav-item">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
      <span>Документація</span>
    </a>
    <?php endif; ?>
    <a href="<?= ADMIN_URL ?>/updates.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'updates.php' ? 'active' : '' ?>" style="position:relative">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
      <span>Оновлення</span>
      <?php if (!empty($__hasUpdate)): ?>
      <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:#22c55e;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px">NEW</span>
      <?php endif; ?>
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item nav-logout">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Вийти</span>
    </a>
  </div>
</aside>

<?php
$__supportUrl = '';
if (defined('LIBRARY_API_URL') && LIBRARY_API_URL) {
    $__supportUrl = preg_replace('#/api/library\.php.*$#', '', LIBRARY_API_URL) . '/support/';
    $__supportApiUrl = preg_replace('#/api/library\.php.*$#', '', LIBRARY_API_URL) . '/api/support.php';
}
if ($__supportUrl):
?>
<!-- ── Floating support button ──────────────────────────────────────────── -->
<button id="supportFab" onclick="openSupportWidget()" title="Підтримка спільноти"
  style="position:fixed;bottom:24px;right:24px;z-index:8000;display:flex;align-items:center;gap:8px;padding:11px 18px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;border-radius:50px;box-shadow:0 4px 20px rgba(99,102,241,.45);cursor:pointer;font-size:14px;font-weight:600;transition:transform .2s,box-shadow .2s">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
  Підтримка
  <span id="supportBadge" style="display:none;background:#ef4444;border-radius:50%;width:8px;height:8px;flex-shrink:0"></span>
</button>
<style>
#supportFab:hover { transform:translateY(-2px); box-shadow:0 6px 28px rgba(99,102,241,.55); }
#supportFab:active { transform:translateY(0); }
</style>

<!-- ── Support Widget Modal ──────────────────────────────────────────────── -->
<div id="supportModal" style="display:none;position:fixed;inset:0;z-index:8500;pointer-events:none">
  <div id="supportModalInner"
    style="position:absolute;bottom:80px;right:24px;width:380px;max-width:calc(100vw - 32px);background:var(--c-card,#fff);border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.22);pointer-events:auto;overflow:hidden;transform:scale(.95) translateY(10px);opacity:0;transition:transform .2s,opacity .2s">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:18px 20px">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:15px;font-weight:700;color:#fff;margin-bottom:3px">Підтримка спільноти</div>
          <div style="font-size:12px;color:rgba(255,255,255,.8)">Задайте питання і отримайте допомогу</div>
        </div>
        <button onclick="closeSupportWidget()" style="background:rgba(255,255,255,.2);border:none;border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- CTA buttons -->
      <div style="display:flex;gap:8px;margin-top:14px">
        <a href="<?= htmlspecialchars($__supportUrl) ?>ask.php" target="_blank"
           style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;background:#fff;color:#6366f1;border-radius:8px;font-size:13px;font-weight:700;text-decoration:none">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Задати питання
        </a>
        <a href="<?= htmlspecialchars($__supportUrl) ?>" target="_blank"
           style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;background:rgba(255,255,255,.2);color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
          Всі питання
        </a>
      </div>
    </div>

    <!-- Recent questions list -->
    <div id="supportQList" style="max-height:300px;overflow-y:auto">
      <div style="padding:16px 20px;text-align:center;color:#94a3b8;font-size:13px">
        <div style="width:20px;height:20px;border:2px solid #e2e8f0;border-top-color:#6366f1;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 8px"></div>
        Завантаження…
      </div>
    </div>

    <div style="padding:10px 20px 14px;border-top:1px solid #f1f5f9;text-align:center">
      <a href="<?= htmlspecialchars($__supportUrl) ?>" target="_blank"
         style="font-size:12px;color:#6366f1;text-decoration:none;font-weight:600">Перейти до всіх питань →</a>
    </div>
  </div>
</div>

<script>
var supportLoaded = false;
var supportOpen   = false;

function openSupportWidget() {
  var modal = document.getElementById('supportModal');
  var inner = document.getElementById('supportModalInner');
  supportOpen = true;
  modal.style.display = 'block';
  requestAnimationFrame(function(){
    requestAnimationFrame(function(){
      inner.style.transform = 'scale(1) translateY(0)';
      inner.style.opacity   = '1';
    });
  });
  if (!supportLoaded) loadSupportQuestions();
  document.addEventListener('click', supportClickOutside);
}

function closeSupportWidget() {
  var inner = document.getElementById('supportModalInner');
  var modal = document.getElementById('supportModal');
  inner.style.transform = 'scale(.95) translateY(10px)';
  inner.style.opacity   = '0';
  supportOpen = false;
  document.removeEventListener('click', supportClickOutside);
  setTimeout(function(){ modal.style.display = 'none'; }, 180);
}

function supportClickOutside(e) {
  var inner = document.getElementById('supportModalInner');
  var fab   = document.getElementById('supportFab');
  if (!inner.contains(e.target) && !fab.contains(e.target)) closeSupportWidget();
}

async function loadSupportQuestions() {
  supportLoaded = true;
  try {
    var r = await fetch(<?= json_encode($__supportApiUrl ?? '') ?>);
    var d = await r.json();
    var el = document.getElementById('supportQList');
    if (!d.items || !d.items.length) {
      el.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px">Питань поки немає</div>';
      return;
    }
    var statusColors = {answered:'#16a34a', open:'#6366f1', closed:'#94a3b8'};
    var statusIcons  = {answered:'✅', open:'❓', closed:'🔒'};
    el.innerHTML = d.items.map(function(q) {
      var d2 = new Date(q.created_at);
      var date = d2.getDate() + '.' + String(d2.getMonth()+1).padStart(2,'0') + '.' + d2.getFullYear();
      return '<a href="' + q.url + '" target="_blank" style="display:block;padding:11px 20px;border-bottom:1px solid #f1f5f9;text-decoration:none;transition:.1s" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'\'">' +
        '<div style="display:flex;align-items:flex-start;gap:8px">' +
        '<span style="font-size:14px;margin-top:1px">' + q.topic_icon + '</span>' +
        '<div style="flex:1;min-width:0">' +
        '<div style="font-size:13px;font-weight:600;color:#0f172a;line-height:1.3;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + q.title.replace(/</g,'&lt;') + '</div>' +
        '<div style="display:flex;align-items:center;gap:8px;font-size:11px;color:#94a3b8">' +
        '<span style="color:' + (statusColors[q.status]||'#6366f1') + ';font-weight:600">' + (statusIcons[q.status]||'❓') + ' ' + (q.status==='answered'?'Відповідь є':q.status==='closed'?'Закрито':'Відкрито') + '</span>' +
        '<span>💬 ' + q.answers + '</span>' +
        '<span>@' + q.author + '</span>' +
        '</div></div></div></a>';
    }).join('');
  } catch(e) {
    document.getElementById('supportQList').innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px">Не вдалося завантажити</div>';
  }
}
</script>
<?php endif; ?>

<script>
function toggleSidebar() {
  document.body.classList.toggle('sidebar-open');
}
function closeSidebar() {
  document.body.classList.remove('sidebar-open');
}
// Close on Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeSidebar();
});
// Close on nav link click (mobile)
document.querySelectorAll('.sidebar .nav-item').forEach(function(el) {
  el.addEventListener('click', function() {
    if (window.innerWidth <= 768) closeSidebar();
  });
});
</script>
