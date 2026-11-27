# Phase 3.0 — Implementation Roadmap: WooCommerce Product Group Attributes (GPA)

> **Phase:** 3.0 (implementation roadmap — ROADMAP §3, Phase 3, part 0).
> **Status:** documentation only. This document is the **evidence-based plan** that breaks
> Phase 3 (full implementation) into ordered, verifiable subphases. It **implements
> nothing** and does **not** authorize implementation. Phase 3.1 (the first implementation
> subphase) may begin **only after explicit owner authorization** of this roadmap.
>
> **Authoritative architecture:** the Phase 2A.3 **native-template model**
> (`docs/architecture/phase-2a-architecture-specification.md`), decisions **D28–D43**
> (`.clinerules/decisions.md`), and the **runtime-verified** Phase 2B evidence
> (`reports/2026-11-26-phase-2b-runtime-verification.md`, 14/14 PASS;
> `reports/2026-11-27-phase-2b-evidence-gap-followup.md`).
>
> **Label conventions** (inherited from the specification):
>
> | Label | Meaning |
> |-------|---------|
> | **[DECISION D#]** | Approved owner decision (see `.clinerules/decisions.md`). |
> | **[REQ]** | Technical requirement — forced by the live environment, project rules, or an approved decision. |
> | **[RECOMMEND]** | Recommendation — a design choice proposed here, **not yet owner-approved**. |
> | **[2B-VERIFIED]** | Confirmed at runtime in Phase 2B (14/14 PASS). |
> | **[3-VERIFY]** | Must be re-confirmed at runtime during the relevant Phase 3 subphase. |
> | **[OWNER-APPROVAL]** | Requires explicit owner authorization before proceeding. |

---

## 0. Purpose, scope, and status of this document

### 0.1 Purpose

Phase 3 is **Full Implementation** (ROADMAP §3): build the complete GPA module in the
Blocksy child theme, following the approved architecture, incrementally, verifying each
component before proceeding. This document (Phase 3.0) is the **roadmap** for that work:
it decomposes Phase 3 into ordered subphases (3.1–3.5), each with an explicit scope,
dependencies, acceptance criteria, verification steps, exclusions, and an owner-approval
point. It is the single planning artifact that Phase 3.1+ will execute against.

### 0.2 In scope (Phase 3.0 — this task)

1. Decompose Phase 3 into subphases 3.1–3.5 with the required attributes (scope,
   dependencies, acceptance criteria, verification steps, exclusions, owner-approval
   points).
2. Build an **evidence/source traceability table** mapping every planned implementation
   item to its authoritative source (specification section, decision, Phase 2B evidence,
   or project rule).
3. Record the **invariant architecture constraints** that every subphase must preserve.
4. Record the **runtime-verified foundation** (Phase 2B) and the
   specification→WooCommerce 11.3.1 function mapping that implementation must target.
5. Flag every **superseded / obsolete / conflicting** reference so it is not mistaken for
   an active requirement.
6. Produce the completion report
   `reports/2026-11-27-phase-3-0-implementation-roadmap.md`.

### 0.3 Out of scope (Phase 3.0 — this task)

- **Any implementation:** no PHP, JS, or CSS is written or modified. No module files are
  created or changed under `blocksy-child` or anywhere in the WordPress installation.
- **Any database write**, any live WordPress change, any plugin/theme install or
  activation, any fixture change.
- **Any catalog cleanup or delimiter normalization** (D20) and **any catalog repair**
  (D21).
- **Any Phase 3.1+ implementation work.** This roadmap does not begin implementation.

### 0.4 Relationship to the roadmap phases

| Phase | Objective | Status after this task |
|-------|-----------|------------------------|
| Phase 3.0 | Implementation roadmap (this document) | **COMPLETE (2026-11-27)** |
| Phase 3.1 | Module foundation (CPT, admin menu, config) | NOT STARTED — [OWNER-APPROVAL] |
| Phase 3.2 | Group editor (admin CRUD + reference validation) | NOT STARTED — [OWNER-APPROVAL] |
| Phase 3.3 | Product Data → Attributes integration (Load) | NOT STARTED — [OWNER-APPROVAL] |
| Phase 3.4 | Frontend uniform table styles (CSS-only) | NOT STARTED — [OWNER-APPROVAL] |
| Phase 3.5 | i18n, asset finalization, module integration & regression | NOT STARTED — [OWNER-APPROVAL] |
| Phase 4 | Integration and testing in the real Anishop environment | NOT STARTED (unchanged) |
| Phase 5 | Optimization and finalization | NOT STARTED (unchanged) |

> **Phase discipline (project rules):** each subphase is a separate task. Completing one
> subphase does **not** authorize the next. Each subphase begins only after explicit owner
> authorization, works on its own branch, and is merged into `main` only after completion
> **and** owner approval.

---

## 1. Authoritative inputs (evidence base)

This roadmap is derived exclusively from the following authoritative sources. No
requirement in this document is invented; every planned item traces to one of these.

| # | Source | Role in this roadmap |
|---|--------|----------------------|
| S1 | `docs/architecture/phase-2a-architecture-specification.md` (Phase 2A.3, authoritative) | The design contract: data model, file structure, group editor, product integration, Load, native editing, frontend rendering, table styles, admin menu, security, performance, testing. |
| S2 | `.clinerules/decisions.md` (D1–D43) | Owner decisions. Active: D1–D8, D11, D12, D13, D15, D19, D20, D21, D23, **D28–D34, D36–D43** (D35 is superseded by D43 and is **not** active). Superseded/obsolete: D9, D10, D14, D16, D17, D18, D22, D24, D25, D26, D27, D35. |
| S3 | `.clinerules/project-rules.md` | Execution rules, security rules, i18n rules, test-environment policy, Git workflow. |
| S4 | `.clinerules/memory-bank.md` | Current state, data model, conventions, fixtures. |
| S5 | `.clinerules/project-context.md` | Mandatory functionality, core principles, Definition of Done. |
| S6 | `.clinerules/technical-conventions.md` | Module pattern, asset pattern, house style, existing modules to preserve. |
| S7 | `.clinerules/environment.md` | Live environment versions, tooling, paths. |
| S8 | `reports/2026-11-26-phase-2b-runtime-verification.md` | 14/14 runtime PASS; spec→WC 11.3.1 function mapping (§6). |
| S9 | `reports/2026-11-27-phase-2b-evidence-gap-followup.md` | B2-19 (save path) corrected to PASS; B2-2 (menu) evidence basis corrected; B2-20 (save button) browser-verified; fixture 13143 restored byte-exact. |
| S10 | `reports/2026-11-27-finalize-owner-decisions-q1-q4.md` | D40–D43 final owner decisions. |
| S11 | `reports/2026-11-27-doc-consistency-fixes.md` | Latest documentation baseline (commit `fe9f370`). |
| S12 | `docs/PROJECT-ROADMAP.md` | Phase definitions, required functionality, Definition of Done. |
| S13 | `docs/testing/test-fixtures.md` | Permanent test fixtures (13143 simple, 17028 variable) + registry. |
| S14 | `docs/woocommerce-attributes-dev-notes.md` | Native WC attribute storage/API notes (Phase 1). |
| S15 | `docs/references/group-attributes-reference.md` | Reference plugin behavior (context only — the clarified vision supersedes its value-selection model). |

> **Precedence:** where sources could conflict, the order of authority is
> **S2 (decisions, latest D40–D43) → S1 (Phase 2A.3 specification) → S8/S9 (Phase 2B
> runtime evidence) → S3/S5/S6 (rules/context/conventions) → S12 (roadmap)**. The
> superseded/obsolete decisions (S2) are **never** treated as active requirements (see §6).


---

## 2. Invariant architecture constraints

The following constraints are **invariant** across every Phase 3 subphase. A subphase that
violates any of them is out of conformance with the approved architecture and must not be
merged. These are restated here so each subphase's acceptance criteria can reference them
directly.

### 2.1 Data model (S1 §3, S2 D28/D29/D30/D39, S4)

| Constraint | Value | Source |
|------------|-------|--------|
| Group CPT | `ani_gpa_group` | S1 §2.3, S2 D28 |
| Group attribute meta | `_ani_gpa_group_attributes` — a **serialized array of ordered `pa_{slug}` strings** (Global Attribute references) | S1 §3.2, S4 |
| Settings storage | Theme Mod `anishop_gpa_settings` (single sanitizer, versioned schema); `table_style` ∈ {`default`, `minimal`, `boxed`} | S1 §10.4, S2 D39 |
| **No** GPA product metadata | The product's native `_product_attributes` is the **single source of truth** | S2 D30 |
| **No** `_ani_gpa_assigned_groups` | Removed from the active architecture | S2 D30 |
| **No** `_ani_gpa_selected_values` | Removed from the active architecture | S2 D30 |
| **No** `ani_gpa_group_cat` taxonomy | Group categories are **not** in the initial version | S2 D40 |
| **No** three-tier A/B/C value model | Removed | S2 D28 |
| **No** custom-attribute aggregation | Removed | S2 D28 |

### 2.2 Naming and isolation (S3, S6)

| Constraint | Value |
|------------|-------|
| PHP function prefix | `ani_gpa_` |
| PHP class prefix | `Ani_GPA_` |
| CSS class prefix | `.ani-gpa-` |
| Text domain | `anishop` |
| Module location | `wp-content/themes/blocksy-child/inc/ani-group-product-attributes/` |
| Assets | `wp-content/themes/blocksy-child/assets/js|css/ani-group-product-attributes*.{js,css}` |
| Bootstrap | one new `require_once` in `functions.php` (consistent with the 14 existing entries) |
| **No** new plugins / JS frameworks / page builders | [REQ] |
| **No** Blocksy Pro runtime dependency | [REQ] |
| **No** modification of Blocksy core / existing child-theme modules | [REQ] |


### 2.3 Rendering and presentation (S1 §9/§10, S2 D33/D34/D42)

| Constraint | Value |
|------------|-------|
| Frontend output | **Native only** — the standard WooCommerce/Blocksy Additional Information tab |
| **No** separate GPA frontend renderer / tables / group headings / grouping by source group | S2 D33 |
| **No** custom Blocksy product layer for GPA output | S2 D33 (D16 superseded) |
| **No** configurable GPA frontend location; **no** native-table suppression | S2 D33 (D17 superseded) |
| Table styles | `default` (native, enqueues nothing) / `minimal` / `boxed` — applied **uniformly** to all attributes |
| Styling mechanism | **CSS-only** (body-class scoping + conditional enqueue); **no** PHP render changes, **no** template overrides, **no** frontend JavaScript | S2 D42 |
| Displayed attribute order | The product's **native** attribute order (GPA never overrides it) | S2 D31/D33 |

### 2.4 Product integration (S1 §5, S2 D29/D37/D38/D41)

| Constraint | Value |
|------------|-------|
| Placement | Inside WooCommerce **Product Data → Attributes** via `woocommerce_product_options_attributes` | S2 D38 |
| Toggle | "Create group for this product" is **UI-only** (not a persisted flag) | S2 D37 |
| Group select | Lists **`publish`** groups only | S2 D41 |
| Load mechanism | Reuses the **native** `woocommerce_add_attribute` AJAX action; no parallel data structure | S2 D29 |
| Duplicate handling | A `pa_{slug}` row already present is **skipped** (by `data-taxonomy`), not duplicated | S1 §5.5 |
| Save | GPA adds **no** product-meta save handler; the native `woocommerce_process_product_meta` lifecycle is the sole writer of `_product_attributes` | S2 D29 |


### 2.5 Admin menu (S1 §12, S2 D43)

| Constraint | Value |
|------------|-------|
| Menu type | **Submenu** of the WooCommerce **Products** menu (no top-level menu) |
| Position | **Immediately after** the existing WooCommerce **Attributes** submenu |
| Persian label | **گروه ویژگی‌های آنی** |
| CPT menu | `show_in_menu => false` (no auto CPT menu) |

### 2.6 Security (S1 §13, S3)

| Constraint | Value |
|------------|-------|
| Group save | nonce + `current_user_can( 'edit_post', $post_id )` |
| Load REST endpoint | nonce (`X-WP-Nonce`) + `permission_callback` checking `current_user_can( 'edit_product', $product_id )` |
| Load row insertion | reuses native `woocommerce_add_attribute` (enforces `edit_products` + `add-attribute` nonce); GPA does not bypass |
| Settings save | nonce + `current_user_can( 'edit_theme_options' )` |
| Output escaping | `esc_html()` / `esc_attr()` / `wp_kses_post()` on all admin UI output |
| Input sanitization | `sanitize_text_field()` + `wp_unslash()`; references validated to `pa_{slug}` shape + registry |
| Safe unserialization | `unserialize( $raw, array( 'allowed_classes' => false ) )` + type + structure validation; **never** `maybe_unserialize()` for safety |
| No raw SQL | The Global Attribute registry is read via WC functions (which cache); GPA performs no raw SQL |


---

## 3. Runtime-verified foundation (Phase 2B) and function mapping

Phase 2B verified the architecture's runtime assumptions against the live environment
(WP 7.1.5 / WooCommerce 11.3.1 / Blocksy 2.1.62 / PHP 8.3.36). **14/14 items PASS**
(S8), with three evidence-basis corrections applied in the follow-up (S9). These are the
**verified facts** that Phase 3 implementation can rely on. Items marked **[3-VERIFY]**
must be re-confirmed at runtime during the relevant subphase (they were verified in
isolation in Phase 2B but will be exercised end-to-end during implementation).

### 3.1 Verified facts (rely-on list)

| # | Fact | Evidence |
|---|------|----------|
| V1 | The WooCommerce **Products** admin menu is at position **26** (rendered sidebar, authoritative). | S8 B2-2 [2B-VERIFIED]; S9 (evidence basis corrected to rendered sidebar) |
| V2 | Candidate submenu positions immediately after Attributes are **free** (no collision). | S8 B2-3 [2B-VERIFIED] |
| V3 | Stale-reference validation works: a `pa_{slug}` not in the registry is detected and flagged. | S8 B2-7 [2B-VERIFIED] |
| V4 | The **Load row-insertion mechanism** is confirmed: the native `woocommerce_add_attribute` AJAX action inserts a structurally-identical native row. | S8 B2-14 [2B-VERIFIED] |
| V5 | The **native admin attribute row markup** is confirmed (row structure, `data-taxonomy`, select2 wiring). | S8 B2-18 [2B-VERIFIED] |
| V6 | The **admin save/processing path** is confirmed: `WC_Meta_Box_Product_Data::save` → `prepare_attributes()` → `set_attributes()` → `_product_attributes`. | S8 B2-19 [2B-VERIFIED]; S9 (corrected to PASS) |
| V7 | The **Save button** disables when an attribute row has no value (`maybe_disable_save_button`). | S8 B2-20 [2B-VERIFIED]; S9 (browser-verified) |
| V8 | **Native coexistence** is feasible: reordering, remove, and manual add (Global + Custom) coexist with loaded attributes. | S8 B2-21 [2B-VERIFIED] |
| V9 | The **frontend `shop_attributes`** table renders through the native Additional Information tab. | S8 B2-23 [2B-VERIFIED] |
| V10 | The **CSS-only styling mechanism** is feasible (body-class scoping + conditional enqueue against live Blocksy 2.1.62 + WC 11.3.1 markup). | S8 B2-24 [2B-VERIFIED] |
| V11 | The **Blocksy Additional Information** markup is confirmed (no Pro dependency). | S8 B2-26 [2B-VERIFIED] |
| V12 | **`persian-woocommerce`** does not interfere with the CSS-only style + native render. | S8 B2-27 [2B-VERIFIED] |
| V13 | **WooCommerce 11.3.1** conformance: `woocommerce_add_attribute`, `prepare_attributes`, `shop_attributes` template behave as the spec assumes. | S8 B2-28 [2B-VERIFIED] |
| V14 | **Blocksy Free** (no Pro) renders the native Additional Information tab. | S8 B2-29 [2B-VERIFIED] |

### 3.2 Specification → WooCommerce 11.3.1 function mapping (S8 §6)

The Phase 2A.3 specification used descriptive names for native behaviors. Phase 2B mapped
each to the **actual WooCommerce 11.3.1 function**. Implementation **must** target the
right-hand column, not the descriptive name.

| Spec descriptive name | Actual WC 11.3.1 function / location |
|-----------------------|----------------------------------------|
| "set product attributes" | `WC_Product::set_attributes()` / `get_attribute()` |
| "persist product attributes" | `_product_attributes` in `WC_Product_Data_Store_CPT` |
| "admin save handler" | `WC_Meta_Box_Product_Data::save` |
| "add attribute AJAX" | `WC_AJAX::add_attribute` (action `woocommerce_add_attribute`) |
| "row insertion / index / select2" | `meta-boxes-product.js` |
| "frontend attribute display" | `wc_display_product_attributes()` |
| "filter visible attributes" | `wc_attributes_array_filter_visible()` |
| "attribute taxonomy lookup" | `wc_attribute_taxonomy_id_by_name()` |
| "Global Attribute registry" | `wc_get_attribute_taxonomies()` (cached) |
| "taxonomy name from slug" | `wc_attribute_taxonomy_name()` → `pa_{slug}` |

### 3.3 Items to re-verify during implementation ([3-VERIFY])

These were verified in isolation in Phase 2B but must be re-confirmed **end-to-end**
during the relevant Phase 3 subphase, because implementation exercises them in combination:

| # | Item | Subphase |
|---|------|----------|
| R1 | Exact `i` index computation for **sequential multi-row** insertion (loading a group with N attributes) + select2 re-initialization of all inserted rows. | 3.3 |
| R2 | Save-button enable/disable when a **loaded** row has no value (does a GPA-inserted row trigger `maybe_disable_save_button`?). | 3.3 |
| R3 | Native save **discard** behavior: loaded rows persist only after values are selected. | 3.3 |
| R4 | Admin menu placement end-to-end: GPA submenu appears **immediately after** Attributes with no collision, in the rendered sidebar. | 3.1 |
| R5 | CSS-only table style: exact selectors + body-class scoping against live markup for `minimal` and `boxed`. | 3.4 |
| R6 | `persian-woocommerce` runtime coexistence with the final CSS-only style + native render. | 3.4 / 3.5 |
| R7 | Native coexistence end-to-end: reordering, remove, manual add (Global + Custom) alongside loaded attributes. | 3.3 |


---

## 4. Target file structure (S1 §1.4)

The complete set of files Phase 3 will create/modify. Each file is assigned to the
subphase that owns it. No file outside this list is created or modified (except the single
`functions.php` `require_once` line and the `languages/` files).

```
wp-content/themes/blocksy-child/
  functions.php                                  [3.1] +1 require_once line
  inc/ani-group-product-attributes/
    config.php                                   [3.1] Theme Mod schema + single sanitizer (anishop_gpa_settings); constants
    bootstrap.php                                [3.1] CPT registration; admin menu; asset registration; hook wiring
    admin.php                                    [3.2] group list/edit UI; group save handler; reference validation
    product.php                                  [3.3] Product Data -> Attributes GPA section; Load REST endpoint; admin JS wiring
    styles.php                                   [3.4] uniform table-style CSS enqueue (body class + conditional)
  assets/js/ani-group-product-attributes-admin.js   [3.2 group editor DnD] + [3.3 Load]
  assets/css/ani-group-product-attributes-admin.css  [3.2]
  assets/css/ani-group-product-attributes-styles.css [3.4]
  languages/
    anishop.pot                                  [3.5]
    fa_IR.po / fa_IR.mo                          [3.5] (merged into the existing child-theme .po/.mo)
```

> **Note on `languages/`:** the child theme uses a **locale-only filename convention**
> (`fa_IR.mo`, not `anishop-fa_IR.mo`) and a single text domain `anishop`. The new GPA
> strings are added to the **existing** `fa_IR.po` / `fa_IR.mo` (S6 §1), not a separate
> file. A `.pot` is maintained for the module's strings.


---

## 5. Subphase breakdown

Phase 3 is decomposed into five ordered subphases. Each is a **separate task** requiring
explicit owner authorization.

**Approval and verification order (strict, invariant):** the subphases are approved and
verified in the order 3.1 → 3.2 → 3.3 → 3.4 → 3.5. Each subphase begins only after
explicit owner authorization and is verified before the next is approved.

**Independent development vs. prerequisites vs. integration/verification:**
- **Hard prerequisite (implementation):** a subphase's *implementation* may begin only
  after its declared dependencies are complete. 3.4's only hard prerequisite is 3.1 (the
  Theme Mod `anishop_gpa_settings` must exist for the style to be selectable).
