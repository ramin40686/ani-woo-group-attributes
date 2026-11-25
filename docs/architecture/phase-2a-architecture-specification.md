# Phase 2A — Architecture Specification: WooCommerce Product Group Attributes (GPA)

> **Implementation-ready architecture specification** for the GPA module as a Blocksy
> child-theme module for the Anishop store.
>
> **Phase:** 2A (architecture design — ROADMAP §3, Phase 2, part A).
> **Status:** documentation only. This document defines the contracts, data models, and
> integration points that Phase 3 (implementation) will build against. It implements
> **nothing** and does **not** authorize implementation.
>
> **Phase 2A.1 correction (2026-11-25):** corrected the `get_post()` group-reference
> sequence (§3.3), the custom-attribute aggregation query (posts ⋈ postmeta JOIN, §5.2),
> the group-value ↔ product-attribute matching contract (§5.7, §5.8), taxonomy-scoped term
> validation (§5.8), strict Boolean sanitization (§9.3), and save/render revalidation
> (§4.4, §7.2). See `reports/2026-11-25-phase-2a-1-architecture-corrections.md`.
>
> **Architecture direction:** **A1** (data ownership: CPT + taxonomy + product meta +
> Theme Mod) + **B1** (Blocksy custom-layer rendering). Axis **C** (delimiter
> normalization) is **DEFERRED** (D20).
>
> **Label conventions used throughout this document:**
>
> | Label | Meaning |
> |-------|---------|
> | **[DECISION D#]** | Approved owner decision (see `.clinerules/decisions.md`). |
> | **[REQ]** | Technical requirement — forced by the live environment, project rules, or an approved decision. |
> | **[RECOMMEND]** | Recommendation — a design choice proposed here, **not yet owner-approved**. |
> | **[UNRESOLVED]** | Open question requiring owner input. |
> | **[2B-VERIFY]** | Must be verified at runtime in **Phase 2B** before implementation. |
>
> **Provenance labels** (per `project-rules.md` §Verification):
> `[LIVE-VERIFIED <date>]` · `[STATIC CODE EVIDENCE]` · `[WC 11.3.1 SOURCE]` ·
> `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` · `[NOT VERIFIED]`.

---

## 0. Scope and status of this document

- **In scope (Phase 2A):** the full technical architecture — component responsibilities,
  file structure, data contracts, hook-by-hook integration, security, performance,
  compatibility, and testing strategy — for owner approval.
- **Out of scope (Phase 2A):** any production PHP/JS/CSS, any database write, any catalog
  change, any delimiter normalization (D20), any catalog cleanup (D21).
- **Intentionally deferred to Phase 2B:** runtime verification of the Blocksy custom-layer
  hook/registration/position (B1 is a *selected direction*, not yet runtime-verified —
  D16), and runtime `persian-woocommerce` compatibility testing (D19).
- **Unresolved (must not be assumed):** the "View Attribute" shop-page button behavior
  (D18). It is kept architecturally isolated and configurable so the final choice is a
  small change.

**New evidence gathered for this specification (2026-11-25):** the native
`shop_attributes` table's exact render chain in Blocksy 2.1.62 + WC 11.3.1, the exact
attribute-name / option formats returned by `WC_Product::get_attributes()` for both
attribute kinds (live-verified on fixtures 13143 and 17028), and the clean non-destructive
mechanism to hide the native table. See §8.2 and the Evidence Appendix (§13).

---

## 1. System overview

### 1.1 What GPA is

GPA lets Anishop store admins organize WooCommerce product attributes into named, sortable
**groups** (optionally organized into **group categories**), assign those groups to
products, choose which attribute values each product displays, and render the selected
attributes on the frontend — all managed from the WordPress admin and rendered inside the
Blocksy child theme. The target experience matches the official WooCommerce Group
Attributes plugin (reference: `docs/references/`) **without** any runtime dependency on
that plugin or on Blocksy Pro.

GPA is a **presentation layer** [DECISION D9]: it renders selected attribute values
**without modifying** native WooCommerce attribute data (`_product_attributes`,
visibility flags, or variation semantics). Precedent: the existing
`anishop-inline-variations` module.

### 1.2 Architecture direction (A1 + B1; C deferred)

| Axis | Choice | Status |
|------|--------|--------|
| **A — Data ownership** | **A1**: CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat` for group definitions; product meta `_ani_gpa_assigned_groups` + `_ani_gpa_selected_values` for per-product state; Theme Mod for global settings. | [DECISION D22] selected; matches the decided data model in `project-rules.md`. |
| **B — Frontend rendering** | **B1**: Blocksy custom layer (`blocksy:woocommerce:product:custom:layer`) at a configurable position; GPA renders its own grouped tables. | [DECISION D16] selected; **NOT yet runtime-verified** → [2B-VERIFY]. |
| **C — Value parsing** | **DEFERRED.** No comma/Persian-comma splitting, no `_product_attributes` migration, no value rewriting. GPA respects actual stored values. | [DECISION D20] deferred. |

### 1.3 Module file structure

All new code is isolated in the child theme (project rules). Target layout:

```
wp-content/themes/blocksy-child/
  inc/ani-group-product-attributes/
    config.php      — Theme Mod schema + single sanitizer (anishop_gpa_settings);
                      value-reference helpers; constants (meta keys, layer ID, caps).
    bootstrap.php   — CPT ani_gpa_group + taxonomy ani_gpa_group_cat registration;
                      asset registration; hook wiring (the module entry point).
    admin.php       — Appearance → Anishop tabs (anishop_admin_tabs filter);
                      group list/edit UI; group-category UI; settings form + save.
    product.php     — product-edit GPA section (load groups / select values / sort);
                      product-meta save handler.
    render.php      — frontend grouped-table renderer (Blocksy custom layer, B1);
                      value resolution; native-table hide filter; "View Attribute"
                      pluggable render function (D18).
  assets/js/ani-group-product-attributes.js        — frontend (likely none; see §7.5).
  assets/js/ani-group-product-attributes-admin.js  — drag-and-drop (vanilla IIFE).
  assets/css/ani-group-product-attributes.css      — frontend tables (RTL-first).
  assets/css/ani-group-product-attributes-admin.css
  languages/        — .pot + fa_IR.po/.mo (text domain `anishop`).
```

- **Bootstrap entry:** one new `require_once` in `functions.php`
  (`inc/ani-group-product-attributes/bootstrap.php`), consistent with the 14 existing
  entries. [REQ — house pattern]
- **Prefixes** [REQ — project rules]: PHP `ani_gpa_` / `Ani_GPA_`; CSS `.ani-gpa-`;
  text domain `anishop`.
- **No** new plugins, **no** JS frameworks, **no** page builders, **no** Blocksy Pro
  runtime dependency. [REQ]

### 1.4 Data flow

**Frontend (single product page):**

```
Blocksy single-product layout
  └─ custom layer `ani_gpa` (inserted at the configured position)   [B1]
       └─ render.php:
            1. gate: feature enabled? single product? product exists?
            2. read _ani_gpa_assigned_groups (ordered group IDs)
            3. if empty → render nothing (native table stays visible)   [D17]
            4. for each group ID (in order):
                 - load group (CPT); if missing → skip (stale)
                 - read _ani_gpa_selected_values[group_id] (ordered value refs)
                 - if none → skip group
                 - resolve each value ref → display text
                     (taxonomy: term ID → term name; custom: raw string)
                 - render one grouped table (group name + value rows)
            5. (optional) hide native table via filter   [D17, only if setting on
               AND GPA produced renderable content]
```

**Admin (product edit):**

```
"Manage Attribute Groups" + Load  /  "Load Group Categories"
  └─ REST endpoint (nonce-protected) returns the group(s)' value refs
       for the current product (both attribute kinds)
  └─ admin selects which values display + drag-and-drop group sorting
  └─ save handler (nonce + edit_product cap + autosave/revision guards)
       writes _ani_gpa_assigned_groups + _ani_gpa_selected_values
```

## 2. Group CPT and taxonomy design

### 2.1 CPT `ani_gpa_group`

Registered in `bootstrap.php` on `init` (precedent: `anishop-hero` CPT registration).
[REQ — decided data model]

| Argument | Value | Rationale |
|----------|-------|-----------|
| `label` / `labels` | "Attribute Groups" (i18n, text domain `anishop`) | Admin UI. |
| `public` | `false` | Groups are admin-only; never exposed in the frontend query loop or REST. [REQ] |
| `publicly_queryable` | `false` | No public permalinks. [REQ] |
| `show_ui` | `true` | Admin list/edit screens. [REQ] |
| `show_in_menu` | `true` (own top-level menu, `menu_position` ~25, `menu_icon` dashicons) | Precedent: `anishop_hero_slide` uses its own top-level menu. [RECOMMEND] |
| `show_in_rest` | `false` | No Gutenberg/REST exposure. [REQ] |
| `exclude_from_search` | `true` | Not searchable. [REQ] |
| `has_archive` | `false` | No archive. [REQ] |
| `supports` | `['title', 'page-attributes']` | `title` = group name; `page-attributes` = `menu_order` for global group sorting. [RECOMMEND] |
| `capability_type` | `post` (default) with explicit `map_meta_cap` | Save handlers check `edit_post`/`edit_posts` (see §10.1). [REQ] |
| `rewrite` | `false` | No rewrite rules (avoids flush-rewrites side effects). [RECOMMEND] |

- **Group name** = the CPT post title. [REQ]
- **Global group order** = `menu_order` (page-attributes), used as the default order when
  groups are listed/loaded. Per-product order is separate (§3.1). [RECOMMEND]
- **No** `post_status` beyond `publish`/`draft`. A group is renderable only when
  `publish`. [RECOMMEND]

### 2.2 Taxonomy `ani_gpa_group_cat`

Registered in `bootstrap.php` on `init`, attached to `ani_gpa_group`. [REQ — decided data model]

| Argument | Value | Rationale |
|----------|-------|-----------|
| `hierarchical` | `true` | Group categories form a tree (like `product_cat`). [RECOMMEND] |
| `public` | `false` | Admin-only. [REQ] |
| `show_ui` | `true` | Standard WP taxonomy UI (create/edit/delete categories) — satisfies ROADMAP §1.2 with zero custom code. [REQ] |
| `show_in_menu` | `false` (appears under the CPT menu) | Keeps the admin surface compact. [RECOMMEND] |
| `show_in_rest` | `false` | No REST exposure. [REQ] |
| `rewrite` | `false` | No rewrite rules. [RECOMMEND] |

- **Group → category** is the standard term relationship (one group may belong to one or
  more categories). [RECOMMEND]
- "Load Group Categories" (ROADMAP §1.3) loads **all groups of a category** at once —
  resolved via `get_objects_in_term()` / a `WP_Query` on the CPT with a `tax_query` on
  `ani_gpa_group_cat`. [REQ]

### 2.3 Group value storage (`_ani_gpa_group_values`)

A group stores its **ordered list of attribute value references** as post meta
`_ani_gpa_group_values` (serialized array). This is the group's "set of existing
attribute values selected to be displayed under it" (reference behavior). [REQ]

- Written only via the group edit save handler (nonce + `edit_post` cap + single
  authority sanitization). [REQ]
- Order in the array = the group's default value order (drag-and-drop in the group edit
  screen). [REQ]
- Each entry is a **value reference** (§2.4).

### 2.4 Value reference structure (the core data primitive)

A **value reference** is the unit used by both the group's value list and the product's
selected values. It is a small associative array:

```php
array(
  'attribute' => 'pa_color',   // attribute NAME (see §5 for exact format per kind)
  'value'     => 'green',      // taxonomy: term SLUG;  custom: raw value string (display text)
  'value_id'  => 4300,         // taxonomy: term ID;    custom: 0
)
```

**Field semantics (live-verified 2026-11-25 on fixtures 13143 + 17028):**

| Field | Taxonomy (global) attribute | Custom (product-specific) attribute |
|-------|------------------------------|--------------------------------------|
| `attribute` | `pa_{slug}` — e.g. `pa_color` (matches `WC_Product_Attribute::get_name()`) | raw attribute name — e.g. `نوع اصلاح` (matches `get_name()`; **not** URL-encoded) |
| `value` | term **slug** — e.g. `green` (matches `get_variation_attributes()` option format) | raw value **string** — e.g. `برش مستقیم / خشک وخیس` (the display text itself) |
| `value_id` | term **ID** — e.g. `4300` (matches `get_attributes()` option format) | `0` (custom attributes have no term ID) |

**Why both `value` (slug) and `value_id` (term ID) for taxonomy:**
`WC_Product::get_attributes()` returns **term IDs** as options, while
`get_variation_attributes()` returns **term slugs**. Storing both lets the renderer
resolve by ID (stable) and verify by slug, and lets the admin UI match against either
source. [LIVE-VERIFIED 2026-11-25] [REQ]

**Custom attribute note (D20):** for custom attributes, `value` is the **entire stored
string** as WC sees it (parsed by `wc_get_text_attributes()`, which splits only on `|`).
Anishop has **0** pipe-separated custom attributes, so each custom attribute yields
exactly **one** value (the whole string). GPA does **not** split on commas. [DECISION D20]
[LIVE-VERIFIED 2026-11-25]

**A value reference is a *candidate*, not a product assertion.** A value reference
identifies a value in the **group vocabulary** (tier A, §5.7). It does **not** by itself
prove that the value exists on a given product. Product-specific validity — "does this
value actually exist on the *current* product?" — is a **separate** enforcement, applied at
both save and render (§5.7, §5.8, §4.4, §7.2). Membership in a group alone is **not**
sufficient to render a value on a product. [REQ]

**Resolution at render time** (see §7.2) — *only after* the value has passed
product-specific validation (§5.8):
- Taxonomy: `get_term( $value_id, $expected_taxonomy )` (taxonomy-scoped lookup, §5.8) →
  display `term->name` (current name). If the term is missing, is not a `WP_Term`, or does
  not belong to the expected taxonomy → skip the value (stale/invalid; degrade gracefully,
  R-L5).
- Custom: display `$value` directly (it is the display text), **only if** it still matches
  a value actually present on the product (§5.8).

## 3. Product-to-group data contract

### 3.1 `_ani_gpa_assigned_groups`

- **Type:** product meta, serialized array of **group post IDs** (`int`), in **display
  order**. [REQ — decided data model]
- **Semantics:** the ordered list of groups assigned to this product. The array order is
  the **per-product group order** (drag-and-drop on the product edit screen), independent
  of the groups' global `menu_order`. [REQ]
- **Written** only via the product-edit save handler (§6.6). [REQ]
- **Read** by the frontend renderer (§7) and the product-edit UI. [REQ]
- **Lifecycle (D15, R-L4):** the assignment **survives product-type changes** (simple ↔
  variable, with/without variations). Groups stay assigned; rendering adapts to whatever
  attributes the parent product actually has. GPA never deletes or rewrites the
  assignment on type change. [DECISION D15]
- **Stale group IDs:** if a group post is deleted, its ID may remain in the array. The
  renderer skips missing groups (R-L5). A cleanup pass is optional and **not** required
  (D21 — no catalog cleanup). [RECOMMEND]

### 3.2 Invariants

- Every ID in `_ani_gpa_assigned_groups` is a valid `ani_gpa_group` post ID **at write
  time** (validated on save). [REQ]
- No duplicates. [REQ]
- The array is always an array (never a scalar); empty array = no groups assigned. [REQ]

### 3.3 Group reference validation (correct `get_post()` usage)

A group ID stored in `_ani_gpa_assigned_groups` must be validated as a **valid group
reference** before it is trusted — at save time (§4.4) and again at render time (§7.2).
The validation is a **sequence**, not a single call:

1. `$post = get_post( $group_id );` — retrieve the post. **Do not** pass a post type as the
   second argument: `get_post( $id, 'ani_gpa_group' )` is **incorrect** — the second
   parameter of `get_post()` is an *output format* (`OBJECT` / `ARRAY_A` / `ARRAY_N`),
   **not** a post-type constraint. [REQ — WP core API]
2. **Existence:** if `$post` is `null` → the reference is **stale** (the group was deleted).
   Reject / skip it.
3. **Post type:** verify `$post->post_type === 'ani_gpa_group'`. A post that exists but has
   a different `post_type` is **not** a group — reject it. `get_post()` does **not** enforce
   the expected post type; GPA must check it explicitly. [REQ]
4. **Status:** verify the post status is permitted **for the operation** (see below).
5. Reject any reference that fails steps 2–4.

**Three distinct cases (do not conflate):**

| Case | Meaning | Handling |
|------|---------|----------|
| Valid group reference | exists, `post_type = ani_gpa_group`, status permitted | accepted |
| Exists, wrong post type | `get_post()` returns a post, but `post_type !== 'ani_gpa_group'` | rejected (not a group) |
| Exists, disallowed status | correct post type, but status not permitted for the operation | rejected for that operation |

**Permitted status per operation:**
- **Render (§7.2):** a group renders only when its status is `publish` (consistent with
  §2.1: "A group is renderable only when `publish`"). [RECOMMEND]
- **Save / assignment (§4.4):** the set of statuses that may be *assigned* to a product is
  **not** fixed by an approved decision. [UNRESOLVED — U11] The recommended default is to
  allow `publish` (and optionally `draft`) at assignment time and re-check at render; the
  exact allowed set is an owner decision. Do **not** invent a policy here.

This sequence is the single source of truth for "is this a usable group?"; both the save
handler and the renderer call it (or an equivalent) rather than re-implementing the checks.
[REQ]

---

## 4. Selected attribute value data contract

### 4.1 `_ani_gpa_selected_values`

- **Type:** product meta, serialized **associative array**:
  `group_id (int) => ordered array of value references` (§2.4). [REQ — decided data model]
- **Semantics:** for each assigned group, the **subset** of that group's values the admin
  chose to display on the product page, in display order. [REQ]
- **Written** only via the product-edit save handler (§6.6). [REQ]
- **Read** by the frontend renderer (§7). [REQ]

Example (product 17028, two assigned groups):

```php
array(
  501 => array(                       // group 501 "رنگ‌ها"
    array( 'attribute' => 'pa_color', 'value' => 'green',  'value_id' => 4300 ),
    array( 'attribute' => 'pa_color', 'value' => 'black',  'value_id' => 4286 ),
  ),
  502 => array(                       // group 502 "حافظه"
    array( 'attribute' => 'pa_memory-capacity', 'value' => '256gb', 'value_id' => 4292 ),
  ),
)
```

### 4.2 Validation (on save)

Save-time validation enforces **both** group membership **and** product-attribute
membership. A value reference is accepted only if it passes **all** of the following;
anything that fails is dropped (not persisted as a valid selection). [REQ]

- **Group key:** the key must be a group ID present in `_ani_gpa_assigned_groups` **and** a
  valid group reference (§3.3: exists, `post_type = ani_gpa_group`, status permitted for
  save). [REQ]
- **Group membership (tier A):** the value reference must be a member of that group's
  `_ani_gpa_group_values` (matched by `attribute` + `value_id` for taxonomy,
  `attribute` + `value` for custom). Non-members are dropped. [REQ]
- **Product-attribute membership (tier B):** the value reference must also match a value
  **actually present on the current product** (§5.8). Group membership alone is **not**
  sufficient — a value that exists in the group but not on the product is dropped. [REQ]
- **Term / taxonomy identity (taxonomy values):** the term ID must resolve to a `WP_Term`
  in the expected taxonomy, and the stored slug must be consistent with it (§5.8). [REQ]
- Value references are re-serialized in the admin's chosen order. [REQ]
- Groups assigned but with **no** selected values are allowed (they render as empty —
  see §7.4). [RECOMMEND]

### 4.3 Empty / stale handling

- **No assigned groups** → renderer outputs nothing; native table stays visible. [D17]
- **Assigned group, no selected values** → the group is **skipped** (no empty table
  rendered). [RECOMMEND]
- **Selected value whose term was deleted** → that value is skipped; the rest of the
  group renders. [RECOMMEND] (R-L5)

### 4.4 Save-time vs render-time revalidation

GPA treats stored metadata as **untrusted input** at both boundaries. The two boundaries
are distinct and both are mandatory. [REQ]

**Save-time validation** (product-edit save handler, §6.6): validate *incoming* data before
persisting. For each submitted group ID and value reference:
- Verify the group reference (§3.3): exists, `post_type = ani_gpa_group`, status permitted
  for save.
- Verify group membership (tier A) **and** product-attribute membership (tier B) per §4.2.
- Verify taxonomy / term identity (§5.8) for taxonomy values; exact value identity (§5.8)
  for custom values.
- Persist **only** the accepted subset, in the admin's chosen order. Rejected references are
  dropped, not stored. [REQ]

**Render-time validation** (frontend renderer, §7.2): treat *stored* metadata as potentially
stale or invalid — it may have been valid when saved, but the product, its attributes, the
group, or the terms may have changed since. Before rendering, re-verify **everything**:
- Verify each group reference (§3.3) and its render-permitted status (`publish`).
- Verify the group / value relationship (the value is still in the group's vocabulary).
- Verify taxonomy + term identity (§5.8) for taxonomy values.
- Verify custom attribute identity + exact value match (§5.8).
- Verify each selected value **still exists on the current product** (tier B).
- **Exclude** any invalid or stale value / group from the output. [REQ]

**Rendering must not:** assume previously saved metadata is permanently valid; modify product
metadata; or perform destructive cleanup. [REQ]

**Stale selected-value lifecycle** [RECOMMEND — U13]: a selected value that no longer exists
on the product is **ignored at render** (not displayed) and **rejected at the next save**
(not re-persisted as valid), but is **retained in storage** until an explicit save rewrites
the array. GPA performs **no** destructive automatic cleanup (consistent with D21 — no
catalog cleanup). The exact "ignored vs retained" default is an owner-confirmation item (U13).

**No valid values remain:** if, after render-time validation, a group has no valid values (or
no group has any), GPA renders nothing for that group / product and the native table stays
visible (§7.3, D17). [REQ]

## 5. Global vs custom attribute resolution

GPA **must** support both global (taxonomy) and custom (product-specific) attributes; the
two storage mechanisms are **not** identical internally and each is resolved with the
appropriate WooCommerce API. [DECISION D14]

### 5.1 Taxonomy (global) attributes

- **Key:** `pa_{slug}` (e.g. `pa_color`, `pa_memory-capacity`). [LIVE-VERIFIED 2026-11-25]
- **Detection:** `WC_Product_Attribute::is_taxonomy() === true`; the attribute has a
  non-zero taxonomy ID (`get_id()`). [WC 11.3.1 SOURCE]
- **Value source (store-wide vocabulary):** all terms of the `pa_{slug}` taxonomy —
  `get_terms( array( 'taxonomy' => 'pa_{slug}', 'hide_empty' => false ) )`. Used by the
  admin value picker. [REQ]
- **Value source (per product):** `wc_get_product_terms( $product_id, 'pa_{slug}' )` or
  the term IDs from `get_attributes()[name]->get_options()`. [WC 11.3.1 SOURCE]
- **Display text:** the **term name**, resolved at render time so renames are reflected.
  Resolve with a **taxonomy-scoped** lookup — `get_term( $value_id, 'pa_{slug}' )` — so the
  term is verified to belong to the expected taxonomy. A bare `get_term( $value_id )` does
  **not** prove taxonomy membership (see §5.8). [RECOMMEND]
- **Value reference:** `attribute = pa_{slug}`, `value = term slug`, `value_id = term ID`.

### 5.2 Custom (product-specific) attributes

- **Key:** the raw attribute name (e.g. `نوع اصلاح`) — **not** URL-encoded.
  [LIVE-VERIFIED 2026-11-25]
- **Detection:** `WC_Product_Attribute::is_taxonomy() === false`; `get_id() === 0`.
  [WC 11.3.1 SOURCE]
- **Value source (per product):** the `value` string in `_product_attributes`, parsed by
  `wc_get_text_attributes()` (splits **only** on `|`). For Anishop (0 pipe-separated
  custom attributes) this yields exactly **one** value per custom attribute — the whole
  string. [DECISION D20] [LIVE-VERIFIED 2026-11-25]
- **Value source (store-wide vocabulary, for the admin picker):** there is **no** central
  registry of custom attribute values. GPA aggregates distinct custom attribute
  name → value pairs from the catalog. [RECOMMEND]

  **Query architecture (corrected 2026-11-25).** The aggregation reads
  `_product_attributes` from the postmeta table **and** restricts to product posts. Because
  `wp_postmeta` has **no** `post_type` column, the query **must JOIN the posts table** to
  filter by post type (and, if chosen, by post status). Conceptual shape (table names via
  `$wpdb->posts` / `$wpdb->postmeta`; the prefix is **never** hardcoded):

  ```sql
  SELECT pm.meta_value
  FROM {$wpdb->postmeta} AS pm
  INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id
  WHERE pm.meta_key = '_product_attributes'
    AND p.post_type = 'product'
    -- AND p.post_status IN ( ... )   -- see "Product-status inclusion" below
  ```

  - **Why the JOIN is required:** `post_type` (and `post_status`) live on `wp_posts`, not on
    `wp_postmeta`. Filtering `post_type` directly on `wp_postmeta` is structurally invalid
    (no such column) and would either error or silently match non-product meta. [REQ]
  - **Product-status inclusion** [UNRESOLVED — U12]: which product statuses contribute
    values to the picker is **not** fixed by an approved decision. Alternatives:
    - **(a) `publish` only** — picker shows only values on live products. Simplest; may hide
      values on in-progress (draft/private) products the admin is editing.
    - **(b) all non-trashed** (`publish`, `draft`, `private`, `pending`, `future`) — picker
      also surfaces in-progress values; broader vocabulary.
    - **(c) all including `trash`** — not recommended; trashed products should not seed the
      live vocabulary.
    The exact set is an owner decision (U12). Trashed products are **not** recommended in any
    case. [UNRESOLVED]
  - **Safe parsing of serialized `_product_attributes`:** each `meta_value` is a
    **serialized** PHP array. Parse with `unserialize()` **guarded** — verify the result is
    an `array` before use. Do **not** `eval()` or `unserialize()` untrusted data without the
    guard. [REQ]
  - **Malformed / unexpected metadata:** if a blob fails to parse, is not an array, or has an
    unexpected shape (missing `name`/`value` keys, non-string values), **skip that blob**
    (and log under `WP_DEBUG`). Never let one malformed product abort the whole aggregation.
    [REQ]
  - **Caching:** the distinct `name => [values]` map is **cached in a transient** (single
    key) so the expensive query runs only on cache miss. [RECOMMEND]
  - **Cache invalidation** — the transient must be invalidated (deleted) on events that can
    change custom attribute values. Required coverage:
    - Product creation (`save_post_product` / `wp_insert_post` for `product`).
    - Product update (`save_post_product`).
    - Product deletion / trashing (`wp_trash_post`, `before_delete_post`).
    - Product restoration (`untrash_post`).
    - Direct changes to `_product_attributes` (covered by `save_post_product` in the normal
      admin flow; **import / bulk-update paths** that write `_product_attributes` without
      firing `save_post_product` must be verified). [2B-VERIFY]
    - **Do not** attempt an exhaustive event implementation here; the above is the required
      architectural behavior, and the exact hook coverage (especially import / bulk /
      programmatic writes) is a Phase 2B verification item (B1-14). [2B-VERIFY]

  This avoids the per-product object loop that R-L10 warns against. [RECOMMEND] — see §10.2
  for the performance contract.
- **Display text:** the raw value string, stored verbatim in the value reference. [REQ]
- **Value reference:** `attribute = raw name`, `value = raw string`, `value_id = 0`.

### 5.3 Dual support (D14)

- Both kinds participate in groups and in per-product selection. [DECISION D14]
- The admin value picker presents **both** kinds (taxonomy terms + custom values),
  clearly labeled. [REQ]
- The renderer resolves each value reference by its kind (taxonomy → term lookup; custom
  → verbatim string). [REQ]
- A single group may mix values from multiple attributes of either kind. [REQ]

### 5.4 Parent-first (D15)

- The **parent product** is the primary attribute source. GPA must **not** require actual
  variation children to display attributes. [DECISION D15]
- `variable` type does **not** prove variations exist. GPA handles: simple,
  variable-with-variations, variable-without-variations, and inconsistent variation
  flags — always reading attributes from the parent via `wc_get_product( $id )` +
  `get_attributes()`. [DECISION D15]
- GPA **never** modifies native variation semantics. [DECISION D15]

### 5.5 Product-type detection (R-L11)

- **Single product:** use the product API — `$product->get_type()` /
  `$product->is_type( 'variable' )`. Never infer type from the presence of variations or
  from `is_variation` flags. [REQ]
- **Batch taxonomy queries** (e.g. "which products use term X"): use
  `WP_Query` with `field => 'slug'` (or `'ID'`) to avoid full object hydration. [REQ]

### 5.6 Value source contract (summary)

| Concern | Taxonomy | Custom |
|---------|----------|--------|
| Attribute key | `pa_{slug}` | raw name |
| Kind detection | `is_taxonomy() === true` | `is_taxonomy() === false` |
| Store-wide values (picker) | `get_terms( pa_{slug} )` | cached catalog aggregation (transient) |
| Per-product values | term IDs / `wc_get_product_terms()` | `wc_get_text_attributes( value )` |
| Display text | term name (resolved by ID) | raw string (verbatim) |

### 5.7 Value validity model (three tiers)

GPA distinguishes **three** distinct sets of values. Conflating them is the root cause of
incorrect frontend output, so the contract is explicit. [REQ]

| Tier | Name | Definition | Source of truth |
|------|------|------------|-----------------|
| **A** | **Group vocabulary** | The reusable set of candidate values defined by the attribute group (`_ani_gpa_group_values`). Store-wide, shared across products. | The group's own meta. |
| **B** | **Product attribute values** | The values **actually present** on the current WooCommerce product (its real `_product_attributes` / taxonomy terms). | The product's native WC attribute data (read-only). |
| **C** | **Selected product values** | The subset the admin explicitly chose to display on *this* product (`_ani_gpa_selected_values`). | Product meta, but **only valid** where C ⊆ (A ∩ B). |

**The validity rule:** a value may be *selected* (tier C) and *rendered* on a product only
if it is a member of the group's vocabulary (tier A) **and** actually exists on the product
(tier B). In set terms: **valid C ⊆ (A ∩ B)**. [REQ]

**Worked example (the failure this prevents):**
- Group "رنگ‌ها" vocabulary (A): Green, Black, White, Gold.
- Product's actual `pa_color` values (B): Green, Black.
- The admin may select **only** Green and Black (A ∩ B). **White and Gold must not be
  selectable or renderable** for this product, even though they exist in the group. [REQ]

**Loading a group onto a product** (§6.3): loading a group makes its vocabulary (A)
*available as candidates* in the admin UI. It does **not** copy values onto the product and
does **not** modify native attributes. Only values in **A ∩ B** can become valid selections
(C). The product's existing attribute data remains the source of truth (B). [REQ]
[DECISION D9 — presentation-only]

### 5.8 Matching contract: group value ↔ actual product attribute

This is the exact identity contract used at **save** (§4.2) and **render** (§7.2) to decide
whether a value reference (tier A) is actually present on the product (tier B). It is
per-attribute-kind. [REQ]

**Taxonomy (global) attributes** — match by **term ID + taxonomy identity together** (never
by display label alone):

1. The expected taxonomy is derived from the value reference's `attribute` field, which for
   taxonomy attributes **is** the taxonomy slug (e.g. `pa_color`). [REQ]
2. Resolve the term with a **taxonomy-scoped** lookup: `get_term( $value_id, $attribute )`.
   This returns a `WP_Term` **only if** the term exists **and** belongs to that exact
   taxonomy; otherwise it returns `null` / `WP_Error`. A bare `get_term( $value_id )` is
   **insufficient** — it does not prove taxonomy membership. [REQ — WP core API]
3. **Consistency:** the resolved term's `slug` must equal the stored `value` (slug) field.
   A mismatch (stale slug, or an ID pointing at a different term) is invalid. [REQ]
4. **Product membership:** the term must actually be assigned to the current product for this
   attribute — i.e. `$value_id` is in the product's term set for that taxonomy, obtained via
   `wc_get_product_terms( $product_id, $attribute )` (returns `WP_Term[]`) or the term IDs
   from `get_attributes()[ $attribute ]->get_options()`. [REQ — WC 11.3.1 SOURCE]
5. A term belonging to **any other** taxonomy (even one sharing the same `term_id` in a
   different `term_taxonomy`) is **not** a valid match. [REQ]

**Custom (product-specific) attributes** — match by **attribute-name identity + exact value
identity**:

1. **Name identity:** the value reference's `attribute` (raw name) must equal the product's
   custom attribute name exactly (byte-for-byte; no case-folding, no extra trimming beyond
   what WC itself does). [REQ]
2. **Value identity:** the value reference's `value` (raw string) must be **exactly equal**
   to one of the product's parsed values for that attribute, where the product's values are
   obtained by `wc_get_text_attributes( $stored_value )` — the **existing WooCommerce
   parsing behavior** (splits on `|` only, `html_entity_decode`s, trims, drops empties).
   [REQ — WC 11.3.1 SOURCE]
3. **No new normalization (D20):** GPA does **not** split or normalize on `|`, Persian comma
   `،`, or ASCII comma `,`. It uses WC's parsed values as the reference point. Anishop has 0
   pipe-separated custom attributes, so each custom attribute yields exactly one value (the
   whole string). [DECISION D20]
4. If the exact matching contract for a particular edge case (e.g. entity-encoding
   differences between the stored and parsed forms) cannot be established from the current
   specification and verified WC APIs, it is marked [2B-VERIFY] rather than guessed.
   [2B-VERIFY]

**Handling invalid / deleted / mismatched / stale references** (applies to both kinds):

| Context | Behavior |
|---------|----------|
| Group validation (group edit save) | A value reference whose term is missing / wrong-taxonomy / inconsistent is rejected from `_ani_gpa_group_values` (or flagged). [REQ] |
| Product save (§4.2) | A value reference that fails the matching contract is **dropped** (not persisted as a valid selection). [REQ] |
| Frontend render (§7.2) | A value reference that fails the matching contract is **skipped** (not rendered); the rest of the group renders. [RECOMMEND] (R-L5) |

**Preserved architecture:** the Global (taxonomy) and Custom attribute architecture is
unchanged; this section only defines the *matching / identity* contract on top of it. [REQ]

## 6. Admin integration contracts

### 6.1 CPT admin (group list / edit)

- **List table:** standard `WP_Post_Type` list (title, category, value count, order,
  date). Pagination + batched queries (R-L10). [REQ]
- **Edit screen:**
  - **Name** = post title. [REQ]
  - **Group category** = taxonomy `ani_gpa_group_cat` (standard WP metabox). [REQ]
  - **Order** = `menu_order` (page-attributes). [RECOMMEND]
  - **Values** = ordered list of value references with **drag-and-drop** sorting. The
    value picker lists existing values for **both** attribute kinds (§5.6). [REQ]
- **Save handler:** `save_post_ani_gpa_group` — nonce + `current_user_can( 'edit_post',
  $post_id )` + single-authority sanitization of `_ani_gpa_group_values`. [REQ]

### 6.2 Taxonomy admin (group categories)

- Standard WP hierarchical taxonomy UI (create/edit/delete) — zero custom code.
  Satisfies ROADMAP §1.2. [REQ]

### 6.3 Product-edit integration

The GPA section appears on the product edit screen (Product Data → **Attributes** tab,
consistent with the reference plugin's placement). [RECOMMEND]

- **"Create group for this product" toggle** — a UI affordance that reveals the GPA
  section for this product. The actual state is the presence of
  `_ani_gpa_assigned_groups`; the toggle is not a separate persisted flag. [RECOMMEND]
  (The reference exposes this toggle; its exact persistence is not specified — flagged.)
- **"Manage Attribute Groups" select + Load** — loads **one** group onto the product: adds
  its ID to `_ani_gpa_assigned_groups` and makes the group's vocabulary (tier A) available
  as **candidates** in the value-selection UI. It does **not** copy values onto the product
  and does **not** modify native attributes. Only values in **A ∩ B** (present on the
  product, §5.7 / §5.8) can be selected; the seeded `_ani_gpa_selected_values` contains only
  those valid candidates (or is left empty for the admin to choose). [REQ]
- **"Load Group Categories"** — loads **all groups of a category** at once (tax_query on
  `ani_gpa_group_cat`); the same A ∩ B candidate rule applies to each loaded group. [REQ]
- **Value selection** — the admin checks/unchecks which **valid** (A ∩ B) loaded values
  display on the product page (writes `_ani_gpa_selected_values`). Values that are in the
  group but **not** on the product are not offered as selectable. [REQ]
- **Per-product group sorting** — drag-and-drop reorders `_ani_gpa_assigned_groups`.
  [REQ]
- **Integration hook:** `woocommerce_product_options_attributes` (render) +
  `woocommerce_process_product_meta` (save). [REQ]
- **Data source for the picker:** a nonce-protected REST endpoint returns the group(s)'
  value references for the current product (both attribute kinds), so the admin UI can
  render checkboxes without a full page reload. [RECOMMEND]

### 6.4 Settings tab (Appearance → Anishop)

- GPA adds a tab to the existing Appearance → Anishop page via the
  `anishop_admin_tabs` filter (house pattern; the mobile-nav module owns the page).
  [REQ]
- The tab renders the global settings form (§9) and handles its save (nonce +
  `edit_theme_options`). [REQ]

### 6.5 AJAX / REST strategy

- **REST-first** (house rule: prefer REST over `admin-ajax.php`). [REQ]
- Endpoints (namespaced `anishop/v1`, nonce-protected, capability-checked):
  - `GET  /gpa/groups` — list groups (for the product-edit picker), with optional
    `category` filter.
  - `GET  /gpa/groups/{id}/values` — a group's value references.
  - `GET  /gpa/attribute-values` — the store-wide value vocabulary (both kinds; custom
    values from the cached aggregation, §5.2).
- All responses are JSON via `wp_send_json_*`-equivalent REST responses; all inputs
  sanitized/validated. [REQ]
- **No** `admin-ajax.php` endpoints unless a specific admin-screen constraint forces it
  (none identified). [RECOMMEND]

### 6.6 Save handlers (summary)

| Screen | Hook | Guard | Writes |
|--------|------|-------|--------|
| Group edit | `save_post_ani_gpa_group` | nonce + `edit_post` | `_ani_gpa_group_values`, `menu_order`, category terms |
| Product edit | `woocommerce_process_product_meta` | nonce + `edit_product` + **autosave/revision guards** | `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values` |
| Settings tab | `admin_init` (or `options.php`-style) | nonce + `edit_theme_options` | Theme Mod `anishop_gpa_settings` |

## 7. Frontend rendering pipeline

### 7.1 Render trigger and gating

- **Trigger:** the Blocksy custom layer `ani_gpa` fires
  `blocksy:woocommerce:product:custom:layer` with `$layer` on single product pages (B1).
  [2B-VERIFY — exact hook/registration/position]
- **Gate (all must be true, else render nothing):**
  1. Feature enabled (Theme Mod `anishop_gpa_settings.enabled`). [REQ]
  2. Single product view (`is_product()`), product exists. [REQ]
  3. `_ani_gpa_assigned_groups` is non-empty. [REQ]
- If any gate fails → **render nothing**; the native table is **not** touched (stays
  visible). [D17]

### 7.2 Resolution and rendering

The renderer treats stored metadata as **untrusted** and re-validates everything at render
time (§4.4). For each group ID in `_ani_gpa_assigned_groups` (in order):

1. **Validate the group reference** (§3.3): `get_post( $group_id )` (no post-type argument),
   then confirm it exists, `post_type === 'ani_gpa_group'`, and status is `publish`
   (render-permitted). If any check fails → **skip** (stale / invalid). [REQ]
2. Group name = the CPT title. [REQ]
3. Read `_ani_gpa_selected_values[ $group_id ]` (ordered value refs). If empty →
   **skip the group** (no empty table). [RECOMMEND]
4. For each value ref, **re-validate against the current product** (§5.8) and resolve
   display text:
   - Taxonomy: `get_term( $value_id, $attribute )` (taxonomy-scoped) → confirm it is a
     `WP_Term` in the expected taxonomy, its slug matches the stored `value`, and the term
     is actually assigned to this product (`wc_get_product_terms()` / `get_options()`).
     Display `term->name`. If any check fails → **skip the value**. [REQ]
   - Custom: confirm the stored `value` still exactly matches a parsed value on the product
     (`wc_get_text_attributes()`, §5.8). Display the stored `value` (verbatim). If it no
     longer matches → **skip the value**. [REQ]
5. Render **one grouped table** for the group: a header (group name) + one row per
   **valid** resolved value. All output escaped (`esc_html` / `esc_attr`). [REQ]

- **Batching:** term lookups are batched per taxonomy (collect all `value_id`s, one
  `get_terms`/`WP_Term_Query` per taxonomy) to avoid N+1. [REQ] (R-L10)
- **No writes:** the renderer is read-only; it never modifies product data or performs
  destructive cleanup. [D9] [REQ]
- **All values invalid:** if a group ends up with zero valid values after re-validation, it
  is skipped (no empty table); if no group has any valid values, GPA renders nothing and the
  native table stays visible (§7.3, D17). [REQ]

### 7.3 Native table (D17)

- **Default:** the native `shop_attributes` table **remains visible**. GPA renders its
  grouped tables **independently** (in the summary custom layer). [DECISION D17]
- **Optional hide:** a Theme Mod setting (`hide_native_table`) can hide the native table
  **only when** the setting is on **and** GPA actually produced **valid** renderable content
  for the current product — i.e. at least one group passed group-reference validation (§3.3)
  **and** at least one value passed product-specific re-validation (§5.8) and was rendered.
  Stale or invalid GPA metadata (deleted groups, removed terms, values no longer on the
  product) does **not** count as renderable content and must **not** trigger the hide.
  Mechanism: the `woocommerce_display_product_attributes` filter returns `array()` (the
  template guard `if ( ! $product_attributes ) { return; }` then suppresses the table).
  This is **non-destructive** (display-only; `_product_attributes` is untouched).
  [DECISION D17] [STATIC CODE EVIDENCE — see §8.3]
- **Fallback (D17):** if GPA has **no** valid assigned groups / selected values /
  renderable content — including the case where all stored GPA data is stale or invalid —
  the native table **must not** disappear. [DECISION D17]

### 7.4 Empty / error states

- **No groups / no values / all stale** → GPA renders nothing; native table visible.
  [D17]
- **Partially stale** (some values/groups missing) → render what is valid; skip the rest.
  Never crash, never render a broken row. [RECOMMEND] (R-L5)
- **Exception safety:** the renderer wraps resolution in a guard so a failure degrades to
  "render nothing" (native table intact), and logs via `error_log` when
  `WP_DEBUG`. [RECOMMEND]

### 7.5 Frontend JavaScript

- **None required** for the core render (the tables are server-rendered). [RECOMMEND]
- The only possible frontend JS is the (unresolved) "View Attribute" button interaction
  (D18) — kept isolated; see §8.5. No JS framework. [REQ]

### 7.6 Markup and styling contract

- Wrapper: `<div class="ani-gpa-wrap">`; one `<table class="ani-gpa-table">` per group;
  group name in a `<caption>`/header row; values in `<tbody>` rows. [RECOMMEND]
- **RTL-first** (Anishop is Persian RTL): logical properties / RTL-aware spacing;
  verified on real mobile (iPhone 12 Pro) in Phase 4. [REQ]
- **House style** (DESIGN.md): Vazirmatn; accent `#2872FA`; radii 2/4px; flat (borders +
  whitespace, no heavy shadows); colors only from the approved palette. [REQ]
