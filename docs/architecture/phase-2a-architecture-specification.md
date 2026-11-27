# Phase 2A — Architecture Specification: WooCommerce Product Group Attributes (GPA)

> **Implementation-ready architecture specification** for the GPA module as a Blocksy
> child-theme module for the Anishop store.
>
> **Phase:** 2A (architecture design — ROADMAP §3, Phase 2, part A).
> **Status:** documentation only. This document defines the contracts, data models, and
> integration points that Phase 3 (implementation) will build against. It implements
> **nothing** and does **not** authorize implementation.
>
> **Phase 2A.3 redesign (2026-11-25) — AUTHORITATIVE.** The product concept was clarified
> by the owner and is materially different from the Phase 2A / 2A.1 / 2A.2 architecture.
> GPA is a **reusable Global WooCommerce Attribute Template Manager**: a GPA group is a
> template of **Global Attribute definitions**; loading a group **adds native WooCommerce
> attribute rows** to the product; the product's **native WooCommerce attribute data is the
> single source of truth**; and **all** product attributes are rendered through the
> **standard WooCommerce/Blocksy Additional Information tab**. This redesign **supersedes**
> the previous value-selection / separate-renderer architecture. The previous contracts
> (three-tier A/B/C value model, `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values`,
> custom-attribute aggregation, GPA frontend renderer, native-table suppression, Blocksy
> custom layer) are **removed from the active architecture** and are recorded as
> superseded in `.clinerules/decisions.md` and in
> `reports/2026-11-25-phase-2a-3-native-attribute-template-redesign.md`. The historical
> Phase 2A / 2A.1 / 2A.2 reports remain accurate records of their time and are **not**
> rewritten.
>
> **Superseded correction history (preserved for provenance):**
> - **Phase 2A.1 correction (2026-11-25):** corrected the `get_post()` group-reference
>   sequence, the custom-attribute aggregation query, the group-value ↔ product-attribute
>   matching contract, taxonomy-scoped term validation, strict Boolean sanitization, and
>   save/render revalidation. See
>   `reports/2026-11-25-phase-2a-1-architecture-corrections.md`. (Most of these addressed
>   contracts that no longer exist; the generally-applicable security/integrity principles
>   are preserved where the new architecture still stores or processes structured data —
>   see the Phase 2A.3 report, "Phase 2A.2 Corrections — Applicability Assessment".)
> - **Phase 2A.2 correction (2026-11-25):** strengthened the safe-unserialization contract
>   for `_product_attributes`, reconciled the empty-group behavior, and recorded owner
>   decisions U11/U12/U13 (D24/D25/D26). See
>   `reports/2026-11-25-phase-2a-2-final-architecture-consistency.md`. (Reassessed under
>   the new architecture in the Phase 2A.3 report.)
>
> **Architecture direction:** **Native-template model** — GPA owns only the group
> template (CPT + ordered Global Attribute references + a single Theme Mod for
> presentation style). It **reads** the Global Attribute registry and **writes** product
> attributes **only** through the native WooCommerce attribute editor/save lifecycle. It
> performs **no** separate frontend rendering of attributes.

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
> `[WP CORE SOURCE]` · `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` · `[NOT VERIFIED]`.

---

## 0. Scope and status of this document

- **In scope (Phase 2A.3):** the full technical architecture of the **native-template
  model** — the GPA group/template concept, the revised data model, the group editor, the
  Product Data → Attributes integration, the Load operation, native attribute editing,
  ordering, Custom Attribute handling, frontend rendering (native), uniform table styles,
  WooCommerce/Blocksy integration, admin menu placement, security, performance,
  compatibility, and testing strategy — for owner approval.
- **Out of scope (Phase 2A.3):** any production PHP/JS/CSS, any database write, any
  catalog change, any delimiter normalization (D20), any catalog cleanup (D21), any
  implementation.
- **Intentionally deferred to Phase 2B:** runtime verification of the native
  `woocommerce_add_attribute` row-insertion flow, the "Save attributes" button
  enable/disable behavior, the admin menu position, the CSS-only table-style mechanism,
  and `persian-woocommerce` compatibility (D19).
- **Removed from the active architecture by this redesign:** the three-tier A/B/C value
  model, `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values`, `_ani_gpa_group_values`
  (value references), custom-attribute aggregation, the GPA frontend renderer, native-table
  suppression, the Blocksy custom layer, and the GPA frontend location setting. These are
  recorded as superseded (see `.clinerules/decisions.md` and the Phase 2A.3 report).

**New evidence gathered for this redesign (2026-11-25):** the Global Attribute registry
APIs and their rename/delete migration behavior, the native product attribute editor
template and row structure, the native save lifecycle (including the discard-empty-
attribute behavior), the native "Add global attribute" AJAX mechanism
(`woocommerce_add_attribute`), the "Save attributes" button enable/disable logic, the
frontend `shop_attributes` render chain, and the WordPress admin menu ordering mechanism.
See the Evidence Appendix (§19).

---

## 1. Product vision and system overview

### 1.1 What GPA is

GPA is a **reusable Global WooCommerce Attribute Template Manager**. A store admin defines
named **groups**, where each group is a **template** containing an **ordered list of
references to existing WooCommerce Global Attribute definitions** (e.g. a "Mobile" group
containing Processor, Display, Internal Storage, RAM, Battery Capacity, Operating System).

The group defines **which attributes should be added to a product**. It does **not** define
the product's actual attribute values. When an admin **loads** a group onto a product, GPA
adds the group's Global Attribute definitions to the product's **native WooCommerce
attribute list** — equivalent to adding those attributes through WooCommerce's normal
native attribute workflow. The admin then uses the **native** WooCommerce attribute
controls to select terms, add values, remove attributes, reorder, and edit.

GPA is **not** a separate product-attribute grouping, value-selection, or frontend
rendering system. The product's **native WooCommerce attribute data is the single source
of truth** for the product's attributes, and **all** product attributes (Global loaded via
GPA, Global added manually, Custom added manually) are displayed through the **standard
WooCommerce/Blocksy Additional Information tab**. [DECISION D28] [DECISION D29] [DECISION
D30] [DECISION D33]

### 1.2 Architecture direction (native-template model)

| Axis | Choice | Status |
|------|--------|--------|
| **Data ownership** | GPA owns only the group template: CPT `ani_gpa_group` + ordered Global Attribute references (post meta) + a single Theme Mod for presentation style. Product attributes are owned **exclusively** by WooCommerce. | [DECISION D28] [DECISION D30] |
| **Attribute insertion** | Loading a group inserts **native** WooCommerce attribute rows via the native `woocommerce_add_attribute` mechanism; the native save lifecycle persists them. No parallel product-attribute data structure. | [DECISION D29] |
| **Frontend rendering** | **Native only.** All attributes render through the standard WooCommerce/Blocksy Additional Information tab. No separate GPA renderer, tables, group headings, or Blocksy custom layer. | [DECISION D33] |
| **Presentation** | Three uniform table styles (`default` / `minimal` / `boxed`) applied to **all** product attributes via a lightweight CSS-only mechanism. | [DECISION D34] |

Axis **C** (delimiter normalization) remains **DEFERRED** (D20). The previous A1 + B1
direction (D22) is **superseded** by this native-template model.

### 1.3 Architecture diagram

```
GPA Group Templates (CPT ani_gpa_group)
        |
        | contain ordered references to
        v
Global WooCommerce Attribute Definitions (wc_get_attribute_taxonomies / pa_{slug})
        |
        | Load (native woocommerce_add_attribute row insertion)
        v
WooCommerce Native Product Attribute Collection (Product Data -> Attributes)
        |
        | Native editing and saving (WC_Meta_Box_Product_Data::save / prepare_attributes)
        v
WooCommerce Product Attribute Data (_product_attributes — single source of truth)
        |
        | Native Additional Information output (wc_display_product_attributes)
        v
WooCommerce / Blocksy Frontend Presentation (Additional Information tab)
        |
        +---- default   (native appearance)
        +---- minimal   (uniform CSS style)
        +---- boxed     (uniform CSS style)

Manually added Global Attributes and Custom Attributes enter the SAME native product
attribute collection (Product Data -> Attributes) and follow the SAME native save and
render path. There is NO separate frontend rendering branch for GPA-loaded attributes.
```

### 1.4 Module file structure

All new code is isolated in the child theme (project rules). Target layout:

```
wp-content/themes/blocksy-child/
  inc/ani-group-product-attributes/
    config.php      — Theme Mod schema + single sanitizer (anishop_gpa_settings);
                      constants (CPT, meta key, caps, style keys).
    bootstrap.php   — CPT ani_gpa_group registration; admin menu; asset registration;
                      hook wiring (the module entry point).
    admin.php       — group list/edit UI (Global Attribute selection + ordering);
                      group save handler.
    product.php     — Product Data -> Attributes GPA section (UI-only toggle, group
                      select + Load); admin JS wiring.
    styles.php      — uniform table-style CSS enqueue (default/minimal/boxed).
  assets/js/ani-group-product-attributes-admin.js  — group editor + product Load (vanilla IIFE).
  assets/css/ani-group-product-attributes-admin.css
  assets/css/ani-group-product-attributes-styles.css — uniform table styles (RTL-first).
  languages/        — .pot + fa_IR.po/.mo (text domain `anishop`).
```

- **Bootstrap entry:** one new `require_once` in `functions.php`
  (`inc/ani-group-product-attributes/bootstrap.php`), consistent with the 14 existing
  entries. [REQ — house pattern]
- **Prefixes** [REQ — project rules]: PHP `ani_gpa_` / `Ani_GPA_`; CSS `.ani-gpa-`;
  text domain `anishop`.
- **No** new plugins, **no** JS frameworks, **no** page builders, **no** Blocksy Pro
  runtime dependency. [REQ]
- **No frontend renderer file** — the previous `render.php` (grouped-table renderer) is
  **removed**. Frontend output is entirely native. [DECISION D33]

### 1.5 Data flow

**Admin (group editor):**

```
Group edit screen (CPT ani_gpa_group)
  └─ admin selects existing Global Attributes (from wc_get_attribute_taxonomies)
  └─ admin orders them (drag-and-drop)
  └─ save handler (nonce + edit_post cap) writes _ani_gpa_group_attributes
       (ordered array of pa_{slug} strings)
```

**Admin (product edit — Load):**

```
Product Data -> Attributes tab
  └─ GPA section (woocommerce_product_options_attributes hook):
       UI-only "Create group for this product" toggle
       group <select> + Load button
  └─ Load: GPA admin JS fetches the group's ordered pa_{slug} list (REST, nonce)
       and, for each attribute, calls the NATIVE woocommerce_add_attribute AJAX action
       (reusing WC's add-attribute nonce) to obtain the native row HTML, appends it to
       #product_attributes .product_attributes, re-indexes, and refreshes the save button.
  └─ admin selects terms / adds values using NATIVE controls
  └─ admin saves via NATIVE controls (Save attributes / product save)
       -> WC_Meta_Box_Product_Data::save -> prepare_attributes -> _product_attributes
```