- **Independent development:** 3.4's CSS authoring (visual specifications + the
  `minimal` / `boxed` stylesheets) can be developed **in parallel** with 3.3, because it
  depends only on 3.1's Theme Mod, not on 3.3's output.
- **Integration/verification (must wait):** 3.4's **end-to-end verification** (styling
  *real* loaded attributes on a product) must wait until 3.3 is complete and verified,
  because it needs 3.3's Load functionality to produce loaded attributes to style.

In short: 3.4 may be *developed* alongside 3.3, but is *verified* after 3.3. No subphase
is added, removed, or reordered, and no approval gate changes.

> **B2 reference:** each subphase lists the Phase 2B checklist items (S1 §17) it
> implements. The B2 items are the implementation checklist; the subphases group them into
> verifiable increments.

### 5.1 Phase 3.1 — Module foundation (CPT, admin menu, config)

**Objective:** establish the module skeleton — the `ani_gpa_group` CPT, the admin submenu
under Products (after Attributes), the Theme Mod settings schema, constants, asset
registration, and hook wiring — so that later subphases have a stable foundation.

**Scope**
- Register CPT `ani_gpa_group` (`show_in_menu => false`, no auto menu) [DECISION D28].
- Register the admin **submenu** under the WooCommerce **Products** menu, **immediately
  after** the Attributes submenu, label **گروه ویژگی‌های آنی** [DECISION D43].