- **Table style / colors** are driven by the Theme Mod settings (§9) via CSS custom
  properties on `.ani-gpa-wrap`. [RECOMMEND]
## 8. Blocksy integration contract

### 8.1 Custom layer (B1)

GPA renders via a **Blocksy custom layer** (the selected B1 direction). Precedent in the
child theme: `anishop-buy-box` (`anishop_buy_box_insert_layer` +
`anishop_buy_box_render_layer`) and `anishop-inline-variations` key-features.
[STATIC CODE EVIDENCE]

- **Layout insertion:** filter `blocksy:woocommerce:product-single:layout` to insert
  `array( 'id' => 'ani_gpa', 'enabled' => true )` at the configured position. [2B-VERIFY]
- **Render:** action `blocksy:woocommerce:product:custom:layer` fires with `$layer` for
  layers that have no built-in renderer; GPA renders when `$layer['id'] === 'ani_gpa'`.
  [2B-VERIFY]
- **Why B1:** independent markup, configurable placement, reduced coupling to the native
  WC attribute table, consistency with the existing child-theme architecture, no Blocksy
  Pro dependency. [DECISION D16]
- **NOT yet runtime-verified.** The exact hook signature, registration mechanism, and
  render position must be confirmed against the installed Blocksy 2.1.62 with a live
  render test in Phase 2B. [DECISION D16] [2B-VERIFY]

