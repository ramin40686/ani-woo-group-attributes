# Report: Phase 3.1 GPA Module — Remediation and Completion

**Date:** 2026-11-28
**Branch:** `feat/phase-3-1-module-foundation`
**Base commit:** `3d4411d` (feat: add Phase 3.1 GPA module foundation to blocksy-child snapshot)
**Task type:** Implementation remediation — fix the GPA admin-menu capability and make the
Customizer `table_style` control/setting safe and functional; keep the single approved Theme
Mod save path; verify at runtime.

---

## 1. Objective

Complete Phase 3.1 GPA module remediation:

1. Correct the GPA menu/list capability from `edit_theme_options` to `edit_posts`.
2. Make the Customizer `table_style` control/setting **safe and functional** (the previous
   implementation had a fatal-error risk because it overrode the `final`
   `WP_Customize_Control::value()` method).
3. Keep **one approved Theme Mod save path** (the Customizer) and the single Theme Mod key
   `anishop_gpa_settings`.
4. Verify at runtime.
5. Report, commit, and push.

## 2. Root Cause of the Customizer Defect

The original Phase 3.1 implementation registered the Customizer setting against the **entire**
`anishop_gpa_settings` Theme Mod (an array) and used a custom `WP_Customize_Control` subclass
that overrode `value()` to extract the `table_style` key from the array.

In the local WordPress version (**7.1.5**), `WP_Customize_Control::value()` is declared
**`final`** (verified in `wp-includes/class-wp-customize-control.php`). Overriding a `final`
method is a **fatal error** in PHP. Because the control class was declared inside the
`customize_register` callback, the fatal error would only surface when the Customizer actually
loaded (Appearance → Customize), not at file-include time — a latent, hard-to-diagnose break.

## 3. The Fix (WordPress-conventional)

The fix uses a **custom `WP_Customize_Setting` subclass** — the WordPress-conventional way to
expose a single scalar key from an array-based Theme Mod to a Customizer control:

- **`Ani_GPA_Table_Style_Setting extends WP_Customize_Setting`**
  - `value()` returns the **scalar** `table_style` value read from the `anishop_gpa_settings`
    Theme Mod (falling back to `default` when absent), so the select control displays the
    correct current value.
  - `update( $value )` writes the **full sanitized settings array** back to the Theme Mod via
    `set_theme_mod( ANI_GPA_THEME_MOD, $value )`. The `$value` is the output of
    `ani_gpa_sanitize_settings()` (the full array), applied by the Customizer save path before
    `update()` is called.
- **`Ani_GPA_Table_Style_Control extends WP_Customize_Control`**
  - A plain select control (`type => 'select'`) with choices `default` / `minimal` / `boxed`.
  - It **does not** override any `final` `WP_Customize_Control` method. The current value is
    supplied by the custom setting, so no `value()` override is needed.
- **Registration**
  - Setting ID: `anishop_gpa_table_style` (the scalar key), `type => 'theme_mod'`,
    `theme_mod => anishop_gpa_settings`, `transport => 'refresh'`,
    `sanitize_callback => 'ani_gpa_sanitize_settings'`, `default => 'default'`.
  - The setting **instance** is passed to `$manager->add_setting()`.
  - The select control is registered against the setting ID `anishop_gpa_table_style`.

Both classes are declared **inside** the `customize_register` callback (not at file-include
time), each guarded by `class_exists()`, because the child-theme bootstrap runs before
WordPress loads the Customizer classes. `WP_Customize_Setting` and `WP_Customize_Control` are
guaranteed available at `customize_register` time (the `WP_Customize_Manager` constructor loads
them before the hook fires).

### Preserved (unchanged)

- `ani_gpa_sanitize_settings()` — the single validation authority (uses `sanitize_key()` +
  allowlist `default` / `minimal` / `boxed`; a raw string input is treated as the
  `table_style` value; non-array/non-scalar input returns the full defaults).
- `ani_gpa_get_settings()` and `ani_gpa_get_defaults()`.
- Theme Mod key `anishop_gpa_settings` and setting key `table_style`.
- The single approved write path: the Customizer (the conflicting unused helper
  `ani_gpa_save_settings()` was removed from `config.php` in a prior step of this task).

