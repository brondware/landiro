/* ================================================================
   Landiro CMS — Section Live Editor  (Phase 2)
   ================================================================ */

let currentMode    = 'visual';
let currentDevice  = 'mobile';
let currentCodeLang = 'html';
let cmEditors      = {};
let sectionVars    = { ...(typeof SECTION_VARS !== 'undefined' ? SECTION_VARS : {}) };
let autoSaveTimer  = null;
let isDirty        = false;

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initCodeMirror();
  initMessageHandler();
  initColorTextSync();
  scheduleAutoSave();

  const frame = document.getElementById('previewFrame');
  if (frame) {
    frame.addEventListener('load', onIframeLoad);
  }
});

function onIframeLoad() {
  /* Small delay — let iframe scripts finish */
  setTimeout(() => {
    restoreVarsInIframe();
  }, 100);
}

function restoreVarsInIframe() {
  const frame = document.getElementById('previewFrame');
  if (!frame || !frame.contentWindow) return;
  Object.entries(sectionVars).forEach(([k, v]) => {
    if (v !== '' && v !== undefined) {
      frame.contentWindow.postMessage({ type: 'update-var', var: k, value: v }, '*');
    }
  });
}

/* ── postMessage handler (iframe → editor) ───────────────── */
function initMessageHandler() {
  window.addEventListener('message', e => {
    if (!e.data || !e.data.type) return;
    switch (e.data.type) {

      case 'html-response':
        /* Iframe sent back its current HTML — update CM silently */
        if (cmEditors['html']) {
          const cur = cmEditors['html'].getValue();
          if (cur !== e.data.html && e.data.html) {
            /* Preserve cursor only when user is actively in code tab */
            const activeEl = document.activeElement;
            const inCM = activeEl && activeEl.closest('.CodeMirror');
            if (!inCM || currentMode !== 'code') {
              cmEditors['html'].setValue(e.data.html);
            }
          }
        }
        break;

      case 'content-changed':
        markDirty();
        break;

      case 'image-click':
        triggerImageUpload(e.data.index, e.data.src);
        break;
    }
  });
}

/* ── Mode switching ───────────────────────────────────────── */
function setMode(mode) {
  currentMode = mode;
  document.querySelectorAll('.mode-tab').forEach(t =>
    t.classList.toggle('active', t.dataset.mode === mode)
  );
  document.querySelectorAll('.editor-panel').forEach(p => p.classList.add('hidden'));
  document.getElementById('mode-' + mode)?.classList.remove('hidden');
  if (mode === 'code' || mode === 'php') {
    setTimeout(() => Object.values(cmEditors).forEach(cm => cm.refresh()), 60);
  }
}

function setDevice(device) {
  currentDevice = device;
  document.querySelectorAll('.device-btn').forEach(b =>
    b.classList.toggle('active', b.dataset.device === device)
  );
  const frame = document.getElementById('previewFrame');
  if (frame) frame.className = 'preview-frame ' + device;
}