- Define the Theme Mod `anishop_gpa_settings` schema + **single sanitizer** (versioned)
  with `table_style` ∈ {`default`, `minimal`, `boxed`} [DECISION D39].
- Define module constants (prefixes, meta keys, Theme Mod key, REST namespace).
- Register admin + frontend assets (handles, `filemtime` versioning) — **registration
  only**; enqueue is wired in the subphases that use them.
- Wire the module bootstrap: one `require_once` in `functions.php`; hook registration.

**Files:** `inc/ani-group-product-attributes/config.php`, `bootstrap.php`;
`functions.php` (+1 line).

**B2 items:** B2-1 (CPT), B2-2 (menu position), B2-3 (submenu placement).

**Dependencies:** none (foundation).

> **Settings UI location (unresolved — [OWNER-APPROVAL], see OI-5):** 3.1 defines the
> Theme Mod schema + sanitizer (approved, D39). The **admin UI location and control
> method** for an administrator to select/change `table_style` is **not** specified by the
> approved architecture and must be confirmed by the owner before the settings UI is
> implemented. The house pattern (Appearance → Anishop page) is the existing convention
> but is **not** an approved decision for GPA's setting. No settings UI is invented in
> this roadmap.

**Acceptance criteria**
1. CPT `ani_gpa_group` is registered (verifiable via `get_post_type_object( 'ani_gpa_group' )`);
   **no** standalone CPT menu appears in the admin sidebar (`show_in_menu => false`). Group
   posts are created/edited **only** through the GPA submenu interface built in 3.2 — 3.1
   provides **no** group CRUD UI.