**Frontend (single product page):**

```
Blocksy single-product tabs (woocommerce_product_tabs)
  └─ "Additional information" tab (native WooCommerce tab)
       └─ woocommerce_product_additional_information()
            └─ wc_display_product_attributes()
                 └─ <table class="woocommerce-product-attributes shop_attributes">
                      (all attributes: GPA-loaded, manual Global, Custom)
  └─ uniform table style (default/minimal/boxed) applied via CSS only
```

---

## 2. GPA group/template concept

### 2.1 A group is a template of Global Attribute definitions

A GPA group is a **reusable template** that contains an **ordered list of references to
existing WooCommerce Global Attribute definitions**. [DECISION D28]

- A group **defines which attributes should be added to a product** — it does **not**
  define the product's actual attribute values. [DECISION D28]
- A group **references attribute definitions, not terms.** It never stores, duplicates, or
  manages attribute terms or product-specific values. [DECISION D28]
- A group is **store-wide and reusable**: the same group can be loaded onto any number of
  products. [DECISION D28]
- **Example:** a group named "Mobile" contains the ordered references
  `pa_processor`, `pa_display`, `pa_storage`, `pa_ram`, `pa_battery`, `pa_os`. Loading it
  onto a product adds those six Global Attributes to the product's native attribute list,
  in that order. The admin then selects the actual terms/values per product.

### 2.2 Global Attribute-only membership

- GPA groups contain **Global Attribute definitions only** (taxonomy-based `pa_{slug}`
  attributes from the WooCommerce Global Attribute registry). [DECISION D28]
- **Custom product attributes are NOT group members.** They are not loaded through GPA
  groups and have no GPA management workflow. They remain fully supported as native
  WooCommerce product attributes and receive the same frontend presentation treatment as
  Global Attributes. [DECISION D32]
- **No custom-attribute aggregation, custom-attribute group membership, or custom-attribute
  vocabulary management subsystem** exists in this architecture. The previous
  custom-attribute aggregation (raw SQL, transient cache, product-status scope) is
  **superseded/obsolete** (D25) because groups no longer draw from a custom-attribute
  vocabulary. [DECISION D28]

### 2.3 Group CPT design

CPT `ani_gpa_group` (registered in `bootstrap.php` on `init`; precedent: `anishop-hero`
CPT registration). [REQ — decided data model]

| Argument | Value | Rationale |
|----------|-------|-----------|
| `label` / `labels` | "Attribute Groups" (i18n, text domain `anishop`) | Admin UI. |
| `public` | `false` | Groups are admin-only; never exposed in the frontend query loop or REST. [REQ] |
| `publicly_queryable` | `false` | No public permalinks. [REQ] |
| `show_ui` | `true` | Admin list/edit screens. [REQ] |
| `show_in_menu` | `false` | The GPA menu is added as a submenu under WooCommerce Products via `add_submenu_page` (§12). [DECISION D43] |
| `show_in_rest` | `false` | No Gutenberg/REST exposure of the CPT itself. [REQ] |
| `exclude_from_search` | `true` | Not searchable. [REQ] |
| `has_archive` | `false` | No archive. [REQ] |
| `supports` | `['title']` | `title` = group name. (No `page-attributes`/`menu_order` — global group ordering is **not** a required feature, §7.2.) [DECISION D31] |
| `capability_type` | `post` (default) with explicit `map_meta_cap` | Save handlers check `edit_post`/`edit_posts` (§13). [REQ] |
| `rewrite` | `false` | No rewrite rules (avoids flush-rewrites side effects). [RECOMMEND] |

- **Group name** = the CPT post title. [REQ]
- **Group description** = optional; stored in the post content (or a dedicated meta if a
  richer editor is needed). Not required by the product vision. [RECOMMEND]
- **Group status:** standard WordPress `publish` / `draft`. Only `publish` groups are
  listed in the product-edit group picker and may be loaded (§4.4, §13). Draft groups
  remain manageable in the GPA admin but are not loadable. [DECISION D41]
- **Global group ordering is NOT a required feature.** The group list uses the default
  (title / date) ordering. [DECISION D31]

### 2.4 Group categories (resolved — not in scope)

The original roadmap (ROADMAP §1.2) included "Group attribute categories" and a
"Load Group Categories" action. The owner has confirmed (Q1, 2026-11-27) that group
categories are **not in scope** for the initial GPA version. [DECISION D40]

- **Status:** [DECISION D40] — No `ani_gpa_group_cat` taxonomy. No category management,
  category assignment, or category filtering. GPA groups remain a simple, flat collection.
- The existing group list and its basic information are sufficient.
- This decision does **not** prevent adding categorization in a future, separately
  approved feature. The core data model, Load operation, and frontend are independent of
  categories, so adding them later would be a contained, orthogonal extension.
- **No** category taxonomy is registered in the core architecture. [DECISION D40]

---

## 3. Data model

### 3.1 GPA group (CPT `ani_gpa_group`)

A reusable group/template entity containing:

| Field | Storage | Notes |
|-------|---------|-------|
| Group identity | CPT post ID | Standard WordPress post. |
| Group title/name | Post title | The group's display name. [REQ] |
| Group description (optional) | Post content (or meta) | Not required by the product vision. [RECOMMEND] |
| Ordered Global Attribute references | Post meta `_ani_gpa_group_attributes` | Serialized array of `pa_{slug}` strings, in order (§3.2). [REQ] |
| Publication status | Standard `post_status` | `publish` / `draft`; only `publish` is loadable (§2.3). [RECOMMEND] |

### 3.2 Global Attribute reference (storage model)

A group stores its **ordered list of Global Attribute references** as post meta
`_ani_gpa_group_attributes` — a **serialized array of `pa_{slug}` strings**, in the
admin's chosen order. [REQ]

```php
// _ani_gpa_group_attributes for a "Mobile" group:
array( 'pa_processor', 'pa_display', 'pa_storage', 'pa_ram', 'pa_battery', 'pa_os' )
```

**Why the taxonomy name (`pa_{slug}`) is the reference key:**

- It is the **exact value** the native `woocommerce_add_attribute` AJAX action expects in
  its `taxonomy` parameter (`WC_AJAX::add_attribute()` calls
  `wc_attribute_taxonomy_id_by_name( $_POST['taxonomy'] )` and
  `set_name( $_POST['taxonomy'] )`). [WC 11.3.1 SOURCE — class-wc-ajax.php:779]
- It is **directly validatable**: `taxonomy_exists( 'pa_{slug}' )` confirms the taxonomy is
  registered, and `wc_attribute_taxonomy_id_by_name( 'pa_{slug}' )` returns the attribute
  ID (`0` if the attribute no longer exists). [WC 11.3.1 SOURCE — wc-attribute-functions.php:199]
- The **label** and **ID** are derived at display time from the registry
  (`wc_get_attribute_taxonomy_labels()`, `wc_get_attribute( $id )`), so they are **not**
  stored (avoiding stale duplicated data). [WC 11.3.1 SOURCE]
- A Global Attribute **definition** is distinct from its **taxonomy terms**. The reference
  points to the definition (the `pa_{slug}` taxonomy), **never** to individual terms.
  [REQ]

**Invariants:**

- Each entry is a non-empty string matching `pa_{slug}` (slug = `wc_sanitize_taxonomy_name`
  output). [REQ]
- **No duplicates** within a group. [REQ]
- The array is always an array (never a scalar); empty array = a group with no attributes
  (a valid administrative state — see §4.4). [REQ]
- **Order in the array = the initial insertion sequence** when the group is loaded (§7.1).
  [DECISION D31]

### 3.3 Reference integrity and validation

A Global Attribute reference can become **stale** if the attribute is **renamed** or
**deleted** in WooCommerce. The verified WC 11.3.1 behavior:

- **Rename** (`wc_update_attribute` / `wc_create_attribute` with a changed slug): WC
  **migrates** the taxonomy — it updates `wp_term_taxonomy.taxonomy` from `pa_{old}` to
  `pa_{new}`, updates the `order_pa_{old}` termmeta, rewrites the `_product_attributes`
  meta of **all** products using that attribute (renaming the key and `name`), and updates
  variation meta keys. After a rename, `pa_{old}` **no longer exists** as a taxonomy.
  [WC 11.3.1 SOURCE — wc-attribute-functions.php:656-714]
- **Delete** (`wc_delete_attribute`): WC deletes the `woocommerce_attribute_taxonomies`
  row, deletes **all terms** of `pa_{slug}`, and **unregisters** the taxonomy. After
  deletion, `pa_{slug}` **no longer exists**. [WC 11.3.1 SOURCE — wc-attribute-functions.php:777]

**Consequence:** a group reference stored as `pa_{old}` becomes **stale** (the taxonomy no
longer exists) after a rename or delete. WC provides **no** "renamed-from" mapping, so a
stale reference **cannot** be auto-resolved; the admin must re-select the attribute.

**Validation contract (two boundaries):**

1. **Group save time** (`save_post_ani_gpa_group` handler): for each submitted reference,
   verify it is a valid `pa_{slug}` string **and** the attribute currently exists
   (`taxonomy_exists( $ref )` **and** `wc_attribute_taxonomy_id_by_name( $ref ) > 0`).
   Reject/flag references that fail. Persist only the accepted, de-duplicated, ordered
   list. [REQ]
2. **Load time** (product-edit Load operation): for each reference in the selected group,
   re-verify the attribute still exists. **Skip** stale references (do not insert a row
   for a non-existent attribute) and surface a warning to the admin listing the skipped
   attributes. [REQ]

- **Stale references are not auto-removed** from the group on load (load is read-only with
  respect to the group). The group editor **flags** stale references so the admin can
  remove or replace them on the next group save. [RECOMMEND]
- This is consistent with D21 (no catalog cleanup) — GPA never mutates the Global
  Attribute registry or product data as a side effect of validation. [DECISION D21]

### 3.4 Product data (native WooCommerce is the single source of truth)

- The product's attributes are stored **exclusively** in WooCommerce's native
  `_product_attributes` post meta (a serialized array of `WC_Product_Attribute` entries),
  managed by the native product data store (`WC_Product_Data_Store_CPT::read_attributes()`
  / `write_attributes()`). [WC 11.3.1 SOURCE]
- **GPA does not create a second source of truth.** After a group is loaded and the admin
  saves, the product's attributes are ordinary native WooCommerce attributes. There is
  **no** behavioral distinction between an attribute loaded through GPA, a Global
  Attribute added manually, and a Custom Attribute added manually. [DECISION D29] [DECISION
  D30]
