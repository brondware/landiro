// Landing editor page (section list management)

let selectedType = '';
let autoSaveTimer = null;

document.addEventListener('DOMContentLoaded', () => {
  initSortable();
});

function initSortable() {
  const list = document.getElementById('sections-list');
  if (!list || typeof Sortable === 'undefined') return;
  Sortable.create(list, {
    handle: '.section-drag-handle',
    animation: 150,
    ghostClass: 'section-ghost',
    onEnd: async () => {
      const order = [...list.querySelectorAll('.section-item')].map(el => el.dataset.id);
      await api('section_reorder', { slug: LANDING_SLUG, order });
      setSaveStatus('Збережено');
    }
  });
}

function showAddSection() {
  document.getElementById('addSectionModal').classList.add('active');
  // Reset to templates tab
  if (typeof switchAddTab === 'function') switchAddTab('templates');
  else {
    document.getElementById('step-type').classList.remove('hidden');
    document.getElementById('step-template').classList.add('hidden');
  }
}

function hideAddSection() {
  document.getElementById('addSectionModal').classList.remove('active');
  selectedType = '';
}

async function selectType(type) {
  selectedType = type;
  document.getElementById('step-type').classList.add('hidden');
  document.getElementById('step-template').classList.remove('hidden');
  const types = await api('section_types');
  const label = types.types?.[type]?.label || type;
  document.getElementById('step2Label').textContent = 'Крок 2: Шаблон для "' + label + '"';
  const grid = document.getElementById('templates-grid');
  grid.innerHTML = '<div class="loading-spinner">Завантаження...</div>';
  const res = await api('templates_by_type', { type });
  const templates = res.templates || [];
  if (templates.length === 0) {
    grid.innerHTML = '<div class="no-templates"><p>Немає шаблонів для цього типу.</p><button class="btn btn-primary" onclick="addBlankSection()">Додати порожню секцію</button></div>';
    return;
  }
  grid.innerHTML = templates.map(t => `
    <div class="template-card-sm" onclick="addSection('${escHtml(type)}', '${escHtml(t.id_dir)}')">
      <div class="template-card-preview" style="background:${escHtml(res.types?.[type]?.color || '#888')}15">
        <span style="color:${escHtml(res.types?.[type]?.color || '#888')};font-size:13px">${escHtml(t.name || t.id_dir)}</span>
      </div>
      <div class="template-card-info">
        <span>${escHtml(t.name || t.id_dir)}</span>
        ${t.has_php ? '<span class="badge badge-php">PHP</span>' : ''}
        ${t.has_js ? '<span class="badge badge-js">JS</span>' : ''}
      </div>
    </div>
  `).join('') + `<div class="template-card-sm" onclick="addSection('${escHtml(type)}', '')">
    <div class="template-card-preview" style="background:#f1f5f9">
      <span style="color:#94a3b8;font-size:13px">+ Порожня</span>
    </div>
    <div class="template-card-info"><span>Порожня секція</span></div>
  </div>`;
}

function backToType() {
  document.getElementById('step-type').classList.remove('hidden');
  document.getElementById('step-template').classList.add('hidden');
  selectedType = '';
}

async function addBlankSection() {
  await addSection(selectedType, '');
}

async function addSection(type, templateId) {
  const res = await api('section_add', { slug: LANDING_SLUG, type, template: templateId });
  if (res.success) {
    hideAddSection();
    // Додаємо рядок у список без перезавантаження
    const list = document.getElementById('sections-list');
    const empty = document.getElementById('sectionsEmpty');
    if (empty) empty.remove();
    const section = res.section;
    const typeColors = { hero:'#6366f1',benefits:'#f59e0b',product:'#10b981','order-form':'#14b8a6',countdown:'#f97316',faq:'#06b6d4',pricing:'#ef4444',cta:'#dc2626',footer:'#475569',testimonials:'#8b5cf6','text-block':'#78716c',custom:'#6b7280',trust:'#64748b',gallery:'#84cc16',video:'#ec4899','how-it-works':'#3b82f6' };
    const typeLabels = { hero:'Hero / Банер',benefits:'Переваги',product:'Продукт','order-form':'Форма замовлення',countdown:'Таймер',faq:'FAQ',pricing:'Ціна / Оффер',cta:'CTA кнопка',footer:'Підвал',testimonials:'Відгуки','text-block':'Текстовий блок',custom:'Кастомний',trust:'Довіра',gallery:'Галерея',video:'Відео','how-it-works':'Як це працює' };
    const color = typeColors[type] || '#888';
    const label = typeLabels[type] || type;
    const item = document.createElement('div');
    item.className = 'section-item';
    item.dataset.id = section.id;
    item.dataset.visible = '1';
    item.innerHTML = `
      <div class="section-drag-handle" title="Перетягнути"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="8" y2="6"/><line x1="16" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="8" y2="12"/><line x1="16" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="8" y2="18"/><line x1="16" y1="18" x2="16" y2="18"/></svg></div>
      <div class="section-type-badge" style="background:${color}20;color:${color}">${label}</div>
      <div class="section-info"><span class="section-template">${escHtml(templateId || 'кастомна')}</span></div>
      <div class="section-actions">
        <a href="editor.php?slug=${encodeURIComponent(LANDING_SLUG)}&id=${encodeURIComponent(section.id)}" class="btn btn-sm btn-outline">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          <span class="btn-label">Редагувати</span>
        </a>
        <button class="btn btn-sm btn-ghost btn-icon" onclick="toggleSection('${escHtml(section.id)}', this)">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
        <button class="btn btn-sm btn-ghost btn-icon danger" onclick="deleteSection('${escHtml(section.id)}')">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
        </button>
      </div>`;
    list.appendChild(item);
    // Переходимо в редактор одразу
    window.location.href = 'editor.php?slug=' + encodeURIComponent(LANDING_SLUG) + '&id=' + encodeURIComponent(section.id);
  } else {
    alert(res.error || 'Помилка додавання секції');
  }
}