## 4. Menu Capability Correction

The GPA admin submenu and the group-list render guard were corrected from
`edit_theme_options` to `edit_posts`:

- `add_submenu_page()` capability argument (`ani_gpa_register_admin_menu()`).
- `current_user_can()` guard in `ani_gpa_render_group_list()`.

This aligns the GPA group-management menu with the WooCommerce Products submenu it lives under
(D43) and with the `edit_posts` capability that product editors hold.

## 5. Verification

### 5.1 Static / syntax

| Check | Result |
|-------|--------|
| `php -l` live `bootstrap.php` | **PASS** (no syntax errors) |
| `php -l` live `config.php` | **PASS** (no syntax errors) |
| `php -l` snapshot `bootstrap.php` | **PASS** (no syntax errors) |
| `php -l` snapshot `config.php` | **PASS** (no syntax errors) |
| `diff -rq` live vs snapshot GPA module | **PASS** (identical) |
| `git diff --check` | **PASS** (no whitespace errors) |
| No remaining references to `ani_gpa_save_settings()` | **PASS** (0 matches) |
| Customizer classes declared only inside the callback | **PASS** (verified by search) |

### 5.2 Runtime (WP-CLI, `wp eval-file temp/gpa-customizer-test.php --user=1`)

The test records the original Theme Mod, fires `customize_register` against a real
`WP_Customize_Manager`, exercises the **real Customizer save path**
(`WP_Customize_Setting::save()` fed via `WP_Customize_Manager::set_post_value()`), and restores
the original value exactly.

| Check | Result |
|-------|--------|
| Section `anishop_gpa` exists (title "Group Attributes", panel `anishop`) | **PASS** |
| Control `anishop_gpa_table_style` exists | **PASS** |
| Control class is `Ani_GPA_Table_Style_Control` | **PASS** |
| Control type is `select` | **PASS** |
| Control choices are exactly `default`, `minimal`, `boxed` | **PASS** |
| Setting `anishop_gpa_table_style` exists (class `Ani_GPA_Table_Style_Setting`, type `theme_mod`) | **PASS** |
| Setting `sanitize_callback` is `ani_gpa_sanitize_settings` | **PASS** |
| Setting `value()` returns the scalar `table_style` (`default`) | **PASS** |
| `check_capabilities()` returns `true` (admin user) | **PASS** |
| Persistence: `default` → Theme Mod `table_style = default` | **PASS** |
| Persistence: `minimal` → Theme Mod `table_style = minimal` | **PASS** |
| Persistence: `boxed` → Theme Mod `table_style = boxed` | **PASS** |
| Full Theme Mod shape preserved on save (`{"version":1,"table_style":"…"}`) | **PASS** |
| Invalid value `invalid_value` → falls back to `default` | **PASS** |
| Invalid value `''` → falls back to `default` | **PASS** |
| Invalid value `hacked; DROP TABLE` → falls back to `default` | **PASS** |
| Invalid value `not_a_style` → falls back to `default` | **PASS** |
| Invalid value `boxed2` → falls back to `default` | **PASS** |
| Normalization `MINIMAL` → `minimal` (valid) | **PASS** |
| Normalization `Default` → `default` (valid) | **PASS** |
| Normalization `BOXED` → `boxed` (valid) | **PASS** |
| Normalization `minimal ` (trailing space) → `minimal` (valid) | **PASS** |
| Original Theme Mod restored exactly (`{"version":1,"table_style":"default"}`) | **PASS** |

> **Note on normalization:** the sanitizer uses `sanitize_key()` (lowercases + strips
> non-alphanumerics), so case/whitespace variants of valid values (e.g. `MINIMAL`, `minimal `)
> normalize to valid values rather than falling back to `default`. This is correct, intended
> behavior; the truly invalid values above all fall back to `default`.


### 5.3 Regression / static