- **GPA writes product attributes only through the native save lifecycle** (the admin
  saves via the native "Save attributes" button or the product save, which runs
  `WC_Meta_Box_Product_Data::save()` → `prepare_attributes()` → `set_attributes()` →
  `save()`). GPA never calls `set_attributes()`/`save()` directly and never writes
  `_product_attributes` itself. [DECISION D29] [REQ]
- **The parent product's native attribute data remains authoritative** for product
  attribute presentation. `variable` type does not prove variations exist; GPA does not
  infer product type from variation existence. [DECISION D15 — preserved]

### 3.5 Product metadata (none)

- **No GPA-specific product metadata is introduced.** The previous contracts
  `_ani_gpa_assigned_groups` and `_ani_gpa_selected_values` are **removed** from the
  active architecture. [DECISION D30]
- **Rationale:** the new architecture has no persistent group assignment (a loaded group's
  attributes become ordinary native attributes) and no per-product value selection (values
  are managed natively). Storing which group "originally supplied" an attribute
  (provenance) has **no** functional requirement in the new model, so it is not stored.
  [DECISION D30]
- **The "Create group for this product" control is UI-only.** It does **not** introduce a
  persisted Boolean product flag. Its state is transient (admin UI session) and has no
  effect on stored data. [DECISION D37]
- If a future, concrete requirement demonstrates a need for a specific piece of product
  metadata, it must be documented and approved before introduction. Until then, the
  architecture avoids GPA-specific product metadata. [REQ]

---

## 4. Group editor workflow

### 4.1 Group list / edit

- **List table:** standard `WP_Post_Type` list (title, attribute count, status, date).
  Pagination + batched queries (R-L10). [REQ]
- **Edit screen:**
  - **Name** = post title. [REQ]
  - **Global Attributes** = an ordered list of the group's Global Attribute references
    (§3.2), with **add** (select from existing Global Attributes), **remove**, and
    **drag-and-drop ordering**. [REQ]
  - The attribute picker lists **existing Global Attribute definitions only** (from
    `wc_get_attribute_taxonomies()`), clearly labeled with the attribute label. It does
    **not** list terms or custom attributes. [DECISION D28] [DECISION D32]
- **Save handler:** `save_post_ani_gpa_group` — nonce + `current_user_can( 'edit_post',
  $post_id )` + single-authority sanitization/validation of `_ani_gpa_group_attributes`
  (§3.3). [REQ]

### 4.2 Global Attribute selection

- The picker source is `wc_get_attribute_taxonomies()` (the Global Attribute registry),
  which returns objects with `attribute_id`, `attribute_name` (slug), `attribute_label`,
  `attribute_type`, `attribute_orderby`, `attribute_public`. [WC 11.3.1 SOURCE —
  wc-attribute-functions.php:55]
- Each selectable entry is rendered as the attribute **label** (human-readable) and stores
  the **taxonomy name** `pa_{slug}` (via `wc_attribute_taxonomy_name( $attribute_name )`)
  as the reference. [WC 11.3.1 SOURCE — wc-attribute-functions.php:148]
- Attributes already in the group are marked as selected; the admin can remove them.
  [REQ]
- **No term selection, no value entry, no custom-attribute entry** in the group editor.
  The group editor manages **attribute definitions only**. [DECISION D28]

### 4.3 In-group attribute ordering

- The admin orders the group's attributes by drag-and-drop. [DECISION D31]
- The order is persisted in `_ani_gpa_group_attributes` (array order). [REQ]
- This order determines the **initial insertion sequence** when the group is loaded onto a
  product (§7.1). [DECISION D31]
- **Global ordering of groups** (ordering the groups themselves relative to each other) is
  **not** a required feature. [DECISION D31]

### 4.4 Group save and empty-group state

- A group may be saved with **no** attributes — a valid administrative state (an empty
  template). The save is **not** rejected solely because the group has no attributes.
  [RECOMMEND]
- A group with no attributes, when loaded, inserts **no** rows (nothing to add). This is
  the new-architecture analogue of the previous "empty group" rule, reinterpreted: a group
  is a template, not a frontend table, so "empty" simply means "adds nothing." [DECISION
  D33] (supersedes the D27 empty-group frontend model)
- On save, references are validated per §3.3 (existence check); invalid references are
  rejected/flagged. [REQ]

---

## 5. Product editor integration

The GPA interface belongs **inside** WooCommerce **Product Data → Attributes**. It
**coexists** with the native interface rather than replacing it. [DECISION D38]

### 5.1 Placement

- **Hook:** `woocommerce_product_options_attributes` — this action fires at the **end** of
  the `#product_attributes` panel (after the native attribute rows and the "Save
  attributes" button), inside the Attributes tab. [WC 11.3.1 SOURCE —
  html-product-data-attributes.php:69]
- GPA renders its section (toggle + group select + Load) via this hook. The section is
  visually distinct (`.ani-gpa-` prefixed markup) but lives within the native Attributes
  tab. [REQ]
- **Save hook:** GPA does **not** add a product-meta save handler (there is no GPA product
  meta to save, §3.5). The native `woocommerce_process_product_meta` save lifecycle is
  unchanged and remains the sole writer of `_product_attributes`. [DECISION D29] [REQ]

### 5.2 UI-only "Create group for this product" toggle

- A checkbox/toggle that reveals the GPA group-select + Load controls for this product.
  [DECISION D37]
- **UI-only:** the toggle is **not** a persisted flag. It has no effect on stored data and
  is not read by any save handler. Its state is transient (admin UI). [DECISION D37]
- When off, the GPA controls are hidden; the native attribute editor is unaffected. [REQ]

### 5.3 Group selection and Load

- **Group select:** a `<select>` listing **`publish`** groups (title = group name). Only
  published groups are offered (§2.3). Draft groups are not listed. [DECISION D41]
- **Load button:** on click, GPA performs the Load operation (§5.4).
- **Data source:** a nonce-protected REST endpoint (`anishop/v1/gpa/groups/{id}/attributes`)
  returns the selected group's **ordered** `pa_{slug}` list (validated per §3.3). The
  endpoint is capability-checked (`edit_product` for the current product). [REQ]
- **Multiple groups:** the admin can load one group, then load another; each Load appends
  that group's attributes (subject to duplicate handling, §5.5). [DECISION D29]

### 5.4 Load operation and native attribute insertion

The Load operation inserts the group's Global Attribute definitions into the product's
**native** attribute collection, using WooCommerce's **own** row-insertion mechanism so the
result is structurally identical to natively-added attributes. [DECISION D29]

**Mechanism (reuses the native `woocommerce_add_attribute` AJAX action):**

