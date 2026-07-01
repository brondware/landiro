# /new-landing — Створення шаблону лендингу для Landiro CMS

Створити готовий шаблон лендингу для публікації в landiro-community за описом: $ARGUMENTS

---

## РОЛЬ І СТАНДАРТ ЯКОСТІ

Виступай як **Senior UI/UX Designer + Creative Director + Frontend Developer** з досвідом створення преміальних лендінгів для SaaS, AI, IT-компаній та дорогих послуг.

Результат має виглядати як продукт digital-агентства вартістю **$5 000–$15 000** — на рівні Awwwards, Land-book та топових агентств рівня 2026 року.

---

## ТЕХНОЛОГІЧНИЙ СТЕК

**Дозволено:**
- HTML5, CSS3, Vanilla JavaScript (ES6+), PHP 8+

**Заборонено:**
- React, Vue, Angular та інші важкі фреймворки

---

## БІБЛІОТЕКИ — ЗАВАНТАЖЕННЯ ЧЕРЕЗ `scripts.head`

Бібліотеки не можна підключати в `template.html` (`<script src>` — заборонено). Підключай через `landing.json → scripts.head`:

```json
"scripts": {
  "head": "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/iconoir@7/css/iconoir.css\">",
  "body_end": "<script src=\"https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1/bundled/lenis.min.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/splitting@1/dist/splitting.min.js\"></script><script src=\"https://cdn.jsdelivr.net/npm/vanilla-tilt@1/dist/vanilla-tilt.min.js\"></script>"
}
```

**Рекомендовані бібліотеки:**
- **GSAP + ScrollTrigger** — scroll-анімації, timeline, parallax
- **Lenis** — плавний скрол
- **Splitting.js** — анімація тексту по літерах/словах
- **Vanilla Tilt.js** — 3D нахил карток на hover
- **Lottie** (за потреби) — SVG-анімації
- **Iconoir або Lucide Icons** — SVG-іконки через CSS клас

**Використання в `script.js`:**

```js
// Перевіряй наявність перед використанням
if (window.gsap && window.ScrollTrigger) {
  gsap.registerPlugin(ScrollTrigger);
  gsap.from('.element', { opacity: 0, y: 40, duration: 0.8, scrollTrigger: { trigger: '.element', start: 'top 80%' } });
}
if (window.Splitting) {
  Splitting({ target: '.headline', by: 'words' });
}
if (window.VanillaTilt) {
  VanillaTilt.init(el.querySelectorAll('.card'), { max: 8, speed: 400, glare: true, 'max-glare': 0.15 });
}
// Lenis — ініціалізуй лише один раз глобально (у hero або першій секції)
if (window.Lenis && !window.__lenis) {
  window.__lenis = new Lenis({ lerp: 0.07, smooth: true });
  function raf(time) { window.__lenis.raf(time); requestAnimationFrame(raf); }
  requestAnimationFrame(raf);
}
```

---

## ОБОВ'ЯЗКОВІ ЕФЕКТИ

### Анімації появи
- **Fade In** + **Blur Reveal** — елементи з'являються з opacity 0 + blur(8px) → blur(0)
- **Parallax** — різна швидкість скролу для фону і контенту
- **Stagger Animations** — картки появляються одна за одною з затримкою
- **Text Reveal** — заголовки по словах (Splitting.js) або масок-рядках

### Візуальні ефекти
- **Aurora Effect** — рухомі кольорові blobs через CSS keyframes + filter: blur
- **Glow Effect** — box-shadow/text-shadow що пульсує або активується на hover
- **Glassmorphism** — `backdrop-filter: blur()` + прозорий border
- **Noise Texture** — SVG-шум через CSS `url("data:image/svg+xml,...")` або pseudo-element
- **Mouse Follow** — курсор-glow або паралакс елементів по mousemove
- **Floating Elements** — CSS keyframes `translateY` + `rotate` для float

### CSS-підходи (обов'язкові)
- `clamp()` для адаптивної типографіки: `font-size: clamp(28px, 5vw, 72px)`
- CSS Container Queries де підходить
- CSS View Transitions API: `@view-transition { navigation: auto; }` (де підтримується)

---

## ТИПОГРАФІКА

**Google Fonts** — підключати в `template.html` першої секції (hero):

```html
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

**Ієрархія:**
- Заголовки: **Space Grotesk** / Sora / Outfit — великий розмір, tight letter-spacing
- Тіло: **Manrope** / Inter / Geist — легкий, читабельний
- Eyebrow: uppercase + wide letter-spacing (0.3–0.5em)
- Багато повітря: `line-height: 1.0–1.15` для заголовків, `1.7–1.85` для тіла

---

## КОЛЬОРОВА СХЕМА

Будуй на CSS Variables. Приклад для темної теми:

```css
:root {
  --bg: #050505;
  --surface: #111111;
  --border: rgba(255,255,255,.07);
  --text: #ffffff;
  --text-muted: rgba(255,255,255,.45);
  --accent: #4F46E5;
  --accent-2: #06B6D4;
  --gold: #E8C97A;
}
```

Підтримуй **градієнти** та **glow**: `box-shadow: 0 0 40px rgba(var(--accent-rgb), .3)`.

---

## ОБОВ'ЯЗКОВІ БЛОКИ

| Блок | Вимоги |
|---|---|
| **Hero** | Заголовок (Splitting animate), підзаголовок, 2 CTA, соціальний доказ (stats/badges), анімований фон (aurora/gradient) |
| **Про компанію** | Короткий trust-блок, числа/факти, фото |
| **Переваги** | Картки з іконками; hover: glow + scale + lift (VanillaTilt або CSS) |
| **Як ми працюємо** | Timeline або 4-кроки; стagger появи |
| **Кейси/Результати** | Великі цифри, статистика, преміальне оформлення |
| **Відгуки** | Картки з ім'ям, посадою, компанією; vanilla slider або grid |
| **FAQ** | Accordion на JS |
| **Фінальний CTA** | Великий блок із заголовком, підзаголовком, 1–2 кнопками |

---

## UX-ВИМОГИ

- **Sticky Header** — nav фіксується при скролі, змінює фон/тінь
- **Active Navigation** — активний пункт підсвічується при скролі секцій
- **Smooth Scroll** — через Lenis або `scroll-behavior: smooth`
- **Hover States** — всі інтерактивні елементи мають `transition` стани
- **Scroll Animations** — IntersectionObserver або GSAP ScrollTrigger
- **Lazy Loading** — `loading="lazy"` на всіх `<img>`
- **Accessibility** — `aria-label`, `aria-expanded`, семантичний HTML

---

## ОПТИМІЗАЦІЯ

- Lighthouse 95+
- Зображення у форматі WebP де можливо
- SVG іконки inline або через CSS клас (Iconoir/Lucide)
- Мінімальний JS — без непотрібних залежностей
- `preconnect` для Google Fonts та CDN

---

## ФОРМАТ ШАБЛОНУ ДЛЯ COMMUNITY

Шаблон — це ZIP-архів або папка з такою структурою:

```
{template-name}/
  meta.json          ← метадані шаблону для бібліотеки
  landing.json       ← повна конфігурація лендингу
  img/               ← зображення (installer копіює в UPLOADS_PATH автоматично)
    offer.jpg
    photo_1.jpg
    ...
  templates/         ← файли секцій (installer копіює в TEMPLATES_PATH)
    {type}/
      {template-id}/
        meta.json
        template.html
        style.css
        script.js    ← тільки якщо потрібна інтерактивність