### 8.2 Native table location — CORRECTED finding (2026-11-25)

**The native `shop_attributes` table renders inside the "Additional information" tab, not
in the product summary.** This corrects the Phase 1 assumption that it sits in the
summary. [LIVE-VERIFIED 2026-11-25] [STATIC CODE EVIDENCE]

Render chain (Blocksy 2.1.62 + WC 11.3.1):

```
Blocksy single-product tabs (blocksy/inc/components/woocommerce/single/tabs.php)
  └─ "Additional information" tab
       └─ woocommerce_product_additional_information()   [WC template function]
            └─ wc_display_product_attributes()            [WC template function]
                 └─ locate_template( 'single-product/product-attributes.php' )
                      └─ <table class="shop_attributes">  (guarded by
                         if ( ! $product_attributes ) { return; })
```

**Implication for the integration contract:** GPA's grouped tables render in the
**summary** (custom layer, B1), while the native table lives in the **Additional
information tab**. They are in different regions of the page. The "hide native table"
setting (§7.3) therefore hides the table **in the tab**, and the D17 fallback (native
table must not disappear when GPA has no content) is naturally satisfied because GPA only
applies the hide filter when it has renderable content. [REQ]

### 8.3 Native table hide mechanism (non-destructive)

- **Filter:** `woocommerce_display_product_attributes` (applied inside
  `wc_display_product_attributes()`). Returning `array()` makes the template guard
  `if ( ! $product_attributes ) { return; }` suppress the table. [WC 11.3.1 SOURCE]