async function cloneSection(sectionId) {
  const res = await api('section_clone', { slug: LANDING_SLUG, section_id: sectionId });
  if (res.success) {
    location.reload();
  } else {
    alert(res.error || 'Помилка клонування секції');
  }
}

async function toggleSection(sectionId, btn) {
  const res = await api('section_toggle', { slug: LANDING_SLUG, section_id: sectionId });
  if (res.success) {
    const item = btn.closest('.section-item');
    item.dataset.visible = res.visible ? '1' : '0';
    const badge = item.querySelector('.section-hidden-badge');
    if (res.visible) {
      if (badge) badge.remove();
      btn.title = 'Приховати';
    } else {
      if (!badge) {
        const info = item.querySelector('.section-info');
        const b = document.createElement('span');
        b.className = 'section-hidden-badge';
        b.textContent = 'приховано';
        info.appendChild(b);
      }
      btn.title = 'Показати';
    }
  }
}

async function deleteSection(sectionId) {
  if (!confirm('Видалити секцію? Це незворотно.')) return;
  const res = await api('section_delete', { slug: LANDING_SLUG, section_id: sectionId });
  if (res.success) {
    const item = document.querySelector('.section-item[data-id="' + sectionId + '"]');
    if (item) item.remove();
    if (!document.querySelector('.section-item')) {
      const list = document.getElementById('sections-list');
      list.innerHTML = '<div class="sections-empty" id="sectionsEmpty"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg><p>Немає секцій. Додайте першу!</p></div>';
    }
  }
}

async function togglePublish() {
  const btn = document.getElementById('publishBtn');
  const isPublished = btn.textContent.includes('Зняти');
  const res = await api('landing_publish', { slug: LANDING_SLUG, published: !isPublished });
  if (res.success) {
    if (!isPublished) {
      btn.textContent = 'Зняти з публікації';
      btn.className = 'btn btn-sm btn-outline';
    } else {
      btn.textContent = 'Опублікувати';
      btn.className = 'btn btn-sm btn-primary';
    }
  }
}

async function saveGlobalStyles() {
  const styles = {
    primary_color: document.getElementById('gs_primary')?.value,
    secondary_color: document.getElementById('gs_secondary')?.value,
    accent_color: document.getElementById('gs_accent')?.value,
    text_color: document.getElementById('gs_text')?.value,
    font_family: document.getElementById('gs_font')?.value,
    custom_css: document.getElementById('gs_custom_css')?.value,
  };
  const res = await api('landing_save', { slug: LANDING_SLUG, global_styles: styles });
  if (res.success) setSaveStatus('Збережено');
  else alert('Помилка збереження');
}

async function saveSettings() {
  const data = {
    slug: LANDING_SLUG,
    title: document.getElementById('set_title')?.value,
    password: document.getElementById('set_password')?.value,
    seo: {
      title: document.getElementById('seo_title')?.value,
      description: document.getElementById('seo_description')?.value,
      og_image: document.getElementById('seo_og_image')?.value,
      favicon: document.getElementById('seo_favicon')?.value,
    },
    scripts: {
      ga_id:     document.getElementById('sc_ga')?.value,
      fb_pixel:  document.getElementById('sc_fb')?.value,
      gtm_id:    document.getElementById('sc_gtm')?.value,
      tt_pixel:  document.getElementById('sc_tt')?.value || '',
      snap_pixel:document.getElementById('sc_snap')?.value || '',
      head:      document.getElementById('sc_head')?.value,
      body_end:  document.getElementById('sc_body')?.value,
    },
    success_url: document.getElementById('set_success_url')?.value || '',
    webhook_url: document.getElementById('set_webhook_url')?.value || '',
  };
  const res = await api('landing_save', data);
  if (res.success) {
    setSaveStatus('Збережено');
    const titleEl = document.getElementById('landingTitleDisplay');
    if (titleEl && data.title) titleEl.textContent = data.title;
  } else {
    alert('Помилка збереження');
  }
}

function setSaveStatus(msg) {
  const el = document.getElementById('saveStatus');
  if (el) el.textContent = msg;
}

function escHtml(str) {
  return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
