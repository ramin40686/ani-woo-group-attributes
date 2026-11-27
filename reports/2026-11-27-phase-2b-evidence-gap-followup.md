# Phase 2B — Evidence-Gap Follow-Up Report

**Date:** 2026-11-27
**Branch:** `chore/phase-2b-evidence-gap-followup` (based on `c63aacf`)
**Status:** COMPLETE — B2-19 corrected to PASS, B2-2 clarified, B2-20 re-confirmed, fixture 13143 restored byte-exact
**Scope:** Close three evidence gaps from the Phase 2B runtime verification report (2026-11-26) and restore test fixture 13143 to its pre-test state.

---

## 1. Purpose

The Phase 2B runtime verification report (14/14 PASS) left three evidence gaps that
required follow-up:

1. **B2-19** (admin save/processing path) — the original verification was **inconclusive**
   because it used an empty taxonomy attribute, which does not exercise the full save path.
2. **B2-2** (Products top-level menu position) — the original verification relied on
   WP-CLI menu dumps, which are unreliable due to WooCommerce's `custom_menu_order`
   repositioning. The rendered admin sidebar is the authoritative source.
3. **B2-20** (save-button disable on empty attrs) — re-confirmed as valid.

Additionally, the B2-19 correction test modified fixture product **13143** (added a custom
attribute `gpa-test-b2-19` and triggered a native WooCommerce save that removed 5 stale
taxonomy references). This fixture must be restored to its exact pre-test state.

---

## 2. B2-19 Correction — Admin Save/Processing Path

### 2.1 Why the original was inconclusive

The original B2-19 verification confirmed that `WC_Meta_Box_Product_Data::save` processes
attributes, but the test used an **empty taxonomy attribute** (a `pa_*` attribute with no
terms selected). An empty taxonomy attribute does not exercise the full save path because:

- WooCommerce's save handler skips empty taxonomy attributes.
- The `maybe_disable_save_button()` guard prevents the save button from being enabled
  when all attribute fields are empty.
- No actual `_product_attributes` mutation occurs.

### 2.2 Corrected test procedure

1. **Added a custom attribute** `GPA TEST — B2-19` (slug: `gpa-test-b2-19`) to product
   13143 via the native WooCommerce product editor.
2. **Manually called `jQuery.maybe_disable_save_button()`** to re-evaluate the save button
   state. This was necessary because WooCommerce's save button does **not** re-enable on
   `input`/`change` events — it only re-evaluates on row add/remove.
3. **Saved the product** via the native WooCommerce save button.
4. **Verified persistence** of `gpa-test-b2-19` in `_product_attributes`.

### 2.3 Results

| Check | Result |
|-------|--------|
| Custom attribute `gpa-test-b2-19` persisted in `_product_attributes` | **PASS** |
| Native WooCommerce save removed 5 stale taxonomy references | **PASS** (expected native behavior) |
| Save button required manual `maybe_disable_save_button()` re-evaluation | **Documented** |

**B2-19 status: PASS** (corrected). The native WooCommerce admin save path can persist a
new custom attribute. The save button's re-evaluation behavior is a native WooCommerce
characteristic that GPA must respect (not bypass).

### 2.4 Side effect on fixture 13143

The native save removed the 5 stale taxonomy references (`pa_brand`, `pa_at_mdl`,
`pa_at_mnbe-anrzhi`, `pa_وضعیت-محصول`, `pa_guarantee`) from `_product_attributes`, reducing
the attribute count from 13 to 9 (8 custom + 1 new test attribute). This required a
full fixture restoration (see §5).

---

## 3. B2-2 Clarification — Products Menu Position

### 3.1 Issue

The original B2-2 verification reported the Products menu at position **26** based on
WP-CLI menu dumps. However, WP-CLI menu dumps are **unreliable** for this purpose because:

- WooCommerce enables `custom_menu_order` (via `add_filter('custom_menu_order', '__return_true')`).
- WooCommerce's `menu_order` filter moves the Products menu **after** the WooCommerce menu.
- The raw WP-CLI `$menu` array reflects the **registered** position, not the **rendered**
  position after `custom_menu_order` repositioning.

### 3.2 Corrected evidence

The observation that WooCommerce appears first and Products immediately after it is a
**rendered-browser observation** from a real WordPress admin sidebar session — not a
conclusion derived from WP-CLI menu-position values. The WP-CLI `$menu` array reflects
registered positions before `custom_menu_order` repositioning and does not represent
what the admin actually sees.

In the rendered sidebar:

- The **WooCommerce** menu appears first.
- The **Products** menu appears **immediately after** WooCommerce.
- This is the position GPA's independent top-level menu must target (immediately after
  Products, per decision D35).

No screenshot was captured for this observation.

### 3.3 B2-2 status

**B2-2 remains PASS**, but the evidence basis is corrected: the rendered admin sidebar is
authoritative, not the raw WP-CLI menu position. The fractional offset (B2-36) should be
computed relative to the **rendered** Products position, not the registered position.

---