- **Non-destructive:** display-only. `_product_attributes`, visibility flags, and
  variation semantics are **never** modified. [D9] [D17]
- **Condition:** applied **only** when `hide_native_table` is on **and** GPA produced
  renderable content for the current product. [D17]
- **Compatibility note:** `persian-woocommerce` does **not** hook the attribute display
  path (static evidence), so this filter is not expected to conflict — but runtime
  compatibility must still be **tested** (D19). [STATIC CODE EVIDENCE] [2B-VERIFY]

### 8.4 Location setting

- The "location" setting (§9) maps to **where in the single-product layout** the `ani_gpa`
  custom layer is inserted. Candidate anchors (from Blocksy's default single-product
  layers): after `product_title`, after `product_price`, after `product_desc`, after
  `product_add_to_cart`, before/after `product_meta`. [RECOMMEND]
- The layout filter inserts `ani_gpa` relative to the chosen anchor. [2B-VERIFY — exact
  anchor IDs and insertion index against Blocksy 2.1.62]

### 8.5 "View Attribute" button (D18) — architecturally isolated

- The reference plugin documents an optional shop-page "View Attribute" button, but its
  exact behavior (modal / link / other) is **NOT** established by available
  documentation. **Do not invent its behavior.** [DECISION D18]
- **Architecture:** a single pluggable render function
  (`ani_gpa_render_view_attribute_button()`) + a Theme Mod toggle
  (`view_attribute_button`, default **off**). The function is a no-op stub until the
  behavior is finalized. [DECISION D18]
