# Report: GPA Settings Relocation + Anishop Customizer Navigation Guide

**Date:** 2026-11-28
**Branch:** `feat/phase-3-1-module-foundation`
**Base commit:** `9a14263` (Phase 3.1 module foundation)
**Main SHA (unchanged):** `2b4b0902fbaee70519df20f1bcc63723e1d591ba`
**Task type:** Narrowly scoped Phase 3.1 follow-up — relocate the GPA `table_style` setting
from the Customizer to the GPA admin page (two independently-saved sections), and convert the
Anishop Customizer section into a navigation guide. **NOT** authorization to begin Phase 3.2.

---

## 1. Owner Decision Recorded

**New decision D46** (2026-11-28) is recorded in `.clinerules/decisions.md` and **supersedes
D44** (the Customizer-control location). D46 specifies:

- The GPA `table_style` setting belongs on the GPA admin page under Products
  (Dashboard → Products → **گروه ویژگی‌های آنی**).
- The GPA page contains two visually separated, independently-saved sections:
  - **گروه‌های ویژگی** (group management — a Phase 3.1 placeholder; group CRUD is Phase 3.2).
  - **تنظیمات نمایش** (display settings — the `table_style` select).
- Each section has its own form and save action.
- The setting remains stored in the existing Theme Mod `anishop_gpa_settings` under the key
  `table_style`; accepted values `default` / `minimal` / `boxed`; default/fallback `default`.
- The GPA Table Style control is removed from the Customizer.
- The existing Anishop Customizer section is preserved as a **navigation guide** (not a
  settings container) with clickable links to the child-theme modules' configuration pages.
- D45 (Load REST endpoint product-ID transport) and D40–D43 remain unchanged.

**D44 status:** moved to the **Superseded / Obsolete** table with status **Superseded**,
superseded by D46. The historical D44 text is preserved verbatim.

**Documentation updated** (all authoritative docs that specified the old D44 Customizer
location):
- `.clinerules/decisions.md` — D46 added (active); D44 moved to superseded; header note +
  Resolved section updated.
- `.clinerules/memory-bank.md` — Current State + Decisions sections updated to D46.
- `docs/implementation/phase-3-implementation-roadmap.md` — 3.1 scope, acceptance criteria,
  and OI-5 resolution updated to D46.
- `docs/architecture/phase-2a-architecture-specification.md` — **inspected, no change needed**:
  it documents only the Theme Mod storage contract (`anishop_gpa_settings` → `table_style`,
  D34/D39), which D46 preserves; it does not reference the Customizer UI location.

## 2. Files Changed

**Live child theme** (`/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/`):
- `inc/ani-group-product-attributes/bootstrap.php` — modified (page render, save handler,
  Customizer nav guide, admin CSS enqueue).
- `inc/ani-group-product-attributes/config.php` — modified (docblock references D46).
- `assets/css/ani-group-product-attributes-admin.css` — **created** (minimal admin section
  styling + Customizer nav-guide styling).

**Workspace / git repo** (`/home/ramin/Downloads/woo-group-attributes/`):
- `.clinerules/decisions.md` — modified (D46).
- `.clinerules/memory-bank.md` — modified (D46).
- `docs/implementation/phase-3-implementation-roadmap.md` — modified (D46).
- `snapshots/blocksy-child/inc/ani-group-product-attributes/bootstrap.php` — refreshed (matches live).
- `snapshots/blocksy-child/inc/ani-group-product-attributes/config.php` — refreshed (matches live).
- `snapshots/blocksy-child/assets/css/ani-group-product-attributes-admin.css` — **created** (matches live).
- `reports/2026-11-28-gpa-settings-relocation-and-customizer-guide.md` — **created** (this report).

**No unrelated child-theme module was modified.** No frontend JS/CSS/renderer changes.

## 3. Actual Admin Architecture Inspected

Before coding, the following was inspected against the live child theme (source-of-truth
order: live site → real WP/Woo data):