The native "Add global attribute" flow (`add_attribute_to_list( globalAttributeId )` in
`meta-boxes-product.js:692`) works as follows:
1. Count existing rows: `numberOfAttributesInList = $('.product_attributes .woocommerce_attribute').length`.
2. POST `action: woocommerce_add_attribute` with `taxonomy` (the `pa_{slug}` name), `i`
   (the row index), `product_type`, and `security` (WC's `add-attribute` nonce).
3. The server handler `WC_AJAX::add_attribute()` (`class-wc-ajax.php:779`) requires
   `current_user_can( 'edit_products' )` + the `add-attribute` nonce, builds a
   `WC_Product_Attribute` (id via `wc_attribute_taxonomy_id_by_name`, name = the taxonomy,
   `visible` default `1`, `variation` default by product type), and renders the native row
   HTML via `html-product-attribute.php`.
4. The JS appends the returned row to `#product_attributes .product_attributes`, runs
   `show_and_hide_controls()`, `init_select_controls()` (select2 init),
   `update_attribute_row_indexes()` (re-indexes `attribute_position`), expands the row,
   triggers `woocommerce_added_attribute`, and calls `jQuery.maybe_disable_save_button()`.
[WC 11.3.1 SOURCE — meta-boxes-product.js:692, class-wc-ajax.php:779]

**GPA Load reuses this exact mechanism:**

1. GPA admin JS fetches the group's ordered `pa_{slug}` list (REST, §5.3).
2. For **each** attribute (in the group's order), GPA JS calls the **native**
   `woocommerce_add_attribute` AJAX action (reusing WC's `add-attribute` nonce, which is
   already localized on the product edit screen) with the correct `taxonomy` and an
   incrementing `i` (continuing from the current row count).
3. GPA JS appends each returned row, re-indexes, initializes select controls, and refreshes
   the save button state — the same post-processing the native flow performs.
4. The inserted rows are **structurally identical** to natively-added Global Attribute
   rows (same markup, same hidden `attribute_names[i]` / `attribute_position[i]`, same
   `attribute_values[i][]` term multiselect). [DECISION D29]

**Consequences:**

- The loaded attributes appear in the native attribute list and are edited/saved with the
  **native** controls. [DECISION D29]
- **No parallel data structure** is created; the rows live in the native
  `#product_attributes .product_attributes` container. [DECISION D29]
- **The admin must select values and save** for the attributes to persist (see §6.2 — the
  native save discards attribute rows with no values). [REQ]
- **Capability:** the native `woocommerce_add_attribute` handler requires `edit_products`;
  the Load operation is therefore only available to users with that capability (consistent
  with the native "Add global" control). [REQ]
- **Exact row insertion and index handling** (including how `i` is computed when multiple
  rows are added in sequence, and select2 re-initialization) must be confirmed against the
  live WC 11.3.1 admin in Phase 2B. [2B-VERIFY]

### 5.5 Duplicate handling

- **A Global Attribute already present on the product is not added again.** Before
  inserting a row for a group attribute, GPA JS checks whether a row for that `pa_{slug}`
  already exists in `.product_attributes` (by the row's `data-taxonomy` attribute). If it
  exists, the attribute is **skipped** (not duplicated). [REQ]
- This mirrors the native behavior: the native "Add global" select2 **disables**
  already-selected Global Attributes (`selectedAttributes` tracking,
  `meta-boxes-product.js:583-597`), so the same Global Attribute cannot be added twice via
  the native UI either. [WC 11.3.1 SOURCE]
- **Skipped (duplicate) attributes are reported** to the admin (e.g. a notice listing which
  group attributes were already present and therefore not re-added). [RECOMMEND]
- **Stale references** (attribute no longer exists, §3.3) are also skipped and reported.
  [REQ]
- Duplicates are determined by **attribute identity** (the `pa_{slug}` taxonomy), not by
  value. Two rows for the same Global Attribute are never created. [REQ]

### 5.6 Multiple-group loading

- The admin can load **multiple** groups onto the same product, one Load at a time.
  [DECISION D29]
- Each Load appends that group's attributes (in the group's order), subject to duplicate
  handling (§5.5): attributes already present (from a previous group load or added
  manually) are skipped. [REQ]
- The **combined** set of attributes from all loaded groups, plus any manually-added
  attributes, forms the product's native attribute collection. [DECISION D29]
- There is **no** record of which group supplied which attribute (no provenance, §3.5).
  After loading, the attributes are indistinguishable from manually-added ones. [DECISION
  D30]

### 5.7 Coexistence with native editing

- **Loaded attributes are edited with native controls only.** The admin selects existing
  terms, creates new values (where WooCommerce supports it), removes attributes, adds
  attributes manually (Global or Custom), reorders attributes, and edits existing
  attributes — all through the native WooCommerce attribute UI. [DECISION D29]
- **GPA does not intercept or modify** the native attribute editing or saving. The native
  `WC_Meta_Box_Product_Data::save()` / `prepare_attributes()` lifecycle is unchanged.
  [DECISION D29] [REQ]
- **Manually-added Global and Custom attributes coexist** with loaded attributes in the same
  native collection and follow the same save/render path. [DECISION D29] [DECISION D32]
- **Removing a loaded attribute** uses the native "Remove" control on the row; GPA has no
  separate removal mechanism. [REQ]

### 5.8 Ordering preservation

- **Initial insertion order:** when a group is loaded, its attributes are inserted in the
  group's configured order (§3.2, §4.3), appended after the product's existing attribute
  rows. [DECISION D31]
- **Final order is controlled exclusively by the native WooCommerce interface.** The admin
  reorders attribute rows by drag-and-drop in the native editor (the `rel`/position
  attribute), and the native save persists the order. [DECISION D31]
- **GPA does not impose group ordering on the frontend** and does **not** override the
  product's native attribute order. The displayed order is the product's native attribute
  order. [DECISION D31] [DECISION D33]

---

## 6. Native WooCommerce attribute editing

This section documents the **verified** native save lifecycle that GPA relies on. GPA does
not modify it; it depends on its behavior.

### 6.1 Native save lifecycle

- **Main product save:** `woocommerce_process_product_meta` → `WC_Meta_Box_Product_Data::save( $post_id, $post )` (`class-wc-meta-box-product-data.php:333`) → `self::prepare_attributes()` (line 339) → `$product->set_attributes( $attributes )` → `$product->save()`. [WC 11.3.1 SOURCE]
- **AJAX "Save attributes" button:** `WC_AJAX::save_attributes()` (`class-wc-ajax.php:922`) → `create_product_with_attributes( $data )` → `WC_Meta_Box_Product_Data::prepare_attributes( $data )` → `set_attributes()` → `save()`. It is guarded by `check_ajax_referer( 'save-attributes', 'security' )` + `current_user_can( 'edit_product', $post_id )`. [WC 11.3.1 SOURCE]
- **`prepare_attributes( $data )`** (`class-wc-meta-box-product-data.php:234`) reads `attribute_names[i]`, `attribute_values[i]`, `attribute_visibility[i]`, `attribute_variation[i]`, `attribute_position[i]`; for each row it resolves the attribute ID (via `wc_attribute_taxonomy_id_by_name` for `pa_` names), parses options (term ID list for taxonomy, `wc_get_text_attributes` for text), and builds a `WC_Product_Attribute`. [WC 11.3.1 SOURCE]

### 6.2 Empty attribute rows are discarded on save (verified)

- **`prepare_attributes()` skips rows with no values:** `if ( empty( $options ) ) { continue; }` (`class-wc-meta-box-product-data.php`). An attribute row whose `attribute_values[i]` is empty (no terms selected for a Global Attribute, or an empty textarea for a Custom Attribute) is **not** included in the saved attribute set. [WC 11.3.1 SOURCE]
- **Consequence for GPA Load:** when a group is loaded, the inserted rows have **no values** selected. If the admin saves the product **without** selecting values, those rows are **discarded** by the native save (they are not persisted to `_product_attributes`). The attributes persist **only** once the admin selects at least one value (term) for each and saves. [REQ]
- This is **equivalent to the native workflow**: a Global Attribute added manually via "Add global" with no values selected is likewise discarded on save. GPA's loaded rows behave exactly like native rows. [DECISION D29]
- **The admin must be informed** (UI hint) that loaded attributes require values to be selected before saving in order to persist. [RECOMMEND]

### 6.3 "Save attributes" button enable/disable behavior (unverified — Phase 2B)

- **Static source evidence (WC 11.3.1):** `jQuery.maybe_disable_save_button()`
  (`assets/js/admin/meta-boxes.js:33`) disables `button.save_attributes` (adds `disabled`
  class + `aria-disabled`) when **any** non-optional field within `.product_attributes` is
  empty. `jQuery.is_attribute_or_variation_empty()` (`meta-boxes.js:5`) skips fields with
  the `optional_attribute_or_variation_data` class, the `checkbox` class, or matching
  `[class*=search__field]`; for a `select` it is empty when no option is `:selected`; for
  other inputs it is empty when `!val()`. It is bound to `change` on
  `select.attribute_values`, `keyup` on `input, textarea` within `#product_attributes`,
  and runs once on load. [WC 11.3.1 SOURCE — STATIC CODE EVIDENCE]
- **Implication (static):** a freshly-loaded Global Attribute row (no terms selected) has
  an empty `select.attribute_values`, so the "Save attributes" button is expected to be
  **disabled** until the admin selects at least one value. [STATIC CODE EVIDENCE]
- **This behavior is treated as UNVERIFIED for architectural purposes.** Per the task, the
  runtime behavior of the Save Attributes button when an attribute row has no value is an
  **unverified technical question**. The architecture **does not** depend on it. It must be
  confirmed against the live WC 11.3.1 admin in Phase 2B (including whether GPA's
  programmatically-inserted rows trigger the same enable/disable logic, and whether the
  main product save is affected). [2B-VERIFY]

---

## 7. Attribute ordering

### 7.1 In-group order (initial insertion)

- The order of attributes **within a group** is configurable (drag-and-drop in the group
  editor) and persisted in `_ani_gpa_group_attributes` (array order). [DECISION D31]
- This order determines the **initial insertion sequence** when the group is loaded: the
  group's attributes are appended to the product's attribute list in that order. [DECISION
  D31]

### 7.2 Final order (native control)

- The **final** attribute order on an individual product is controlled **exclusively** by
  the native WooCommerce Product Data → Attributes interface (drag-and-drop reordering of
  rows; persisted by the native save via `attribute_position`). [DECISION D31]
- **Global ordering of GPA groups** (ordering the groups relative to each other) is **not**
  a required feature. [DECISION D31]
- GPA **does not** impose group ordering on the frontend and **does not** override the
  product's native attribute order. [DECISION D31] [DECISION D33]

---

## 8. Custom Attribute handling

- **Custom Attributes remain fully supported** as native WooCommerce product attributes.
  [DECISION D32]
- They are **not** GPA group members, are **not** loaded through GPA groups, and have **no**
  separate GPA management workflow. [DECISION D32]
- They are added and managed **only** through WooCommerce's native product editor (the
  "Add new" custom-attribute control). [DECISION D32]
- They **must receive the same frontend presentation treatment** as Global Attributes: they
  render in the same native Additional Information tab, in the same `shop_attributes`
  table, and are styled by the same uniform table style (§10). [DECISION D32] [DECISION D34]
- **No custom-attribute aggregation, vocabulary discovery, or group membership** for Custom
  Attributes exists in this architecture (the previous subsystem is superseded/obsolete,
  D25). [DECISION D28]
- Custom Attribute values continue to be parsed by WooCommerce's native
  `wc_get_text_attributes()` (splits on `|` only); GPA does **not** split or normalize on
  commas (D20 preserved). [DECISION D20]

---

## 9. Frontend rendering

### 9.1 Native Additional Information tab (single output mechanism)

- **All** product attributes are displayed through the **standard WooCommerce/Blocksy
  product attribute presentation** in the existing **Additional Information** tab. This
  includes Global Attributes loaded through GPA, Global Attributes added manually, and
  Custom Attributes added manually. [DECISION D33]
- **The customer must not see or need to know how an attribute was added.** There is no
  visual or structural distinction between GPA-loaded and manually-added attributes.
  [DECISION D33]
- **The final displayed attribute order follows the product's native WooCommerce attribute
  order.** [DECISION D31] [DECISION D33]

### 9.2 What does NOT exist (superseded)

The following are **removed** from the active architecture (superseded by D33):

- **No** separate GPA attribute tables.
- **No** GPA group headings.
- **No** GPA group-category headings.
- **No** grouping of frontend attributes by their source group.
- **No** separate GPA frontend renderer (the previous `render.php` is removed).
- **No** custom Blocksy product layer for GPA attribute output (the previous B1 custom
  layer is removed; D16 superseded).
- **No** configurable GPA frontend location (the previous `location` setting is removed;
  D17's hide-native-table mechanism is removed).
- **No** native WooCommerce table suppression. The native `shop_attributes` table is the
  **only** attribute table and is always the output mechanism. [DECISION D33]

### 9.3 Render chain (verified)

The native render chain (Blocksy 2.1.62 + WC 11.3.1), previously live-verified
(2026-11-25) and re-confirmed against source for this redesign:

```
Blocksy single-product tabs (blocksy/inc/components/woocommerce/single/tabs.php)
  └─ "Additional information" tab (native WooCommerce tab via woocommerce_product_tabs)
       └─ woocommerce_product_additional_information()   [WC template function]
            └─ wc_display_product_attributes( $product )  [wc-template-functions.php:4261]
                 ├─ builds $product_attributes (weight/dimensions + visible attributes)
                 ├─ array_filter( get_attributes(), 'wc_attributes_array_filter_visible' )
                 ├─ apply_filters( 'woocommerce_display_product_attributes', … )
                 └─ wc_get_template( 'single-product/product-attributes.php' )
                      └─ <table class="woocommerce-product-attributes shop_attributes">
                           (guarded by if ( ! $product_attributes ) { return; })
```

- **Template markup (WC 11.3.1, template version 9.3.0):**
  `<table class="woocommerce-product-attributes shop_attributes" aria-label="Product
  Details">` with rows `<tr class="woocommerce-product-attributes-item
  woocommerce-product-attributes-item--{key}">` containing
  `<th class="woocommerce-product-attributes-item__label">` +
  `<td class="woocommerce-product-attributes-item__value">`. [WC 11.3.1 SOURCE —
  templates/single-product/product-attributes.php]
- **Visibility:** only attributes with `visible = true` are shown
  (`wc_attributes_array_filter_visible`). A loaded Global Attribute is visible by default
  (the native `woocommerce_add_attribute` handler sets `visible = 1`). [WC 11.3.1 SOURCE]
- **GPA performs no frontend rendering.** It only enqueues the uniform table-style CSS
  (§10). [DECISION D33]

---

## 10. Uniform table presentation

### 10.1 Three presentation styles

GPA provides three presentation styles for the product attribute table: [DECISION D34]

| Style | Behavior |
|-------|----------|
| `default` | Preserves the **standard WooCommerce/Blocksy appearance** (no GPA CSS applied). |
| `minimal` | A lighter uniform style applied to the attribute table. |
| `boxed` | A boxed/bordered uniform style applied to the attribute table. |

### 10.2 Uniform application (provenance-independent)

- The selected style applies **uniformly to ALL product attributes**, regardless of:
  whether the attribute is Global or Custom, and whether it was loaded through GPA or
  added manually. [DECISION D34]
- The style **must not** depend on group membership or attribute provenance. Because there
  is no provenance (§3.5) and no separate GPA output (§9.2), uniformity is inherent: there
  is a single native table, and the style is applied to that table as a whole. [DECISION
  D34]
- The presentation must remain **lightweight** and must **not** introduce duplicate
  attribute tables or unnecessary frontend JavaScript. [DECISION D34]

### 10.3 Implementation mechanism (CSS-only — confirmed)

The owner has confirmed (Q3, 2026-11-27) that the presentation mechanism is **CSS-only**:
a stylesheet that styles the existing native `shop_attributes` table, with **no** PHP
rendering changes, **no** template overrides, **no** filters on the attribute data, and
**no** frontend JavaScript. [DECISION D42]

- **Mechanism:**
  1. A single stylesheet `ani-group-product-attributes-styles.css` contains the rules for
     `minimal` and `boxed` (the `default` style enqueues **nothing**).
  2. The active style is scoped by a **body class** (e.g. `ani-gpa-table-style-minimal` /
     `ani-gpa-table-style-boxed`) added via the `body_class` filter **only** on single
     product pages **and** only when the selected style is not `default`.
  3. The CSS rules target the native table and its rows
     (`.shop_attributes`, `.woocommerce-product-attributes-item`,
     `.woocommerce-product-attributes-item__label`,
     `.woocommerce-product-attributes-item__value`) **within** the body-class scope, so
     they apply uniformly to every attribute row.
- **Why CSS-only is safest:**
  - It does **not** touch the native render pipeline, the `woocommerce_display_product_attributes`
    filter, or the template — so it cannot suppress or duplicate the table, and it cannot
    break `persian-woocommerce` or other plugins that hook the attribute display path.
  - It is trivially reversible (remove the body class / dequeue the CSS).
  - It has **zero** frontend JavaScript and **zero** PHP render logic.
  - It applies uniformly to all rows by construction (single table, scoped CSS).
- **Constraints:**
  - Colors are constrained to the approved house palette (DESIGN.md); the sanitizer rejects
    out-of-palette values. [REQ]
  - **RTL-first** (Anishop is Persian RTL): logical properties / RTL-aware spacing. [REQ]
  - The exact selectors and the body-class scoping must be confirmed against the live
    Blocksy 2.1.62 + WC 11.3.1 markup in Phase 2B (the table is inside the Blocksy
    Additional Information tab wrapper). [2B-VERIFY]
- **The CSS-only mechanism is confirmed** by the owner (D42). The exact selectors and
  body-class scoping must be confirmed against the live Blocksy 2.1.62 + WC 11.3.1 markup
  in Phase 2B. The visual specifications for `minimal` and `boxed` must be defined and
  documented before final CSS implementation. [DECISION D42]

### 10.4 Settings

- The style is stored in the Theme Mod `anishop_gpa_settings` as `table_style`
  (`default` | `minimal` | `boxed`, default `default`). [DECISION D34] [DECISION D39]
- **There is no `location` setting** (the frontend location is fixed to the native
  Additional Information tab) and **no `hide_native_table` setting** (the native table is
  never hidden). [DECISION D33]

---

## 11. WooCommerce/Blocksy integration

### 11.1 Admin integration (verified hooks)

| Concern | Hook / API | Evidence |
|---------|-----------|----------|
| GPA section in Product Data → Attributes | `woocommerce_product_options_attributes` (render) | Fires at the end of the `#product_attributes` panel, inside the Attributes tab. [WC 11.3.1 SOURCE — html-product-data-attributes.php:69] |
| Product save (unchanged, native) | `woocommerce_process_product_meta` → `WC_Meta_Box_Product_Data::save()` | GPA adds **no** product save handler. [WC 11.3.1 SOURCE] |
| Row insertion (Load) | Native `woocommerce_add_attribute` AJAX action (`WC_AJAX::add_attribute`) | Reused by GPA Load; requires `edit_products` + `add-attribute` nonce. [WC 11.3.1 SOURCE — class-wc-ajax.php:779] |
| Global Attribute registry | `wc_get_attribute_taxonomies()`, `wc_attribute_taxonomy_name()`, `wc_attribute_taxonomy_id_by_name()`, `wc_get_attribute()` | Group editor picker + reference validation. [WC 11.3.1 SOURCE — wc-attribute-functions.php] |
| Group CPT admin | `save_post_ani_gpa_group` | Group save handler. [WP core API] |
| Admin menu | `add_submenu_page` on `admin_menu` | Submenu under WooCommerce Products, after Attributes (§12). [WP core API] [DECISION D43] |

- **Preserved technical corrections from earlier phases (still applicable):**
  - `woocommerce_product_options_attributes` is associated with the **Attributes** tab
    (confirmed at the end of the `#product_attributes` panel). [WC 11.3.1 SOURCE]
  - `woocommerce_product_options_general_product_data` is associated with the **General**
    tab (GPA does **not** use it). [WC 11.3.1 SOURCE]
  - Product type must be determined using the product object
    (`$product->get_type()` / `is_type()`), **not** inferred from variation existence.
    [DECISION D15 — preserved]
  - A `product_type` taxonomy query using a slug must explicitly specify
    `field => 'slug'` when appropriate. [Phase 1.1.1 — preserved]
  - Do **not** infer product type from variation existence. [DECISION D15 — preserved]

### 11.2 Frontend integration (native only)

- GPA has **no** frontend render hook. The native render chain (§9.3) is unchanged.
  [DECISION D33]
- GPA's only frontend footprint is the uniform table-style CSS (§10.3): a `body_class`
  filter (single product pages, non-`default` style only) + a conditional stylesheet
  enqueue. [RECOMMEND]
- **No** Blocksy custom layer, **no** `blocksy:woocommerce:product:custom:layer`, **no**
  `blocksy:woocommerce:product-single:layout` filter. (The previous B1 integration is
  superseded; D16.) [DECISION D33]

### 11.3 `persian-woocommerce` compatibility

- `persian-woocommerce` (10.0.7) must be considered in the integration design. [DECISION
  D19]
- The CSS-only table-style mechanism (§10.3) **reduces** (but does not eliminate) the
  compatibility surface: it does not hook the attribute display path, so it is not expected
  to conflict with `persian-woocommerce`'s attribute handling. [STATIC CODE EVIDENCE —
  previous finding that persian-woocommerce does not hook the attribute display path]
- **Runtime compatibility must still be TESTED** in Phase 2B/Phase 4 — do not claim
  compatibility based solely on architectural assumption. [DECISION D19] [2B-VERIFY]

---

## 12. Admin menu placement

GPA is registered as a **submenu** of the WooCommerce **Products** menu, placed
**immediately after** the existing WooCommerce **Attributes** submenu. [DECISION D43]

The exact Persian menu label is: **گروه ویژگی‌های آنی**

### 12.1 Desired menu hierarchy

```
Products (WooCommerce)
  ├── All Products
  ├── Add Product
  ├── Categories
  ├── Tags
  ├── Attributes
  └── Anishop Attribute Groups (گروه ویژگی‌های آنی)  ← GPA submenu
```

- **No separate top-level WordPress admin menu** is created for GPA. [DECISION D43]
- The GPA CPT is registered with `show_in_menu => false` (no auto CPT menu). [DECISION D43]
- The GPA submenu is added via `add_submenu_page()` under the WooCommerce Products menu.
  [DECISION D43]

### 12.2 WooCommerce Products menu structure (verified)

- The WooCommerce **Products** menu is a top-level menu registered by WooCommerce at
  position `58` (the `edit.php?post_type=product` entry). [WC 11.3.1 SOURCE —
  `class-wc-post-types.php`]
- WooCommerce registers the following submenus under Products:
  - `edit.php?post_type=product` (All Products)
  - `post-new.php?post_type=product` (Add Product)
  - `edit-tags.php?taxonomy=product_cat&post_type=product` (Categories)
  - `edit-tags.php?taxonomy=product_tag&post_type=product` (Tags)
  - `edit.php?post_type=product&page=product_attributes` (Attributes)
  [WC 11.3.1 SOURCE]
- The **Attributes** submenu slug is `edit.php?post_type=product&page=product_attributes`.
  [WC 11.3.1 SOURCE]

### 12.3 Suitable approach (recommended)

The robust way to place GPA "immediately after Attributes" is to **use the WooCommerce
Products menu slug as the parent** and register the GPA submenu with `add_submenu_page()`:

1. Register the GPA CPT with `show_in_menu => false` (no auto CPT menu). [DECISION D43]
2. On `admin_menu` (or a later hook that fires after WooCommerce has registered its
   submenus), call:
   ```php
   add_submenu_page(
       'edit.php?post_type=product',  // WooCommerce Products parent slug
       __( 'Anishop Attribute Groups', 'anishop' ),  // page title
       __( 'گروه ویژگی‌های آنی', 'anishop' ),  // menu title (Persian)
       'edit_posts',  // capability
       'ani_gpa_group',  // menu slug
       'ani_gpa_render_group_list'  // callback
   );
   ```
3. **Placement after Attributes:** WordPress submenus are rendered in registration order
   (not by position). To ensure GPA appears **immediately after** the Attributes submenu,
   the GPA submenu must be registered **after** WooCommerce's Attributes submenu. This is
   achieved by hooking into `admin_menu` with a priority **greater than** WooCommerce's
   submenu registration priority (WooCommerce uses priority `10` for its admin menu
   registration). Using priority `20` (or later) ensures GPA is registered after all
   WooCommerce submenus. [WP CORE SOURCE]
4. **Alternative (if ordering is not guaranteed):** if the registration-order approach does
   not reliably place GPA after Attributes (e.g. due to plugin interference), a
   `admin_menu` filter can reorder the `$submenu['edit.php?post_type=product']` array to
   move the GPA entry to the position immediately after the Attributes entry. This is a
   fallback and should only be used if the primary approach fails. [RECOMMEND]

- **Why `add_submenu_page` is preferred over `add_menu_page`:** the owner explicitly
  prefers a submenu under Products over an independent top-level menu (D43 supersedes D35).
  Submenu registration is simpler, more robust, and does not require computing a float
  position relative to an auto-assigned CPT menu position. [DECISION D43]
- **Collision consideration:** another plugin could theoretically register a submenu with
  the same slug `ani_gpa_group`. The slug is prefixed and specific, making collision
  unlikely. The browser-visible ordering must be verified in Phase 2B/Phase 4. [2B-VERIFY]
- **WooCommerce menu slug verification:** the exact parent slug
  (`edit.php?post_type=product`) and the Attributes submenu slug must be confirmed against
  the live WC 11.3.1 installation in Phase 2B. [2B-VERIFY]

### 12.4 Verification of the intended placement

- **Phase 2B / Phase 4 verification:** after implementation, confirm the live admin
  sidebar order — GPA must appear as a submenu under Products, immediately after
  Attributes. This can be verified by:
  - Inspecting the rendered admin menu (visual / CDP), and/or
  - Reading the `$submenu['edit.php?post_type=product']` array to confirm GPA's position
    relative to the Attributes entry.
  [2B-VERIFY]
- **Do not change the live WordPress admin menu** as part of this documentation task. [REQ]

---

## 13. Validation and security

### 13.1 Nonces and capabilities

- **Group edit save** (`save_post_ani_gpa_group`): nonce + `current_user_can( 'edit_post',
  $post_id )`. [REQ]
- **Group list / admin menu**: standard CPT capabilities (`edit_posts` / `edit_post` via
  `capability_type = post` + `map_meta_cap`). No elevated capabilities granted. [REQ]
- **Product-edit Load (REST endpoint)**: nonce (via `X-WP-Nonce`) + `permission_callback`
  checking `current_user_can( 'edit_product', $product_id )`. [REQ]
- **Load row insertion**: reuses the native `woocommerce_add_attribute` action, which
  enforces `current_user_can( 'edit_products' )` + the `add-attribute` nonce. GPA does not
  bypass these. [REQ] [WC 11.3.1 SOURCE]
- **Settings save** (Theme Mod): nonce + `current_user_can( 'edit_theme_options' )`. [REQ]

### 13.2 Input sanitization and output escaping

- **Group references** (`_ani_gpa_group_attributes`): each entry sanitized to a `pa_{slug}`
  string (`sanitize_text_field` + a `pa_` prefix + slug shape check) and validated against
  the registry (§3.3). Unknown/invalid entries rejected. [REQ]
- **Settings**: single sanitizer for the whole `anishop_gpa_settings` array; `table_style`
  validated against the allowlist `default` / `minimal` / `boxed`; unknown keys dropped.
  [REQ]
- **Strict Boolean parsing** (preserved from Phase 2A.1): if any Boolean setting is added,
  use a strict allowlist parser — do **not** use a bare `(bool)` cast or
  `rest_sanitize_boolean()` alone (both coerce arbitrary non-empty strings to `true`).
  [REQ — preserved correction]
- **Output escaping**: all admin UI output via `esc_html()` / `esc_attr()` /
  `wp_kses_post()` as appropriate. [REQ]
- **No direct DB writes** outside the WP data APIs. The Global Attribute registry is read
  via the WC functions (which use their own caching); GPA performs no raw SQL. [REQ]

### 13.3 Safe handling of serialized/structured data (preserved principle)

- The group meta `_ani_gpa_group_attributes` is a **serialized array** written by GPA and
  read by GPA. When reading it, GPA must treat it as **untrusted structured data**:
  - Use `unserialize( $raw, array( 'allowed_classes' => false ) )` (or the metadata API)
    and **never** rely on `maybe_unserialize()` for deserialization safety (it does not
    restrict object instantiation — verified in WP 7.1.5, `wp-includes/functions.php:653`).
  - **Type validation:** the result must be an `array`; anything else is rejected.
  - **Structure validation:** each entry must be a non-empty `pa_{slug}` string; non-matching
    entries (including `__PHP_Incomplete_Class`) are rejected.
  - **Graceful handling:** malformed/corrupted meta is skipped (logged under `WP_DEBUG`),
    never causing warnings/fatals.
- **This principle is preserved from the Phase 2A.2 safe-unserialization correction**,
  re-scoped to the data the new architecture actually stores (the group reference array).
  The previous contract's target (`_product_attributes` raw-SQL aggregation) no longer
  exists, but the underlying security principle (no object instantiation from untrusted
  serialized data + type/structure validation) remains applicable wherever GPA reads
  serialized meta. [REQ — preserved correction; see the Phase 2A.3 report]

### 13.4 Error handling

- The Load operation degrades safely: a failed REST fetch or a failed
  `woocommerce_add_attribute` call surfaces an admin notice and leaves the native attribute
  list unchanged (no partial/corrupt rows). [RECOMMEND]
- `error_log` under `WP_DEBUG` for unexpected failures. [RECOMMEND]

---

## 14. Performance and caching

### 14.1 Performance

- **No N+1 queries:** the Global Attribute registry is read via `wc_get_attribute_taxonomies()`
  (which is transient + object-cache backed by WooCommerce). The group editor and the Load
  REST endpoint each read the registry **once** per request. [WC 11.3.1 SOURCE]
- **Admin list pagination + batched queries** for the group list (R-L10). [REQ]
- **Assets:** enqueued **only** where needed —
  - Admin JS/CSS only on the group edit screen and the product edit screen (Attributes tab).
  - Frontend style CSS only on single product pages **and** only when `table_style` is not
    `default`.
  - `filemtime`-based versioning (house convention). [REQ]
- **No frontend JavaScript** for the core presentation (the table is native; the style is
  CSS-only). [DECISION D34]
- **No new plugins / frameworks / page builders.** [REQ]

### 14.2 Caching (minimal — no premature caching)

- **The previous cache contracts are removed** (superseded): the custom-attribute
  aggregation transient, its invalidation hooks, and any group-specific frontend rendering
  cache no longer exist, because the features they served (custom-attribute aggregation,
  GPA frontend table construction) are removed. [DECISION D28] [DECISION D33]
- **No new GPA-specific cache is introduced** in the core architecture. The only caching in
  play is WooCommerce's own Global Attribute registry cache (transient
  `wc_attribute_taxonomies` + object cache), which WC invalidates on attribute
  create/update/delete (`wc_create_attribute` / `wc_delete_attribute` call
  `delete_transient( 'wc_attribute_taxonomies' )` +
  `WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' )`). GPA relies on that
  and adds no parallel cache. [WC 11.3.1 SOURCE]