| Check | Result |
|-------|--------|
| Frontend (homepage) loads | **PASS** (HTTP 200, no PHP errors in output) |
| Admin (`wp-admin/`) reachable | **PASS** (HTTP 302 → login, expected without auth) |
| Customizer (`customize.php`) reachable | **PASS** (HTTP 302 → login, expected without auth) |
| GPA admin page (`edit.php?post_type=ani_gpa_group`) reachable | **PASS** (HTTP 302 → login) |
| All 14 existing child-theme modules present (bootstrap/config) | **PASS** |
| GPA CPT `ani_gpa_group` registered | **PASS** |
| GPA settings retrieval + defaults (`{"version":1,"table_style":"default"}`) | **PASS** |
| GPA functions exist (`sanitize_settings`, `get_settings`, `get_defaults`, `table_styles`, `customize_register`) | **PASS** |
| No new PHP fatal errors / warnings | **PASS** |

### 5.4 NOT VERIFIED

| Item | Status | Reason |
|------|--------|--------|
| Browser-based Customizer UI (Appearance → Customize → Anishop → Group Attributes: visual section, select control, choices, and in-browser save) | **NOT VERIFIED** | No browser/screenshot tooling (WordPress Studio MCP `take_screenshot` / `inspect_design`) was available in this session, and the Customizer page requires an authenticated browser session that could not be safely driven headlessly. The Customizer **registration and save path** are verified at runtime (§5.2), but the **visual UI** has not been confirmed in a real browser. |

## 6. Changed Files

| File | Change |
|------|--------|
| `snapshots/blocksy-child/inc/ani-group-product-attributes/bootstrap.php` | Modified — (a) menu/list capability `edit_theme_options` → `edit_posts`; (b) Customizer rewritten: custom `Ani_GPA_Table_Style_Setting` (scalar `value()`, array `update()`) + plain select `Ani_GPA_Table_Style_Control` (no `final`-method override); setting registered as an instance against ID `anishop_gpa_table_style`. |
| `snapshots/blocksy-child/inc/ani-group-product-attributes/config.php` | Modified — removed the conflicting unused helper `ani_gpa_save_settings()` (single write path = the Customizer). |
| `reports/2026-11-28-phase-3-1-remediation-and-completion.md` | This report (created). |

The live files
`/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/inc/ani-group-product-attributes/{bootstrap,config}.php`
were edited first and then copied to the snapshot; `diff -rq` confirms the live and snapshot
GPA module directories are **identical**.

A temporary diagnostic script `temp/gpa-customizer-test.php` was used for the runtime
verification (workspace-only, outside production) and is removed after this task.

## 7. Confirmation

- **Theme Mod restored:** the original value `{"version":1,"table_style":"default"}` was
  recorded before testing and restored exactly after testing (verified byte-for-byte).
- **Single write path:** the only code path that writes the `anishop_gpa_settings` Theme Mod is
  the Customizer (`Ani_GPA_Table_Style_Setting::update()`). The conflicting
  `ani_gpa_save_settings()` helper was removed; no other write path exists.
- **No fatal-error risk:** the Customizer control no longer overrides the `final`
  `WP_Customize_Control::value()` method; the scalar extraction is done in the custom
  `WP_Customize_Setting` subclass, which is the WordPress-conventional approach.
- **No Phase 3.2 work performed:** no group CRUD UI, no group editor, no drag-and-drop
  ordering, and no other Phase 3.2 feature was implemented. This task is limited to the
  Phase 3.1 module-foundation remediation.
- **No unrelated changes:** only the two GPA snapshot files and this report changed.
- **No merge into `main`:** `main` remains at `2b4b0902fbaee70519df20f1bcc63723e1d591ba`; the
  branch `feat/phase-3-1-module-foundation` is **not** merged.
- **No database/catalog changes:** no products, attributes, terms, fixtures, or catalog data
  were modified. The only data touched was the `anishop_gpa_settings` Theme Mod during the
  runtime test, which was restored to its original value.

## 8. Open Item

- **Browser-based Customizer UI verification** (§5.4) remains **NOT VERIFIED** and should be
  confirmed by the owner (or a session with browser/screenshot tooling) at
  Appearance → Customize → Anishop → Group Attributes before Phase 3.1 is considered fully
  closed. The runtime registration and save path are verified; only the visual UI is pending.