2. The GPA submenu appears in the rendered admin sidebar **immediately after** the
   WooCommerce Attributes submenu, with the exact Persian label **گروه ویژگی‌های آنی**
   [3-VERIFY R4].
3. The Theme Mod `anishop_gpa_settings` round-trips through the single sanitizer; an
   invalid `table_style` falls back to `default`.
4. No duplicate hooks, no changes to existing child-theme modules, no Blocksy core
   changes, no new plugins.
5. All new PHP uses `ani_gpa_` / `Ani_GPA_` prefixes; text domain `anishop`.

**Verification steps**
- Static: `php -l` on new files; confirm `functions.php` diff is exactly +1 line.
- Runtime (admin): load the Products menu; screenshot the sidebar; confirm GPA submenu
  position relative to Attributes [3-VERIFY R4].
- Runtime: confirm the CPT is registered (e.g. `wp post type` / `get_post_type_object`);
  confirm **no** standalone CPT menu in the rendered sidebar. (Group CRUD is verified in
  3.2, not here.)
- Regression: confirm the 14 existing modules still load (no fatal, no hook collision).

**Exclusions**
- No group editor UI (list/edit form) — that is 3.2.
- No product integration, no Load, no frontend styling.
- No i18n file generation (3.5).

**Owner approval point:** [OWNER-APPROVAL] — owner approves the 3.1 scope before
implementation begins; owner approves the 3.1 result (menu + CPT + config verified) before
3.2 begins.


### 5.2 Phase 3.2 — Group editor (admin CRUD + reference validation)

**Objective:** build the group administration UI — list all groups, create/edit/delete a
group, select existing **Global Attribute definitions** to include, and order them with
drag-and-drop — plus the group save handler with reference validation.

**Scope**
- **Group list** screen: table of all `ani_gpa_group` posts (name, attribute count,
  status, edit/delete actions).
- **Group edit** screen:
  - Group name (post title).
  - **Global Attribute picker**: list existing Global Attribute definitions
    (`wc_get_attribute_taxonomies()`); select which to include.
  - **Add / remove** attributes from the group.
  - **Drag-and-drop ordering** of the group's attributes (defines the initial insertion
    order when loaded) [DECISION D31].
- **Group save handler**: nonce + `current_user_can( 'edit_post', $post_id )`; sanitize
  the ordered reference list; validate each reference to `pa_{slug}` shape **and** against
  the live registry; persist to `_ani_gpa_group_attributes` (serialized array of ordered
  `pa_{slug}` strings).
- **Reference validation**: reject/flag references that are not valid Global Attribute
  definitions (stale after a rename/delete) [2B-VERIFIED V3].
- **Empty group** is a valid administrative state (a group with no attributes) [S1 §4.4].
- Admin JS: dependency-free vanilla IIFE for the picker + drag-and-drop; admin CSS
  (`.ani-gpa-` prefix, house style, RTL).

**Files:** `inc/ani-group-product-attributes/admin.php`;
`assets/js/ani-group-product-attributes-admin.js` (group-editor portion);
`assets/css/ani-group-product-attributes-admin.css`.

**B2 items:** B2-4 (list table), B2-5 (edit: Global Attribute picker + add/remove +
drag-and-drop ordering), B2-6 (save handler), B2-7 (reference validation), B2-8
(stale-reference flagging), B2-9 (empty group valid state).

**Dependencies:** 3.1 (CPT + menu + config).

**Acceptance criteria**
1. An admin can create, edit, and delete groups from the GPA submenu.
2. The picker lists **only** existing Global Attribute definitions (taxonomy-based
   `pa_{slug}`); custom product attributes are **not** listed [DECISION D28].
3. Attributes can be added, removed, and reordered (drag-and-drop); the order is persisted
   to `_ani_gpa_group_attributes` as an ordered array.
4. The save handler enforces nonce + capability; sanitizes input; validates every
   reference against the registry; rejects invalid references with a clear admin notice.
5. A group with zero attributes is savable (valid state).
6. All admin output is escaped; all input is sanitized; no raw SQL.
7. Admin UI is RTL, uses the house style, and uses the `anishop` text domain.

