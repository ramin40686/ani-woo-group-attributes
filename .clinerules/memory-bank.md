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

- **Phase:** 0 complete; Phase 1 (investigation) prepared — awaiting explicit owner authorization, not started
- **Documentation:** workspace docs finalized 2026-11-23 (rules, roadmap, context, environment, conventions, decisions)
- **Repository:** baseline pushed to `main` 2026-11-23; per-phase branch workflow active (see `project-rules.md` §Git Workflow & Repository)
- **Next:** owner authorization → Phase 1 investigation on branch `phase-1-investigation` (see `docs/PROJECT-ROADMAP.md` §3) → evidence-based report + options A/B/C → owner approval → Phase 2

## Decisions

See `decisions.md` (D1–D12).