- **Rationale:** the new architecture has no expensive derived data to cache. The group
  reference list is small (a handful of `pa_{slug}` strings) and read directly from post
  meta. Introducing a cache would be premature. [REQ]
- If a future, demonstrated performance need arises (e.g. a very large number of groups), a
  targeted cache may be proposed and approved then. Until then, **no** GPA cache. [REQ]

---

## 15. Compatibility requirements

- **Coexist with all 14 existing child-theme modules** (technical-conventions §2): no
  duplicate hooks, no parallel save handlers, no changes to those modules, MD5-locked files
  (`anishop-mobile-nav.js/.css`) untouched. [REQ]
- **`persian-woocommerce` (10.0.7):** runtime compatibility must be **tested** (D19). The
  CSS-only style mechanism reduces the conflict surface but does not remove the testing
  requirement. [DECISION D19] [2B-VERIFY]
- **No Blocksy Pro runtime dependency.** [REQ]
- **No modification** of WordPress core, Blocksy parent, Blocksy Companion, or WC core.
  [REQ]
- **WooCommerce 11.3.1:** the architecture depends on verified WC 11.3.1 behavior (the
  `woocommerce_add_attribute` action, `prepare_attributes` discard-empty behavior, the
  `shop_attributes` template). These must be re-confirmed in Phase 2B against the installed
  version. [2B-VERIFY]