- **GPA submenu registration** (`bootstrap.php`): `add_submenu_page( 'edit.php?post_type=product',
  …, 'گروه ویژگی‌های آنی', 'edit_posts', 'ani_gpa_group', 'ani_gpa_render_group_list' )` at
  `admin_menu` priority 20, plus a reorder callback (priority 99) that places the GPA item
  immediately after the WooCommerce `product_attributes` submenu (D43). **Unchanged.**
- **GPA admin page callback**: `ani_gpa_render_group_list()` — capability `edit_posts`.
- **GPA config/sanitizer** (`config.php`): `ANI_GPA_THEME_MOD = 'anishop_gpa_settings'`,
  `ani_gpa_get_settings()` (reads Theme Mod, falls back to `default`), and
  `ani_gpa_sanitize_settings()` (accepts only `default`/`minimal`/`boxed`, case/whitespace
  normalized, invalid/empty → `default`, pins `version` to 1). **Reused unchanged.**
- **The five Anishop dashboard tabs** and their registration mechanism: the Appearance →
  Anishop page is owned by `anishop-mobile-nav/admin.php` (page slug `anishop-mobile-nav`),
  with tabs added via the `anishop_admin_tabs` filter. Actual tab identifiers (verified in
  source): `mobile-nav` (default), `inline-variations`, `key-features`, `buy-box`,
  `search-box`.
- **Hero Slider admin destination**: the `anishop_hero_slide` CPT registers its own top-level
  admin menu (`edit.php?post_type=anishop_hero_slide`) in `anishop-hero/post-type.php`.
- **GPA admin destination**: `edit.php?post_type=product&page=ani_gpa_group`.
- **Existing Customizer Anishop section**: the GPA module registered the `anishop` panel +
  `anishop_gpa` section + `anishop_gpa_table_style` setting/control (D44). No other module
  registers a Customizer section, so the `anishop` panel is GPA-owned.

**No page slugs, tab IDs, or navigation URLs were invented** — all were verified in source.

## 4. GPA Page Layout Implementation

`ani_gpa_render_group_list()` now renders two visually separated sections inside
`<div class="wrap ani-gpa-wrap">`:

- **Section A — گروه‌های ویژگی**: an `<h2>` heading + a `.ani-gpa-section` container holding
  the Phase 3.1 placeholder text ("The group management interface is under construction
  (Phase 3.2)."). **No group-management form or action** (Phase 3.2 is not started).
- **Section B — تنظیمات نمایش**: an `<h2>` heading + a `.ani-gpa-section` container holding
  the display-settings form (see §5).

Visual separation: each section is a bordered `.ani-gpa-section` container (1px `#E1E8ED`
border, 4px radius, 16px padding, 24px bottom margin) with its own `<h2>` heading — a
WordPress-style section container. The layout is RTL-compatible (no hardcoded left/right
direction) and responsive (max-width 960px, fluid below). No decorative styling; house
palette + radii only.

## 5. Independent Form / Save Behavior

The display-settings section has its **own** form: a native HTML `<select>` (WordPress
`form-table` / `description` / `submit` conventions) with the three choices
(`default` / `minimal` / `boxed`), a dedicated nonce field, and its own save button
(`ذخیره تنظیمات نمایش`). The current value is selected via `selected( $settings['table_style'], … )`;
when no valid saved value exists, `default` is selected (verified, §8). Concise Persian
descriptions explain the three choices.

The form is **independent** of the group-management section: it is the only form on the page
in Phase 3.1, and its handler writes only the `table_style` key (and schema `version`) — it
never touches group-management data. Future group-management submissions (Phase 3.2) will use
a separate form/handler and will not overwrite display settings. **No shared form** for both
sections.

## 6. Capability and Nonce Security

The save handler `ani_gpa_save_settings()` (hooked on `admin_post_ani_gpa_save_settings`):

1. **POST-only**: rejects non-POST requests (`wp_die`).
2. **Capability**: requires `current_user_can( 'edit_theme_options' )` — **not** just the GPA
   page's `edit_posts` group-management capability. Users who can view the GPA page but lack
   `edit_theme_options` cannot save (verified, §9).
3. **Nonce**: a dedicated nonce field `ani_gpa_settings_nonce` (action `ani_gpa_save_settings`),
   verified server-side with `wp_verify_nonce()`.
4. **Rejects unauthorized** requests before any Theme Mod write.
5. **Sanitizes** via the existing `ani_gpa_sanitize_settings()` (single validation authority).
6. **Accepts only** `default` / `minimal` / `boxed`; invalid/empty/missing → `default`.
7. **Preserves unrelated** `anishop_gpa_settings` keys (reads existing array, updates only
   `table_style` + `version`).
8. **Persists** via `set_theme_mod( ANI_GPA_THEME_MOD, $existing )`.
9. **Safe redirect** back to the GPA admin page via `wp_safe_redirect( add_query_arg( …,
   admin_url( 'edit.php?post_type=product' ) ) )` with an `ani_gpa_notice=saved` flag — no
   user-controlled redirect destination.
10. **Success notice**: a WordPress-style `notice notice-success is-dismissible` is displayed
    when `ani_gpa_notice=saved` is present.

The form is only rendered when `current_user_can( 'edit_theme_options' )`; otherwise a
permission message is shown (the save action is not exposed to users who only have the GPA
page's group-management capability).

## 7. Theme Mod Persistence and Sanitizer Behavior

- **Storage contract preserved**: Theme Mod `anishop_gpa_settings`, key `table_style`. No
  separate WordPress option was created.
- **Sanitizer reused unchanged**: `ani_gpa_sanitize_settings()` (config.php) — accepts only
  `default`/`minimal`/`boxed`, normalizes case/whitespace, invalid/empty → `default`, pins
  `version` to 1.
- **Unrelated keys preserved**: the handler reads the existing Theme Mod array and updates
  only `table_style` + `version` (verified with a `sentinel` key, §9).

## 8. Default-Value Verification

All relevant paths verified at runtime (WP-CLI, `--user=1`):

| Path | Result |
|------|--------|
| Fresh install (no Theme Mod) | `default` ✅ |
| Theme Mod without `table_style` | `default` ✅ |
| Empty value (`''`) | `default` ✅ |
| Invalid value (`foo`) | `default` ✅ |
| Valid `minimal` | `minimal` ✅ |
| Valid `boxed` | `boxed` ✅ |
| Valid `default` | `default` ✅ |
| Case/whitespace ` MINIMAL ` | `minimal` ✅ |

The frontend style behavior is unchanged: `default` retains the native WooCommerce/Blocksy
appearance (no GPA frontend CSS is enqueued for `default`; the frontend CSS is a Phase 3.4
scope and was not touched).

## 9. Customizer Removal and Navigation Guide

**Removed** (verified absent at runtime, §10):
- The `anishop_gpa_table_style` Customizer setting.
- The `Ani_GPA_Table_Style_Setting` class.
- The `Ani_GPA_Table_Style_Control` class.
- The `anishop_gpa` Customizer section.

**Preserved**: the `anishop` Customizer panel (GPA-owned; no other module registers a
Customizer section).

**Added**: a static navigation-guide control in the `anishop` panel. It is a **display-only**
control — **no setting is bound** to it, so it writes nothing to the database. It renders a
list of clickable links (each `target="_blank" rel="noopener"`) to the configuration pages of
the child-theme modules:

| # | Link label | Destination (verified in source) |
|---|-----------|----------------------------------|
| 1 | Mobile Navigation | `admin.php?page=anishop-mobile-nav&tab=mobile-nav` |
| 2 | Inline Variations | `admin.php?page=anishop-mobile-nav&tab=inline-variations` |
| 3 | Key Features | `admin.php?page=anishop-mobile-nav&tab=key-features` |
| 4 | Buy Box | `admin.php?page=anishop-mobile-nav&tab=buy-box` |
| 5 | Search Box | `admin.php?page=anishop-mobile-nav&tab=search-box` |
| 6 | Hero Slider | `edit.php?post_type=anishop_hero_slide` |
| 7 | Group Attributes (GPA) | `edit.php?post_type=product&page=ani_gpa_group` |

All 7 URLs are generated with `admin_url()` / `add_query_arg()` — **no hardcoded hostnames**.
The five dashboard tabs use the real tab identifiers verified in source (§3). The guide is
styled minimally (`.ani-gpa-customizer-guide` list) and is RTL-compatible.

**No fake Theme Mod or option** was introduced for the guide. (WP core represents an
unbound control's setting as `array( 'default' => NULL )`; this was treated correctly in
tests — the guide control has no `setting` argument, so it is not a settings container.)

## 10. Runtime Verification (WP-CLI)

A 21-check WP-CLI verification (`--user=1`) passed **21/21**:

- **Sanitizer behavior** (8): fresh/absent/empty/invalid → `default`; valid `minimal`/`boxed`/
  `default` preserved; case/whitespace normalized.
- **Theme Mod key preservation**: the `anishop_gpa_settings` key is used (not a new option);
  unrelated keys preserved.
- **Customizer registration**: the `anishop` panel + `anishop_gpa` section + nav-guide control
  are registered; the nav-guide control has **no bound setting**.
- **Old GPA Customizer removal**: `anishop_gpa_table_style` setting, `Ani_GPA_Table_Style_Setting`,
  and `Ani_GPA_Table_Style_Control` are all absent.
- **Nav-guide rendering**: the guide control renders all 7 links with correct destinations.
- **GPA page rendering**: the page renders both sections (گروه‌های ویژگی + تنظیمات نمایش),
  the select with the correct current value, the nonce field, and the save button.

**Separate WP-CLI subprocess tests** (each in an isolated `wp eval` process) verified:
- **Valid save** updates `table_style` and preserves unrelated Theme Mod keys (sentinel key
  survived).
- **Invalid input** falls back to `default`.
- **Unauthorized user** (no `edit_theme_options`) cannot modify the Theme Mod.
- **Original Theme Mod state was restored** after the tests.

## 11. Static Verification

- **PHP lint** passed for all changed PHP files (`bootstrap.php`, `config.php`).
- **No old identifiers remain** in the live child theme: `anishop_gpa_table_style`,
  `Ani_GPA_Table_Style_Setting`, `Ani_GPA_Table_Style_Control` — all confirmed absent.
- **No hardcoded hostnames** in the nav-guide URLs (all `admin_url()` / `add_query_arg()`).
- **No new frontend JS/CSS/renderer** changes; no product/catalog/attribute/term/variation
  data modified.

## 12. Manual Browser Verification Required

The following require a real browser (not performed in this task — CLI cannot fully render
the admin UI or the Customizer):

- Confirm the GPA page shows two visually separated sections (گروه‌های ویژگی + تنظیمات نمایش).
- Save each Table Style value (`default` / `minimal` / `boxed`) and confirm persistence after
  reload.
- Confirm invalid/fallback behavior if manually testable.
- Confirm a user without `edit_theme_options` cannot save (permission message shown).
- Confirm the Customizer Anishop guide is visible in Appearance → Customize → Anishop.
- Confirm all 7 links navigate to the correct admin destinations.
- Confirm the five existing Anishop dashboard tabs and the Hero Slider destination still work.

## 13. Checks Not Performed

- **Browser UI verification** (see §12) — not performed; manual browser checks remain required.
- **`wp_styles()` queue inspection in CLI** — WP-CLI could not fully inspect the `wp_styles()`
  queue; the admin CSS enqueue was verified by code/hook checks (the `admin_enqueue_scripts`
  hook is registered and the enqueue is scoped to the GPA admin page hook suffix).
- **Real-mobile verification** — not applicable (admin-only change; no frontend output).

## 14. Phase 3.2 Boundary

**Phase 3.2 was NOT started.** Explicitly confirmed:

- **No group CRUD** (no create/edit/delete group functionality).
- **No product editor integration** (no `woocommerce_product_options_attributes` hook).
- **No REST endpoint** (no `anishop/v1/gpa/...` route).
- **No frontend JS/CSS/renderer changes.**
- **No product/catalog/attribute/term/variation data modified.**

The **گروه‌های ویژگی** section is a placeholder only. The group-management form and its
independent save action are Phase 3.2 scope.

## 15. Risks and Open Questions

- **Manual browser verification outstanding** (§12): the admin UI and Customizer guide were
  verified at the code/hook level and via WP-CLI rendering, but a real-browser pass is still
  required to confirm visual layout, link navigation, and save persistence in the UI.
- **`wp_styles()` queue not fully inspectable in CLI**: the admin CSS enqueue was verified by
  code/hook checks, not by inspecting the live `wp_styles()` queue.
- **No other open questions** — the storage contract, sanitizer, capability, and nonce
  behavior are all verified.

## 16. Git State

- **Branch:** `feat/phase-3-1-module-foundation` (existing branch; no new branch created).
- **Base preserved:** the branch base commit is unchanged; no merge/reset performed.
- **Main unchanged:** `main` remains at `2b4b0902fbaee70519df20f1bcc63723e1d591ba`.
- **Not merged into main.**
- **Snapshot refreshed:** the three changed child-theme files were synced into
  `snapshots/blocksy-child/` (verified byte-identical to live).
- **No temporary files committed:** `temp/gpa-settings-test.php` and `temp/gpa-menu-inspect.php`
  are excluded (gitignored / not staged).

## 17. Verification Summary

| Check | Result |
|-------|--------|
| PHP lint (changed PHP files) | ✅ PASS |
| WP-CLI runtime verification (21 checks) | ✅ 21/21 PASS |
| Valid save updates `table_style` + preserves keys | ✅ PASS |
| Invalid input falls back to `default` | ✅ PASS |
| Unauthorized user cannot modify Theme Mod | ✅ PASS |
| Original Theme Mod state restored after tests | ✅ PASS |
| Old Customizer identifiers removed | ✅ PASS |
| Nav-guide renders 7 correct links | ✅ PASS |
| GPA page renders two sections + form | ✅ PASS |
| No old identifiers remain in live theme | ✅ PASS |
| No hardcoded hostnames in guide URLs | ✅ PASS |
| Snapshot matches live (3 files) | ✅ PASS |
| Browser UI verification | ⏳ NOT PERFORMED (manual required) |
| `wp_styles()` queue inspection | ⏳ NOT PERFORMED (code/hook verified) |

## 18. Completion Statement

The narrowly scoped Phase 3.1 follow-up is complete:

- The GPA `table_style` setting is relocated from the Customizer to the GPA admin page
  (Dashboard → Products → **گروه ویژگی‌های آنی**) in a dedicated **تنظیمات نمایش** section
  with its own form and `admin_post_ani_gpa_save_settings` save handler (POST-only, nonce,
  `edit_theme_options`).
- The GPA page shows two visually separated, independently-saved sections (گروه‌های ویژگی
  placeholder + تنظیمات نمایش).
- The storage contract is preserved (Theme Mod `anishop_gpa_settings` → `table_style`,
  `default`/`minimal`/`boxed`, fallback `default`, existing sanitizer, unrelated keys
  preserved).
- The GPA Table Style control is removed from the Customizer; the Anishop Customizer section
  is preserved as a display-only navigation guide with 7 verified admin links.
- D46 is recorded (supersedes D44); all authoritative docs updated.
- The snapshot is refreshed; no temporary files committed.
- **Phase 3.2 remains untouched.**

**Remaining:** manual browser verification (§12) before the task is considered fully
verified in the UI.