## 4. B2-20 Re-confirmation — Save-Button Disable on Empty Attrs

B2-20 was tested with a dedicated 4-scenario browser interaction sequence
(`temp/b2-20-run.py` + `temp/b2-20-s3b.py`) on product 13143's native
Product Data → Attributes panel. The save button is
`#product_attributes button.save_attributes`.

**Observed sequence** (evidence: `temp/b2-20-results.json`, `temp/b2-20-s3b.json`):

| Step | Action | Button class | Row count |
|------|--------|-------------|-----------|
| S1 | Initial (8 existing custom attrs, all with values) | `button save_attributes button-primary` | 8 |
| S2 | Added a global attribute row (`pa_color`, no term selected) | `button save_attributes button-primary disabled` | 9 |
| S3 | Attempted term selection (term options were empty — no value set) | `button save_attributes button-primary disabled` | 9 |
| S3b | Re-opened term select, 4 terms loaded, selected "سبز" | `button save_attributes button-primary` | 9 |
| S4 | Removed the added row | `button save_attributes button-primary` | 8 |

**Findings:**

- The `disabled` CSS class is added to the save button when a newly added attribute
  row has no value (S2). The HTML `disabled` property is **not** set — WooCommerce
  uses a CSS class, not the native `disabled` attribute.
- The button remains disabled while the row has no value (S3).
- The button is re-enabled (class removed) when a term is actually selected (S3b).
- The button is re-enabled when the row is removed (S4).
- The re-evaluation is triggered by row add/remove and select2 change events, not by
  generic `input`/`change` on text fields.

**B2-20 status: PASS.** The native `maybe_disable_save_button()` guard correctly
disables the save button when attribute fields are empty and re-enables it when
values are provided or the row is removed. This is a native WooCommerce guard that
GPA must respect (not bypass).



---

## 5. Fixture 13143 Restoration

### 5.1 Problem

After the B2-19 correction test, fixture 13143's `_product_attributes` was in a modified
state:
- Test attribute `gpa-test-b2-19` was present.
- 5 stale taxonomy references were removed by the native save.
- A subsequent restore attempt via `gpa-state.php` **double-serialized** the value
  (stored a serialized string `s:2985:"a:13:{...}"` instead of the raw serialized array
  `a:13:{...}`).

### 5.2 Recovery procedure

The following procedure was executed to restore the exact original raw value:

#### Step 1: Extract and verify the original raw value from the backup

```
Backup file: backups/gpa-test-fixtures/product-13143-...json
Field: meta._product_attributes[0]
```

| Property | Value |
|----------|-------|
| Type | PHP serialized array (`a:13:{...}`) |
| Character length (mb_strlen) | 2775 |
| Byte length (strlen) | 2985 |
| SHA-256 | `d1c6e427979179d135e5e34db68002ec2889a977b17ad03963f7213ad2843871` |
| First 50 chars | `a:13:{s:8:"pa_brand";a:6:{s:4:"name";s:8:"pa_brand` |
| Last 50 chars | `;s:12:"is_variation";i:0;s:11:"is_taxonomy";i:1;}}` |

Cross-verified against `temp/b2-19r-before.json` (pre-test snapshot): **identical** (same
SHA-256, same length).

#### Step 2: Snapshot current (corrupted) DB state

```
File: temp/snapshot-current-13143.json
```

| Property | Value |
|----------|-------|
| Row count for `_product_attributes` | 1 (no duplicates) |
| meta_id | **1564267** |
| post_id | 13143 |
| Byte length | 2995 |
| SHA-256 | `a50bb2d415a22685c443e7fd616c276cc397fb68fc4d096ab17bc2c96c5da428` |
| First 60 chars | `s:2985:"a:13:{s:8:"pa_brand";a:6:{s:4:"name";s:8:"pa_brand";` |
| Total meta keys for post 13143 | 88 |

The current value was **double-serialized**: a serialized string (`s:2985:"..."`) containing
the serialized array (`a:13:{...}`).

#### Step 3: Direct parameterized DB update

**Method:** `$wpdb->update('wp_postmeta', ...)` with a prepared statement — **NOT**
`add_post_meta()` or `update_post_meta()` (which would serialize the already-serialized
string again).

```php
$wpdb->update(
    'wp_postmeta',
    array('meta_value' => $raw_value),  // raw serialized array string
    array('meta_id' => 1564267),
    array('%s'),
    array('%d')
);
```

**Result:** 1 row affected.

#### Step 4: Verification

| Check | Before | After | Expected | Status |
|-------|--------|-------|----------|--------|
| Byte length | 2995 | 2985 | 2985 | **PASS** |
| SHA-256 | `a50bb2d4...` | `d1c6e427...` | `d1c6e427...` | **PASS** |
| Byte-for-byte match with backup | — | Yes | Yes | **PASS** |
| `get_post_meta()` type | string (corrupted) | array | array | **PASS** |
| `get_post_meta()` count | — | 13 | 13 | **PASS** |
| Test attr `gpa-test-b2-19` | present | absent | absent | **PASS** |
| Stale ref `pa_brand` | absent | present | present | **PASS** |
| Stale ref `pa_guarantee` | absent | present | present | **PASS** |
| Unrelated meta rows modified | — | 0 | 0 | **PASS** |