- **Isolation:** the button (and any future JS) is fully isolated from the core render
  pipeline, so finalizing it later is a small, contained change. [DECISION D18]
- **Phase 2B** must investigate the reference behavior sufficiently for an informed
  decision. **Do NOT** install the reference plugin on the production Anishop site; any
  sandbox installation requires separate explicit owner approval. [DECISION D18]
- **Status:** [UNRESOLVED]

### 8.6 Phase 2B verification items (Blocksy)

| # | Item | Why |
|---|------|-----|
| B1-1 | Exact `blocksy:woocommerce:product:custom:layer` signature + how a custom layer is registered/enabled in Blocksy 2.1.62 | B1 not yet runtime-verified (D16). |
| B1-2 | Exact `blocksy:woocommerce:product-single:layout` filter shape + valid anchor layer IDs + insertion index for the "location" setting | §8.4. |
| B1-3 | Live render test: a test product with assigned groups renders the grouped tables at the chosen position (CDP + real mobile) | Confirms B1 end-to-end. |
| B1-4 | Confirm the native table hide filter (`woocommerce_display_product_attributes`) suppresses the table in the Additional information tab without side effects | §8.3. |
## 9. Theme Mod settings

### 9.1 Theme Mod key

- **Key:** `anishop_gpa_settings` (a single Theme Mod holding the whole settings array).
  [RECOMMEND]
