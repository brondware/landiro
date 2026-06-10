<script>const CSRF_TOKEN = '<?= Auth::csrf() ?>';</script>
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
  </nav>
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item nav-logout">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      <span>Вийти</span>
    </a>
  </div>
</aside>

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