```

---

## КРОК 1: АНАЛІЗ ОРИГІНАЛЬНОГО HTML

Якщо є оригінальний HTML-файл — **читай уважно перед написанням будь-чого**:

1. Розбий HTML на логічні блоки (секції): hero, countdown, product, testimonials, trust, form, footer
2. Для кожного блоку запиши:
   - точні CSS-класи (`.item-card`, `.featured-media`, `.discount`, `.big-button` тощо)
   - CSS-правила з оригінальних `.css` файлів для цих класів
   - JS-залежності (jQuery, Swiper, countdown.js, inputmask тощо)
   - зовнішні ресурси (шрифти, CDN, зображення)

**Мета:** повне копіювання дизайну, а не нова реалізація.

---

## КРОК 2: СТРУКТУРА ФАЙЛІВ СЕКЦІЇ

Для кожної секції — 4 файли в `templates/{type}/{template-id}/`:

### `meta.json`

**ОБОВ'ЯЗКОВИЙ для кожної секції.** Без нього панель "Кольори та розміри" в редакторі не відображається — користувач не зможе візуально змінювати кольори та тексти секції.

```json
{
  "id": "{template-id}",
  "type": "{type}",
  "name": "Назва шаблону українською",
  "description": "Короткий опис",
  "tags": ["tag1", "tag2"],
  "vars": [
    { "name": "BG_COLOR",     "label": "Колір фону",      "type": "color",  "default": "#ffffff" },
    { "name": "ACCENT_COLOR", "label": "Акцентний колір", "type": "color",  "default": "#d32f2f" },
    { "name": "TITLE",        "label": "Заголовок",       "type": "text",   "default": "Заголовок" },
    { "name": "FONT_SIZE",    "label": "Розмір тексту",   "type": "range",  "default": "16", "min": 12, "max": 32 },
    { "name": "ALIGN",        "label": "Вирівнювання",    "type": "select", "default": "left", "options": ["left","center","right"] }
  ]
}
```

**Типи полів у `vars`:**

| `type`   | UI в редакторі       | Коли використовувати |
|----------|----------------------|----------------------|
| `color`  | Color picker         | Будь-який колір (`BG_COLOR`, `ACCENT_COLOR`) |
| `range`  | Слайдер (px)         | Розміри, відступи (`FONT_SIZE`, `PADDING`) |
| `select` | Випадаючий список    | Вибір з фіксованих варіантів |
| `text`   | Текстовий input      | Тексти, ціни, URL, секунди таймера |

**Що ВКЛЮЧАТИ у `vars`:**
- Всі `{{VAR_NAME}}` з `template.html`
- CSS custom properties типу `var(--BG_COLOR)` → тип `color`
- Числові CSS custom properties типу `var(--TIMER_SECONDS)` → тип `text`

**Що НЕ включати у `vars`:**
- CSS vars з `url(...)` значеннями (зображення в CSS: `--PRODUCT_BG`, `--SALE_BADGE_IMG`) — вони керуються через панель завантаження зображень, а не текстовим полем
- `HTML_` prefixed vars — вони для програмного заповнення, не ручного редагування

---

### `template.html` — КРИТИЧНІ ПРАВИЛА

**НІКОЛИ** не порушувати:

```html
<!-- ✅ ПРАВИЛЬНО -->
<section class="cms-section-hero led-offer-01">
  <div class="item-card">
    <h1>{{TITLE}}</h1>
    <img src="{{PRODUCT_IMAGE}}" alt="{{PRODUCT_ALT}}">
  </div>
</section>

<!-- ❌ ЗАБОРОНЕНО — inline style tag -->
<section>
  <style>.foo { color: red; }</style>   <!-- НЕ МОЖНА -->
</section>

<!-- ❌ ЗАБОРОНЕНО — inline script tag -->
<section>
  <script>console.log('hello');</script>   <!-- НЕ МОЖНА -->