- **Blocksy Free:** the frontend relies on Blocksy's native rendering of the Additional
  Information tab (verified render chain, §9.3). No Blocksy Pro feature is used. [REQ]
- **Persian RTL:** all admin UI and the table style are RTL-first; verified on real mobile
  (Phase 4). [REQ]
- **Existing catalog anomalies are tolerated** (D21): stale `product_type` term counts,
  inconsistent `is_variation` flags, and attribute-data irregularities must not crash GPA.
  GPA reads the registry and native attribute data defensively. [DECISION D21]

---

## 16. Testing strategy

- **Fixtures (D23):** Simple **13143** (Philips OneBlade) + Variable **17028** (OnePlus 15,
  16 real variation children). Duplicates may be created. Test-only data uses the
  `GPA TEST — ` prefix. Registry: `docs/testing/test-fixtures.md`. [DECISION D23]
- **Isolation boundary (D23):** no unrelated products, orders, inventory, pricing, global
  settings, or production modules. Shared global attributes/terms are inspected before any
  create/rename/delete; prefer uniquely-prefixed test attributes/terms. [DECISION D23]
- **Coverage (Phase 4):**
  - **Group editor:** create/edit/delete groups; add/remove Global Attributes; drag-and-drop
    ordering; save validation (invalid/stale references rejected); empty group (no
    attributes) is a valid state.
  - **Product Load:** load one group; load multiple groups; duplicate handling (attribute
    already present is skipped); stale reference skipped + reported; loaded rows are
    structurally identical to native rows; admin selects values + saves → attributes
    persist in `_product_attributes`; admin saves without values → rows discarded (native
    behavior).
  - **Native coexistence:** manually-added Global and Custom attributes coexist with loaded
    attributes; native reordering works; native remove works; no GPA-specific behavior
    distinguishes loaded from manual attributes.
  - **Frontend:** all attributes (GPA-loaded, manual Global, Custom) render in the native
    Additional Information tab in native order; `default` style = native appearance;
    `minimal` / `boxed` styles apply uniformly to all rows; no duplicate tables; no group
    headings.
  - **Admin menu:** GPA submenu appears under Products, immediately after Attributes.
  - **RTL + Persian** (all UI surfaces); desktop + **real mobile** (iPhone 12 Pro via Live
    Link — CDP success is not sufficient).
  - **`persian-woocommerce`** coexistence (D19).
  - **Module regression** (all 14 existing child-theme modules).
  - **Security:** nonce/capability enforcement on group save, Load REST endpoint, and
    settings save; output escaping; safe unserialization of group meta.
  [REQ]
- **Provenance labels** on every test result (real-mobile vs CDP vs static). [REQ]

---

## 17. Phase 2B implementation checklist

Phase 2B (runtime verification) must confirm every **[2B-VERIFY]** item before Phase 3
implementation. The checklist is organized by category. **All items are pending** — none
has been executed or verified as of this document (Phase 2A.3, 2026-11-25).

**Legend:**
- **[CONFIRMED]** — a confirmed architectural requirement (must be implemented as specified).
- **[VERIFIED]** — API behavior verified against installed source (re-confirm at runtime).
- **[2B-VERIFY]** — requires Phase 2B source/runtime verification before implementation.
- **[OPEN]** — an implementation decision that remains open.

