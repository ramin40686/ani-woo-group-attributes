# WooCommerce Product Group Attributes Manager — Technical Conventions
> Items marked `[DOCUMENTED]` are carried over from the old workspace (verified at the stated dates). Items marked `[LIVE-VERIFIED 2026-11-23]` were re-checked against the live child theme.

## 1. Child Theme Architecture

- Blocksy is a **block theme**. The child (`blocksy-child`) has **NO template overrides** — it contains only `functions.php`, `style.css`, `screenshot.jpg`, `inc/`, `assets/`, `languages/`.
- **`functions.php` is bootstrap-only:** textdomain loader + parent-style enqueue + `require_once` entries. `[DOCUMENTED 2026-10-23: 44 lines, 13 require_once]`
- **Module pattern:** `inc/{module-name}/{config.php, admin.php, render.php, bootstrap.php}` (files as needed per module).
- **Asset pattern:** `assets/js/{module}.js`, `assets/css/{module}.css`; admin variants `{module}-admin.{js,css}`.
- **Text domain:** `anishop`.
- **i18n:** Persian `.mo` at `blocksy-child/languages/fa_IR.mo` (**locale-only filename convention** — NOT `anishop-fa_IR.mo`); loaded via `load_theme_textdomain('anishop', …/languages)` on `after_setup_theme`.
- **Settings storage:** Theme Mod (`get_theme_mod`/`set_theme_mod`) with a **single sanitizer** and a versioned schema.
- **Admin UI:** Appearance → Anishop page; tab nav is extensible via the `anishop_admin_tabs` filter (the mobile-nav module owns the page; other modules add tabs through the filter).
- **Save handlers:** POST + capability check + nonce + single-authority sanitization; autosave/revision guards for post meta.
- **Asset versioning:** `filemtime`-based version argument on `wp_enqueue_script/style` (established convention since the 2026-10-23 mobile-nav CSS fix).
- **Blocksy Dynamic CSS: NEVER edit by hand.** Regenerate via the official mechanism: `do_action('blocksy:dynamic-css:refresh-caches')` (a 2nd refresh may be needed to clear a stale cache).
- **Blocksy hooks used by existing modules:** `blocksy:woocommerce:product-single:layout` (layout filter) + `blocksy:woocommerce:product:custom:layer` (custom layer render).
- **Frontend JS convention:** dependency-free vanilla JS (IIFE), no frameworks; `wp_localize_script` for per-page config.

## 2. Existing Child Theme Modules — PRESERVE (do not modify without explicit approval)

`[LIVE-VERIFIED 2026-11-23 — the 14 `require_once` entries in `functions.php` (13 documented 2026-10-23 + `anishop-search-box` added 2026-11-16):]`

| # | File | Module / purpose |
|---|------|------------------|
| 1 | `inc/anishop-hero/bootstrap.php` | CPT `anishop_hero_slide` + dynamic block `anishop/hero-slider` (homepage hero) |
| 2 | `inc/anishop-home-products/bootstrap.php` | Front-page product carousel (4 Product Collection sections) |
| 3 | `inc/anishop-mobile-nav/config.php` | Mobile bottom nav — config layer (Theme Mod `anishop_mobile_nav`) |
| 4 | `inc/anishop-mobile-nav/bootstrap.php` | Mobile bottom nav — runtime |
| 5 | `inc/anishop-mobile-nav/admin.php` | Mobile bottom nav — admin UI (owns the Appearance → Anishop page) |
| 6 | `inc/anishop-inline-variations/config.php` | Inline variations display — config |
| 7 | `inc/anishop-inline-variations/admin.php` | Inline variations display — admin (Layer A/B + term HEX) |
| 8 | `inc/anishop-inline-variations/render.php` | Inline variations display — frontend renderer |
| 9 | `inc/anishop-inline-variations/key-features.php` | Per-product Key Features (meta box + global toggle + custom layer) |
| 10 | `inc/anishop-buy-box/config.php` | Buy Box — config (Theme Mod `anishop_buy_box`) |
| 11 | `inc/anishop-buy-box/admin.php` | Buy Box — admin |
| 12 | `inc/anishop-buy-box/render.php` | Buy Box — render (desktop 3-column + mobile sticky bar) |
| 13 | `inc/anishop-buy-box/product-rows.php` | Buy Box — per-product rows (product-edit Advanced tab) |
| 14 | `inc/anishop-search-box/bootstrap.php` | Live Search Box — runtime (admin: `admin.php`, config: `config.php`) |

**Not loaded from `functions.php`:** `inc/panel-builder/header/anishop-header-search/` (`config.php` + `view.php`) — the header search panel, registered by the search-box bootstrap (adds `inc/panel-builder/header` to the panel paths). `[LIVE-VERIFIED 2026-11-23]`

**Theme Mods in use:** `anishop_mobile_nav` · `anishop_inline_variations` · `anishop_key_features_settings` · `anishop_buy_box` · `footer_placements` (child).
**Options in use:** `anishop_attribute_presentation`.
**MD5-locked files (old project protection):** `assets/js/anishop-mobile-nav.js` (`0c0fe75a6a4c7ec98293e179270a97bf`) · `assets/css/anishop-mobile-nav.css` (`b2e052cd58e82365ee71d4c974ee11c5`).

** woocommerce Product group attributes manager implication:** the new module must coexist with all of the above — no duplicate hooks, no parallel handlers, no changes to these modules. 

## 3. House Style (from old workspace `DESIGN.md` — captured from live Blocksy theme settings)

- **Font:** Vazirmatn everywhere — headings 700, body 400, buttons 500. Scale from theme font sizes: 13 / 20 / 30 / 50 / 80px.
- **Colors:** bg `#FAFBFC` · surface `#FFFFFF` · ink `#192A3D` · accent `#2872FA` · accent-deep `#1559ED` · muted `#3A4F66` · border `#E1E8ED` · tint `#F2F5F7`.
- **Radii:** 2px buttons/controls · 4px cards. No pill shapes, no large radii.
- **Elevation:** flat by default — borders + whitespace, no heavy/stacked shadows.
- **Layout:** RTL first (right-aligned text, right-anchored navigation); composed, mostly symmetric sections; one focused message per section.
- **Buttons:** solid `#2872FA` white text (primary) or outline (secondary); 40px min-height; one label per intent, reused across the page.
- **Do not introduce colors, fonts, or radii outside this set** without owner approval.

## 4. User Preferences (observed and documented in the old workspace)

- **Approval before moving between major phases** — never silently advance.
- **Small, verifiable tasks** over large autonomous tasks.
- **Evidence-based reporting** with exact file paths; separate Changed / Verified / Not Verified / Risks.
- **No speculative fixes**; report blockers instead of entering repetitive analysis loops.
- **Physical-device verification** for mobile-only behavior (owner uses an iPhone 12 Pro via Local Live Link; CDP success is not sufficient for mobile-only claims).
- **Bilingual admin UI:** English source strings + Persian `.mo` translations.
- **Restore points** before significant changes (old pattern: `restore-points/{name}/` with MANIFEST + guarded `restore.sh` that refuses to overwrite a differing live file without `--force`).
- **Test artifacts** live in the workspace (outside production) and clean up after themselves.
- The owner communicates in Persian for site content; old-workspace documentation was bilingual (Persian rules + English reports). This new workspace uses English documentation.