**Verification steps**
- Static: `php -l`; confirm no `maybe_unserialize()`; confirm escaping/sanitization.
- Runtime (admin): create a group, add 3 Global Attributes, reorder them, save; reload and
  confirm the order persisted.
- Runtime: attempt to save a reference to a non-existent `pa_{slug}`; confirm it is
  rejected/flagged.
- Runtime: create an empty group; confirm it saves.
- Runtime: confirm the picker does not list custom (non-taxonomy) attributes.
- Regression: confirm existing modules unaffected.

**Exclusions**
- No product-level loading (3.3).
- No frontend styling (3.4).
- No group categories (out of scope — D40).
- No i18n file generation (3.5).

**Owner approval point:** [OWNER-APPROVAL] — owner approves the 3.2 scope before
implementation; owner approves the 3.2 result (group CRUD + validation verified) before
3.3 begins.


### 5.3 Phase 3.3 — Product Data → Attributes integration (Load)

**Objective:** integrate GPA into the WooCommerce product editor (Product Data →
Attributes) so an admin can load a group's Global Attribute definitions into the product's
**native** attribute collection, reusing the native row-insertion mechanism.

**Scope**
- **GPA section** inside Product Data → Attributes via
  `woocommerce_product_options_attributes` [DECISION D38].
- **UI-only toggle** "Create group for this product" that reveals the group select + Load
  controls (not a persisted flag) [DECISION D37].
- **Group select**: lists **`publish`** groups only [DECISION D41].
- **Load REST endpoint** `anishop/v1/gpa/groups/{id}/attributes`:
  - The route carries the **group** ID in `{id}`. The **product** ID is **not** in the
    route; it must be supplied by the product edit screen (the actual product being
    edited). The exact transport (query parameter vs. request body) is **not settled** by
    the approved architecture [UNRESOLVED — see OI-6, [OWNER-APPROVAL]].
  - `permission_callback` checks `current_user_can( 'edit_product', $product_id )`, where
    `$product_id` is the **actual product being edited** (validated to exist and be a
    product before the capability check). The check is **not** weakened.
  - Nonce via `X-WP-Nonce`.
  - Returns the group's ordered, **validated** `pa_{slug}` references (stale references
    excluded/flagged).
- **Load row insertion**: the admin JS calls the **native**
  `woocommerce_add_attribute` AJAX action once per reference, in the group's order, so the
  inserted rows are structurally identical to natively-added rows [2B-VERIFIED V4/V5].
- **Duplicate handling**: a `pa_{slug}` row already present on the product is **skipped**
  (matched by `data-taxonomy`), not duplicated [S1 §5.5].
- **Multiple groups**: several groups may be loaded into the same product; duplicates are
  skipped.
- **No GPA product-meta save handler**: the native `woocommerce_process_product_meta`
  lifecycle is the sole writer of `_product_attributes` [DECISION D29].
- Admin JS (Load portion) + wiring; reuses the 3.2 admin JS file.

**Files:** `inc/ani-group-product-attributes/product.php`;
`assets/js/ani-group-product-attributes-admin.js` (Load portion).

**B2 items:** B2-10 (GPA section), B2-11 (UI-only toggle), B2-12 (group select,
`publish` only), B2-13 (Load REST endpoint), B2-14 (row insertion), B2-15 (duplicate
handling), B2-16 (stale-reference handling on Load), B2-17 (multiple-group loading),
B2-18 (loaded rows structurally identical to native), B2-19 (native save discard),
B2-20 (save-button behavior), B2-21 (native coexistence), B2-22 (native order preserved).

**Dependencies:** 3.1 (config/menu), 3.2 (groups must exist to load).

**Acceptance criteria**
1. The GPA section appears inside Product Data → Attributes, coexisting with the native
   interface (not replacing it).
2. The toggle reveals the group select + Load; it is **not** persisted as product meta.
3. The group select lists only `publish` groups; draft groups are absent.
4. Loading a group inserts its Global Attribute definitions as **native** attribute rows,
   in the group's order; each inserted row is structurally identical to a natively-added
   row [3-VERIFY R1].
5. Loading a group whose attribute already exists on the product **skips** the duplicate
   (no duplicate rows).
6. Loading multiple groups works; cross-group duplicates are skipped.
7. After loading, the admin uses **native** controls to select terms/values, edit, remove,
   add (Global or Custom), and reorder; all of these coexist with loaded attributes
   [3-VERIFY R7].
8. No GPA-specific product metadata is written; `_product_attributes` is the only store.
9. The REST endpoint enforces nonce + `edit_product` capability; unauthorized requests are
   rejected.
10. A loaded row with no value behaves per native save semantics [3-VERIFY R2/R3].

**Verification steps**
- Static: `php -l`; confirm no product-meta write in GPA code; confirm REST
  `permission_callback` + nonce.
- Runtime (admin, fixture 13143 simple / 17028 variable): load a group; confirm native
  rows inserted in order; confirm select2 initialized on inserted rows [3-VERIFY R1].
- Runtime: load the same group twice; confirm no duplicate rows.
- Runtime: load two groups sharing an attribute; confirm the shared attribute appears once.
- Runtime: after loading, select a term natively, save, reload; confirm the value persisted
  via the native path [3-VERIFY R3].
- Runtime: reorder / remove / manually add a Global and a Custom attribute; confirm
  coexistence [3-VERIFY R7].
- Security: call the REST endpoint without a valid nonce / as a user without
  `edit_product`; confirm rejection.
- Regression: confirm existing modules unaffected; confirm the native attribute UI is
  unmodified.

**Exclusions**
- No frontend styling (3.4).
- No i18n file generation (3.5).
- No "View Attribute" shop-page button (deferred / out of scope — D36).
- No catalog cleanup or delimiter normalization (D20/D21).

**Owner approval point:** [OWNER-APPROVAL] — owner approves the 3.3 scope before
implementation; owner approves the 3.3 result (Load verified end-to-end on both fixtures)
before 3.4/3.5 proceed.


### 5.4 Phase 3.4 — Frontend uniform table styles (CSS-only)

**Objective:** provide the three uniform table styles (`default` / `minimal` / `boxed`)
applied **CSS-only** to the native `shop_attributes` table, uniformly to **all** product
attributes regardless of source.

**Scope**
- **Visual specifications** for `minimal` and `boxed` (defined and documented **before**
  final CSS, within the house palette) [DECISION D42].
- **`default`**: the native WooCommerce/Blocksy appearance; **no** GPA-specific CSS is
  enqueued.
- **`minimal`**: a lightweight, visually simplified presentation.
- **`boxed`**: a clearly bordered/boxed presentation.
- **Mechanism (CSS-only)** [DECISION D42]:
  - A `body` class (e.g. `ani-gpa-style-{style}`) added via a `body_class` filter when the
    style is not `default`.
  - Conditional enqueue of `ani-group-product-attributes-styles.css` **only** when the
    style is `minimal` or `boxed` (never for `default`).
  - Selectors scoped to the native `shop_attributes` table; **no** PHP render changes,
    **no** template overrides, **no** frontend JavaScript, **no** duplicate tables.