**Attribute keys restored (13):**
`pa_brand`, `pa_at_mdl`, `۶وع-اصلاح`, `pa_at_mnbe-anrzhi`, `pa_وضعیت-محصول`,
`سری-قابل-هشتشو`, `طراحی`, `مدتا-شارج-کامل`, `مدتا-زمان-استفاده`, `سری-جایگزین`,
`سایز-امکانات`, `محهویات-جعبه`, `pa_guarantee`

#### Step 5: Read-only WooCommerce verification

| Check | Result |
|-------|--------|
| Product name | ماشین اصلاح فیلیپس مدل OneBlade QP2824/10 |
| Product type | simple |
| `get_attributes()` count | 8 (all custom) |
| Taxonomy attrs returned | 0 (5 stale refs filtered by WooCommerce) |
| Custom attrs returned | 8 |
| Test attr in WooCommerce | ABSENT |

This matches the original pre-test state: 13 raw entries (5 stale taxonomy + 8 custom),
with WooCommerce rendering only the 8 valid custom attributes.

### 5.3 Root cause of double-serialization

The `gpa-state.php` restore mode called `add_post_meta($id, '_product_attributes', $value)`
where `$value` was the raw serialized array string (`a:13:{...}`). WordPress's
`add_post_meta()` → `update_metadata()` → `sanitize_meta()` path **serialized the string
again**, producing `s:2985:"a:13:{...}"`. The fix was to bypass the WP meta API entirely
and use a direct parameterized `$wpdb->update()` on the specific `meta_id` row.

### 5.4 Lesson for future fixture operations

**Never pass an already-serialized string through `add_post_meta()` or `update_post_meta()`.**
For byte-exact restoration of serialized meta values, use a direct parameterized DB update
on the specific `meta_id` row.

---

## 6. Changed Files

| Action | Path |
|--------|------|
| Created | `reports/2026-11-27-phase-2b-evidence-gap-followup.md` (this report) |
| Created | `temp/snapshot-current-13143.json` (pre-fix DB snapshot) |
| Created | `temp/snapshot-current-13143.php` (snapshot script) |
| Created | `temp/fix-13143-pa.php` (direct DB fix script) |
| Created | `temp/verify-13143-final.php` (verification script) |
| Created | `temp/b2-19r-restored.json` (restoration artifact) |
| Created | `temp/b2-19r-final-verify.json` (final verification artifact) |
| Created | `temp/verify-13143-output.txt` (verification output log) |
| Removed | `gpa-magic-login.php` (WordPress root — temporary diagnostic endpoint) |
| Removed | `gpa-state.php` (WordPress root — temporary diagnostic endpoint) |

No production code, theme file, or plugin file was modified. The restoration operation
targeted a single `wp_postmeta` row (`meta_id` 1564267, post 13143, key
`_product_attributes`). The verification confirmed that all 88 meta rows for post 13143
were unchanged except the target row. This verification was scoped to post 13143's meta
rows and a read-only WooCommerce attribute check; it does not constitute a full database
audit of all tables, products, terms, or options.

---

## 7. Verification Summary

| Item | Status |
|------|--------|
| B2-19 (admin save path) | **PASS** (corrected) |
| B2-2 (Products menu position) | **PASS** (evidence basis corrected) |
| B2-20 (save-button disable) | **PASS** (browser-verified) |
| Fixture 13143 byte-exact restoration | **PASS** |
| Post 13143 meta rows unchanged (except target) | **PASS** |

---

## 8. Risks & Open Questions

1. **`gpa-state.php` restore mode is unsafe for serialized meta values.** The script's
   restore mode uses `add_post_meta()`, which double-serializes already-serialized strings.
   This script is a temporary diagnostic tool (to be removed after this task) and should
   not be reused for fixture restoration without fixing the serialization issue.
2. **Open owner items (unchanged):** group categories (B2-33), group status policy
   (B2-34), table-style visual design (B2-35), admin menu fractional offset (B2-36).

---

## 9. Conclusion

All three evidence gaps are closed:
- **B2-19** is now **PASS** with a corrected test that exercises the full save path.
- **B2-2** evidence basis is corrected to use the rendered admin sidebar as authoritative.
- **B2-20** is browser-verified (4-scenario save-button disable test).

Fixture 13143's `_product_attributes` raw value is restored to its **exact pre-test state**
(byte-for-byte match with the original backup, SHA-256 verified). The direct database
update targeted the identified `meta_id` (1564267). The verification confirmed that all
88 meta rows for post 13143 were unchanged except the target row. This verification was
scoped to post 13143's meta rows and a read-only WooCommerce attribute check; it does not
constitute a full database audit of all tables, products, terms, or options.

**Cleanup:** Temporary diagnostic endpoints (`gpa-magic-login.php`, `gpa-state.php`)
removed from the WordPress installation as part of this task.