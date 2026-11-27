# Group Product Attributes — Memory Bank

> Project-specific memory. Keep this file current: record durable facts, decisions, and state here.

## Identity

- **Project:** WooCommerce Product Group Attributes Manager (`ani-group-product-attributes`)
- **Type:** Blocksy child-theme module (NOT a standalone plugin)
- **Store:** Anishop — `http://anishop.local` (Persian, RTL)
- **Reference:** official WooCommerce Group Attributes plugin (docs in `docs/references/`)

## Key Paths

| Item | Path |
|------|------|
| Workspace | `/home/ramin/Downloads/woo-group-attributes/` |
| WordPress | `/home/ramin/Local Sites/anishop/app/public/` |
| Child theme | `.../wp-content/themes/blocksy-child/` |
| Module target (Phase 3) | `.../blocksy-child/inc/ani-group-product-attributes/` |
| Assets target (Phase 3) | `.../blocksy-child/assets/js|css/ani-group-product-attributes*.js|css` |

## Data Model (Phase 2A.3 native-template model — decisions D28–D39)

- CPT: `ani_gpa_group` (group templates of Global Attribute definitions)
- Group meta: `_ani_gpa_group_attributes` (serialized array of ordered `pa_{slug}` references)
- Settings: Theme Mod `anishop_gpa_settings` (`table_style`: default / minimal / boxed)
- **No** GPA-specific product metadata — the product's native `_product_attributes` is the single source of truth (D30)
- **No** `ani_gpa_group_cat` taxonomy in the core — group categories are not in scope (D40, resolved 2026-11-27)
- Prefixes: PHP `ani_gpa_` / `Ani_GPA_` · CSS `.ani-gpa-` · text domain `anishop`

## Live Environment (LIVE-VERIFIED 2026-11-23)

- WP 7.1.5 · PHP 8.3.36 · WooCommerce 11.3.1 · Blocksy 2.1.62 · blocksy-companion 2.1.62 · persian-woocommerce 10.0.7 · blocksy-child 1.1.1
- Child theme: 14 `require_once` modules + `panel-builder` (see `technical-conventions.md` §2)
- Real attribute data: products use both taxonomy attributes (`pa_*`, `is_taxonomy: 1`) and custom Persian-named attributes (`is_taxonomy: 0`, URL-encoded meta keys) — see `docs/woocommerce-attributes-dev-notes.md`

## Conventions (short form — full detail in technical-conventions.md)

- Module pattern: `inc/{module}/{config,admin,render,bootstrap}.php`
- Settings: Theme Mod + single sanitizer + versioned schema
- Admin UI: Appearance → Anishop page, tabs via `anishop_admin_tabs` filter (GPA exception: submenu under WooCommerce Products, after Attributes — D43)
- Frontend JS: dependency-free vanilla IIFE + `wp_localize_script`
- Asset versioning: `filemtime`
- House style: Vazirmatn, accent `#2872FA`, radii 2/4px, flat, RTL-first
- Git: per-phase branches `phase-{N}-{slug}` → merge to `main` only after owner approval; task reports in `reports/`; child-theme snapshot in `snapshots/blocksy-child/`

## Current State

- **Phase:** 0, 0.1, 1, 1.1, 1.1.1 complete · Phase 2A.1 (architecture corrections) **COMPLETE** (2026-11-25) · Phase 2A.2 (final architecture consistency) **COMPLETE** (2026-11-25) · Phase 2A.3 (native attribute template redesign) **COMPLETE** (2026-11-25, branch `phase-2a-3-native-attribute-template-redesign`, commit `d531d2e`) · Phase 2B (runtime verification) **COMPLETE** (branch `phase-2b-runtime-verification`, commit `c63aacf`) · Phase 2B Evidence-Gap Follow-Up **COMPLETE** (branch `chore/phase-2b-evidence-gap-followup`, commit `d5279f2`) · Owner decisions Q1–Q4 finalized (D40–D43, 2026-11-27) · Owner decisions D44–D45 finalized (table_style settings UI + Load product-ID transport, 2026-11-27)
- **Architecture (approved, not implemented):** **native-template model** — GPA groups are templates of Global Attribute definitions (CPT `ani_gpa_group` + ordered `pa_{slug}` references); Load inserts native attribute rows; the product's native `_product_attributes` is the single source of truth; all attributes render through the native Additional Information tab. Decisions D28–D45; the previous A1 + B1 direction (D22) is superseded. C (delimiter normalization) remains **deferred** (D20). See `docs/architecture/phase-2a-architecture-specification.md`.
- **Documentation:** Phase 1 research docs + synthesis report; Phase 1.1 decisions D14–D23 + permanent test-environment policy; Phase 2A.3 native-template specification + decisions D28–D39; Owner decisions Q1–Q4 finalized (D40–D43, 2026-11-27); Owner decisions D44–D45 finalized (table_style settings UI + Load product-ID transport, 2026-11-27).
- **Repository:** `main` at `2b4b090` (last approved state); Phase 1, 1.1, 1.1.1, 2A, 2A.1, 2A.2, 2A.3, 2B, 2B follow-up on their own branches, not merged (awaiting owner approval).
- **Next:** explicit owner authorization → Phase 3 (full implementation) on a dedicated branch (see `docs/PROJECT-ROADMAP.md` §3). Phase 3 remains **NOT STARTED — awaiting explicit owner authorization**.

## Test Fixtures (permanent — D23)

- **Simple:** product **13143** (Philips OneBlade QP2824/10) · **Variable:** product **17028** (OnePlus 15, 16 real variation children).
- Backups (raw, byte-exact): `backups/gpa-test-fixtures/` (gitignored). Restoration-verified on hidden copies **17601** (simple) and **17602** (variable).
- Full policy: `project-rules.md` §Test Environment Policy · Registry: `docs/testing/test-fixtures.md`.

## Decisions

See `decisions.md` (D1–D45). Phase 1.1 added D14–D23. Phase 2A.3 added D28–D39 (native-template model: groups = templates of Global Attribute definitions; Load adds native attribute rows; no product meta / no provenance; native rendering only; uniform table styles; View Attribute deferred; UI-only toggle; Product Data → Attributes integration; Theme Mod `anishop_gpa_settings`). Owner decisions Q1–Q4 (2026-11-27) added D40–D43 (no group categories; only Published groups loadable; CSS-only table styling confirmed; GPA submenu under Products after Attributes). Owner decisions D44–D45 (2026-11-27) added D44 (table_style settings UI at Appearance → Customize → Anishop → Group Attributes) and D45 (Load REST endpoint product-ID via required `product_id` query parameter). Superseded: D9, D10, D14, D16, D17, D18, D22, D24, D27, D35. Obsolete: D25, D26.
