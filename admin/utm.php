<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';

Auth::requireLogin();

$landings = (new Landing())->getAll();
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>UTM-генератор — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.utm-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 700px) { .utm-layout { grid-template-columns: 1fr; } }
.utm-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 14px; padding: 24px; }
.utm-card h2 { font-size: 15px; font-weight: 700; margin: 0 0 18px; }
.utm-result { background: var(--c-bg); border: 1.5px solid var(--c-border); border-radius: 10px; padding: 14px 16px; font-size: 13px; font-family: monospace; word-break: break-all; line-height: 1.6; min-height: 60px; color: var(--c-text); margin-bottom: 12px; }
.utm-result.empty { color: var(--c-muted); font-family: inherit; font-style: italic; }
.presets-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.preset-btn { background: var(--c-bg); border: 1.5px solid var(--c-border); border-radius: 8px; padding: 10px 12px; cursor: pointer; text-align: left; font-size: 12px; transition: .15s; }
.preset-btn:hover { border-color: var(--c-primary); background: #eef2ff; }
.preset-btn strong { display: block; font-size: 13px; margin-bottom: 2px; }
.history-list { display: flex; flex-direction: column; gap: 8px; max-height: 400px; overflow-y: auto; }
.history-item { background: var(--c-bg); border: 1px solid var(--c-border); border-radius: 8px; padding: 10px 12px; font-size: 12px; }
.history-item-url { font-family: monospace; word-break: break-all; color: #6366f1; cursor: pointer; }
.history-item-url:hover { text-decoration: underline; }
.history-item-meta { color: var(--c-muted); margin-top: 4px; }
.history-empty { text-align: center; color: var(--c-muted); padding: 24px; font-size: 13px; }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">UTM-генератор</h1>
    </div>

    <div class="utm-layout">
      <!-- Builder -->
      <div>
        <div class="utm-card">
          <h2>Параметри посилання</h2>

          <div class="form-field">
            <label>Лендинг</label>
            <select id="utm_landing" onchange="updateBaseUrl()">
              <option value="">— вибрати —</option>
              <?php foreach ($landings as $l): ?>
              <option value="<?= htmlspecialchars(LANDINGS_URL . '/' . $l['slug'] . '/') ?>">
                <?= htmlspecialchars($l['title']) ?> (<?= htmlspecialchars($l['slug']) ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label>Або вкажіть URL вручну</label>
            <input type="url" id="utm_custom_url" placeholder="https://..." oninput="buildUrl()">
          </div>

          <div class="form-row" style="grid-template-columns:1fr 1fr">
            <div class="form-field">
              <label>utm_source <span style="color:#ef4444">*</span></label>
              <input type="text" id="utm_source" placeholder="facebook" oninput="buildUrl()">
            </div>
            <div class="form-field">
              <label>utm_medium <span style="color:#ef4444">*</span></label>
              <input type="text" id="utm_medium" placeholder="cpc" oninput="buildUrl()">
            </div>
          </div>

          <div class="form-field">
            <label>utm_campaign</label>
            <input type="text" id="utm_campaign" placeholder="spring_sale" oninput="buildUrl()">
          </div>

          <div class="form-row" style="grid-template-columns:1fr 1fr">
            <div class="form-field">
              <label>utm_content</label>
              <input type="text" id="utm_content" placeholder="banner_v1" oninput="buildUrl()">
            </div>
            <div class="form-field">
              <label>utm_term</label>
              <input type="text" id="utm_term" placeholder="keyword" oninput="buildUrl()">
            </div>
          </div>
        </div>

        <!-- Presets -->
        <div class="utm-card" style="margin-top:16px">
          <h2>Швидкі пресети</h2>
          <div class="presets-grid">
            <button class="preset-btn" onclick="applyPreset('facebook','cpc','fb_ad')">
              <strong>Facebook Ads</strong>
              source: facebook / medium: cpc
            </button>
            <button class="preset-btn" onclick="applyPreset('instagram','social','ig_post')">
              <strong>Instagram</strong>
              source: instagram / medium: social
            </button>
            <button class="preset-btn" onclick="applyPreset('google','cpc','google_ad')">
              <strong>Google Ads</strong>
              source: google / medium: cpc
            </button>
            <button class="preset-btn" onclick="applyPreset('tiktok','paid','tiktok_ad')">
              <strong>TikTok Ads</strong>
              source: tiktok / medium: paid
            </button>
            <button class="preset-btn" onclick="applyPreset('telegram','messenger','tg_channel')">
              <strong>Telegram</strong>
              source: telegram / medium: messenger
            </button>
            <button class="preset-btn" onclick="applyPreset('viber','messenger','viber_msg')">
              <strong>Viber</strong>
              source: viber / medium: messenger
            </button>
            <button class="preset-btn" onclick="applyPreset('email','email','newsletter')">
              <strong>Email-розсилка</strong>
              source: email / medium: email
            </button>
            <button class="preset-btn" onclick="applyPreset('sms','sms','sms_blast')">
              <strong>SMS</strong>
              source: sms / medium: sms
            </button>
          </div>
        </div>
      </div>

      <!-- Result & History -->
      <div>
        <div class="utm-card">
          <h2>Результат</h2>
          <div id="utm_result" class="utm-result empty">Заповніть поля ліворуч...</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="copyUrl()" id="copyBtn" disabled>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              Копіювати
            </button>
            <button class="btn btn-ghost" onclick="saveToHistory()" id="saveBtn" disabled>Зберегти</button>
            <button class="btn btn-ghost" onclick="resetForm()">Очистити</button>
          </div>
          <p id="copy-msg" style="font-size:12px;color:#15803d;margin-top:8px;display:none">✓ Скопійовано!</p>
        </div>

        <div class="utm-card" style="margin-top:16px">
          <h2 style="margin-bottom:14px">Збережені посилання</h2>
          <div class="history-list" id="historyList">
            <div class="history-empty">Тут будуть збережені UTM-посилання</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="clearHistory()" style="margin-top:12px;font-size:12px">Очистити історію</button>
        </div>
      </div>
    </div>
  </main>
</div>

<script>
const LANDINGS_URL = <?= json_encode(LANDINGS_URL) ?>;
let currentUrl = '';

function updateBaseUrl() {
  const sel = document.getElementById('utm_landing');
  if (sel.value) {
    document.getElementById('utm_custom_url').value = '';
  }
  buildUrl();
}

function buildUrl() {
  const base = document.getElementById('utm_landing').value
    || document.getElementById('utm_custom_url').value.trim();

  if (!base) {
    setResult('', 'Заповніть поля ліворуч...');
    return;
  }

  const params = new URLSearchParams();
  const fields = ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'];
  let hasRequired = false;
  fields.forEach(id => {
    const v = document.getElementById(id).value.trim();
    if (v) {
      params.set(id, v);
      if (id === 'utm_source' || id === 'utm_medium') hasRequired = true;
    }
  });

  if (!hasRequired) {
    setResult('', 'Вкажіть utm_source та utm_medium...');
    return;
  }

  const sep = base.includes('?') ? '&' : '?';
  currentUrl = base + sep + params.toString();
  setResult(currentUrl);
}

function setResult(url, placeholder = '') {
  const el = document.getElementById('utm_result');
  const copyBtn = document.getElementById('copyBtn');
  const saveBtn = document.getElementById('saveBtn');
  if (url) {
    el.className = 'utm-result';
    el.textContent = url;
    copyBtn.disabled = false;
    saveBtn.disabled = false;
  } else {
    el.className = 'utm-result empty';
    el.textContent = placeholder;
    copyBtn.disabled = true;
    saveBtn.disabled = true;
  }
  currentUrl = url;
}

function copyUrl() {
  if (!currentUrl) return;
  navigator.clipboard?.writeText(currentUrl).then(() => {
    const msg = document.getElementById('copy-msg');
    msg.style.display = 'block';
    clearTimeout(msg._t);
    msg._t = setTimeout(() => msg.style.display = 'none', 2500);
  }).catch(() => prompt('Скопіюйте URL:', currentUrl));
}

function applyPreset(source, medium, content) {
  document.getElementById('utm_source').value = source;
  document.getElementById('utm_medium').value = medium;
  document.getElementById('utm_content').value = content;
  buildUrl();
}

function resetForm() {
  ['utm_source','utm_medium','utm_campaign','utm_content','utm_term'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('utm_custom_url').value = '';
  document.getElementById('utm_landing').value = '';
  setResult('', 'Заповніть поля ліворуч...');
}

// ── History (localStorage) ──────────────────────────────
const STORAGE_KEY = 'Landiro CMS_utm_history';

function getHistory() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch { return []; }
}

function saveToHistory() {
  if (!currentUrl) return;
  const h = getHistory().filter(i => i.url !== currentUrl);
  h.unshift({ url: currentUrl, date: new Date().toLocaleString('uk-UA'), source: document.getElementById('utm_source').value, medium: document.getElementById('utm_medium').value });
  if (h.length > 50) h.pop();
  localStorage.setItem(STORAGE_KEY, JSON.stringify(h));
  renderHistory();
}

function clearHistory() {
  if (!confirm('Очистити всю історію UTM-посилань?')) return;
  localStorage.removeItem(STORAGE_KEY);
  renderHistory();
}

function renderHistory() {
  const list = document.getElementById('historyList');
  const h = getHistory();
  if (!h.length) {
    list.innerHTML = '<div class="history-empty">Тут будуть збережені UTM-посилання</div>';
    return;
  }
  list.innerHTML = h.map(item => `
    <div class="history-item">
      <div class="history-item-url" onclick="navigator.clipboard?.writeText('${item.url.replace(/'/g,"\\'")}').then(()=>this.style.color='#15803d')" title="Клікніть щоб скопіювати">${item.url}</div>
      <div class="history-item-meta">${item.source || '?'} / ${item.medium || '?'} &nbsp;·&nbsp; ${item.date}</div>
    </div>
  `).join('');
}

renderHistory();
</script>
</body>
</html>
