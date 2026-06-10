Generate a new section template for Landiro CMS based on the user's description: $ARGUMENTS

## Your task

Create a complete section template in the correct Landiro CMS format. The section should be ready to use in the landing page builder immediately after creation.

## Output: 4 files

Create all files in: `templates/{type}/{id}/`

Where:
- `{type}` — one of the allowed section types (see below)
- `{id}` — kebab-case slug, e.g. `hero-03`, `pricing-02`, `benefits-dark-01`

### 1. `meta.json`
```json
{
  "id": "{id}",
  "type": "{type}",
  "name": "Human-readable name in Ukrainian",
  "description": "Short description in Ukrainian",
  "tags": ["tag1", "tag2"],
  "vars": [
    { "name": "VAR_NAME", "label": "Ukrainian label", "type": "text|textarea|color|image|url", "default": "default value" }
  ]
}
```

### 2. `template.html`
- Plain HTML, no PHP, no JS frameworks
- Variables use `{{VAR_NAME}}` syntax (double curly braces)
- Root element must have class `cms-section-{type}` and optionally a variant class
- Mobile-first, works without any external CSS libraries
- Images use `{{IMAGE_URL}}` pattern, colors use `{{BG_COLOR}}` etc.

### 3. `style.css`
- Scoped to `.cms-section-{type}` (or more specific variant class)
- Mobile-first with `@media (min-width: 768px)` for desktop
- Use CSS custom properties for colors/sizes when possible
- No external fonts or dependencies — use `-apple-system, sans-serif`
- Smooth animations with `transition` and `@keyframes` where appropriate

### 4. `script.js` (only if interactive behavior is needed)
- Vanilla JS only, no jQuery or frameworks
- Wrap in `document.addEventListener('DOMContentLoaded', ...)` or use direct function calls
- Must be safe to include multiple times on a page (check if already initialized)

## Allowed section types

| type | description |
|------|-------------|
| `hero` | Hero banner, first screen |
| `benefits` | Benefits / features list |
| `product` | Product showcase |
| `how-it-works` | Step-by-step process |
| `testimonials` | Reviews / social proof |
| `pricing` | Pricing / offer blocks |
| `countdown` | Countdown timer |
| `faq` | FAQ accordion |
| `gallery` | Image gallery |
| `video` | Video section |
| `order-form` | Order form (PHP handler) |
| `trust` | Trust badges / guarantees |
| `cta` | Call-to-action button |
| `footer` | Page footer |
| `before-after` | Before/after image comparison |
| `text-block` | Rich text block |
| `custom` | Custom HTML section |

## Quality checklist before creating files

- [ ] All text content is in Ukrainian (labels, placeholder text, defaults)
- [ ] Every editable piece of content has a corresponding `vars` entry
- [ ] CSS is fully scoped — won't leak into other sections
- [ ] Mobile looks good at 375px width
- [ ] No hardcoded colors/fonts — everything customizable via vars
- [ ] template.html has no inline `<style>` or `<script>` tags

## After creating the files

Tell the user:
- The full path of the created template
- Which variables were defined and what they control
- Any usage notes (e.g. "requires an image in 16:9 ratio")