- **Collision check — LIVE-VERIFIED 2026-11-25:**
  - Existing `anishop_*` **Theme Mods**: `anishop_mobile_nav` · `anishop_inline_variations`
    · `anishop_key_features_settings` · `anishop_buy_box` · `anishop_search_box`.
  - `anishop_gpa_settings` is **NOT set** → **free to use, no collision**. [LIVE-VERIFIED
    2026-11-25]
  - `anishop_attribute_presentation` is an **option** (not a Theme Mod), currently set to
    `{"pa_color":"color"}` — an attribute→presentation-type map used by an existing
    concern. It is the **only** `anishop_*`/`%gpa%` option in the DB. GPA does **not**
    read or write it; the name similarity is coincidental. [LIVE-VERIFIED 2026-11-25]
    [RECOMMEND — keep distinct; do not reuse.]

### 9.2 Schema (versioned)

```php
array(
  'version'             => 1,            // int — schema version
  'enabled'             => false,        // bool — master switch (default OFF)
  'table_style'         => 'default',    // string — e.g. 'default' | 'minimal' | 'boxed'
  'location'            => 'after_add_to_cart', // string — custom-layer anchor (§8.4)
  'table_colors'        => array(        // object — all from the approved palette
    'header_bg'   => '#2872FA',
    'header_fg'   => '#FFFFFF',
    'row_bg'      => '#FFFFFF',
    'row_alt_bg'  => '#F2F5F7',
    'border'      => '#E1E8ED',
    'text'        => '#192A3D',
  ),
  'hide_native_table'   => false,        // bool — D17 (default: native table VISIBLE)
  'view_attribute_button' => false,      // bool — D18 (default OFF; behavior unresolved)
)
```

- **Defaults** are safe: feature **off**, native table **visible**, View Attribute
  **off**. A fresh install changes nothing on the frontend. [REQ]
- **`table_colors`** values are constrained to the approved house palette (DESIGN.md);
  the sanitizer rejects out-of-palette colors unless the owner approves an extension.
  [REQ]

### 9.3 Sanitizer

- **Single sanitizer** for the whole array (house pattern): one function validates and
  sanitizes every field, returns the cleaned array, and bumps/validates `version`.
  [REQ]
- **Booleans — strict parsing (corrected 2026-11-25).** Do **not** use a bare `(bool)` cast:
  `(bool) 'false' === true`, so a raw cast silently turns arbitrary non-empty strings into
  `true`. Use a strict parser that distinguishes the three input classes:
  - **Valid true:** `true`, `'1'`, `'true'`, `'yes'`, `'on'` (case-insensitive).
  - **Valid false:** `false`, `'0'`, `'false'`, `'no'`, `'off'` (case-insensitive).
  - **Invalid / ambiguous:** anything else (e.g. `'maybe'`, `''`, non-scalar) → reject the
    field and fall back to the schema default (do **not** coerce to `true`).
  Implementation: an explicit allowlist is the recommended mechanism. `rest_sanitize_boolean()`
  is acceptable **only** for inputs known to originate from a REST request, and even then it
  maps any other non-empty string to `true` (e.g. `rest_sanitize_boolean( 'yes' ) === true`),
  so it does **not** by itself satisfy the "no arbitrary non-empty string → true" rule.
  [REQ] [WP core API — `rest_sanitize_boolean()`, `wp-includes/rest-api.php`]
- Strings via `sanitize_key()`/`sanitize_text_field()`; colors via a palette whitelist;
  unknown keys dropped. [REQ]
- **Input paths:** the settings array is written from the Appearance → Anishop → GPA
  Settings tab (admin form POST). The sanitizer must not assume the input is a REST payload;
  the same strict boolean parsing applies to the admin form path. [REQ]

### 9.4 Save

- Saved from the Appearance → Anishop → GPA Settings tab (§6.4): nonce +
  `current_user_can( 'edit_theme_options' )` + `set_theme_mod( 'anishop_gpa_settings',
  $sanitized )`. [REQ]
