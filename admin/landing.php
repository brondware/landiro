<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Template.php';
require_once dirname(__DIR__) . '/core/Analytics.php';

Auth::requireLogin();

$slug = $_GET['slug'] ?? '';
$landingManager = new Landing();
$landing = $landingManager->get($slug);

if (!$landing) {
    header('Location: ' . ADMIN_URL . '/');
    exit;
}

$types = Template::$SECTION_TYPES;
$stats = (new Analytics())->getStats($slug);
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title><?= htmlspecialchars($landing['title']) ?> — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <div class="page-header-left">
        <a href="<?= ADMIN_URL ?>/" class="btn-back">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <div>
          <h1 class="page-title" id="landingTitleDisplay"><?= htmlspecialchars($landing['title']) ?></h1>
          <p class="page-subtitle"><?= htmlspecialchars($landing['slug']) ?></p>
        </div>
      </div>
      <div class="page-header-right">
        <span class="save-status" id="saveStatus">Збережено</span>
        <a href="<?= LANDINGS_URL ?>/<?= urlencode($landing['slug']) ?>/" target="_blank" class="btn btn-ghost btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Переглянути
        </a>
        <a href="history.php?slug=<?= urlencode($landing['slug']) ?>" class="btn btn-ghost btn-sm" title="Версії та відновлення">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
          Версії
        </a>
        <a href="export.php?slug=<?= urlencode($landing['slug']) ?>" class="btn btn-ghost btn-sm" title="Завантажити ZIP-архів лендингу">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Експорт
        </a>
        <button class="btn btn-sm <?= $landing['published'] ? 'btn-outline' : 'btn-primary' ?>" id="publishBtn" onclick="togglePublish()">
          <?= $landing['published'] ? 'Зняти з публікації' : 'Опублікувати' ?>
        </button>
      </div>
    </div>

    <!-- Stats bar -->
    <div class="stats-bar">
      <div class="stats-bar-item">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        <span class="stats-bar-val"><?= number_format($stats['views'] ?? 0) ?></span>
        <span class="stats-bar-label">переглядів</span>
      </div>
      <div class="stats-bar-sep"></div>
      <div class="stats-bar-item">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <span class="stats-bar-val stats-bar-val-green"><?= number_format($stats['orders'] ?? 0) ?></span>
        <span class="stats-bar-label">замовлень</span>
      </div>
      <?php if (($stats['views'] ?? 0) > 0): ?>
      <div class="stats-bar-sep"></div>
      <div class="stats-bar-item">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span class="stats-bar-val"><?= round(($stats['orders'] ?? 0) / $stats['views'] * 100, 1) ?>%</span>
        <span class="stats-bar-label">конверсія</span>
      </div>
      <?php endif; ?>
      <button class="btn btn-ghost btn-sm stats-bar-reset" onclick="resetStats()" title="Скинути статистику">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.05"/></svg>
      </button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" data-tab="sections">Секції</button>
      <button class="tab" data-tab="styles">Стилі</button>
      <button class="tab" data-tab="settings">Налаштування</button>
    </div>

    <!-- Sections Tab -->
    <div class="tab-content active" id="tab-sections">
      <div id="sections-list" class="sections-list">
        <?php if (empty($landing['sections'])): ?>
        <div class="sections-empty" id="sectionsEmpty">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
          <p>Немає секцій. Додайте першу!</p>
        </div>
        <?php endif; ?>
        <?php foreach ($landing['sections'] as $section): ?>
        <?php $typeInfo = $types[$section['type']] ?? ['label' => $section['type'], 'color' => '#888']; ?>
        <div class="section-item" data-id="<?= htmlspecialchars($section['id']) ?>" data-visible="<?= $section['visible'] ? '1' : '0' ?>">
          <div class="section-drag-handle" title="Перетягнути">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="8" y2="6"/><line x1="16" y1="6" x2="16" y2="6"/><line x1="8" y1="12" x2="8" y2="12"/><line x1="16" y1="12" x2="16" y2="12"/><line x1="8" y1="18" x2="8" y2="18"/><line x1="16" y1="18" x2="16" y2="18"/></svg>
          </div>
          <div class="section-type-badge" style="background:<?= htmlspecialchars($typeInfo['color']) ?>20;color:<?= htmlspecialchars($typeInfo['color']) ?>">
            <?= htmlspecialchars($typeInfo['label']) ?>
          </div>
          <div class="section-info">
            <span class="section-template"><?= htmlspecialchars($section['template'] ?? 'кастомна') ?></span>
            <?php if (!($section['visible'] ?? true)): ?>
            <span class="section-hidden-badge">приховано</span>
            <?php endif; ?>
            <?php if (!empty($section['ab_html'])): ?>
            <span class="ab-badge-small">A/B</span>
            <?php endif; ?>
          </div>
          <div class="section-actions">
            <?php if ($section['type'] === 'order-form'): ?>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="openFormBuilder('<?= htmlspecialchars($section['id']) ?>')" title="Конструктор форми" style="color:#0891b2">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01M12 14h.01M8 18h.01M12 18h.01M16 14h.01"/></svg>
            </button>
            <?php endif; ?>
            <a href="editor.php?slug=<?= urlencode($slug) ?>&id=<?= urlencode($section['id']) ?>" class="btn btn-sm btn-outline" title="Редагувати">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              <span class="btn-label">Редагувати</span>
            </a>
            <?php if (!empty($section['ab_html'])): ?>
            <a href="editor.php?slug=<?= urlencode($slug) ?>&id=<?= urlencode($section['id']) ?>&variant=b" class="btn btn-sm btn-ghost" title="Редагувати варіант B" style="font-size:11px;font-weight:700;color:#8b5cf6">B</a>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="clearAbTest('<?= htmlspecialchars($section['id']) ?>')" title="Вимкнути A/B тест" style="color:#ef4444">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <?php else: ?>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="startAbTest('<?= htmlspecialchars($section['id']) ?>')" title="Запустити A/B тест" style="font-size:10px;font-weight:700">A/B</button>
            <?php endif; ?>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="cloneSection('<?= htmlspecialchars($section['id']) ?>')" title="Клонувати секцію">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="toggleSection('<?= htmlspecialchars($section['id']) ?>', this)" title="<?= ($section['visible'] ?? true) ? 'Приховати' : 'Показати' ?>">
              <?php if ($section['visible'] ?? true): ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <?php else: ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
              <?php endif; ?>
            </button>
            <button class="btn btn-sm btn-ghost btn-icon" onclick="savePreset('<?= htmlspecialchars($section['id']) ?>')" title="Зберегти як пресет" style="color:#7c3aed">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            </button>
            <button class="btn btn-sm btn-ghost btn-icon danger" onclick="deleteSection('<?= htmlspecialchars($section['id']) ?>')" title="Видалити">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <button class="btn-add-section" onclick="showAddSection()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Додати секцію
      </button>
    </div>

    <!-- Global Styles Tab -->
    <div class="tab-content" id="tab-styles">
      <div class="settings-form">
        <h3 class="settings-section-title">Кольори</h3>
        <div class="form-row">
          <div class="form-field">
            <label>Основний колір</label>
            <div class="color-input-wrap">
              <input type="color" id="gs_primary" value="<?= htmlspecialchars($landing['global_styles']['primary_color'] ?? '#FF5A1F') ?>">
              <input type="text" class="color-text" id="gs_primary_text" value="<?= htmlspecialchars($landing['global_styles']['primary_color'] ?? '#FF5A1F') ?>">
            </div>
          </div>
          <div class="form-field">
            <label>Другорядний колір</label>
            <div class="color-input-wrap">
              <input type="color" id="gs_secondary" value="<?= htmlspecialchars($landing['global_styles']['secondary_color'] ?? '#1A1A2E') ?>">
              <input type="text" class="color-text" id="gs_secondary_text" value="<?= htmlspecialchars($landing['global_styles']['secondary_color'] ?? '#1A1A2E') ?>">
            </div>
          </div>
          <div class="form-field">
            <label>Акцентний колір</label>
            <div class="color-input-wrap">
              <input type="color" id="gs_accent" value="<?= htmlspecialchars($landing['global_styles']['accent_color'] ?? '#FFD700') ?>">
              <input type="text" class="color-text" id="gs_accent_text" value="<?= htmlspecialchars($landing['global_styles']['accent_color'] ?? '#FFD700') ?>">
            </div>
          </div>
          <div class="form-field">
            <label>Колір тексту</label>
            <div class="color-input-wrap">
              <input type="color" id="gs_text" value="<?= htmlspecialchars($landing['global_styles']['text_color'] ?? '#333333') ?>">
              <input type="text" class="color-text" id="gs_text_text" value="<?= htmlspecialchars($landing['global_styles']['text_color'] ?? '#333333') ?>">
            </div>
          </div>
        </div>

        <h3 class="settings-section-title">Шрифт</h3>
        <div class="form-field">
          <label>Шрифт (Google Fonts або системний)</label>
          <select id="gs_font" class="select-input">
            <?php
            $fonts = ['Inter, sans-serif', 'Roboto, sans-serif', 'Open Sans, sans-serif', 'Montserrat, sans-serif', 'Raleway, sans-serif', 'Nunito, sans-serif', 'Poppins, sans-serif', 'Ubuntu, sans-serif', 'Arial, sans-serif'];
            $currentFont = $landing['global_styles']['font_family'] ?? 'Inter, sans-serif';
            foreach ($fonts as $f): ?>
            <option value="<?= htmlspecialchars($f) ?>" <?= $currentFont === $f ? 'selected' : '' ?>><?= explode(',', $f)[0] ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <h3 class="settings-section-title">Кастомний CSS</h3>
        <div class="form-field">
          <label>Глобальні стилі (застосовуються до всього лендингу)</label>
          <textarea id="gs_custom_css" class="code-textarea" rows="8" placeholder="/* Ваш CSS тут */"><?= htmlspecialchars($landing['global_styles']['custom_css'] ?? '') ?></textarea>
        </div>

        <button class="btn btn-primary" onclick="saveGlobalStyles()">Зберегти стилі</button>
      </div>
    </div>

    <!-- Settings Tab -->
    <div class="tab-content" id="tab-settings">
      <div class="settings-form">
        <h3 class="settings-section-title">Загальне</h3>
        <div class="form-field">
          <label>Назва лендингу</label>
          <input type="text" id="set_title" value="<?= htmlspecialchars($landing['title']) ?>">
        </div>

        <h3 class="settings-section-title">SEO</h3>
        <div class="form-field">
          <label>Title (заголовок браузера)</label>
          <input type="text" id="seo_title" value="<?= htmlspecialchars($landing['seo']['title'] ?? '') ?>">
        </div>
        <div class="form-field">
          <label>Description</label>
          <textarea id="seo_description" rows="3"><?= htmlspecialchars($landing['seo']['description'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
          <label>OG Image URL (для соцмереж)</label>
          <input type="text" id="seo_og_image" value="<?= htmlspecialchars($landing['seo']['og_image'] ?? '') ?>" placeholder="https://...">
        </div>
        <div class="form-field">
          <label>Favicon URL</label>
          <input type="text" id="seo_favicon" value="<?= htmlspecialchars($landing['seo']['favicon'] ?? '') ?>" placeholder="https://...">
        </div>

        <h3 class="settings-section-title">Аналітика та пікселі</h3>
        <div class="form-row">
          <div class="form-field">
            <label>Google Analytics ID</label>
            <input type="text" id="sc_ga" value="<?= htmlspecialchars($landing['scripts']['ga_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
          </div>
          <div class="form-field">
            <label>Facebook Pixel ID</label>
            <input type="text" id="sc_fb" value="<?= htmlspecialchars($landing['scripts']['fb_pixel'] ?? '') ?>" placeholder="123456789">
          </div>
          <div class="form-field">
            <label>GTM ID</label>
            <input type="text" id="sc_gtm" value="<?= htmlspecialchars($landing['scripts']['gtm_id'] ?? '') ?>" placeholder="GTM-XXXXXXX">
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>TikTok Pixel ID</label>
            <input type="text" id="sc_tt" value="<?= htmlspecialchars($landing['scripts']['tt_pixel'] ?? '') ?>" placeholder="C1XXXXXXXXXXXXXXXXXX">
          </div>
          <div class="form-field">
            <label>Snapchat Pixel ID</label>
            <input type="text" id="sc_snap" value="<?= htmlspecialchars($landing['scripts']['snap_pixel'] ?? '') ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx">
          </div>
        </div>

        <h3 class="settings-section-title">Webhook</h3>
        <div class="form-field">
          <label>URL для webhook <span class="muted">(POST з даними замовлення: Zapier, Make, власний сервер)</span></label>
          <input type="url" id="set_webhook_url" value="<?= htmlspecialchars($landing['webhook_url'] ?? '') ?>" placeholder="https://hooks.zapier.com/hooks/catch/...">
        </div>
        <div style="display:flex;gap:8px;margin-top:4px">
          <button class="btn btn-ghost btn-sm" onclick="testWebhook()" type="button">Надіслати тест</button>
        </div>
        <p id="webhook-status" style="font-size:12px;margin-top:8px;display:none"></p>

        <h3 class="settings-section-title">Кастомні скрипти</h3>
        <div class="form-field">
          <label>Скрипт у &lt;head&gt;</label>
          <textarea id="sc_head" class="code-textarea" rows="4"><?= htmlspecialchars($landing['scripts']['head'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
          <label>Скрипт перед &lt;/body&gt;</label>
          <textarea id="sc_body" class="code-textarea" rows="4"><?= htmlspecialchars($landing['scripts']['body_end'] ?? '') ?></textarea>
        </div>

        <h3 class="settings-section-title">Захист</h3>
        <div class="form-field">
          <label>Пароль для перегляду <span class="muted">(залиште порожнім для відкритого доступу)</span></label>
          <input type="text" id="set_password" value="<?= htmlspecialchars($landing['password'] ?? '') ?>" placeholder="секретний пароль">
        </div>

        <h3 class="settings-section-title">Після відправки форми</h3>
        <div class="form-field">
          <label>URL сторінки подяки <span class="muted">(залиште порожнім — показати повідомлення на місці)</span></label>
          <input type="url" id="set_success_url" value="<?= htmlspecialchars($landing['success_url'] ?? '') ?>" placeholder="https://example.com/dyakuemo">
        </div>

        <button class="btn btn-primary" onclick="saveSettings()" style="margin-bottom:8px">Зберегти налаштування</button>

        <h3 class="settings-section-title" style="margin-top:28px">Popup</h3>
        <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" id="popup_enabled" <?= !empty($landing['popup']['enabled']) ? 'checked' : '' ?> style="width:15px;height:15px">
            Увімкнути popup
          </label>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Тип тригера</label>
            <select id="popup_trigger" class="select-input">
              <option value="delay" <?= ($landing['popup']['trigger'] ?? 'delay') === 'delay' ? 'selected' : '' ?>>Затримка (секунд)</option>
              <option value="exit" <?= ($landing['popup']['trigger'] ?? '') === 'exit' ? 'selected' : '' ?>>Exit-intent (курсор покидає)</option>
            </select>
          </div>
          <div class="form-field">
            <label>Затримка (сек)</label>
            <input type="number" id="popup_delay" value="<?= (int)($landing['popup']['delay'] ?? 5) ?>" min="1" max="60">
          </div>
        </div>
        <div class="form-field">
          <label>HTML вмісту popup</label>
          <textarea id="popup_html" class="code-textarea" rows="6" placeholder="<h2>Спеціальна пропозиція!</h2>..."><?= htmlspecialchars($landing['popup']['html'] ?? '') ?></textarea>
        </div>
        <div class="form-field">
          <label>CSS стилів popup (необов'язково)</label>
          <textarea id="popup_css" class="code-textarea" rows="3" placeholder="/* стилі popup */"><?= htmlspecialchars($landing['popup']['css'] ?? '') ?></textarea>
        </div>
        <button class="btn btn-primary" onclick="savePopup()">Зберегти popup</button>

        <h3 class="settings-section-title" style="margin-top:28px">Sticky CTA-бар</h3>
        <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" id="bar_enabled" <?= !empty($landing['sticky_bar']['enabled']) ? 'checked' : '' ?> style="width:15px;height:15px">
            Увімкнути sticky бар
          </label>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Текст / підпис</label>
            <input type="text" id="bar_text" value="<?= htmlspecialchars($landing['sticky_bar']['text'] ?? 'Замовте зараз!') ?>">
          </div>
          <div class="form-field">
            <label>Телефон (опціонально)</label>
            <input type="tel" id="bar_phone" value="<?= htmlspecialchars($landing['sticky_bar']['phone'] ?? '') ?>" placeholder="+380991234567">
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>Текст кнопки</label>
            <input type="text" id="bar_btn_text" value="<?= htmlspecialchars($landing['sticky_bar']['button_text'] ?? 'Замовити') ?>">
          </div>
          <div class="form-field">
            <label>Посилання кнопки</label>
            <input type="text" id="bar_btn_link" value="<?= htmlspecialchars($landing['sticky_bar']['button_link'] ?? '#orderForm') ?>" placeholder="#orderForm або https://...">
          </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Колір фону</label>
            <div class="color-input-wrap">
              <input type="color" id="bar_bg" value="<?= htmlspecialchars($landing['sticky_bar']['bg_color'] ?? '#FF5A1F') ?>">
              <input type="text" class="color-text" id="bar_bg_text" value="<?= htmlspecialchars($landing['sticky_bar']['bg_color'] ?? '#FF5A1F') ?>">
            </div>
          </div>
          <div class="form-field">
            <label>Колір тексту</label>
            <div class="color-input-wrap">
              <input type="color" id="bar_fg" value="<?= htmlspecialchars($landing['sticky_bar']['text_color'] ?? '#ffffff') ?>">
              <input type="text" class="color-text" id="bar_fg_text" value="<?= htmlspecialchars($landing['sticky_bar']['text_color'] ?? '#ffffff') ?>">
            </div>
          </div>
        </div>
        <button class="btn btn-primary" onclick="saveStickyBar()">Зберегти бар</button>

        <h3 class="settings-section-title" style="margin-top:28px">Floating Contact Widget</h3>
        <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" id="fw_enabled" <?= !empty($landing['floating_widget']['enabled']) ? 'checked' : '' ?> style="width:15px;height:15px">
            Увімкнути floating кнопки
          </label>
        </div>
        <div class="form-field">
          <label>Позиція</label>
          <select id="fw_position" class="select-input">
            <option value="right" <?= ($landing['floating_widget']['position'] ?? 'right') === 'right' ? 'selected' : '' ?>>Праворуч</option>
            <option value="left" <?= ($landing['floating_widget']['position'] ?? '') === 'left' ? 'selected' : '' ?>>Ліворуч</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#25D366" stroke-width="2.5" style="vertical-align:middle"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
              WhatsApp номер
            </label>
            <input type="tel" id="fw_whatsapp" value="<?= htmlspecialchars($landing['floating_widget']['whatsapp'] ?? '') ?>" placeholder="+380991234567">
          </div>
          <div class="form-field">
            <label>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#7360F2" stroke-width="2.5" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/></svg>
              Viber номер
            </label>
            <input type="tel" id="fw_viber" value="<?= htmlspecialchars($landing['floating_widget']['viber'] ?? '') ?>" placeholder="+380991234567">
          </div>
        </div>
        <div class="form-row">
          <div class="form-field">
            <label>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#2AABEE" stroke-width="2.5" style="vertical-align:middle"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
              Telegram @username або номер
            </label>
            <input type="text" id="fw_telegram" value="<?= htmlspecialchars($landing['floating_widget']['telegram'] ?? '') ?>" placeholder="username або +380...">
          </div>
          <div class="form-field">
            <label>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#FF5A1F" stroke-width="2.5" style="vertical-align:middle"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.22h3a2 2 0 0 1 2 1.72"/></svg>
              Телефон (click-to-call)
            </label>
            <input type="tel" id="fw_phone" value="<?= htmlspecialchars($landing['floating_widget']['phone'] ?? '') ?>" placeholder="+380991234567">
          </div>
        </div>
        <button class="btn btn-primary" onclick="saveFloatingWidget()">Зберегти widget</button>
      </div>

      <!-- Countdown Timer -->
      <div class="settings-section">
        <h3 class="settings-section-title">Таймер зворотного відліку</h3>
        <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" id="cd_enabled" <?= !empty($landing['countdown']['enabled']) ? 'checked' : '' ?> style="width:15px;height:15px">
            Увімкнути таймер
          </label>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Тип таймера</label>
            <select id="cd_type" class="select-input" onchange="toggleCountdownType()">
              <option value="session" <?= ($landing['countdown']['type'] ?? 'session') === 'session' ? 'selected' : '' ?>>Плаваючий (скидається щосесії)</option>
              <option value="fixed" <?= ($landing['countdown']['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Фіксована дата</option>
            </select>
          </div>
          <div class="form-field" id="cd_fixed_wrap" style="<?= ($landing['countdown']['type'] ?? 'session') === 'fixed' ? '' : 'display:none' ?>">
            <label>Дата/час завершення</label>
            <input type="datetime-local" id="cd_end_date" value="<?= htmlspecialchars($landing['countdown']['end_date'] ?? '') ?>">
          </div>
        </div>
        <div class="form-row" id="cd_duration_wrap" style="grid-template-columns:1fr 1fr 1fr;<?= ($landing['countdown']['type'] ?? 'session') === 'fixed' ? 'display:none' : '' ?>">
          <div class="form-field">
            <label>Годин</label>
            <input type="number" id="cd_hours" min="0" max="99" value="<?= (int)($landing['countdown']['hours'] ?? 0) ?>" placeholder="0">
          </div>
          <div class="form-field">
            <label>Хвилин</label>
            <input type="number" id="cd_minutes" min="0" max="59" value="<?= (int)($landing['countdown']['minutes'] ?? 30) ?>" placeholder="30">
          </div>
          <div class="form-field">
            <label>Секунд</label>
            <input type="number" id="cd_seconds" min="0" max="59" value="<?= (int)($landing['countdown']['seconds'] ?? 0) ?>" placeholder="0">
          </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Текст до таймера</label>
            <input type="text" id="cd_label_before" value="<?= htmlspecialchars($landing['countdown']['label_before'] ?? 'Акція закінчується через:') ?>" placeholder="Акція закінчується через:">
          </div>
          <div class="form-field">
            <label>Текст після закінчення</label>
            <input type="text" id="cd_label_expired" value="<?= htmlspecialchars($landing['countdown']['label_expired'] ?? 'Акція завершена') ?>" placeholder="Акція завершена">
          </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Колір фону</label>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="color" id="cd_bg_color" value="<?= htmlspecialchars($landing['countdown']['bg_color'] ?? '#1e293b') ?>" style="width:40px;height:34px;padding:2px;border:1.5px solid var(--c-border);border-radius:6px;cursor:pointer">
              <input type="text" id="cd_bg_color_text" value="<?= htmlspecialchars($landing['countdown']['bg_color'] ?? '#1e293b') ?>" style="flex:1" oninput="document.getElementById('cd_bg_color').value=this.value">
            </div>
          </div>
          <div class="form-field">
            <label>Колір тексту</label>
            <div style="display:flex;gap:8px;align-items:center">
              <input type="color" id="cd_text_color" value="<?= htmlspecialchars($landing['countdown']['text_color'] ?? '#ffffff') ?>" style="width:40px;height:34px;padding:2px;border:1.5px solid var(--c-border);border-radius:6px;cursor:pointer">
              <input type="text" id="cd_text_color_text" value="<?= htmlspecialchars($landing['countdown']['text_color'] ?? '#ffffff') ?>" style="flex:1" oninput="document.getElementById('cd_text_color').value=this.value">
            </div>
          </div>
        </div>
        <button class="btn btn-primary" onclick="saveCountdown()">Зберегти таймер</button>
      </div>

      <!-- Social Proof Ticker -->
      <div class="settings-section">
        <h3 class="settings-section-title">Соціальний доказ</h3>
        <p class="settings-section-desc">Спливаючі сповіщення «Хтось щойно замовив!» для підвищення довіри та FOMO.</p>
        <div class="form-field" style="flex-direction:row;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px">
            <input type="checkbox" id="sp_enabled" <?= !empty($landing['social_proof']['enabled']) ? 'checked' : '' ?> style="width:15px;height:15px">
            Увімкнути
          </label>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Позиція</label>
            <select id="sp_position" class="select-input">
              <option value="bottom-left" <?= ($landing['social_proof']['position'] ?? 'bottom-left') === 'bottom-left' ? 'selected' : '' ?>>Ліворуч знизу</option>
              <option value="bottom-right" <?= ($landing['social_proof']['position'] ?? '') === 'bottom-right' ? 'selected' : '' ?>>Праворуч знизу</option>
            </select>
          </div>
          <div class="form-field">
            <label>Затримка першого (сек)</label>
            <input type="number" id="sp_delay" min="1" max="120" value="<?= (int)($landing['social_proof']['delay'] ?? 5) ?>">
          </div>
        </div>
        <div class="form-row" style="grid-template-columns:1fr 1fr">
          <div class="form-field">
            <label>Інтервал між сповіщеннями (сек)</label>
            <input type="number" id="sp_interval" min="3" max="300" value="<?= (int)($landing['social_proof']['interval'] ?? 15) ?>">
          </div>
          <div class="form-field">
            <label>Тривалість показу (сек)</label>
            <input type="number" id="sp_duration" min="2" max="30" value="<?= (int)($landing['social_proof']['duration'] ?? 4) ?>">
          </div>
        </div>
        <div class="form-field">
          <label>Записи (JSON-масив)</label>
          <textarea id="sp_entries" rows="6" style="font-family:monospace;font-size:12px" placeholder='[{"name":"Олег","city":"Київ","product":"товар","time":"2 хв тому"}]'><?= htmlspecialchars(json_encode($landing['social_proof']['entries'] ?? [
            ['name' => 'Олег', 'city' => 'Київ', 'product' => '', 'time' => '2 хв тому'],
            ['name' => 'Марина', 'city' => 'Харків', 'product' => '', 'time' => '7 хв тому'],
            ['name' => 'Дмитро', 'city' => 'Одеса', 'product' => '', 'time' => '14 хв тому'],
            ['name' => 'Катерина', 'city' => 'Дніпро', 'product' => '', 'time' => '21 хв тому'],
          ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
          <small class="field-hint">Кожен запис: name, city, product (необов'язково), time</small>
        </div>
        <button class="btn btn-primary" onclick="saveSocialProof()">Зберегти соціальний доказ</button>
      </div>

    </div>
  </main>
</div>

<!-- Add Section Modal -->
<div class="modal" id="addSectionModal">
  <div class="modal-overlay" onclick="hideAddSection()"></div>
  <div class="modal-content modal-large">
    <div class="modal-header">
      <h2>Додати секцію</h2>
      <button class="modal-close" onclick="hideAddSection()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:4px;margin-bottom:16px">
        <button class="btn btn-sm btn-primary" id="addTabTemplates" onclick="switchAddTab('templates')">Шаблони</button>
        <button class="btn btn-sm btn-ghost" id="addTabPresets" onclick="switchAddTab('presets')">Мої пресети</button>
      </div>
      <!-- Step 1: Type selection -->
      <div id="step-type" class="add-step" data-tab="templates">
        <p class="step-label">Крок 1: Оберіть тип секції</p>
        <div class="type-grid">
          <?php foreach ($types as $typeId => $typeInfo): ?>
          <button class="type-card" onclick="selectType('<?= htmlspecialchars($typeId) ?>')" style="--type-color:<?= htmlspecialchars($typeInfo['color']) ?>">
            <span class="type-card-color"></span>
            <span class="type-card-label"><?= htmlspecialchars($typeInfo['label']) ?></span>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
      <!-- Step 2: Template selection -->
      <div id="step-template" class="add-step hidden" data-tab="templates">
        <div class="step-nav">
          <button class="btn btn-ghost btn-sm" onclick="backToType()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Назад
          </button>
          <p class="step-label" id="step2Label">Крок 2: Оберіть шаблон</p>
        </div>
        <div id="templates-grid" class="templates-grid">
          <div class="loading-spinner">Завантаження...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Form Builder Modal -->
<div class="modal" id="formBuilderModal">
  <div class="modal-overlay" onclick="closeFormBuilder()"></div>
  <div class="modal-content" style="max-width:560px;width:95vw">
    <div class="modal-header">
      <h2>Конструктор форми</h2>
      <button class="modal-close" onclick="closeFormBuilder()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--c-muted);margin-bottom:16px">Налаштуйте поля форми. Натисніть «Згенерувати HTML» — секція оновиться.</p>
      <div id="fb-fields" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px"></div>
      <button class="btn btn-ghost btn-sm" onclick="fbAddField()" style="margin-bottom:20px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Додати поле
      </button>
      <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="fbSave()">Згенерувати HTML</button>
        <button class="btn btn-ghost" onclick="closeFormBuilder()">Скасувати</button>
      </div>
      <!-- Presets Tab -->
      <div id="step-presets" class="add-step hidden" data-tab="presets">
        <div id="presets-grid" class="templates-grid">
          <div class="loading-spinner">Завантаження...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const LANDING_SLUG = <?= json_encode($slug) ?>;
const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;
const PUBLISHED = <?= json_encode($landing['published']) ?>;

async function testWebhook() {
  const url = document.getElementById('set_webhook_url')?.value.trim();
  const el  = document.getElementById('webhook-status');
  el.style.display = 'block'; el.style.color = '#64748b'; el.textContent = 'Надсилаємо тест...';
  const res = await api('webhook_test', { url });
  el.style.color = res.success ? '#15803d' : '#dc2626';
  el.textContent  = res.success ? '✓ Тест надіслано успішно!' : '✗ ' + (res.error || 'Помилка');
}

async function saveFloatingWidget() {
  const res = await api('landing_save', {
    slug: LANDING_SLUG,
    floating_widget: {
      enabled:  document.getElementById('fw_enabled').checked,
      position: document.getElementById('fw_position').value,
      whatsapp: document.getElementById('fw_whatsapp').value.trim(),
      viber:    document.getElementById('fw_viber').value.trim(),
      telegram: document.getElementById('fw_telegram').value.trim(),
      phone:    document.getElementById('fw_phone').value.trim(),
    },
  });
  res.success ? showNotice('Floating widget збережено!') : alert(res.error || 'Помилка');
}

function toggleCountdownType() {
  const type = document.getElementById('cd_type').value;
  document.getElementById('cd_fixed_wrap').style.display    = type === 'fixed' ? '' : 'none';
  document.getElementById('cd_duration_wrap').style.display = type === 'session' ? '' : 'none';
}

// Sync color picker <-> text input
document.getElementById('cd_bg_color').addEventListener('input', e => { document.getElementById('cd_bg_color_text').value = e.target.value; });
document.getElementById('cd_text_color').addEventListener('input', e => { document.getElementById('cd_text_color_text').value = e.target.value; });

async function saveCountdown() {
  const res = await api('landing_save', {
    slug: LANDING_SLUG,
    countdown: {
      enabled:       document.getElementById('cd_enabled').checked,
      type:          document.getElementById('cd_type').value,
      end_date:      document.getElementById('cd_end_date').value,
      hours:         parseInt(document.getElementById('cd_hours').value) || 0,
      minutes:       parseInt(document.getElementById('cd_minutes').value) || 30,
      seconds:       parseInt(document.getElementById('cd_seconds').value) || 0,
      label_before:  document.getElementById('cd_label_before').value,
      label_expired: document.getElementById('cd_label_expired').value,
      bg_color:      document.getElementById('cd_bg_color').value,
      text_color:    document.getElementById('cd_text_color').value,
    },
  });
  res.success ? showNotice('Таймер збережено!') : alert(res.error || 'Помилка');
}

async function saveSocialProof() {
  let entries = [];
  try { entries = JSON.parse(document.getElementById('sp_entries').value); } catch { alert('Помилка JSON у записах'); return; }
  const res = await api('landing_save', {
    slug: LANDING_SLUG,
    social_proof: {
      enabled:  document.getElementById('sp_enabled').checked,
      position: document.getElementById('sp_position').value,
      delay:    parseInt(document.getElementById('sp_delay').value) || 5,
      interval: parseInt(document.getElementById('sp_interval').value) || 15,
      duration: parseInt(document.getElementById('sp_duration').value) || 4,
      entries,
    },
  });
  res.success ? showNotice('Соціальний доказ збережено!') : alert(res.error || 'Помилка');
}

async function savePreset(sectionId) {
  const name = prompt('Назва пресету:');
  if (!name?.trim()) return;
  const res = await api('preset_save', { slug: LANDING_SLUG, section_id: sectionId, name: name.trim() });
  res.success ? showNotice('Пресет збережено!') : alert(res.error || 'Помилка');
}

function switchAddTab(tab) {
  document.querySelectorAll('.add-step').forEach(el => el.classList.add('hidden'));
  document.getElementById('addTabTemplates').className = tab === 'templates' ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost';
  document.getElementById('addTabPresets').className   = tab === 'presets'   ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-ghost';
  if (tab === 'templates') {
    document.getElementById('step-type').classList.remove('hidden');
  } else {
    document.getElementById('step-presets').classList.remove('hidden');
    loadPresets();
  }
}

async function loadPresets() {
  const grid = document.getElementById('presets-grid');
  grid.innerHTML = '<div class="loading-spinner">Завантаження...</div>';
  const res = await api('preset_list', {});
  if (!res.success || !res.presets.length) {
    grid.innerHTML = '<p style="text-align:center;color:var(--c-muted);padding:32px;font-size:13px">Немає збережених пресетів.<br>Натисніть іконку 💾 на секції щоб зберегти.</p>';
    return;
  }
  grid.innerHTML = res.presets.map(p => `
    <div class="template-card-sm" style="position:relative">
      <div class="template-card-preview" style="background:#7c3aed15">
        <span style="color:#7c3aed;font-size:13px;font-weight:600">${escHtml(p.name)}</span>
      </div>
      <div class="template-card-info">
        <span class="template-name">${escHtml(p.type)}</span>
        <span class="template-meta">${new Date(p.created_at).toLocaleDateString('uk-UA')}</span>
      </div>
      <div style="display:flex;gap:4px;padding:6px 10px 10px">
        <button class="btn btn-sm btn-primary" style="flex:1;font-size:11px" onclick="usePreset('${escHtml(p.id)}')">Додати</button>
        <button class="btn btn-sm btn-ghost btn-icon danger" onclick="deletePreset('${escHtml(p.id)}',this)" title="Видалити">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
        </button>
      </div>
    </div>
  `).join('');
}

async function usePreset(presetId) {
  const res = await api('preset_use', { slug: LANDING_SLUG, preset_id: presetId });
  if (res.success) {
    hideAddSection();
    location.reload();
  } else { alert(res.error || 'Помилка'); }
}

async function deletePreset(presetId, btn) {
  if (!confirm('Видалити пресет?')) return;
  const res = await api('preset_delete', { id: presetId });
  if (res.success) btn.closest('.template-card-sm').remove();
  else alert(res.error || 'Помилка');
}

function escHtml(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function savePopup() {
  const res = await api('landing_save', {
    slug: LANDING_SLUG,
    popup: {
      enabled: document.getElementById('popup_enabled').checked,
      trigger: document.getElementById('popup_trigger').value,
      delay:   parseInt(document.getElementById('popup_delay').value) || 5,
      html:    document.getElementById('popup_html').value,
      css:     document.getElementById('popup_css').value,
    },
  });
  res.success ? showNotice('Popup збережено!') : alert(res.error || 'Помилка');
}

async function saveStickyBar() {
  const bgEl = document.getElementById('bar_bg_text') || document.getElementById('bar_bg');
  const fgEl = document.getElementById('bar_fg_text') || document.getElementById('bar_fg');
  const res = await api('landing_save', {
    slug: LANDING_SLUG,
    sticky_bar: {
      enabled:     document.getElementById('bar_enabled').checked,
      text:        document.getElementById('bar_text').value,
      phone:       document.getElementById('bar_phone').value,
      button_text: document.getElementById('bar_btn_text').value,
      button_link: document.getElementById('bar_btn_link').value,
      bg_color:    bgEl.value,
      text_color:  fgEl.value,
    },
  });
  res.success ? showNotice('Sticky бар збережено!') : alert(res.error || 'Помилка');
}

async function resetStats() {
  if (!confirm('Скинути всю статистику цього лендингу?')) return;
  const res = await api('analytics_reset', { slug: LANDING_SLUG });
  if (res.success) location.reload();
}

function startAbTest(sectionId) {
  if (!confirm('Запустити A/B тест для цієї секції?\n\nВаріант B буде копією A — відредагуйте його у редакторі.')) return;
  window.location.href = 'editor.php?slug=' + encodeURIComponent(LANDING_SLUG) + '&id=' + encodeURIComponent(sectionId) + '&variant=b';
}

async function clearAbTest(sectionId) {
  if (!confirm('Вимкнути A/B тест? Варіант B буде видалено.')) return;
  const res = await api('section_clear_ab', { slug: LANDING_SLUG, section_id: sectionId });
  if (res.success) location.reload();
  else alert(res.error || 'Помилка');
}
</script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script src="<?= BASE_URL ?>/assets/js/landing.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// ── Color sync for sticky bar ────────────────────────────
['bar_bg','bar_fg'].forEach(id => {
  const picker = document.getElementById(id);
  const text   = document.getElementById(id + '_text');
  if (!picker || !text) return;
  picker.addEventListener('input', () => text.value = picker.value);
  text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; });
});

// ── Form Builder ────────────────────────────────────────
let fbSectionId = null;

const FB_TYPES = [
  { value: 'text',     label: 'Текст' },
  { value: 'tel',      label: 'Телефон' },
  { value: 'email',    label: 'Email' },
  { value: 'textarea', label: 'Textarea' },
  { value: 'select',   label: 'Вибір (select)' },
  { value: 'hidden',   label: 'Прихований' },
];

async function openFormBuilder(sectionId) {
  fbSectionId = sectionId;
  const res = await api('landing_get', { slug: LANDING_SLUG });
  if (!res.success) return;
  const sec = res.landing.sections.find(s => s.id === sectionId);
  const fields = sec?.data?.fields || defaultFields();
  renderFbFields(fields);
  document.getElementById('formBuilderModal').classList.add('open');
}

function closeFormBuilder() {
  document.getElementById('formBuilderModal').classList.remove('open');
  fbSectionId = null;
}

function defaultFields() {
  return [
    { name: 'name',  type: 'text',  label: "Ваше ім'я",   placeholder: "Іван",          required: true,  options: '' },
    { name: 'phone', type: 'tel',   label: 'Номер телефону', placeholder: '+380XXXXXXXXX', required: true,  options: '' },
  ];
}

function renderFbFields(fields) {
  const wrap = document.getElementById('fb-fields');
  wrap.innerHTML = '';
  fields.forEach((f, i) => wrap.appendChild(fbFieldRow(f, i)));
}

function fbFieldRow(f, idx) {
  const row = document.createElement('div');
  row.className = 'fb-row';
  row.style.cssText = 'display:grid;grid-template-columns:1fr 90px 1fr 80px 28px;gap:6px;align-items:center;background:#f8fafc;border:1px solid var(--c-border);border-radius:8px;padding:8px 10px';
  row.dataset.idx = idx;

  const typeOpts = FB_TYPES.map(t => `<option value="${t.value}" ${f.type===t.value?'selected':''}>${t.label}</option>`).join('');

  row.innerHTML = `
    <input type="text" placeholder="Label" value="${esc(f.label||'')}" title="Підпис поля" style="font-size:12px;padding:4px 8px;border:1px solid var(--c-border);border-radius:6px;width:100%">
    <select title="Тип поля" style="font-size:12px;padding:4px 6px;border:1px solid var(--c-border);border-radius:6px;width:100%">${typeOpts}</select>
    <input type="text" placeholder="Placeholder / name=" value="${esc(f.placeholder||f.name||'')}" title="Placeholder або значення" style="font-size:12px;padding:4px 8px;border:1px solid var(--c-border);border-radius:6px;width:100%">
    <label style="font-size:11px;display:flex;align-items:center;gap:4px;cursor:pointer;white-space:nowrap">
      <input type="checkbox" ${f.required?'checked':''} style="width:13px;height:13px"> Обов'язк.
    </label>
    <button onclick="this.closest('.fb-row').remove()" style="width:24px;height:24px;border:none;background:none;color:#ef4444;cursor:pointer;font-size:16px;line-height:1;padding:0">×</button>
  `;
  return row;
}

function fbAddField() {
  const wrap = document.getElementById('fb-fields');
  const idx = wrap.querySelectorAll('.fb-row').length;
  wrap.appendChild(fbFieldRow({ name: '', type: 'text', label: '', placeholder: '', required: false }, idx));
}

function fbGetFields() {
  return Array.from(document.querySelectorAll('.fb-row')).map(row => {
    const inputs = row.querySelectorAll('input[type=text], select');
    const label  = inputs[0].value.trim();
    const type   = inputs[1].value;
    const ph     = inputs[2].value.trim();
    const req    = row.querySelector('input[type=checkbox]').checked;
    const name   = label.toLowerCase().replace(/[^a-z0-9а-яіїє]/gi, '_').replace(/_+/g,'_').replace(/^_|_$/g,'') || ('field_' + Math.random().toString(36).slice(2,6));
    return { name, type, label, placeholder: ph, required: req };
  });
}

async function fbSave() {
  if (!fbSectionId) return;
  const fields = fbGetFields();
  if (!fields.length) return alert('Додайте хоча б одне поле');

  const html = fbGenerateHtml(fields);

  // Save fields to section data and regenerate HTML
  const res = await api('section_update', {
    slug: LANDING_SLUG,
    section_id: fbSectionId,
    data: { html, data: { fields } },
  });

  if (res.success) {
    closeFormBuilder();
    showNotice('Форму оновлено!');
  } else {
    alert(res.error || 'Помилка');
  }
}

function fbGenerateHtml(fields) {
  const rows = fields.map(f => {
    if (f.type === 'textarea') {
      return `  <div class="form-group">
    <label>${esc(f.label)}${f.required?' <span style="color:red">*</span>':''}</label>
    <textarea name="${esc(f.name)}" placeholder="${esc(f.placeholder)}"${f.required?' required':''}></textarea>
  </div>`;
    }
    if (f.type === 'select') {
      const opts = f.placeholder.split(',').map(o => `<option value="${esc(o.trim())}">${esc(o.trim())}</option>`).join('');
      return `  <div class="form-group">
    <label>${esc(f.label)}${f.required?' <span style="color:red">*</span>':''}</label>
    <select name="${esc(f.name)}"${f.required?' required':''}><option value="">— обрати —</option>${opts}</select>
  </div>`;
    }
    if (f.type === 'hidden') {
      return `  <input type="hidden" name="${esc(f.name)}" value="${esc(f.placeholder)}">`;
    }
    return `  <div class="form-group">
    <label>${esc(f.label)}${f.required?' <span style="color:red">*</span>':''}</label>
    <input type="${esc(f.type)}" name="${esc(f.name)}" placeholder="${esc(f.placeholder)}"${f.required?' required':''}>
  </div>`;
  }).join('\n');

  return `<section class="order-form-section">
<div class="container">
  <h2 class="form-title">Замовити зараз</h2>
  <form class="order-form" id="orderForm" onsubmit="return false">
    <input type="text" name="_hp" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0" aria-hidden="true">
${rows}
    <button type="submit" class="btn-submit">Замовити</button>
    <p class="form-privacy">Натискаючи кнопку, ви погоджуєтесь з умовами обробки персональних даних</p>
  </form>
  <div class="form-success" id="formSuccess" style="display:none">
    <div class="success-icon">✓</div>
    <h3>Дякуємо за замовлення!</h3>
    <p>Ми зателефонуємо вам найближчим часом</p>
  </div>
</div>
</section>`;
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showNotice(msg) {
  let t = document.getElementById('cms-notice');
  if (!t) {
    t = document.createElement('div');
    t.id = 'cms-notice';
    t.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;z-index:9999';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.opacity = '0', 3000);
}
</script>
</body>
</html>