/* ── CSS Variables ────────────────────────────────────────── */
function updateCssVar(input) {
  const varName = input.dataset.var;
  let value = input.value;

  if (input.type === 'range') {
    const unit = input.dataset.unit || 'px';
    value = value + unit;
    const display = input.nextElementSibling;
    if (display) display.textContent = value;
  }

  /* Sync color ↔ text inputs */
  if (input.type === 'color') {
    const textInput = input.nextElementSibling;
    if (textInput && textInput.classList.contains('css-var-text')) textInput.value = value;
  }
  if (input.classList.contains('css-var-text')) {
    if (!/^#[0-9a-f]{3,6}$/i.test(value)) return; /* wait for valid hex */
    const colorInput = input.previousElementSibling;
    if (colorInput && colorInput.type === 'color') colorInput.value = value;
  }

  sectionVars[varName] = value;

  const frame = document.getElementById('previewFrame');
  if (frame && frame.contentWindow) {
    frame.contentWindow.postMessage({ type: 'update-var', var: varName, value }, '*');
  }
  markDirty();
}

/* ── Image upload ─────────────────────────────────────────── */
function triggerImageUpload(imgIndex, currentSrc) {
  const input = document.createElement('input');
  input.type  = 'file';
  input.accept = 'image/*';
  input.onchange = async () => {
    const file = input.files[0];
    if (!file) return;
    const res = await uploadFile(file);
    if (res.success) {
      /* Update iframe image */
      const frame = document.getElementById('previewFrame');
      if (frame && frame.contentWindow) {
        frame.contentWindow.postMessage({ type: 'update-image', index: imgIndex, src: res.path }, '*');
      }
      /* Request updated HTML */
      setTimeout(() => {
        frame.contentWindow.postMessage({ type: 'get-html' }, '*');
      }, 150);
      markDirty();
    } else {
      alert(res.error || 'Помилка завантаження');
    }
  };
  input.click();
}

/* Manual image upload button in panel */
function uploadImage(input) {
  if (!input.files[0]) return;
  uploadFile(input.files[0]).then(res => {
    if (res.success) {
      navigator.clipboard?.writeText(res.path).catch(() => {});
      showToast('Зображення завантажено. URL скопійовано в буфер.');
    } else {
      alert(res.error || 'Помилка');
    }
  });
}

async function uploadFile(file) {
  const fd = new FormData();
  fd.append('file', file);
  fd.append('slug', LANDING_SLUG);
  const res = await fetch(ADMIN_URL + '/api.php?action=file_upload', {
    method: 'POST', body: fd
  });
  return await res.json();
}

/* ── CodeMirror ───────────────────────────────────────────── */
function initCodeMirror() {
  const configs = {
    html: { mode: 'htmlmixed' },
    css:  { mode: 'css' },
    js:   { mode: 'javascript' },
    php:  { mode: { name: 'application/x-httpd-php', startOpen: true } },
  };
  Object.entries(configs).forEach(([lang, extra]) => {
    const ta = document.getElementById('editor-' + lang);
    if (!ta) return;
    cmEditors[lang] = CodeMirror.fromTextArea(ta, {
      ...extra,
      theme: 'dracula',
      lineNumbers: true,
      matchBrackets: true,
      autoCloseBrackets: true,
      lineWrapping: false,
      tabSize: 2,
      indentWithTabs: false,
      extraKeys: {
        'Ctrl-S': () => saveSection(),
        'Cmd-S':  () => saveSection(),
      }
    });
    cmEditors[lang].on('change', () => markDirty());
  });
}

function setCodeLang(lang) {
  currentCodeLang = lang;
  document.querySelectorAll('.code-tab').forEach(t =>
    t.classList.toggle('active', t.dataset.lang === lang)
  );
  document.querySelectorAll('.code-panel').forEach(p => p.classList.add('hidden'));
  document.getElementById('code-' + lang)?.classList.remove('hidden');
  setTimeout(() => cmEditors[lang]?.refresh(), 60);
}

async function applyCodeToPreview() {
  /* Save first so iframe re-renders with new code */
  await saveSection(true);
  const frame = document.getElementById('previewFrame');
  if (frame) {
    frame.contentWindow.location.reload();
  }
  setSaveStatus('Застосовано');
}

/* ── Save ─────────────────────────────────────────────────── */
function markDirty() {
  isDirty = true;
  setSaveStatus('Не збережено...');
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(() => saveSection(true), 5000);
}

function scheduleAutoSave() {
  setInterval(() => { if (isDirty) saveSection(true); }, 30000);
}

async function saveSection(silent = false) {
  clearTimeout(autoSaveTimer);

  /* Get current HTML from iframe (waits up to 1.5s) */
  const iframeHtml = await getIframeHtml();
  const html = (iframeHtml !== null) ? iframeHtml : (cmEditors['html']?.getValue() ?? '');

  /* Sync CM HTML silently */
  if (iframeHtml !== null && cmEditors['html']) {
    const cur = cmEditors['html'].getValue();
    if (cur !== iframeHtml && iframeHtml) {
      cmEditors['html'].setValue(iframeHtml);
    }
  }

  const css = cmEditors['css']?.getValue()  ?? '';
  const js  = cmEditors['js']?.getValue()   ?? '';
  const php = cmEditors['php']?.getValue()  ?? '';

  let res;
  if (typeof AB_VARIANT !== 'undefined' && AB_VARIANT === 'b') {
    // Variant B: only save ab_html, CSS/JS/PHP are shared with A
    res = await api('section_set_ab', { slug: LANDING_SLUG, section_id: SECTION_ID, ab_html: html });
  } else {
    res = await api('section_update', {
      slug: LANDING_SLUG,
      section_id: SECTION_ID,
      data: { html, css, js, php, data: { vars: sectionVars } }
    });
  }

  if (res.success) {
    isDirty = false;
    setSaveStatus('Збережено ' + now());
  } else {
    setSaveStatus('Помилка збереження');
    if (!silent) alert(res.error || 'Не вдалося зберегти');
  }
}

/* Promise-based: ask iframe for HTML, wait for response */
function getIframeHtml() {
  return new Promise(resolve => {
    const frame = document.getElementById('previewFrame');
    if (!frame || !frame.contentWindow) { resolve(null); return; }

    let resolved = false;
    const handler = e => {
      if (e.data?.type === 'html-response') {
        resolved = true;
        window.removeEventListener('message', handler);
        resolve(e.data.html ?? null);
      }
    };
    window.addEventListener('message', handler);
    frame.contentWindow.postMessage({ type: 'get-html' }, '*');

    /* Timeout fallback */
    setTimeout(() => {
      if (!resolved) {
        window.removeEventListener('message', handler);
        resolve(null);
      }
    }, 1500);
  });
}

/* ── PHP handler generator ────────────────────────────────── */
function showPhpHandlerConfig(type) {
  const container = document.getElementById('phpHandlerConfig');
  if (!container) return;
  const forms = {
    email: `<div class="form-field" style="padding:10px 0 0">
      <label>Email адреса</label>
      <input type="email" id="php_email" placeholder="you@example.com" style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;margin-bottom:8px">
      <label>Тема листа</label>
      <input type="text" id="php_subject" value="Нове замовлення" style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;margin-bottom:8px">
    </div>`,
    telegram: `<div class="form-field" style="padding:10px 0 0">
      <label>Bot Token</label>
      <input type="text" id="php_tg_token" placeholder="123456:AABxx..." style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;margin-bottom:8px">
      <label>Chat ID</label>
      <input type="text" id="php_tg_chat" placeholder="-100123456789" style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;margin-bottom:8px">
    </div>`,
    webhook: `<div class="form-field" style="padding:10px 0 0">
      <label>Webhook URL</label>
      <input type="url" id="php_webhook" placeholder="https://crm.example.com/webhook" style="width:100%;border:1.5px solid #e2e8f0;border-radius:7px;padding:8px 10px;font-size:13px;margin-bottom:8px">
    </div>`,
    custom: `<p style="color:#64748b;font-size:12px;padding:8px 0">Перейдіть у режим PHP для написання коду.</p>`,
  };
  container.innerHTML = (forms[type] || '');
  if (type && type !== 'custom') {
    container.innerHTML += `<button class="btn btn-outline btn-sm" onclick="generatePhpHandler('${type}')" style="margin-top:4px">Згенерувати PHP код</button>`;
  }
}

function generatePhpHandler(type) {
  const v = id => document.getElementById(id)?.value || '';
  const tpl = {
    email: `<?php
function handleOrderForm(array $data): array {
    $to      = '${v('php_email') || 'your@email.com'}';
    $subject = '${v('php_subject') || 'Нове замовлення'}';
    $body    = "Нове замовлення:\\n";
    foreach ($data as $k => $val) $body .= ucfirst($k) . ': ' . $val . "\\n";
    $body .= "Час: " . date('d.m.Y H:i');
    mail($to, $subject, $body, 'From: noreply@' . $_SERVER['HTTP_HOST']);
    return ['success' => true, 'message' => 'Дякуємо! Ми зв\\'яжемося з вами.'];
}`,
    telegram: `<?php
function handleOrderForm(array $data): array {
    $token  = '${v('php_tg_token') || 'YOUR_BOT_TOKEN'}';
    $chatId = '${v('php_tg_chat') || 'YOUR_CHAT_ID'}';
    $text   = "📦 *Нове замовлення*\\n";
    foreach ($data as $k => $val) $text .= "*" . ucfirst($k) . ":* " . $val . "\\n";
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode(['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown']), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch); curl_close($ch);
    return ['success' => true, 'message' => 'Замовлення прийнято!'];
}`,
    webhook: `<?php
function handleOrderForm(array $data): array {
    $url = '${v('php_webhook') || 'https://your-crm.com/webhook'}';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($data), CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch); curl_close($ch);
    return ['success' => true, 'message' => 'Замовлення відправлено!'];
}`,
  };
  const code = tpl[type] || '';
  if (code && cmEditors['php']) {
    cmEditors['php'].setValue(code);
    setMode('php');
    markDirty();
  }
}

/* ── Helpers ──────────────────────────────────────────────── */
function setSaveStatus(msg) {
  const el = document.getElementById('editorSaveStatus');
  if (el) el.textContent = msg;
}

function now() {
  const d = new Date();
  return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function showToast(msg) {
  let t = document.getElementById('cms-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'cms-toast';
    t.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;z-index:9999;pointer-events:none;transition:opacity .3s';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._timer);
  t._timer = setTimeout(() => { t.style.opacity = '0'; }, 3000);
}

function initColorTextSync() {
  document.querySelectorAll('.color-input-wrap').forEach(wrap => {
    const colorIn = wrap.querySelector('input[type="color"]');
    const textIn  = wrap.querySelector('.color-text, .css-var-text');
    if (!colorIn || !textIn) return;
    colorIn.addEventListener('input', () => { textIn.value = colorIn.value; });
    textIn.addEventListener('input', () => {
      if (/^#[0-9a-f]{6}$/i.test(textIn.value)) colorIn.value = textIn.value;
    });
  });
}

/* api() shared from admin.js */