## 10. Security, performance, compatibility, testing, module boundaries

### 10.1 Security

- **Nonces + capabilities on every write:**
  - Group edit: nonce + `current_user_can( 'edit_post', $post_id )`. [REQ]
  - Product edit: nonce + `current_user_can( 'edit_product', $post_id )` +
    autosave/revision guards. [REQ]
  - Settings: nonce + `current_user_can( 'edit_theme_options' )`. [REQ]
  - REST endpoints: nonce (via `X-WP-Nonce`) + capability check in `permission_callback`.
    [REQ]
- **Output escaping:** all frontend output via `esc_html()` / `esc_attr()` /
  `wp_kses_post()` as appropriate. [REQ]
- **Input sanitization:** `sanitize_text_field()`, `wp_unslash()`, palette whitelist for
  colors, integer casts for IDs; value references validated against the group's own list
  on save. [REQ]
- **CPT/taxonomy capabilities:** explicit `capability_type` + `map_meta_cap`; no elevated
  capabilities granted. [REQ]
- **No direct DB writes** outside the WP data APIs; any raw SQL (the custom-value
  aggregation, §5.2) is read-only and uses `$wpdb->prepare()` where parameters are
  involved. [REQ]
- **Error handling:** renderer degrades safely (§7.4); `error_log` under `WP_DEBUG`.
  [RECOMMEND]

### 10.2 Performance

- **No N+1 term queries:** batch term lookups per taxonomy in the renderer (§7.2). [REQ]
- **Admin list pagination + batched queries** (R-L10). [REQ]
- **Custom-value aggregation** (§5.2): a **single** raw-SQL query that **JOINs
  `wp_posts` to `wp_postmeta`** (post type / status live on `wp_posts`, not `wp_postmeta`),
  not a per-product object loop; parsed in PHP, **cached in a transient** and invalidated
  on the product events listed in §5.2. [RECOMMEND]
- **Assets:** enqueued **only** where needed — frontend CSS on single product pages (and
  only when the feature is enabled); admin JS/CSS only on the relevant admin screens.
  `filemtime`-based versioning (house convention). [REQ]
- **No frontend JS** for the core render (§7.5). [RECOMMEND]
- **No new plugins / frameworks / page builders.** [REQ]

### 10.3 Compatibility

- **Coexist with all 14 existing child-theme modules** (technical-conventions §2): no
  duplicate hooks, no parallel save handlers, no changes to those modules, MD5-locked
  files (`anishop-mobile-nav.js/.css`) untouched. [REQ]
- **`persian-woocommerce` (10.0.7):** static evidence shows it does **not** hook the
  attribute display path, so GPA's independent output + the native-table hide filter are
  not expected to conflict. **Runtime compatibility must still be TESTED** (D19) — do not
  claim compatibility from architectural assumption alone. [DECISION D19] [2B-VERIFY]
- **No Blocksy Pro runtime dependency.** [REQ]
- **No modification** of WordPress core, Blocksy parent, Blocksy Companion, or WC core.
  [REQ]
- **Persian RTL:** all frontend output RTL-first; verified on real mobile (Phase 4).
  [REQ]

### 10.4 Testing strategy

- **Fixtures (D23):** Simple **13143** (Philips OneBlade) + Variable **17028** (OnePlus 15,
  16 real variation children). Duplicates may be created. Test-only data uses the
  `GPA TEST — ` prefix. Registry: `docs/testing/test-fixtures.md`. [DECISION D23]
- **Isolation boundary (D23):** no unrelated products, orders, inventory, pricing, global
  settings, or production modules. Shared global attributes/terms are inspected before
  any create/rename/delete; prefer uniquely-prefixed test terms. [DECISION D23]
- **Coverage (Phase 4):** admin CRUD (groups, categories, settings); product metabox
  (load one / load category / select values / sort); frontend rendering (simple +
  variable, with/without variations, custom + taxonomy attributes); RTL; desktop +
  **real mobile** (iPhone 12 Pro via Live Link — CDP success is not sufficient);
  empty/error/stale states; native-table visible-by-default + optional hide + D17
  fallback; `persian-woocommerce` coexistence; module regression (all 14 modules).
  [REQ]
- **Provenance labels** on every test result (real-mobile vs CDP vs static). [REQ]

### 10.5 Module boundaries

- **All new code** in `inc/ani-group-product-attributes/` + `assets/` + `languages/`
  (§1.3). One new `require_once` in `functions.php`. [REQ]
- **No** modification of existing modules, Blocksy core, WC core, or the parent theme.
  [REQ]
- **Data ownership (A1):** GPA owns its CPT, taxonomy, product meta, and Theme Mod. It
  **reads** native WC attribute data but **never writes** it (D9). [DECISION D9] [DECISION
  D22]
- **Rollback:** deactivating the module = removing the `require_once` (and optionally
  deleting the CPT/taxonomy/meta). No destructive migration is required because GPA never
  modifies native data. [RECOMMEND]

---

## 11. Phase 2B verification checklist

Phase 2B (runtime verification) must confirm every **[2B-VERIFY]** item before Phase 3
implementation. Consolidated list:

| # | Item | Section |
|---|------|---------|
| B1-1 | Exact `blocksy:woocommerce:product:custom:layer` signature + custom-layer registration/enabled mechanism in Blocksy 2.1.62 | §8.1, §8.6 |
| B1-2 | Exact `blocksy:woocommerce:product-single:layout` filter shape + valid anchor layer IDs + insertion index for the "location" setting | §8.4, §8.6 |
| B1-3 | Live render test: a test product with assigned groups renders the grouped tables at the chosen position (CDP + real mobile) | §8.6 |
| B1-4 | Confirm the native-table hide filter (`woocommerce_display_product_attributes`) suppresses the table in the Additional information tab without side effects | §8.3, §8.6 |
| B1-5 | `persian-woocommerce` runtime compatibility: GPA output + native-table hide coexist correctly (D19) | §8.3, §8.6, §10.3 |
| B1-6 | Confirm the product-edit integration hooks (`woocommerce_product_options_attributes` render + `woocommerce_process_product_meta` save) fire as expected in the live admin | §6.3 |
| B1-7 | Confirm the custom-value aggregation query (§5.2) returns correct distinct values on the live catalog and the transient invalidation works | §5.2, §10.2 |
| B1-8 | Investigate the reference "View Attribute" button behavior sufficiently for an informed decision (D18) — **no** production install; sandbox install needs separate owner approval | §8.5 |
| B1-9 | Group post retrieval + post-type validation: confirm the `get_post( $id )` → exists → `post_type === 'ani_gpa_group'` → status sequence works as specified (no post-type argument passed to `get_post`) | §3.3, §7.2 |
| B1-10 | Allowed group-status behavior: confirm which statuses are permitted at save/assignment vs render (U11) and that the render gate enforces `publish` | §3.3, §7.2 |
| B1-11 | Custom-attribute aggregation query: confirm the `wp_posts` ⋈ `wp_postmeta` JOIN (post type / status on `wp_posts`) returns correct distinct values on the live catalog | §5.2, §10.2 |
| B1-12 | Product-status inclusion in the aggregation: confirm the chosen status set (U12) and that trashed products are excluded | §5.2 |
| B1-13 | Safe parsing of serialized `_product_attributes`: confirm guarded `unserialize()` + array check, and that malformed blobs are skipped without aborting the aggregation | §5.2 |
| B1-14 | Cache invalidation coverage: confirm the transient is invalidated on product create / update / delete / trash / restore, and identify import / bulk / programmatic write paths that bypass `save_post_product` | §5.2 |
| B1-15 | Global (taxonomy) attribute matching: confirm a group value matches a product only via term ID + taxonomy identity + actual product term membership (`wc_get_product_terms()` / `get_options()`) | §5.8 |
| B1-16 | Custom attribute matching: confirm a group value matches a product only via exact attribute-name identity + exact parsed-value identity (`wc_get_text_attributes()`) | §5.8 |
| B1-17 | Attribute-name and value-identity rules: confirm byte-for-byte name match, exact value match, and that no splitting / normalization on `|` / `،` / `,` occurs (D20) | §5.8 |
| B1-18 | Strict taxonomy-specific term validation: confirm `get_term( $id, $taxonomy )` rejects missing / wrong-taxonomy / slug-inconsistent terms, and that a term from another taxonomy is never accepted | §5.8 |
| B1-19 | Strict Boolean parsing: confirm the settings sanitizer rejects ambiguous input and does not coerce arbitrary non-empty strings to `true` (allowlist / `rest_sanitize_boolean()` behavior) | §9.3 |
| B1-20 | Save-time validation: confirm the product save handler validates group reference + tier A + tier B + term identity and persists only the accepted subset | §4.2, §4.4 |
| B1-21 | Render-time validation: confirm the renderer re-verifies group reference, term / value identity, and product membership, and excludes stale / invalid values | §4.4, §7.2 |
| B1-22 | Stale selected-value behavior: confirm a value no longer on the product is ignored at render, rejected at next save, retained in storage, with no destructive auto-cleanup (U13) | §4.4 |
| B1-23 | Native table visibility: confirm the native `shop_attributes` table stays visible when GPA content is absent **or** entirely stale / invalid, and is hidden only when valid renderable content exists and the setting permits | §7.3, §8.3 |

