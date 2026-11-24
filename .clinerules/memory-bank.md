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

## Data Model (decided in project-rules.md)

- CPT: `ani_gpa_group` (attribute groups)
- Taxonomy: `ani_gpa_group_cat` (group categories)
- Product meta: `_ani_gpa_assigned_groups` (array), `_ani_gpa_selected_values` (associative array)
- Prefixes: PHP `ani_gpa_` / `Ani_GPA_` · CSS `.ani-gpa-` · text domain `anishop`

## Live Environment (LIVE-VERIFIED 2026-11-23)

- WP 7.1.5 · PHP 8.3.36 · WooCommerce 11.3.1 · Blocksy 2.1.62 · blocksy-companion 2.1.62 · persian-woocommerce 10.0.7 · blocksy-child 1.1.1
- Child theme: 14 `require_once` modules + `panel-builder` (see `technical-conventions.md` §2)
- Real attribute data: products use both taxonomy attributes (`pa_*`, `is_taxonomy: 1`) and custom Persian-named attributes (`is_taxonomy: 0`, URL-encoded meta keys) — see `docs/woocommerce-attributes-dev-notes.md`

## Conventions (short form — full detail in technical-conventions.md)

- Module pattern: `inc/{module}/{config,admin,render,bootstrap}.php`
- Settings: Theme Mod + single sanitizer + versioned schema
- Admin UI: Appearance → Anishop page, tabs via `anishop_admin_tabs` filter
- Frontend JS: dependency-free vanilla IIFE + `wp_localize_script`
- Asset versioning: `filemtime`
- House style: Vazirmatn, accent `#2872FA`, radii 2/4px, flat, RTL-first
- Git: per-phase branches `phase-{N}-{slug}` → merge to `main` only after owner approval; task reports in `reports/`; child-theme snapshot in `snapshots/blocksy-child/`

## Current State

- **Phase:** 0 complete · Phase 1 (investigation) **COMPLETE** (2026-11-24, branch `phase-1-investigation`, commit `67f70ad`) · Phase 1.1 (decisions finalization + testing policy) **COMPLETE** (2026-11-24, branch `phase-1-1-decisions-finalization`) · Phase 2 **NOT STARTED — awaiting explicit owner authorization**
- **Architecture direction (selected, not implemented):** A1 (data ownership) + B1 (Blocksy custom layer). C (delimiter normalization) **deferred** (D20). See `decisions.md` D22.
- **Documentation:** Phase 1 research docs + synthesis report; Phase 1.1 corrected technical inaccuracies, recorded owner decisions D14–D23, added permanent test-environment policy.
- **Repository:** `main` at `2b4b090` (last approved state); Phase 1 + Phase 1.1 on their own branches, not merged (awaiting owner approval).
- **Next:** owner reviews Phase 1.1 report → explicit authorization → Phase 2 architecture design on branch `phase-2-architecture` (see `docs/PROJECT-ROADMAP.md` §3).

## Test Fixtures (permanent — D23)

- **Simple:** product **13143** (Philips OneBlade QP2824/10) · **Variable:** product **17028** (OnePlus 15, 16 real variation children).
- Backups (raw, byte-exact): `backups/gpa-test-fixtures/` (gitignored). Restoration-verified on hidden copies **17601** (simple) and **17602** (variable).
- Full policy: `project-rules.md` §Test Environment Policy · Registry: `docs/testing/test-fixtures.md`.

## Decisions

See `decisions.md` (D1–D23). Phase 1.1 added D14–D23 (dual attribute support, parent-first, B1 selected, native-table visibility, View Attribute deferred, persian-woocommerce testing, delimiter normalization deferred, no catalog cleanup, A1+B1 direction, test-fixture policy).