### 17.1 GPA group CPT and admin menu

| # | Item | Type |
|---|------|------|
| B2-1 | Register CPT `ani_gpa_group` per §2.3 (`show_in_menu => false`, `supports => ['title']`, `capability_type => post` + `map_meta_cap`, `rewrite => false`). | [CONFIRMED] |
| B2-2 | Add the GPA submenu under WooCommerce Products via `add_submenu_page` (§12.3); confirm the live sidebar order (GPA appears after Attributes). | [2B-VERIFY] |
| B2-3 | Confirm the WooCommerce Products parent slug and Attributes submenu slug at runtime; verify GPA submenu placement is immediately after Attributes with no collision. | [2B-VERIFY] |

### 17.2 Group editor

| # | Item | Type |
|---|------|------|
| B2-4 | Group list table (title, attribute count, status, date) with pagination. | [CONFIRMED] |
| B2-5 | Group edit: Global Attribute picker sourced from `wc_get_attribute_taxonomies()` (labels + `pa_{slug}` references); add/remove; drag-and-drop ordering. | [CONFIRMED] |
| B2-6 | Group save handler (`save_post_ani_gpa_group`): nonce + `edit_post` cap; sanitize + validate references (§3.3); persist ordered `_ani_gpa_group_attributes`. | [CONFIRMED] |
| B2-7 | Reference validation: `taxonomy_exists( $ref )` + `wc_attribute_taxonomy_id_by_name( $ref ) > 0`; reject/flag invalid; de-duplicate. | [VERIFIED] (APIs) / [2B-VERIFY] (runtime) |
| B2-8 | Stale-reference flagging in the group editor (attribute renamed/deleted since last save). | [CONFIRMED] |
| B2-9 | Empty group (no attributes) is a valid save state. | [CONFIRMED] |

### 17.3 Product Data → Attributes integration