---

## 12. Unresolved items and owner decisions needed

| # | Item | Status | Needed from owner |
|---|------|--------|-------------------|
| U1 | "View Attribute" button behavior (modal / link / other) | [UNRESOLVED] (D18) | Final decision after Phase 2B investigation (B1-8). |
| U2 | CPT menu placement (own top-level menu vs under a parent) | [RECOMMEND] | Confirm the recommended own top-level menu. |
| U3 | Group edit: global order via `menu_order` (page-attributes) | [RECOMMEND] | Confirm. |
| U4 | Product-edit GPA section placement (Attributes tab) | [RECOMMEND] | Confirm. |
| U5 | "Create group for this product" toggle persistence (UI-only vs persisted flag) | [RECOMMEND] | Confirm UI-only interpretation. |
| U6 | Custom-value aggregation strategy (single raw-SQL + transient) | [RECOMMEND] | Confirm (performance-critical). |
| U7 | `table_style` options set (`default` / `minimal` / `boxed`) | [RECOMMEND] | Confirm the style variants. |
| U8 | `location` anchor options set | [RECOMMEND] | Confirm the allowed positions. |
| U9 | Theme Mod key `anishop_gpa_settings` (distinct from the `anishop_attribute_presentation` option) | [RECOMMEND] | Confirm. |
| U10 | Empty-group rendering (skip vs render empty header) | [RECOMMEND] | Confirm skip. |
| U11 | Group-status policy: which post statuses may be *assigned* to a product at save time (vs the render-time `publish` gate) | [UNRESOLVED] | Decide the allowed assignment-time status set (recommended: `publish` + optionally `draft`). |
| U12 | Custom-value aggregation: which product statuses contribute values to the admin picker (publish-only vs all non-trashed vs all) | [UNRESOLVED] | Choose the status set (recommended: publish-only or all non-trashed; never trash). |
| U13 | Stale selected-value lifecycle: ignored-at-render + retained-in-storage (no auto-cleanup) vs other | [RECOMMEND] | Confirm the non-destructive default (ignored at render, retained until explicit save). |

## 13. Evidence appendix

### 13.1 Live-verified (2026-11-25) — attribute name / option formats

Via `wp eval-file` on the live site (read-only), `WC_Product::get_attributes()`:

**Simple fixture 13143 (custom attributes only):**
```
id=0 | taxonomy=0 | name=[نوع اصلاح]        | options=[برش مستقیم / خشک وخیس]
id=0 | taxonomy=0 | name=[سری قابل شستشو]   | options=[دارد]
id=0 | taxonomy=0 | name=[طراحی]           | options=[ارگونومیک]
id=0 | taxonomy=0 | name=[مدت شارژ کامل]    | options=[8 ساعت]
id=0 | taxonomy=0 | name=[مدت زمان استفاده] | options=[45 دقیقه]
id=0 | taxonomy=0 | name=[سری جایگزین]      | options=[QP210,QP220, QP230]
id=0 | taxonomy=0 | name=[سایر امکانات]     | options=[سبک و کم حجم برای مسافرت]
id=0 | taxonomy=0 | name=[محتویات جعبه]     | options=[یک عدد تیغه اضافه, 3 عدد شانه برای صورت, ...]
```
→ Custom attribute `name` = **raw Persian** (not URL-encoded); `options` = **raw string**
(single value; commas **not** split — D20).

**Variable fixture 17028 (taxonomy attributes):**
```
TYPE: variable
id=2 | taxonomy=1 | name=[pa_color]           | options=[4300,4288,4287,4286]   (term IDs)
id=3 | taxonomy=1 | name=[pa_memory-capacity] | options=[4291,4292,4301,4293]   (term IDs)
--- variation attributes ---
varkey=[pa_color]           => [green,white,gold,black]        (term slugs)
varkey=[pa_memory-capacity] => [128gb,256gb,512-gb,64gb]       (term slugs)
```
→ Taxonomy attribute `name` = `pa_{slug}`; `get_attributes()` options = **term IDs**;
`get_variation_attributes()` options = **term slugs**. This dual format is why the value
reference stores both `value` (slug) and `value_id` (term ID). (§2.4)

### 13.2 Static code evidence (2026-11-25)

- **Native table render chain** (Blocksy 2.1.62 + WC 11.3.1):
  - `blocksy/inc/components/woocommerce/single/tabs.php` — "Additional information" tab.
  - `woocommerce/includes/wc-template-functions.php` — `woocommerce_product_additional_information()` → `wc_display_product_attributes()`.
  - `woocommerce/templates/single-product/product-attributes.php` — `<table class="shop_attributes">`, guarded by `if ( ! $product_attributes ) { return; }`.
  - `wc_display_product_attributes()` applies the `woocommerce_display_product_attributes` filter → returning `array()` suppresses the table. (§8.2, §8.3)
- **`read_attributes()`** (`woocommerce/includes/data-stores/class-wc-product-data-store-cpt.php:613`): taxonomy → `options = wc_get_object_terms( $product_id, $name, 'term_id' )`; custom → `options = wc_get_text_attributes( $meta_value['value'] )`; `set_name( $meta_value['name'] )`. (§5)
- **`persian-woocommerce`** source tree: no hooks on the attribute display path
  (`wc_display_product_attributes` / `woocommerce_product_attribute` / the
  `shop_attributes` template). (§8.3) [STATIC CODE EVIDENCE]
- **Child-theme precedents:** `anishop-buy-box` (custom layer insert + render),
  `anishop-inline-variations` (presentation-layer precedent), `anishop-hero` (CPT
  registration), `anishop-mobile-nav` (owns the Appearance → Anishop page +
  `anishop_admin_tabs` filter). (§1.3, §6.4, §8.1)

### 13.3 Carried-over (Phase 1 / 1.1)

- Live environment: WP 7.1.5 · PHP 8.3.36 · WooCommerce 11.3.1 · Blocksy 2.1.62 ·
  blocksy-companion 2.1.62 · persian-woocommerce 10.0.7 · blocksy-child 1.1.1.
  [LIVE-VERIFIED 2026-11-23]
- Attribute data landscape: 328 products with attributes; 13 taxonomy attributes; 399
  distinct custom names; 1521 custom entries; 294 multi-value (comma) custom attributes;
  **0** pipe-separated custom attributes. [Phase 1 research]
- Decisions D9, D14–D23 (see `.clinerules/decisions.md`).

### 13.4 API verifications for Phase 2A.1 corrections (2026-11-25)

Verified against the installed source (read-only):

- **`WC_Product_Attribute::get_options()`** (`woocommerce/includes/class-wc-product-attribute.php`):
  returns the raw `options` array — **term IDs** (ints) for taxonomy attributes, **parsed
  value strings** for custom attributes. `get_terms()` resolves taxonomy options to
  `WP_Term[]` (returns `null` if not a taxonomy or the taxonomy is unregistered);
  `get_slugs()` returns term slugs for taxonomy, options as-is for text. (§5.8)
  [WC 11.3.1 SOURCE]
- **`wc_get_product_terms( $product_id, $taxonomy, $args = array() )`**
  (`woocommerce/includes/wc-term-functions.php:226`): takes a product **ID** + taxonomy
  slug; returns `array()` if the taxonomy does not exist; backed by `wp_get_post_terms()`
  (cached). Used for product-membership checks. (§5.8) [WC 11.3.1 SOURCE]
- **`get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' )`**
  (`wp-includes/taxonomy.php:977`): when `$taxonomy` is supplied, the term is resolved via
  `WP_Term::get_instance( $term, $taxonomy )`, which returns the term **only if it belongs
  to that taxonomy** (otherwise `null`); an invalid taxonomy returns `WP_Error`. This is the
  basis for taxonomy-scoped term validation. (§3.3, §5.8) [WP core API]
- **`rest_sanitize_boolean( $value )`** (`wp-includes/rest-api.php:1539`): a string
  `'false'` or `'0'` (case-insensitive) → `false`; **everything else** → `(bool) $value`,
  so any other non-empty string (e.g. `'yes'`) → `true`. Confirms a bare `(bool)` cast and
  `rest_sanitize_boolean()` both coerce arbitrary non-empty strings to `true`; hence the
  strict allowlist recommendation. (§9.3) [WP core API]
- **`get_post( $post = null, $output = OBJECT, $filter = 'raw' )`**
  (`wp-includes/post.php:1138`): the second parameter is an **output format**, not a
  post-type constraint — confirming the corrected group-reference sequence. (§3.3)
  [WP core API]

---

*End of Phase 2A architecture specification. This document is the contract for Phase 2B
(runtime verification) and Phase 3 (implementation). It authorizes no implementation.*
