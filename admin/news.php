<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
Auth::requireLogin();

$newsApiUrl = defined('NEWS_API_URL') ? NEWS_API_URL : '';
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Новини — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 18px;
}
.news-card {
  background: var(--c-card, #fff);
  border: 1px solid var(--c-border, #e2e8f0);
  border-radius: var(--radius, 10px);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: box-shadow .15s, transform .15s;
  text-decoration: none;
  color: inherit;
}
.news-card:hover {
  box-shadow: var(--shadow-md, 0 4px 16px rgba(0,0,0,.10));
  transform: translateY(-2px);
}
.news-card-cover {
  height: 160px;
  background: var(--c-hover, #f1f5f9);
  overflow: hidden;
  flex-shrink: 0;
}
.news-card-cover img { width:100%;height:100%;object-fit:cover; }
.news-card-cover-placeholder {
  height: 4px;
  background: var(--c-primary, #6366f1);
}
.news-card-body {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}
.news-card-meta {
  font-size: 11px;
  color: var(--c-muted, #64748b);
  text-transform: uppercase;
  letter-spacing: .05em;
}
.news-card-title {
  font-size: 15px;
  font-weight: 700;
  line-height: 1.35;
  color: var(--c-text, #0f172a);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.news-card-excerpt {
  font-size: 13px;
  color: var(--c-muted, #64748b);
  line-height: 1.55;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.news-card-author {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-top: 4px;
}
.news-card-author img { width:20px;height:20px;border-radius:50%;object-fit:cover; }
.news-card-author span { font-size:12px;color:var(--c-muted,#64748b); }
.news-spinner {
  display: flex;
  justify-content: center;
  padding: 32px 0;
}
.spinner {
  width: 28px; height: 28px;
  border: 3px solid var(--c-border, #e2e8f0);
  border-top-color: var(--c-primary, #6366f1);
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.news-empty {
  text-align: center;
  padding: 48px 20px;
  color: var(--c-muted, #64748b);
}
.news-empty-icon { font-size: 40px; margin-bottom: 12px; }
.news-empty-text { font-size: 15px; font-weight: 600; }
.news-end {
  text-align: center;
  padding: 24px 0;
  font-size: 13px;
  color: var(--c-muted, #64748b);
}
.news-source-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--c-primary-light, #eef2ff);
  color: var(--c-primary, #6366f1);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
}
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div>
        <h1 class="page-title">Новини спільноти</h1>
        <div style="margin-top:4px">
          <?php if ($newsApiUrl): ?>
          <a href="<?= htmlspecialchars(str_replace('/api/news.php', '/news/', $newsApiUrl)) ?>"
             target="_blank" class="news-source-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            Landiro Community
          </a>
          <?php else: ?>
          <span style="font-size:12px;color:var(--c-muted)">NEWS_API_URL не налаштовано в config.php</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="news-grid" id="news-grid"></div>
    <div id="news-loader" class="news-spinner"><div class="spinner"></div></div>
    <div id="news-end" class="news-end" style="display:none">Всі новини завантажено</div>
    <div id="news-empty" class="news-empty" style="display:none">
      <div class="news-empty-icon">📰</div>
      <div class="news-empty-text">Новин поки немає</div>
      <div style="font-size:13px;margin-top:6px">Перевірте налаштування NEWS_API_URL або з'єднання з сервером</div>
    </div>
    <div id="scroll-anchor" style="height:1px"></div>
  </main>
</div>

<script>
const NEWS_API = <?= json_encode($newsApiUrl) ?>;
let page = 1, loading = false, done = false, total = 0;

const grid    = document.getElementById('news-grid');
const loader  = document.getElementById('news-loader');
const endMsg  = document.getElementById('news-end');
const empty   = document.getElementById('news-empty');
const anchor  = document.getElementById('scroll-anchor');

function formatDate(str) {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleDateString('uk-UA', { day:'2-digit', month:'long', year:'numeric' });
}

function renderCard(n) {
  const cover = n.cover_url
    ? `<div class="news-card-cover"><img src="${n.cover_url}" alt="" loading="lazy"></div>`
    : `<div class="news-card-cover-placeholder"></div>`;
  const avatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(n.author)}&size=40&background=e2e8f0&color=64748b&rounded=true&bold=true`;
  return `<a href="${n.url}" target="_blank" class="news-card">
    ${cover}
    <div class="news-card-body">
      <div class="news-card-meta">${formatDate(n.published_at)}</div>
      <div class="news-card-title">${n.title}</div>
      <div class="news-card-excerpt">${n.excerpt}</div>
      <div class="news-card-author">
        <img src="${avatar}" alt="">
        <span>${n.author}</span>
      </div>
    </div>
  </a>`;
}

async function loadPage() {
  if (loading || done || !NEWS_API) return;
  loading = true;
  loader.style.display = 'flex';
  try {
    const r = await fetch(`${NEWS_API}?page=${page}&limit=12`);
    const d = await r.json();
    total = d.total || 0;
    (d.news || []).forEach(n => grid.insertAdjacentHTML('beforeend', renderCard(n)));
    if (!d.news?.length || page >= (d.pages || 1)) {
      done = true;
      loader.style.display = 'none';
      if (grid.children.length === 0) {
        empty.style.display = 'block';
      } else {
        endMsg.style.display = 'block';
      }
    } else {
      page++;
      loader.style.display = 'none';
    }
  } catch(e) {
    loader.style.display = 'none';
    if (grid.children.length === 0) empty.style.display = 'block';
  }
  loading = false;
}

// IntersectionObserver for infinite scroll
const observer = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting) loadPage();
}, { rootMargin: '200px' });
observer.observe(anchor);

// Initial load
loadPage();
</script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