| # | Item | Type |
|---|------|------|
| B2-10 | Render the GPA section via `woocommerce_product_options_attributes` (inside the Attributes tab, after the native rows + Save button). | [VERIFIED] (hook location) |
| B2-11 | UI-only "Create group for this product" toggle (no persisted flag). | [CONFIRMED] |
| B2-12 | Group `<select>` listing `publish` groups only. | [CONFIRMED] |
| B2-13 | Load REST endpoint (`anishop/v1/gpa/groups/{id}/attributes`): nonce + `edit_product` cap; returns the ordered `pa_{slug}` list (validated). | [CONFIRMED] |
| B2-14 | Load row insertion reuses the native `woocommerce_add_attribute` AJAX action (reusing WC's `add-attribute` nonce); confirm the exact `i` index computation for sequential multi-row insertion and select2 re-initialization against live WC 11.3.1. | [2B-VERIFY] |
| B2-15 | Duplicate handling: skip a group attribute whose `pa_{slug}` row already exists (by `data-taxonomy`); report skipped attributes. | [CONFIRMED] |
| B2-16 | Stale-reference handling on Load: skip + report. | [CONFIRMED] |
| B2-17 | Multiple-group loading: each Load appends its attributes (subject to duplicate handling). | [CONFIRMED] |
| B2-18 | Confirm loaded rows are structurally identical to native rows (markup, hidden `attribute_names[i]` / `attribute_position[i]`, `attribute_values[i][]`). | [2B-VERIFY] |

### 17.4 Native editing and save behavior

| # | Item | Type |
|---|------|------|
| B2-19 | Confirm the native save discards attribute rows with no values (`prepare_attributes` `empty( $options )` skip) and that loaded rows persist only after values are selected. | [VERIFIED] (source) / [2B-VERIFY] (runtime) |
| B2-20 | Confirm the "Save attributes" button enable/disable behavior when a loaded row has no value (whether GPA-inserted rows trigger `maybe_disable_save_button`); the architecture does **not** depend on this. | [2B-VERIFY] |
| B2-21 | Confirm native reordering, remove, and manual add (Global + Custom) coexist with loaded attributes without GPA interference. | [2B-VERIFY] |
| B2-22 | Confirm the final displayed attribute order follows the product's native attribute order. | [CONFIRMED] |

### 17.5 Frontend and uniform table styles

| # | Item | Type |
|---|------|------|
| B2-23 | Confirm all attributes (GPA-loaded, manual Global, Custom) render in the native Additional Information tab via the verified render chain (§9.3). | [VERIFIED] (chain) / [2B-VERIFY] (runtime) |
| B2-24 | Confirm the CSS-only table-style mechanism: body-class scoping + conditional enqueue; `default` enqueues nothing; `minimal` / `boxed` apply uniformly to all rows. | [2B-VERIFY] |
| B2-25 | Confirm no duplicate tables, no group headings, no GPA-specific frontend output. | [CONFIRMED] |
| B2-26 | Confirm the exact CSS selectors against live Blocksy 2.1.62 + WC 11.3.1 markup (table inside the Blocksy Additional Information tab wrapper). | [2B-VERIFY] |

### 17.6 Compatibility and regression

| # | Item | Type |
|---|------|------|
| B2-27 | `persian-woocommerce` runtime compatibility (D19): the CSS-only style + native render coexist correctly. | [2B-VERIFY] |
| B2-28 | WooCommerce 11.3.1 re-confirmation of the depended-on behaviors (`woocommerce_add_attribute`, `prepare_attributes`, `shop_attributes` template). | [2B-VERIFY] |
| B2-29 | Blocksy Free compatibility (native Additional Information tab rendering). | [2B-VERIFY] |
| B2-30 | Regression: all 14 existing child-theme modules remain functional; no duplicate hooks / parallel save handlers. | [CONFIRMED] |
| B2-31 | Security: nonce/capability enforcement (group save, Load REST, settings save); output escaping; safe unserialization of `_ani_gpa_group_attributes`. | [CONFIRMED] |
| B2-32 | RTL + Persian presentation (admin + frontend); real-mobile verification (iPhone 12 Pro). | [CONFIRMED] |

### 17.7 Resolved implementation decisions (2026-11-27)

| # | Item | Type |
|---|------|------|
| B2-33 | Group categories: **RESOLVED** by D40 — not in scope for the initial version. No taxonomy. | [RESOLVED] |
| B2-34 | Group status policy: **RESOLVED** by D41 — only `publish` groups are loadable. | [RESOLVED] |
| B2-35 | Table-style visual design: **RESOLVED** by D42 — CSS-only confirmed; visual specs for `minimal` / `boxed` to be defined before final CSS. | [RESOLVED] |
| B2-36 | Admin menu placement: **RESOLVED** by D43 — submenu under Products, after Attributes. Label: **گروه ویژگی‌های آنی**. | [RESOLVED] |

---

## 18. Open technical questions and verification requirements

### 18.1 Open questions (require owner input)

| # | Question | Status |
|---|----------|--------|
| Q1 | **Group categories** — are they in scope? | [RESOLVED] (D40, 2026-11-27) — Not in scope. No taxonomy. |
| Q2 | **Group status policy** — confirm "only `publish` groups are loadable". | [RESOLVED] (D41, 2026-11-27) — Confirmed. |
| Q3 | **Table-style visual design** — finalize the `minimal` / `boxed` CSS within the house palette. | [RESOLVED] (D42, 2026-11-27) — CSS-only confirmed; visual specs to be defined. |
| Q4 | **Admin menu placement** — confirm the menu structure and label. | [RESOLVED] (D43, 2026-11-27) — Submenu under Products, after Attributes. Label: **گروه ویژگی‌های آنی**. |

### 18.2 Phase 2B verification requirements (consolidated)

The following must be verified at runtime in Phase 2B before Phase 3 implementation (all
currently **pending**):

1. **Load row insertion** — the exact `i` index computation for sequential multi-row
   insertion via the native `woocommerce_add_attribute` action, and select2
   re-initialization of the inserted rows (B2-14, B2-18).
2. **Save Attributes button** — the enable/disable behavior when a loaded row has no value,
   and whether GPA-inserted rows trigger `maybe_disable_save_button` (B2-20). The
   architecture does **not** depend on this.
3. **Native save discard** — confirm loaded rows persist only after values are selected
   (B2-19).
4. **Admin menu placement** — the WooCommerce Products parent slug, the Attributes submenu
   slug, and that the GPA submenu appears immediately after Attributes with no collision
   (B2-2, B2-3).
5. **CSS-only table style** — the exact selectors and body-class scoping against live
   Blocksy 2.1.62 + WC 11.3.1 markup (B2-24, B2-26).
6. **`persian-woocommerce` compatibility** — runtime coexistence of the CSS-only style +
   native render (B2-27, D19).
7. **WooCommerce 11.3.1 re-confirmation** — the depended-on behaviors
   (`woocommerce_add_attribute`, `prepare_attributes`, `shop_attributes` template) (B2-28).
8. **Blocksy Free compatibility** — native Additional Information tab rendering (B2-29).
9. **Native coexistence** — reordering, remove, and manual add (Global + Custom) coexist
   with loaded attributes (B2-21).

### 18.3 Explicitly NOT open (resolved by the redesign)

- The "View Attribute" shop-page button (U1/D18) is **deferred / out of scope** — the new
  vision has no separate GPA frontend output, so there is no clear requirement for it. See
  the Phase 2A.3 report. [DECISION D36]
- The three-tier A/B/C value model, `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values`,
  custom-attribute aggregation, the GPA frontend renderer, native-table suppression, the
  Blocksy custom layer, and the GPA frontend location setting are **superseded/obsolete** —
  they are not open questions; they are removed. See `.clinerules/decisions.md`.

---

## 19. Evidence appendix

All evidence below was gathered on **2026-11-25** by **static source inspection** of the
installed WordPress 7.1.5 / WooCommerce 11.3.1 / Blocksy 2.1.62 (read-only). No live
database writes, no site changes, no fixture changes.

### 19.1 Global Attribute registry (WC 11.3.1)

- **`wc_get_attribute_taxonomies()`** (`woocommerce/includes/wc-attribute-functions.php:55`):
  returns the Global Attribute registry as an array of objects indexed by `id:{attribute_id}`,
  each with `attribute_id`, `attribute_name` (slug), `attribute_label`, `attribute_type`,
  `attribute_orderby`, `attribute_public`. Backed by the
  `woocommerce_attribute_taxonomies` table, cached in the transient
  `wc_attribute_taxonomies` + the `woocommerce-attributes` object cache group. [WC 11.3.1
  SOURCE]
- **`wc_attribute_taxonomy_name( $attribute_name )`** (line 148): returns `pa_{slug}`.
  [WC 11.3.1 SOURCE]
- **`wc_attribute_taxonomy_name_by_id( $attribute_id )`** (line 186): returns the taxonomy
  name from the ID (empty string if not found). [WC 11.3.1 SOURCE]
- **`wc_attribute_taxonomy_id_by_name( $name )`** (line 199): returns the attribute ID from
  the name (`0` if not found). [WC 11.3.1 SOURCE]
- **`wc_get_attribute( $id )`** (line 509): returns a `stdClass` with `id`, `name` (label),
  `slug` (`pa_{slug}`), `type`, `order_by`, `has_archives`. [WC 11.3.1 SOURCE]
- **Rename migration** (`wc_create_attribute` / `wc_update_attribute`, lines 548/735): on a
  slug change, WC updates `wp_term_taxonomy.taxonomy` (`pa_{old}` → `pa_{new}`), the
  `order_pa_{old}` termmeta, the `_product_attributes` meta of all products using the
  attribute (renaming the key + `name`), and the variation meta keys
  (`attribute_pa_{old}` → `attribute_pa_{new}`). After a rename, `pa_{old}` no longer
  exists. [WC 11.3.1 SOURCE — lines 656-714]
- **Delete** (`wc_delete_attribute`, line 777): deletes the registry row, deletes all terms
  of `pa_{slug}`, and unregisters the taxonomy. After deletion, `pa_{slug}` no longer
  exists. [WC 11.3.1 SOURCE]
- **Cache invalidation:** `wc_create_attribute` / `wc_delete_attribute` call
  `delete_transient( 'wc_attribute_taxonomies' )` +
  `WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' )`. [WC 11.3.1 SOURCE]

### 19.2 Product attribute editor (WC 11.3.1)

- **Panel template** (`includes/admin/meta-boxes/views/html-product-data-attributes.php`):
  panel `#product_attributes`; toolbar with "Add new" (`add_custom_attribute`) +
  `select.wc-attribute-search` (select2 for Global Attributes); rows from
  `$product_object->get_attributes( 'edit' )`; "Save attributes" button
  (`type="button"`, `disabled` by default); and `do_action( 'woocommerce_product_options_attributes' )`
  at line 69 (end of the panel, inside the Attributes tab). [WC 11.3.1 SOURCE]
- **Row template** (`html-product-attribute.php`):
  `<div data-taxonomy="…" class="woocommerce_attribute wc-metabox postbox closed {taxonomy}
  {pa_name}" rel="{position}">`. [WC 11.3.1 SOURCE]
- **Row inner** (`html-product-attribute-inner.php`): for taxonomy attributes — hidden
  `attribute_names[i]` (value = `pa_{slug}`), hidden `attribute_position[i]`,
  `select.attribute_values[i][]` (term multiselect), and `checkbox`-classed visible/variation
  checkboxes; for custom attributes — text `attribute_names[i]` + textarea
  `attribute_values[i]`. [WC 11.3.1 SOURCE]

### 19.3 Native save lifecycle (WC 11.3.1)

- **Main product save:** `woocommerce_process_product_meta` →
  `WC_Meta_Box_Product_Data::save( $post_id, $post )`
  (`includes/admin/meta-boxes/class-wc-meta-box-product-data.php:333`) →
  `self::prepare_attributes()` (line 339) → `set_attributes()` → `save()`. [WC 11.3.1 SOURCE]
- **AJAX "Save attributes":** `WC_AJAX::save_attributes()`
  (`includes/class-wc-ajax.php:922`) → `create_product_with_attributes( $data )` →
  `prepare_attributes( $data )` → `set_attributes()` → `save()`. Guarded by
  `check_ajax_referer( 'save-attributes', 'security' )` +
  `current_user_can( 'edit_product', $post_id )`. [WC 11.3.1 SOURCE]
- **`prepare_attributes( $data )`** (`class-wc-meta-box-product-data.php:234`): reads
  `attribute_names[i]`, `attribute_values[i]`, `attribute_visibility[i]`,
  `attribute_variation[i]`, `attribute_position[i]`; resolves the attribute ID via
  `wc_attribute_taxonomy_id_by_name` for `pa_` names; parses options (term ID list for
  taxonomy, `wc_get_text_attributes` for text); and **skips rows with empty options**
  (`if ( empty( $options ) ) { continue; }`). [WC 11.3.1 SOURCE]

### 19.4 Native "Add global attribute" mechanism (WC 11.3.1)

- **JS** (`assets/js/admin/meta-boxes-product.js:692`, `add_attribute_to_list`): counts
  existing rows, POSTs `action: woocommerce_add_attribute` with `taxonomy`, `i`,
  `product_type`, `security` (the `add-attribute` nonce), appends the returned row HTML to
  `#product_attributes .product_attributes`, runs `show_and_hide_controls()`,
  `init_select_controls()`, `update_attribute_row_indexes()`, expands the row, triggers
  `woocommerce_added_attribute`, and calls `jQuery.maybe_disable_save_button()`. [WC 11.3.1
  SOURCE]
- **Server handler** (`WC_AJAX::add_attribute()`, `includes/class-wc-ajax.php:779`):
  `check_ajax_referer( 'add-attribute', 'security' )` +
  `current_user_can( 'edit_products' )`; requires `taxonomy` + `i`; builds a
  `WC_Product_Attribute` (id via `wc_attribute_taxonomy_id_by_name`, name = the taxonomy,
  `visible` default `1`, `variation` default by product type); renders
  `html-product-attribute.php`. [WC 11.3.1 SOURCE]
- **Duplicate prevention** (`meta-boxes-product.js:583-597`): the native UI tracks
  `selectedAttributes` (already-added Global taxonomies) and disables them in the
  "Add global" select2. [WC 11.3.1 SOURCE]
- **Row re-indexing** (`meta-boxes-product.js:567`, `update_attribute_row_indexes`): sets
  each row's `attribute_position` to its DOM index. [WC 11.3.1 SOURCE]

### 19.5 "Save attributes" button enable/disable (WC 11.3.1 — static)

- **`jQuery.maybe_disable_save_button()`** (`assets/js/admin/meta-boxes.js:33`): disables
  `button.save_attributes` (adds `disabled` + `aria-disabled`) when any non-optional field
  within `.product_attributes` is empty. [WC 11.3.1 SOURCE — STATIC CODE EVIDENCE]
- **`jQuery.is_attribute_or_variation_empty()`** (`meta-boxes.js:5`): skips fields with the
  `optional_attribute_or_variation_data` class, the `checkbox` class, or matching
  `[class*=search__field]`; a `select` is empty when no option is `:selected`; other inputs
  are empty when `!val()`. Bound to `change` on `select.attribute_values`, `keyup` on
  `input, textarea` within `#product_attributes`, and runs once on load. [WC 11.3.1 SOURCE —
  STATIC CODE EVIDENCE]
- **Runtime behavior is UNVERIFIED** and is a Phase 2B item (B2-20); the architecture does
  not depend on it. [2B-VERIFY]

### 19.6 Frontend render chain (WC 11.3.1 + Blocksy 2.1.62)

- **`wc_display_product_attributes( $product )`**
  (`woocommerce/includes/wc-template-functions.php:4261`): builds `$product_attributes`
  (weight/dimensions + visible attributes via
  `array_filter( get_attributes(), 'wc_attributes_array_filter_visible' )`), applies the
  `woocommerce_display_product_attributes` filter, and renders
  `single-product/product-attributes.php`. [WC 11.3.1 SOURCE]
- **Template** (`woocommerce/templates/single-product/product-attributes.php`, template
  version 9.3.0): `<table class="woocommerce-product-attributes shop_attributes"
  aria-label="Product Details">` with rows `<tr class="woocommerce-product-attributes-item
  woocommerce-product-attributes-item--{key}">` containing
  `<th class="woocommerce-product-attributes-item__label">` +
  `<td class="woocommerce-product-attributes-item__value">`; guarded by
  `if ( ! $product_attributes ) { return; }`. [WC 11.3.1 SOURCE]
- **Blocksy tabs** (`blocksy/inc/components/woocommerce/single/tabs.php`): renders the
  product tabs via the `woocommerce_product_tabs` filter, calling each tab's callback; the
  "Additional information" tab callback runs
  `woocommerce_product_additional_information()` → `wc_display_product_attributes()`.
  [STATIC CODE EVIDENCE — re-confirmed 2026-11-25; previously LIVE-VERIFIED 2026-11-25]
- **`persian-woocommerce`** (10.0.7): static evidence (previous finding) shows it does not
  hook the attribute display path. Runtime compatibility still to be tested (D19). [STATIC
  CODE EVIDENCE] [2B-VERIFY]

### 19.7 Admin menu ordering (WP core + WC 11.3.1)

- **`$_wp_last_object_menu = 25`** (`wp-admin/menu.php:121`). [WP CORE SOURCE]
- **CPT menu position** (`wp-admin/menu.php:140-142`):
  `is_int( $post_type_obj->menu_position ) ? $post_type_obj->menu_position :
  ++$_wp_last_object_menu` — a float `menu_position` falls back to the auto-increment
  counter. [WP CORE SOURCE]
- **Products CPT** (`woocommerce/includes/class-wc-post-types.php:367-421`): registers
  **no** `menu_position` → auto-assigned integer position (≥ 26). [WC 11.3.1 SOURCE]
- **WooCommerce menu** (`woocommerce/includes/admin/class-wc-admin-menus.php:97`): position
  `55.5`. [WC 11.3.1 SOURCE]
- **`add_menu_page`** accepts a float position; the `$menu` global is keyed by position and
  rendered in position order. [WP CORE SOURCE]

### 19.8 Carried-over (Phase 1 / 1.1 / 2A)

- Live environment: WP 7.1.5 · PHP 8.3.36 · WooCommerce 11.3.1 · Blocksy 2.1.62 ·
  blocksy-companion 2.1.62 · persian-woocommerce 10.0.7 · blocksy-child 1.1.1.
  [LIVE-VERIFIED 2026-11-23]
- Attribute data landscape: 328 products with attributes; 13 taxonomy attributes; 399
  distinct custom names; 1521 custom entries; 294 multi-value (comma) custom attributes;
  **0** pipe-separated custom attributes. [Phase 1 research]
- `maybe_unserialize()` (WP 7.1.5, `wp-includes/functions.php:653`) does **not** restrict
  object instantiation (default `allowed_classes`); `unserialize( …, [ 'allowed_classes' =>
  false ] )` does. [Phase 2A.2 — preserved principle, §13.3]
- `get_post( $id )` second parameter is an output format, not a post-type constraint.
  [Phase 2A.1 — preserved where group posts are retrieved]
- `rest_sanitize_boolean()` coerces arbitrary non-empty strings to `true`; strict allowlist
  parsing required for Booleans. [Phase 2A.1 — preserved, §13.2]

---

*End of Phase 2A architecture specification (Phase 2A.3 native-template redesign). This
document is the contract for Phase 2B (runtime verification) and Phase 3 (implementation).
It authorizes no implementation.*