- **Uniformity**: the style applies to **all** attributes (Global and Custom, GPA-loaded
  and manual) — it must not depend on group membership or provenance [DECISION D34].
- **RTL + house palette**: respect the active child-theme palette and RTL layout
  [DECISION D42].

**Files:** `inc/ani-group-product-attributes/styles.php`;
`assets/css/ani-group-product-attributes-styles.css`.

**B2 items:** B2-23 (all attributes render in native tab), B2-24 (CSS-only mechanism +
uniform application), B2-25 (no duplicate tables / group headings), B2-26 (exact CSS
selectors against live markup).

**Dependencies:** 3.1 (Theme Mod `anishop_gpa_settings` — **hard prerequisite** for
implementation); 3.3 (real loaded attributes to style — required only for end-to-end
**verification**, not for CSS development; see §5 intro).

**Acceptance criteria**
1. `default` enqueues **no** GPA CSS and the table looks exactly native.
2. `minimal` and `boxed` each apply their documented visual spec, **uniformly** to all
   attributes (Global + Custom, loaded + manual).
3. The mechanism is **CSS-only**: no PHP render change, no template override, no frontend
   JS, no duplicate attribute table, no group heading.
4. The `body` class is added only for non-`default` styles; the CSS is enqueued only for
   non-`default` styles.
5. The style respects the house palette (Vazirmatn, accent `#2872FA`, radii 2/4px, flat)
   and RTL.
6. Selectors are scoped to the native `shop_attributes` table and do not leak to other
   tables [3-VERIFY R5].
7. `persian-woocommerce` coexists with the styled table [3-VERIFY R6].

**Verification steps**
- Static: `php -l`; confirm no frontend JS enqueue; confirm conditional enqueue logic;
  confirm no template override.
- Runtime (frontend, fixture product with mixed Global + Custom attributes): set each style
  and screenshot the Additional Information tab (desktop + mobile); confirm uniform
  application and correct RTL.
- Runtime: confirm `default` enqueues no GPA CSS (network inspection).
- Runtime: confirm no duplicate table, no group heading, native order preserved.
- Compatibility: confirm `persian-woocommerce` does not break the styled table
  [3-VERIFY R6].
- Regression: confirm existing modules' frontend unaffected.

**Exclusions**
- No PHP render changes, no template overrides, no frontend JavaScript.
- No configurable frontend location (fixed to the native tab — D33).
- No "View Attribute" button (D36).
- No i18n file generation (3.5).

**Owner approval point:** [OWNER-APPROVAL] — owner approves the `minimal` / `boxed`
**visual specifications** before final CSS is written (D42 requires the specs to be
defined and documented first); owner approves the 3.4 result (all three styles verified)
before 3.5.


### 5.5 Phase 3.5 — i18n, asset finalization, module integration & regression

**Objective:** finalize internationalization (English source + Persian), finalize asset
loading, and confirm the complete module integrates cleanly with the child theme and the
live environment with no regression.

**Scope**
- **i18n**: all admin UI strings use English source + Persian translation; add the GPA
  strings to the existing child-theme `languages/fa_IR.po` and regenerate `fa_IR.mo`
  (locale-only filename convention, text domain `anishop`); maintain a module `.pot`.
- **Asset finalization**: confirm all assets load **conditionally** (admin assets only on
  the relevant admin screens; frontend CSS only for non-`default` styles); confirm
  `filemtime`-based versioning; confirm no duplicate enqueues.
- **Module integration**: confirm the module works as a whole (group CRUD → load → native
  edit → styled frontend) end-to-end on both fixtures.
