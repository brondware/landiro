Create a complete ready-to-use landing page for Landiro CMS based on the user's description: $ARGUMENTS

## Your task

Design and generate a full landing page structure — a sequence of sections that together form a high-converting product landing page. Then create all necessary template files.

## Step 1: Plan the landing structure

Based on the user's description, choose 5-8 sections that make sense for this product/offer. Think like a conversion-focused copywriter. A typical high-converting structure:

1. **Hero** — headline, subheadline, CTA button, product image
2. **Benefits** — 3-4 key benefits with icons
3. **Product** or **How it works** — product details or process
4. **Testimonials** — 2-3 social proof items
5. **Pricing** — offer block with price and CTA
6. **FAQ** — 3-5 common objections answered
7. **Order form** — the conversion point
8. **Footer** — contacts, legal

Adapt this structure to the specific product/niche described by the user.

## Step 2: Create template files for each new section

For each section that doesn't already exist in `templates/`, create all 4 files following the Landiro CMS template format:

**File structure:** `templates/{type}/{id}/`
- `meta.json` — section metadata and editable variables list
- `template.html` — HTML with `{{VAR_NAME}}` variable placeholders
- `style.css` — scoped CSS (`.cms-section-{type}`)
- `script.js` — only if interactive behavior needed

**Rules:**
- Variables use `{{VAR_NAME}}` double curly braces
- CSS scoped to `.cms-section-{type}` or variant class
- Mobile-first, no external dependencies
- All default text in Ukrainian
- Every editable element must have a var

## Step 3: Generate the landing JSON

Create a landing configuration file at `data/landings/{slug}.json`:

```json
{
  "slug": "{slug}",
  "title": "Landing title",
  "published": false,
  "sections": [
    {
      "id": "unique-section-id",
      "type": "{type}",
      "template": "{template-id}",
      "data": {
        "VAR_NAME": "actual value for this landing"
      },
      "order": 0
    }
  ],
  "seo": {
    "title": "",
    "description": "",
    "og_image": ""
  },
  "global_styles": "",
  "scripts": ""
}
```

Fill `data` fields with **real, product-specific content** (not placeholder text). Write actual Ukrainian copy for headlines, benefits, CTA text — tailored to the product the user described.

## Step 4: Report to the user

After all files are created, summarize:
- Landing slug and how to access it in admin panel
- List of sections created with their order
- Which template files were newly created vs reused
- Suggested next steps (add images, configure Telegram, etc.)

## Quality standards

- Copy must sound natural in Ukrainian, not translated
- CTA buttons should be action-oriented ("Замовити зараз", "Отримати знижку", not just "Купити")
- Prices/specifics: use realistic placeholders like `999 ₴` not `0`
- The landing should feel complete and ready to customize, not skeletal