</section>
```

**Правила:**
- Кореневий елемент: `<section class="cms-section-{type} {template-id}">`
- Змінні: `{{VAR_NAME}}` — ВЕЛИКИМИ ЛІТЕРАМИ З ПІДКРЕСЛЕННЯМ
- Без inline `<style>` та `<script>` — тільки в окремих файлах
- Без зовнішніх CDN-скриптів (`<script src="https://...">`)
- Якщо є оригінальні класи з HTML — **залишати ті самі назви класів** (`.item-card`, `.discount`, `.big-button`)
- Для зображень в CSS (background-image): замінити на CSS-змінну або emoji-іконку
- `{{VAR_NAME}}` що не замінені — видаляються Renderer автоматично

**Як Renderer рендерить:**
1. Читає `template.html`
2. Замінює `{{VAR_NAME}}` → значення з `data.vars`
3. Решту `{{ANYTHING}}` видаляє регексом
4. Підключає CSS і JS

---

### `style.css` — КРИТИЧНІ ПРАВИЛА

```css
/* ✅ Всі стилі скоуповані до кореневого класу */
.cms-section-hero {
  background: var(--BG_COLOR, #f5f5f5);   /* CSS custom property від Renderer */
  font-family: 'AvenirNextCyr', -apple-system, Arial, sans-serif;
}

/* ✅ Якщо є оригінальний CSS — копіювати і додавати префікс */
.cms-section-hero .item-card { ... }
.cms-section-hero .featured-media { ... }
.cms-section-hero .discount { ... }

/* ✅ @keyframes — обов'язково з унікальним префіксом щоб не конфліктували між секціями */
@keyframes led-hero-beat {       /* ← префікс "led-hero-" запобігає конфлікту */
  from { transform: rotate(-20deg) scale(1); }
  to   { transform: rotate(-20deg) scale(1.1); }
}

/* ❌ ЗАБОРОНЕНО — глобальні стилі без скоупу */
.item-card { ... }   /* НЕ МОЖНА — ламає інші секції */
body { ... }         /* НЕ МОЖНА */
```

**Як Renderer підключає CSS-змінні з `data.vars`:**

Renderer автоматично генерує для кожної секції:
```css
#section-{id} {
  --BG_COLOR: #f5f5f5;
  --ACCENT_COLOR: #d32f2f;
  /* ... всі ключі з data.vars */
}
```

Тому в CSS можна і треба використовувати:
```css
.cms-section-hero {
  background: var(--BG_COLOR, #f5f5f5);    /* значення з data.vars */
  color: var(--ACCENT_COLOR, #d32f2f);
}
```

**Fallback значення** (`var(--NAME, fallback)`) — обов'язкові для роботи поза CMS.

**Шрифти:** тільки системні. Не підключати Google Fonts або CDN у CSS-файлі шаблону.

---

### `script.js` — КРИТИЧНІ ПРАВИЛА

```js
// ✅ ЗАВЖДИ: IIFE + guard від подвійної ініціалізації
(function () {
  document.querySelectorAll('.cms-section-countdown').forEach(function (el) {
    if (el.dataset.init) return;   // ← guard, бо секція може бути кілька разів
    el.dataset.init = '1';

    // Відносна навігація по DOM — НЕ document.getElementById
    var timerEl = el.querySelector('.countdown__timer');    // ← завжди el.querySelector
    var seconds = parseInt(el.dataset.seconds || '0', 10); // ← дані через data-attr

    // ... логіка
  });
})();

// ❌ ЗАБОРОНЕНО
document.getElementById('timer-123')  // НЕ МОЖНА — ламається при кількох секціях
var swiper = new Swiper(...)           // НЕ МОЖНА — зовнішня бібліотека
$(document).ready(...)                // НЕ МОЖНА — jQuery
```

**Замінники зовнішніх бібліотек:**

| Зовнішня бібліотека | Замінник |
|---|---|
| Swiper.js | Vanilla slider: `swiper-wrapper` translateX + touch events |
| countdown.js | `setInterval` + `innerHTML` рендер `.countdown__item` |
| jQuery inputmask | Vanilla `input` event + regex replace для `+380 XX XXX-XX-XX` |
| jQuery | Нативний DOM API |

**Паттерн слайдера (Swiper-сумісна розмітка):**
```js
// Зберігати ті самі класи що в оригіналі (.swiper-wrapper, .swiper-button-next)
var wrapper = swiper.querySelector('.swiper-wrapper');
function go(idx) {
  current = (idx + total) % total;
  wrapper.style.transform = 'translateX(-' + (current * 100) + '%)';
}
```

**Паттерн відправки в Telegram:**
```js
form.addEventListener('submit', function (e) {
  e.preventDefault();
  var token  = this.dataset.tgToken  || '';   // data-tg-token="{{TELEGRAM_TOKEN}}"
  var chatId = this.dataset.tgChat   || '';   // data-tg-chat="{{TELEGRAM_CHAT_ID}}"
  fetch('https://api.telegram.org/bot' + token + '/sendMessage', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ chat_id: chatId, text: message, parse_mode: 'Markdown' })
  });
});
```

---

## КРОК 3: `landing.json` — СТРУКТУРА

> ⚠️ **ОБОВ'ЯЗКОВИЙ ФОРМАТ** — точна структура, яку читає CMS. Будь-яке відхилення (відсутній `slug`, `title`, неправильні ключі) викликає **500 помилку** в адмін-панелі.

```json
{
  "id": "a1b2c3d4-e5f6-4a7b-8c9d-ef1234567800",
  "slug": "my-landing",
  "title": "Назва лендингу (відображається в адмін-панелі)",
  "created_at": "2026-01-01T12:00:00+02:00",
  "updated_at": "2026-01-01T12:00:00+02:00",
  "published": false,
  "password": "",
  "global_styles": {
    "primary_color": "#d32f2f",
    "secondary_color": "#222222",
    "accent_color": "#ff5722",
    "text_color": "#333333",
    "font_family": "-apple-system, Arial, sans-serif",
    "custom_css": "html{scroll-behavior:smooth;} .cms-section{max-width:100%;}"
  },
  "seo": {
    "title": "SEO заголовок сторінки",
    "description": "Meta description (160 символів)",
    "og_image": "img\/offer.jpg",
    "favicon": ""
  },
  "scripts": {
    "ga_id": "",
    "fb_pixel": "",
    "gtm_id": "",
    "head": "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/iconoir@7/css/iconoir.css\">",
    "body_end": "<script src=\"https://cdn.jsdelivr.net/npm/gsap@3/dist/gsap.min.js\"><\/script><script src=\"https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1/bundled/lenis.min.js\"><\/script><script src=\"https://cdn.jsdelivr.net/npm/vanilla-tilt@1/dist/vanilla-tilt.min.js\"><\/script>"
  },
  "sections": [
    {
      "id": "a1b2c3d4-e5f6-4a7b-8c9d-ef1234567801",
      "type": "hero",
      "template": "{template-id}",
      "visible": true,
      "html": "",
      "css": "",
      "js": "",
      "php": "",
      "data": {
        "vars": {
          "BG_COLOR":       "#f5f5f5",
          "ACCENT_COLOR":   "#d32f2f",
          "PRODUCT_IMAGE":  "img\/offer.jpg",
          "TITLE":          "Заголовок",
          "SUBTITLE":       "Підзаголовок",
          "CTA_TEXT":       "ЗАМОВИТИ ЗАРАЗ",
          "CTA_HREF":       "#order_form"
        }
      }
    }
  ]
}
```

### Обов'язкові поля верхнього рівня

| Поле | Тип | Значення | Навіщо |
|---|---|---|---|
| `id` | string | UUID v4 | Унікальний ідентифікатор |
| `slug` | string | `"my-landing"` | URL та ім'я файлу — **обов'язковий**, без нього 500 помилка |
| `title` | string | Назва лендингу | Відображається в адмін-панелі — **обов'язковий** |
| `created_at` | string | ISO 8601 datetime | Дата створення |
| `updated_at` | string | ISO 8601 datetime | Дата оновлення (CMS оновлює автоматично при збереженні) |
| `published` | bool | `false` | Статус публікації |
| `password` | string | `""` | Пароль доступу (порожньо = без пароля) |
| `global_styles` | object | див. вище | Глобальні CSS-змінні |
| `seo` | object | `title`, `description`, `og_image`, `favicon` | SEO мета-теги |
| `scripts` | object | `ga_id`, `fb_pixel`, `gtm_id`, `head`, `body_end` | Зовнішні скрипти та CDN |
| `sections` | array | масив секцій | Секції лендингу |

### Обов'язкові поля кожної секції

| Поле | Тип | Значення | Навіщо |
|---|---|---|---|
| `id` | string | UUID v4 — унікальний | Ідентифікатор секції |
| `type` | string | `"hero"`, `"about"` тощо | Тип секції |
| `template` | string | `"{template-id}"` | ID шаблону з `templates/` |
| `visible` | bool | `true` | Видимість секції (НЕ `published`) |
| `html` | string | `""` | Inline HTML — порожньо в reference mode |
| `css` | string | `""` | Inline CSS — порожньо в reference mode |
| `js` | string | `""` | Inline JS — порожньо в reference mode |
| `php` | string | `""` | Inline PHP — **обов'язкове поле**, завжди порожньо |
| `data.vars` | object | всі `{{VAR}}` з шаблону | Змінні секції |

### Правила slug

- `slug` = ім'я JSON-файлу без розширення: файл `apex.json` → `"slug": "apex"`
- Тільки латиниця, цифри, дефіс: `my-landing`, `apex-auto`, `wandr`
- Унікальний серед усіх лендингів

### Шляхи зображень у `seo` та `data.vars`

> ⚠️ **КРИТИЧНО:** відносний шлях `img/filename.jpg` працює ТІЛЬКИ при встановленні через ZIP-installer. При ручному створенні landing.json — використовувати **абсолютний шлях**.

**При ручному створенні (без installer)** — одразу писати абсолютний шлях:
```json
"HERO_IMG": "/tovcms/data/uploads/apex_hero.jpg",
"og_image": "/tovcms/data/uploads/apex_hero.jpg"
```

**При створенні ZIP-пакету для бібліотеки** — писати відносний (installer замінить):
```json
"HERO_IMG": "img\/offer.jpg",
"og_image": "img\/offer.jpg"
```

Installer автоматично замінює `img/filename` → `/tovcms/data/uploads/filename` при встановленні.

Файли зображень кладуться вручну в `C:\OSPanel\home\olxchecker\tovcms\data\uploads\`.

> ⚠️ **PowerShell + JSON:** `Set-Content -Encoding UTF8` додає BOM — PHP `json_decode()` його не читає (Syntax error). Для правки JSON-файлів використовувати тільки інструмент **Edit/Write** (не PowerShell Set-Content).

**КРИТИЧНО важливо:**
- `html`, `css`, `js`, `php` — **залишати порожніми** `""` (reference mode: Renderer читає з файлів шаблону)
- `data.vars` — **всі** змінні що використовуються в `template.html`
- `id` секцій — унікальні UUID v4. Генерувати різні для кожної секції, не повторювати
- `slug` файлу в `data/landings/` **завжди** збігається з полем `"slug"` всередині JSON

---

## КРОК 4: `meta.json` (корінь — для бібліотеки)

```json
{
  "title": "Назва шаблону",
  "topic": "product",
  "description": "Опис шаблону для бібліотеки",
  "tags": ["товарка", "product", "telegram", "countdown"],
  "author": "Landiro",
  "version": "1.0.0",
  "cms_version": "1.0.0",
  "sections": ["hero", "countdown", "product", "testimonials", "trust", "order-form", "footer"]
}
```

---

## КРОК 5: ЗОБРАЖЕННЯ

**Папка `img/` в корені шаблону:**
- Installer автоматично копіює `img/*` → `tovcms/data/uploads/`
- Шляхи в `data.vars` пишуться як `"img/offer.jpg"` — installer замінює на `UPLOADS_URL/offer.jpg`
- Формати: `.jpg`, `.png`, `.webp`
- Рекомендований розмір: до 800px ширина, стиснені

**Якщо оригінал використовує `url("icon.png")` в CSS:**
- Замінити на emoji-символ в HTML через `{{ICON}}` змінну
- Або на SVG inline в template.html
- Або прибрати іконку і залишити тільки кольоровий фон

---

## ТОЧНЕ КОПІЮВАННЯ ДИЗАЙНУ З HTML-ФАЙЛУ

Коли є оригінальний HTML-файл і потрібна максимально точна копія — використовувати **метод copy-then-adapt** (переписування CSS з нуля дає помилки у значеннях, пропущені властивості, неправильні кольори).

---

### МЕТОД COPY-THEN-ADAPT (рекомендований для копіювання)

**Принцип:** CSS копіюється точно з оригінальних файлів. Змінюється лише верхній рівень — додається скоуп `.cms-section-{type}`. Всі внутрішні селектори залишаються як є.

#### Алгоритм:

**1. Прочитай всі CSS-файли оригіналу**

Знайди всі `<link rel="stylesheet">` в HTML і прочитай ці файли. Визнач які правила належать якій секції.

**2. Для кожної секції скопіюй CSS блок**

```css
/* Оригінал: WIbFqtcMtp0y.css */
.offer { background: #fff; padding: 0; }
.offer .box { background: url("hero.jpg") 50% 0; height: 480px; }
.offer .sale { position: absolute; left: 30px; top: 287px; ... }

/* Адаптація в style.css — лише додаємо .cms-section-{type} зверху */
.cms-section-hero .offer { background: #fff; padding: 0; }
.cms-section-hero .offer .box { background-image: var(--PRODUCT_BG); height: 480px; }
.cms-section-hero .offer .sale { position: absolute; left: 30px; top: 287px; ... }
```

**3. Зовнішній елемент template.html — `<div>`, НЕ `<section>`**

Використовувати `<div>` з оригінальним класом секції + template-id, щоб уникнути конфліктів з CSS-правилами для елемента `<section>`:

```html
<!-- ✅ Правильно — div з оригінальним класом -->
<div class="offer sy2-offer-01">
    <div class="box">
        <div class="sale">...</div>
    </div>
    ...
</div>

<!-- ❌ Неправильно — section може успадкувати небажані стилі -->
<section class="cms-section-hero sy2-offer-01">...</section>
```

**Чому `<div>`:** Renderer обгортає секцію у `<div class="cms-section cms-section-{type}">`. Якщо шаблон починається з `<section>`, то глобальні правила типу `section { padding: 40px 0; }` з оригінального CSS застосовуються до нього і ламають відступи.

**4. Не переписувати CSS вручну — брати з файлу**

Навіть дрібні значення (padding, font-size, border-radius) повинні братись з оригінального CSS-файлу, а не з пам'яті чи скріншота.

**5. Зображення в CSS — через var()**

```css
/* Оригінал */
.offer .box { background: url("images/1.jpg") 50% 0 no-repeat; }

/* Адаптація */
.cms-section-hero .offer .box {
    background-image: var(--PRODUCT_BG);
    background-position: 50% 0;
    background-repeat: no-repeat;
}
```

```json
"PRODUCT_BG": "url(img/hero_product.jpg)"
```

**6. Шрифти — через Google Fonts у першому шаблоні**

Якщо оригінал використовує шрифт з локальних файлів — підключити через Google Fonts `@import` у style.css першої секції сторінки:

```css
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap');
```

**7. Загальний контейнер `.wrap` — через custom_css**

Оригінальні лендинги мають контейнер типу `.wrap { width: 480px; margin: 0 auto; }`. В CMS такого контейнера немає — кожна секція рендерується прямо в `<body>`. Відтворити через `global_styles.custom_css`:

```json
"custom_css": "body{background:#ff5fbb;min-width:320px;} .cms-section{max-width:480px;margin-left:auto;margin-right:auto;position:relative;overflow-x:hidden;}"
```

**8. `{{VAR}}` в HTML — без HTML-тегів у значеннях**

Renderer застосовує `htmlspecialchars()` до значень vars при підстановці в HTML. Тому `<br>` в значенні стане `&lt;br&gt;`. Для кількох абзаців — окремі vars:

```html
<!-- ✅ Правильно — окремі параграфи -->
<p>{{BLOCK_1_P1}}</p>
<p>{{BLOCK_1_P2}}</p>

<!-- ❌ Неправильно — <br> в значенні vars буде екрановано -->
<p>{{BLOCK_1_TEXT}}</p>  <!-- "text1<br>text2" → "text1&lt;br&gt;text2" -->
```

#### Що зберігати, що змінювати:

```
✅ Зберігати ТОЧНО:
- CSS-правила: всі значення px, rem, %, rgba, градієнти
- CSS-класи в HTML: .offer, .top-line, .s1, .order-steps
- HTML-структуру: вкладеність div/ul/li/p як в оригіналі
- Анімації та transition

✅ Адаптувати мінімально:
- Зовнішній wrapper: <section>/<header> → <div class="original-class template-id">
- background: url("file.png") → background-image: var(--VAR_NAME)
- Хардкодні тексти/числа → {{VAR_NAME}}
- Локальні шрифти @font-face → Google Fonts @import
- Глобальний .wrap → custom_css на .cms-section

❌ НЕ переносити:
- <script src="...cdn..."> теги
- Inline <style> блоки → в style.css
- Inline <script> блоки → в script.js
- jQuery, зовнішні бібліотеки → vanilla JS
```

#### Приклад: секція top-line

**Оригінал (WIbFqtcMtp0y.css):**
```css
.top-line { padding: 10px 0 10px 15px; background: #b12f7a; }
.top-line li { display: inline-block; font-size: 14px; color: #fff; }
.top-line li:nth-child(1) { width: 150px; padding-left: 35px; background: url("R2pLFDM1zuE6.png") 5px 50% no-repeat; }
```

**template.html:**
```html
<div class="top-line sy2-topline-01">
    <ul>
        <li>{{ITEM_1}}</li>
        <li>{{ITEM_2}}</li>
        <li>{{ITEM_3}}</li>
    </ul>
</div>
```

**style.css:**
```css
.cms-section-top-bar .top-line { padding: 10px 0 10px 15px; background: var(--BG_COLOR, #b12f7a); }
.cms-section-top-bar .top-line li { display: inline-block; font-size: 14px; color: #fff; }
.cms-section-top-bar .top-line li:nth-child(1) {
    width: 150px; padding-left: 35px;
    background-image: var(--TOPLINE_ICON);
    background-position: 5px 50%;
    background-repeat: no-repeat;
}
```

---

### СТАРИЙ МЕТОД (для секцій що створюються з нуля)

Якщо оригінального CSS немає або секція пишеться повністю нова:

1. **Прочитай** `index.html` і всі підключені CSS
2. **Розбий** HTML на секції
3. Для кожної секції:
   - Зберегти **точну HTML-структуру** і **класи** з оригіналу
   - Написати `style.css` відповідно до скріншота/специфікації
   - Замінити хардкодні значення на `{{VAR_NAME}}`
   - Перенести JS на vanilla JS

```
✅ Зберігати:
- CSS класи: .item-card, .featured-media, .discount, .label-sold, .big-button
- HTML-структуру: <div class="pricing-block"><div class="old-price">...
- CSS-правила: position, display, padding, animation, colors

✅ Адаптувати:
- Хардкодні значення → {{VAR_NAME}}
- background-image: url("icon.png") → var(--VAR_NAME) або прибрати
- jQuery → vanilla JS

❌ НЕ переносити:
- <script src="...cdn..."> теги
- Inline <style> та <script> блоки
```

**style.css (скоупований оригінальний CSS):**
```css
.cms-section-hero .item-card { background: #fff; }
.cms-section-hero .featured-media { position: relative; overflow: hidden; }
.cms-section-hero .discount {
  position: absolute; top: 80%; left: 7%;
  width: 75px; height: 75px;
  background-color: rgba(255,0,0,0.53);
  transform: rotate(-20deg);
  animation: led-hero-beat 1s ease infinite alternate;
}
@keyframes led-hero-beat {
  from { transform: rotate(-20deg) scale(1); }
  to   { transform: rotate(-20deg) scale(1.1); }
}
```

---

## ТИПОВІ СЕКЦІЇ ТА ЇХ ТИПИ

| Тип | Template ID приклад | Призначення |
|---|---|---|
| `hero` | `led-offer-01` | Оффер: фото товару, ціна, CTA |
| `countdown` | `led-countdown-01` | Таймер акції |
| `product` | `led-photos-01` | Фото + опис товару |
| `testimonials` | `led-reviews-01` | Відгуки зі слайдером |
| `trust` | `led-delivery-01` | Доставка, оплата, гарантія |
| `order-form` | `led-form-01` | Форма замовлення + Telegram |
| `footer` | `led-footer-01` | Футер з посиланнями |
| `benefits` | `benefits-01` | Список переваг |
| `faq` | `faq-01` | Акордеон питань |
| `pricing` | `pricing-01` | Блок ціни/тарифів |

---

## ПАТТЕРНИ JS: ЧАСТІ СЕКЦІЇ

### Countdown timer

```js
(function () {
  document.querySelectorAll('.cms-section-countdown').forEach(function (el) {
    if (el.dataset.init) return;
    el.dataset.init = '1';
    var totalSec = parseInt(el.dataset.seconds || '3600', 10);
    var timerEl  = el.querySelector('.countdown__timer');

    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function render(s) {
      var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
      timerEl.innerHTML =
        '<div class="countdown__item"><div class="countdown__value">' + pad(h) + '</div><span class="countdown__label">годин</span></div>' +
        '<div class="countdown__item"><div class="countdown__value">' + pad(m) + '</div><span class="countdown__label">хвилин</span></div>' +
        '<div class="countdown__item"><div class="countdown__value">' + pad(sec) + '</div><span class="countdown__label">секунд</span></div>';
    }
    render(totalSec);
    setInterval(function () { if (totalSec > 0) totalSec--; render(totalSec); }, 1000);
  });
})();
```

HTML для даних: `<section ... data-seconds="{{TIMER_SECONDS}}">`

### Vanilla slider (Swiper-сумісна розмітка)

```js
(function () {
  document.querySelectorAll('.cms-section-testimonials .swiper-reviews').forEach(function (swiper) {
    if (swiper.dataset.init) return;
    swiper.dataset.init = '1';
    var wrapper = swiper.querySelector('.swiper-wrapper');
    var slides  = swiper.querySelectorAll('.swiper-slide');
    var total   = slides.length, current = 0;
    var bullets = [];
    var pagination = swiper.querySelector('.swiper-pagination');

    for (var i = 0; i < total; i++) {
      var b = document.createElement('span');
      b.className = 'swiper-pagination-bullet' + (i === 0 ? ' swiper-pagination-bullet-active' : '');
      (function(idx) { b.addEventListener('click', function() { go(idx); }); })(i);
      pagination.appendChild(b); bullets.push(b);
    }
    function go(idx) {
      current = (idx + total) % total;
      wrapper.style.transform = 'translateX(-' + (current * 100) + '%)';
      bullets.forEach(function(b, i) {
        b.className = 'swiper-pagination-bullet' + (i === current ? ' swiper-pagination-bullet-active' : '');
      });
    }
    var nextBtn = swiper.querySelector('.swiper-button-next');
    var prevBtn = swiper.querySelector('.swiper-button-prev');
    if (nextBtn) nextBtn.addEventListener('click', function() { go(current + 1); });
    if (prevBtn) prevBtn.addEventListener('click', function() { go(current - 1); });

    var startX = 0;
    wrapper.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, {passive: true});
    wrapper.addEventListener('touchend', function(e) {
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 40) go(dx < 0 ? current + 1 : current - 1);
    });
  });
})();
```

### Маска телефону (Україна +380)

```js
document.querySelectorAll('.cms-section-order-form input[name="phone"]').forEach(function(input) {
  if (input.dataset.masked) return;
  input.dataset.masked = '1';
  input.addEventListener('input', function() {
    var raw = this.value.replace(/\D/g, '');
    if (raw.startsWith('380')) raw = raw.slice(3);
    else if (raw.startsWith('0')) raw = raw.slice(1);
    raw = raw.slice(0, 9);
    var out = '+380';
    if (raw.length > 0) out += ' ' + raw.slice(0, 2);
    if (raw.length > 2) out += ' ' + raw.slice(2, 5);
    if (raw.length > 5) out += '-' + raw.slice(5, 7);
    if (raw.length > 7) out += '-' + raw.slice(7, 9);
    this.value = out;
  });
  input.addEventListener('focus', function() { if (!this.value) this.value = '+380 '; });
});
```

---

## ЧЕКЛИСТ ПЕРЕД ФІНАЛІЗАЦІЄЮ

```
Структура:
[ ] meta.json (корінь) + landing.json + img/ + templates/
[ ] Кожна секція: meta.json + template.html + style.css + (script.js якщо потрібно)

meta.json секції (ОБОВ'ЯЗКОВИЙ):
[ ] Файл існує для КОЖНОЇ секції (без нього панель "Кольори та розміри" порожня)
[ ] vars[] містить всі {{VAR_NAME}} з template.html
[ ] vars[] містить кольорові CSS custom properties (тип "color")
[ ] vars[] НЕ містить image CSS vars з url() — PRODUCT_BG, SALE_BADGE_IMG тощо
[ ] Поле "name" у кожному var збігається з ключем в data.vars та {{VAR}} в шаблоні

template.html:
[ ] Кореневий елемент: <div class="{original-class} {template-id}"> (НЕ <section>)
[ ] Немає inline <style> тегів
[ ] Немає inline <script> тегів
[ ] Немає <script src="https://..."> (зовнішні CDN)
[ ] Всі змінні: {{VAR_NAME}} ВЕЛИКИМИ ЛІТЕРАМИ
[ ] Для raw HTML вставки — prefix HTML_: {{HTML_EXTRA_FIELDS}}
[ ] Для форм замовлення — є {{HTML_EXTRA_FIELDS}} між полями і кнопкою submit

style.css:
[ ] Всі правила скоуповані під .cms-section-{type}
[ ] @keyframes мають унікальний префікс (не "beat" а "{template}-beat")
[ ] Кольори використовують var(--VAR_NAME, fallback)
[ ] CSS image vars через var(--PRODUCT_BG) — значення "url(...)" задається в data.vars
[ ] Немає зовнішніх @import або url(https://...)

script.js:
[ ] IIFE або DOMContentLoaded обгортка
[ ] Guard: if (el.dataset.init) return; el.dataset.init = '1';
[ ] Відносна навігація: el.querySelector(), не document.getElementById()
[ ] Без jQuery, Swiper, інших зовнішніх бібліотек

landing.json (обов'язкова структура — відхилення = 500 помилка в адміні):
[ ] Є поле "id" (UUID v4)
[ ] Є поле "slug" — збігається з іменем файлу (apex.json → "slug": "apex")
[ ] Є поле "title" — назва лендингу для адмін-панелі
[ ] Є поле "created_at" (ISO 8601, наприклад "2026-01-01T12:00:00+02:00")
[ ] Є поле "updated_at" (ISO 8601)
[ ] Є поле "published" (false)
[ ] Є поле "password" ("")
[ ] Є поле "global_styles" з усіма 6 підполями
[ ] Є поле "seo" з title, description, og_image, favicon
[ ] Є поле "scripts" з ga_id, fb_pixel, gtm_id, head, body_end
[ ] html/css/js/php = "" в кожній секції (reference mode)
[ ] Секції мають "visible": true (НЕ "published")
[ ] Секції мають поле "php": "" (обов'язкове)
[ ] Image paths в data.vars: "/tovcms/data/uploads/filename.jpg" (абсолютний — при ручному створенні)
[ ] Image paths в data.vars: "img\/filename.jpg" (відносний — тільки в ZIP-пакеті для installer)
[ ] CSS image vars: "PRODUCT_BG": "url(/tovcms/data/uploads/product.jpg)"
[ ] Всі UUID секцій унікальні
[ ] data.vars містить ВСІ змінні з template.html + CSS custom properties

img/:
[ ] Всі зображення присутні в папці
[ ] Стиснені до розумного розміру (< 300KB кожне)
```

---

## ВІДОМІ ОСОБЛИВОСТІ RENDERER

1. **Підстановка змінних:** `{{VAR_NAME}}` → береться з `section.data.vars` (НЕ з `section.data`)
2. **CSS custom properties:** Renderer генерує `#section-{id} { --BG_COLOR: value; }` для всіх vars
3. **Залишкові теги:** `{{UNRESOLVED}}` після підстановки — видаляються regex автоматично
4. **Reference mode:** якщо `html/css/js = ""` — читає з файлів шаблону, інакше використовує inline
5. **Installer:** копіює `img/` і `uploads/` → `UPLOADS_PATH`; замінює `img/filename` → `UPLOADS_URL/filename` та `url(img/filename)` → `url(UPLOADS_URL/filename)` в data.vars
6. **`HTML_` prefix:** якщо ключ у `data.vars` починається з `HTML_` — значення вставляється як **сирий HTML** (через `strip_tags`, не `htmlspecialchars`). Для звичайних ключів завжди застосовується `htmlspecialchars` — теги в значенні будуть екрановані.

```html
<!-- Placeholder у template.html -->
{{HTML_EXTRA_FIELDS}}
```
```json
"HTML_EXTRA_FIELDS": "<input class=\"field\" type=\"email\" name=\"email\" placeholder=\"Email\">"
```
> Використовувати для: додаткових полів форм, HTML-блоків що вставляються через конструктор, будь-якого контенту де потрібні теги.

7. **Подвійне призначення `data.vars`:** кожен ключ одночасно:
   - підставляється як `{{VAR_NAME}}` у `template.html`
   - генерується як `--VAR_NAME: value;` у CSS custom properties для `style.css`

   Тобто один і той самий ключ може використовуватись і в HTML (`{{TITLE}}`), і в CSS (`var(--TITLE)`).

---

## ПРАВИЛА З АНАЛІЗУ ПОМИЛОК

### НЕ ДОДАВАТИ нічого від себе

❌ **ЗАБОРОНЕНО:** Додавати SVG-іконки, декоративні елементи, emoji, класи, атрибути яких немає в оригінальному HTML
✅ **ПРАВИЛО:** Якщо в оригіналі `<a class="big-button">ТЕКСТ</a>` — так і пишемо. Без SVG, без іконок, без `<span>`, нічого зайвого.

> Причина: довільні доповнення ламають точне відтворення дизайну і CSS-правила оригіналу.

---

### CSS змінні для background-image

Якщо в оригінальному CSS є `background-image: url("filename.png")` — це статична частина дизайну.  
В CMS-шаблоні це оформляється через CSS custom property:

**style.css:**
```css
.benefit_item:nth-child(1):before {
  background-image: var(--BENEFIT_1_ICON);
}
```

**data.vars (в landing.json шаблону):**
```json
"BENEFIT_1_ICON": "url(img/offer3__benefit1_icon.png)"
```

Installer автоматично перетворить `url(img/...)` → `url(UPLOADS_URL/...)`.  
Зображення обов'язково класти в `img/` папку шаблону.

> `url(var(--X))` в CSS не працює — тому значення CSS custom property вже має містити `url(...)`.

---

### Кольори таймерів/лічильників беруться з оригіналу

Якщо в оригінальному CSS `background: var(--tov-main-color, #149cc7)` і реально відображається чорним — в шаблоні CMS використовувати окрему змінну `TIMER_BG` з дефолтом чорного:

```css
background: var(--TIMER_BG, #222222);
```

```json
"TIMER_BG": "#222222"
```

> **НІКОЛИ** не підставляти `ACCENT_COLOR` туди де оригінал використовує інший колір.

---

### Тексти копіювати дослівно з оригіналу

Тексти-значення (описи товарів, характеристики, підписи) — копіювати **точно** з оригінального HTML, включно з:
- пробілами (3Вт, а не 3 Вт)
- розмірами (17х4.5 з буквою х, а не 17×4.5 з символом ×)
- скороченнями (Туре-С, а не Type-C; Макс., а не Максимальна — якщо так в оригіналі)
- переносами рядків (зберігати структуру абзаців)

> Причина: контент замовника може бути юридично або маркетингово значущим. Зміна тексту — це помилка.

---

### Секції з зображеннями: ніколи не замінювати img на emoji

Якщо оригінал використовує `<img src="...">` — шаблон повинен використовувати `<img src="{{VAR_IMG}}">`.  
Emoji — НЕ замінник зображення, навіть якщо оригінальне зображення недоступне.

Правильна дія коли зображення відсутнє:
1. Знайти зображення в оригінальній папці проекту
2. Якщо немає — запитати у користувача
3. Якщо зображення є в оригіналі — скопіювати в `img/` шаблону

**Структура HTML секції:**
```html
<!-- ✅ Правильно — як оригінал -->
<img src="{{ITEM_1_IMG}}" alt="">

<!-- ❌ Неправильно — замінник -->
<div class="info_icon">🚚</div>
```

---

### CSS layout: відтворювати точно, включно з display:table

Якщо оригінал використовує `display: table` + `display: table-cell` + `position: absolute` для розкладки — копіювати саме цей підхід, навіть якщо flexbox здається сучаснішим.

```css
/* ✅ Правильно — як оригінал */
.info_item {
  display: table;
  width: 100%;
  height: 204px;
  position: relative;
}
.info_item img {
  position: absolute;
  top: 0; right: 0;
  width: 220px;
}
.info_item .text_block {
  display: table-cell;
  vertical-align: middle;
  padding: 0 240px 0 20px;
}

/* ❌ Неправильно — "покращений" варіант */
.info_item { display: flex; align-items: center; }
```

> Причина: будь-яке відхилення від оригінального layout змінює пропорції, відступи і вигляд.

---

### Картка всередині секції: секція потребує контрастного фону

Якщо в оригіналі контент виглядає як **білий блок із тінню**, що плаває на фоні — це означає:
- Секція (обгортка) має **сірий або нейтральний фон** (`#f5f5f5` або аналог)
- Внутрішній блок (картка) має **білий фон** (`#fff`)
- Блок має `box-shadow`

Без контрастного фону секції тінь невидима (білий на білому).

**Правило:**
```css
/* ✅ Секція — сіра */
.cms-section-countdown {
  background: var(--BG_COLOR, #f5f5f5);
  padding-bottom: 20px;   /* щоб тінь знизу не обрізалась */
}

/* ✅ Картка — біла з тінню */
.cms-section-countdown .countdown {
  background: #fff;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
}
```

```json
"BG_COLOR": "#f5f5f5"
```

> **Джерело істини — скріншот оригіналу**, не CSS-текст. Якщо в оригіналі видно сіру зону навколо білого блоку — `BG_COLOR` секції сірий, незалежно від того що написано у `var(--xxx, fallback)`.

---

### Фон сторінки (body) — завжди перевіряти в оригіналі

В оригінальному CSS завжди перевіряти `body { background: ... }`. Якщо оригінал має сірий фон сторінки — додати в `global_styles.custom_css` лендингу:

```json
"custom_css": "body{background:#eee;min-width:480px;}"
```

Типові значення:
- `#eee` — стандартний сірий фон для товарних лендингів
- `#f5f5f5` — трохи світліший сірий

> **Де шукати:** `body { background: ... }` у файлах `style.css`, `landing-style-2.css` або в `<style>` тегах оригінального HTML.

---

### Подвійне застосування CSS — не використовувати padding на .cms-section-{type}

Renderer обгортає кожну секцію у `<div class="cms-section cms-section-{type}">`. Шаблонний HTML також починається з `<section class="cms-section-{type} {template-id}">`. Обидва елементи мають однаковий клас `cms-section-{type}`.

**Наслідок:** будь-який `padding-top`/`padding-bottom` на `.cms-section-{type}` застосовується ДВІЧІ (до обох елементів), що подвоює відступи.

**Правило:** для відступів всередині секції — використовувати `margin` на ВНУТРІШНЬОМУ контейнері картки, а НЕ `padding` на самій секції:

```css
/* ❌ Неправильно — padding подвоюється */
.cms-section-countdown {
  padding-top: 15px;
  padding-bottom: 20px;
}

/* ✅ Правильно — margin тільки на картці */
.cms-section-countdown {
  background: var(--BG_COLOR, #f5f5f5);
}
.cms-section-countdown .countdown {
  margin: 15px auto 20px;  /* top=15px сірого зверху, bottom=20px для тіні */
}
```

> Виняток: `background`, `font-family`, `color`, `font-size` на `.cms-section-{type}` — ці властивості безпечні (однаковий колір на обох елементах).

---

## A/B ТЕСТУВАННЯ — ЯК ПІДТРИМУВАТИ В ШАБЛОНАХ

A/B тестування вбудоване в CMS і працює автоматично. Шаблон нічого спеціально робити не повинен. Але є важливі деталі:

**Як працює:**
1. В адмінці адміністратор натискає кнопку "A/B" на секції → відкривається редактор Variant B
2. Варіант B зберігається в `section.ab_html` (inline HTML, незалежно від reference mode)
3. При кожному відвідуванні сайту: рандом 50/50 → cookie `_ab_xxxxxxxx` = "a" або "b" на 7 днів
4. Variant B: `section.html` підмінюється на `section.ab_html` перед рендерингом
5. Аналітика: `data/analytics/{slug}.json` → `ab_xxxxxxxx.a_views`, `b_views`, `a_orders`, `b_orders`

**Наслідок для template-mode секцій (html=""):**
Variant B зберігається як inline HTML (`ab_html`). При показі variant B, Renderer рендерить цей inline HTML замість шаблонного файлу. Тобто після A/B тесту секція може "вийти" з template reference mode для variant B. Це нормально.

**Порада для шаблонів з формами:**
Переконатись що `{{HTML_EXTRA_FIELDS}}` є в template.html форми — це дозволяє конструктору форм додавати поля без порушення template reference mode.

---

## ЗВІТ ПІСЛЯ СТВОРЕННЯ

Після генерації файлів написати:
- Список секцій з типами та template ID
- Які зображення потрібно надати (якщо не включені)
- Що налаштувати перед публікацією (Telegram токен, ціни тощо)
- Шлях до папки шаблону