- **Security review**: nonces, capabilities, sanitization, escaping, safe unserialization
  (final pass over all subphases' code).
- **Regression**: confirm the 14 existing child-theme modules remain functional; confirm
  no Blocksy Pro dependency; confirm no Blocksy core changes.
- **Performance**: confirm minimal resource consumption (conditional assets, cached
  registry reads, no raw SQL).

**Files:** `languages/anishop.pot`, `languages/fa_IR.po`, `languages/fa_IR.mo` (merged);
final touches to any module file as required by the review (no new features).

**B2 items:** B2-27 (persian-woocommerce), B2-28 (WC 11.3.1), B2-29 (Blocksy Free),
B2-30 (regression: 14 modules), B2-31 (security), B2-32 (RTL + Persian +
responsive/browser-level checks; the **real-device** portion of B2-32 is Phase 4).

**Dependencies:** 3.1, 3.2, 3.3, 3.4 (all prior subphases complete).

**Acceptance criteria**
1. All admin strings are translated (English source + Persian); the `.mo` is regenerated
   and loads; no untranslated strings on any GPA admin screen.
2. Assets load conditionally and are versioned by `filemtime`; no duplicate enqueues; no
   frontend JS.
3. The full flow works end-to-end on both fixtures (13143 simple, 17028 variable): create
   group → load → native value selection → styled frontend.
4. Security review passes: every write path has nonce + capability; all output escaped;
   all input sanitized; safe unserialization used; no raw SQL.
5. The 14 existing child-theme modules remain functional (no regression).
6. No Blocksy Pro runtime dependency; no Blocksy core changes; no new plugins/frameworks.
7. `persian-woocommerce` coexists with the module [3-VERIFY R6].

**Verification steps**
- Static: `php -l` on all module files; grep for `maybe_unserialize` (must be absent);
  grep for raw `$wpdb` (must be absent); confirm escaping/sanitization coverage.
- i18n: confirm the `.po`/`.mo` contain all GPA strings; load the admin in Persian and
  confirm no missing translations.
- Runtime (end-to-end, both fixtures): full flow; screenshot admin + frontend (desktop +
  mobile **viewport**, RTL) — browser-level only. Real-device (Live Link) verification is
  Phase 4, not 3.5.
- Regression: exercise each of the 14 existing modules' primary screens; confirm no
  breakage.
- Performance: confirm conditional asset loading (network inspection); confirm no N+1
  registry queries.
- Compatibility: confirm `persian-woocommerce` + Blocksy Free + WC 11.3.1 coexist.

**Exclusions**
- No new features (this is finalization).
- No Phase 4 full integration testing (real-mobile via Live Link, performance audits,
  exhaustive error states) — that is Phase 4.
- No catalog cleanup (D20/D21).

**Owner approval point:** [OWNER-APPROVAL] — owner approves the 3.5 scope before
implementation; owner approves the 3.5 result (module complete, integrated, no
regression), which completes **Phase 3** and gates Phase 4.


---

## 6. Superseded, obsolete, and conflicting references (flagged)

The following references appear in the project history but are **NOT** active
requirements. They are flagged here so no subphase implements them. Any subphase that
introduces one of these is out of conformance.

### 6.1 Superseded decisions (replaced — do not implement)

| # | Historical decision | Superseded by | Do NOT implement |
|---|--------------------|---------------|------------------|
| D9 | Group value selection = presentation layer (render selected values without touching native data) | D28, D29, D33 | A "selected values" presentation layer |
| D10 | "View Attribute" shop-page button (reference-plugin behavior) | D36 | Any shop-page "View Attribute" button |
| D14 | Dual attribute support (both Global + Custom participate in groups) | D28, D32, D33, D34 | Custom attributes as group members |
| D16 | Blocksy custom layer for GPA frontend rendering | D33 | A Blocksy custom layer for GPA output |
| D17 | Native table hide setting | D33 | A `hide_native_table` setting / native-table suppression |
| D18 | View Attribute button (isolated, configurable) | D36 | Any View Attribute feature |
| D22 | Selected architecture A1 + B1 (CPT + product meta + Blocksy custom layer) | D28, D29, D33, D34 | The A1 + B1 direction |
| D24 | Group status policy (publish/draft assignable; publish renders) | D41 | Draft groups as loadable; status tied to rendering |
| D27 | Empty-group frontend behavior (no table when no selected values) | D33 (spec §4.4) | Frontend empty-group table logic |
| D35 | Independent top-level admin menu after Products | D43 | A top-level GPA menu |

### 6.2 Obsolete decisions (feature/contract no longer exists)

| # | Historical decision | Obsolete because |
|---|--------------------|------------------|
| D25 | Custom-attribute aggregation product-status policy | The aggregation subsystem no longer exists (groups are Global-only) |
| D26 | Stale selected-value lifecycle policy | There are no per-product "selected values" (values are native) |

### 6.3 Removed data structures and subsystems (do not create)

| Item | Status |
|------|--------|
| `_ani_gpa_assigned_groups` (product meta) | **Removed** (D30) — no persistent group assignment |
| `_ani_gpa_selected_values` (product meta) | **Removed** (D30) — no per-product selected values |
| `_ani_gpa_group_values` (group meta) | **Removed** — groups store references only |
| Three-tier A/B/C value model | **Removed** (D28) |
| Custom-attribute aggregation subsystem (raw SQL, transient cache) | **Removed** (D28) |
| GPA frontend renderer / separate table / group headings | **Removed** (D33) |
| Native-table suppression / `hide_native_table` | **Removed** (D33) |
| Blocksy custom layer for GPA | **Removed** (D33) |
| GPA frontend location setting | **Removed** (D33) |
| `ani_gpa_group_cat` taxonomy | **Not in scope** (D40) |

### 6.4 Conflicting references (resolved by later decisions)

| Conflict | Resolution |
|----------|------------|
| D24 (group status) vs D41 (only publish loadable) | **D41 governs** (2026-11-27). Only `publish` groups are listed in the product-edit picker and may be loaded. |
| D35 (top-level menu) vs D43 (submenu under Products) | **D43 governs** (2026-11-27). Submenu under Products, after Attributes. |
| D34 (CSS-only as "recommendation") vs D42 (CSS-only confirmed) | **D42 governs** (2026-11-27). CSS-only is confirmed; `minimal`/`boxed` visual specs must be defined before final CSS. |
| Reference plugin value-selection model (S15) vs clarified native-template vision | **The clarified vision (D28–D39) governs.** S15 is context only. |

### 6.5 Unavailable references

- **None.** Every source cited in §1 exists on disk at the baseline commit `fe9f370`. No
  required reference is missing or unreachable.


---

## 7. Cross-cutting concerns (apply to every subphase)

### 7.1 Security (S1 §13, S3)

- **Nonces** on every write path: group save, Load REST endpoint, settings save.
- **Capabilities**: `edit_post` (group save), `edit_product` (Load), `edit_theme_options`
  (settings).
- **Sanitization**: `sanitize_text_field()` + `wp_unslash()` on all input; references
  validated to `pa_{slug}` shape + registry.
- **Escaping**: `esc_html()` / `esc_attr()` / `wp_kses_post()` on all admin output.
- **Safe unserialization**: `unserialize( $raw, array( 'allowed_classes' => false ) )` +
  type + structure validation; **never** `maybe_unserialize()`.
- **No raw SQL**: the Global Attribute registry is read via WC functions (which cache).

### 7.2 Performance (S1 §14)

- **Conditional asset loading**: admin assets only on relevant admin screens; frontend CSS
  only for non-`default` styles.
- **Cached registry reads**: `wc_get_attribute_taxonomies()` is transient-cached; GPA does
  not add per-request registry queries.
- **No N+1**: group list/edit and Load use batched reads.
- **`filemtime`** asset versioning (house convention).

### 7.3 i18n (S3, S6)

- English source strings + Persian translation; text domain `anishop`.
- Locale-only `.mo` filename convention (`fa_IR.mo`); merged into the existing child-theme
  `.po`/`.mo`.
- Technical terms (e.g. Renderer, Swatch) may stay English in the admin UI (house
  convention).

### 7.4 RTL and house style (S6 §3)

- RTL-first (right-aligned text, right-anchored controls).
- Vazirmatn font; accent `#2872FA`; radii 2px (controls) / 4px (cards); flat (borders +
  whitespace, no heavy shadows).
- No colors, fonts, or radii outside the house set without owner approval.

### 7.5 Testing strategy (S1 §15, S13, S3)

- **Fixtures**: Simple **13143** (Philips OneBlade) and Variable **17028** (OnePlus 15,
  16 variations) — the permanent GPA test fixtures (D23). Duplicates may be created.
- **Isolation boundary**: test only on the fixtures and explicitly-created GPA test
  fixtures; never on unrelated products, orders, inventory, pricing, or global settings.
- **Test naming**: test-only products/attributes/terms use the `GPA TEST — ` prefix.
- **Registry**: every fixture, test attribute/term, and group is recorded in
  `docs/testing/test-fixtures.md` (kept current).
- **Shared attributes**: before creating/renaming/deleting any global attribute or term,
  inspect whether it is used by non-test products; never rename/delete a shared production
  attribute or term for testing.
- **Provenance labels** (never convert one type into another):
  `[REAL-MOBILE USER-REPORTED EVIDENCE]` · `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE
  VERIFIED]` · `[STATIC CODE EVIDENCE]` · `[DOCUMENTED <date>]`.
- **CDP success does not imply real-mobile success** (Phase 4 uses the owner's iPhone 12
  Pro via Live Link for mobile-only claims).

### 7.6 Compatibility (S1 §16, S2 D19)

- **WooCommerce 11.3.1**: the depended-on behaviors are [2B-VERIFIED] (V13).
- **Blocksy Free 2.1.62** (no Pro): native Additional Information tab [2B-VERIFIED] (V14).
- **`persian-woocommerce` 10.0.7**: compatibility must be **tested**, not assumed (D19)
  [3-VERIFY R6].
- **Existing child-theme modules**: the 14 modules (S6 §2) must remain functional; no
  duplicate hooks, no parallel handlers, no changes to them.


---

## 8. Owner approval points (summary)

Per the project execution rules, **each subphase is a separate task** that begins only
after explicit owner authorization, and completing one does not authorize the next. The
approval gates are:

| Gate | What the owner approves | Unlocks |
|------|-------------------------|---------|
| **G0** | This roadmap (Phase 3.0) — the subphase decomposition, scope, and acceptance criteria | Phase 3.1 |
| **G1** | Phase 3.1 scope → then the 3.1 result (CPT + menu + config verified) | Phase 3.2 |
| **G2** | Phase 3.2 scope → then the 3.2 result (group CRUD + validation verified) | Phase 3.3 |
| **G3** | Phase 3.3 scope → then the 3.3 result (Load verified end-to-end on both fixtures) | Phase 3.4 / 3.5 |
| **G4** | The `minimal` / `boxed` **visual specifications** (required before final CSS, D42) → then the 3.4 result (all three styles verified) | Phase 3.5 |
| **G5** | Phase 3.5 scope → then the 3.5 result (module complete, integrated, no regression) | **Phase 3 complete** → Phase 4 |

> **Note:** G4's visual-spec approval is a **pre-implementation** gate specific to 3.4
> (D42 requires the `minimal`/`boxed` specs to be defined and documented before final CSS
> is written). All other gates follow the standard scope-then-result pattern.


---

## 9. Risks and open items

### 9.1 Implementation risks (to be managed during Phase 3)

| # | Risk | Mitigation | Subphase |
|---|------|------------|----------|
| RISK-1 | Sequential multi-row insertion index/select2 re-init is the most complex native interaction; a wrong index or missed select2 init could corrupt the attribute list. | [3-VERIFY R1] on both fixtures before proceeding; reuse the native `woocommerce_add_attribute` action (already [2B-VERIFIED] V4) rather than hand-rolling row HTML. | 3.3 |
| RISK-2 | Save-button disable semantics for a loaded row with no value could block saving. | [3-VERIFY R2/R3]; the architecture does **not** depend on the save-button state (S1 §18.2.2). | 3.3 |
| RISK-3 | CSS selectors for `minimal`/`boxed` could leak to other tables or break under `persian-woocommerce`. | Scope to `shop_attributes`; [3-VERIFY R5/R6]; visual specs approved before CSS (G4). | 3.4 |
| RISK-4 | A stale `pa_{slug}` reference (after a WC rename/delete) could be loaded. | Reference validation against the live registry at save (3.2) and at Load (3.3) [2B-VERIFIED V3]. | 3.2 / 3.3 |
| RISK-5 | Duplicate hooks or asset-handle collisions with the 14 existing modules. | Unique `ani_gpa_` handles; regression check in every subphase; no changes to existing modules. | all |
| RISK-6 | Inadvertent introduction of a superseded contract (e.g. product meta, separate renderer). | §6 flagged list is a hard exclusion; acceptance criteria assert their absence. | all |

### 9.2 Open items (carried, not blocking Phase 3.0)

| # | Item | Status |
|---|------|--------|
| OI-1 | **ASCII comma as a value separator** | Deferred (D20). GPA must not assume either interpretation. Not a Phase 3 item. |
| OI-2 | **"View Attribute" shop-page button** | Deferred / out of scope (D36). May be re-proposed if a concrete requirement emerges. |
| OI-3 | **`minimal` / `boxed` visual specifications** | Must be defined and documented **before** final CSS (D42). This is a Phase 3.4 pre-gate (G4), not a Phase 3.0 deliverable. |
| OI-4 | **Group categories** | Not in scope for the initial version (D40). A future, separately-approved feature. |
| OI-5 | **Admin UI location / control method for the `table_style` setting** | **Unresolved — [OWNER-APPROVAL].** The approved architecture specifies the Theme Mod storage (`anishop_gpa_settings` → `table_style`, S1 §10.4, D39) but does **not** specify where an administrator selects/changes the style. The location and control method must be confirmed by the owner before the settings UI is implemented (3.1). The house pattern (Appearance → Anishop page) is the existing convention but is **not** an approved decision for GPA's setting. The Theme Mod architecture and all three styles are preserved. |
| OI-6 | **Product-ID source for the Load REST endpoint's `permission_callback`** | **Unresolved — [OWNER-APPROVAL].** The route `anishop/v1/gpa/groups/{id}/attributes` carries the group ID in `{id}`; the product ID is **not** in the route. The approved architecture requires `current_user_can( 'edit_product', $product_id )` (S1 §5.3/§13.1) but does not specify how `$product_id` is supplied. The product ID must be supplied by the product edit screen (the actual product being edited) and validated (exists + is a product) before the capability check. The exact transport (query parameter vs. request body) must be confirmed by the owner before 3.3 implementation. The permission check is **not** weakened. |

### 9.3 Explicitly NOT open (resolved)

- Q1 (group categories) — RESOLVED by D40.
- Q2 (group status policy) — RESOLVED by D41.
- Q3 (table-style visual design) — RESOLVED by D42 (CSS-only confirmed; specs pending as OI-3).
- Q4 (admin menu placement) — RESOLVED by D43.


---

## 10. Git workflow for Phase 3

Per the project Git rules (S3, S12 §4):

- **This roadmap (Phase 3.0)** is committed on the dedicated branch
  `chore/phase-3-0-implementation-roadmap` (created from the documentation baseline
  `fe9f370`). It is **not** merged into `main` without explicit owner authorization.
- **Each implementation subphase** (3.1–3.5) works on its own branch
  (`phase-3-1-{slug}`, `phase-3-2-{slug}`, …) created from the then-current approved
  baseline.
- A subphase branch merges into `main` **only** after the subphase is complete **and**
  owner-approved (gates G1–G5).
- Every subphase produces a dedicated report under `reports/`
  (`YYYY-MM-DD-{task-slug}.md`), committed and pushed with the subphase changes, listing
  all changed files.
- The child-theme snapshot at `snapshots/blocksy-child/` is refreshed whenever the live
  `blocksy-child` changes (i.e. after each implementation subphase that touches the theme).
- **Never** work on `main` directly; **never** force-push; **never** delete branches
  without authorization; **never** include unrelated changes in a task commit.

---

## 11. Definition of Done for Phase 3 (from ROADMAP §5)

Phase 3 is complete when all of the following hold (verified in 3.5 and re-confirmed in
Phase 4):

1. A fully functional GPA manager is available in the Blocksy child theme (admin +
   product metabox + native frontend).
2. All mandatory features (ROADMAP §1) work: group CRUD + ordering; product-level group
   loading (native row insertion, duplicate skip, multiple groups); uniform table styles
   (`default`/`minimal`/`boxed`); cross-cutting (error handling, responsive, RTL, minimal
   resources).
3. Desktop and mobile behavior is verified (mobile via real device in Phase 4).
4. RTL and Persian presentation work correctly.
5. No Blocksy Pro runtime dependency exists.
6. Existing child-theme modules remain functional.
7. Performance and security checks are completed.
8. The implementation is documented and maintainable.

---

*End of Phase 3.0 implementation roadmap. This document is a plan; it authorizes no
implementation. Phase 3.1 begins only after explicit owner authorization (gate G0).*

