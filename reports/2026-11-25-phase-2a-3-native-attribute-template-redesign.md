# Phase 2A.3 — Native Attribute Template Redesign (GPA)

## 1. Task Title

Perform a comprehensive, documentation-only redesign of the GPA architecture to the
**native-template model**: GPA groups are reusable templates of **Global WooCommerce
Attribute definitions**; loading a group adds **native** WooCommerce attribute rows; the
product's native attribute data is the **single source of truth**; and all attributes render
through the **standard WooCommerce/Blocksy Additional Information tab**.

## 2. Date

2026-11-25

## 3. Executive Summary

This task replaced the incompatible Phase 2A / 2A.1 / 2A.2 value-selection /
separate-renderer architecture with the owner-clarified **native-template model**. The work
is **documentation only**: no PHP/JS/CSS implementation, no database writes, no live
WordPress data changes, no fixture changes, no plugin installation.

The redesigned architecture was written into
`docs/architecture/phase-2a-architecture-specification.md` (rewritten in place as the
authoritative design), the decision records were updated in `.clinerules/decisions.md`
(new decisions **D28–D39**; affected previous decisions preserved and marked
Superseded/Obsolete/Modified), and the roadmap phase statuses were aligned in
`docs/PROJECT-ROADMAP.md`.

Key architectural changes:
- **GPA groups are templates of Global Attribute definitions** (ordered `pa_{slug}`
  references), not value-selection groups. [D28]
- **Loading a group adds native WooCommerce attribute rows** by reusing the native
  `woocommerce_add_attribute` row-insertion mechanism; no parallel data structure. [D29]
- **No persistent group assignment or provenance** — `_ani_gpa_assigned_groups` and
  `_ani_gpa_selected_values` are removed; no GPA-specific product metadata. [D30]
- **In-group ordering** sets the initial insertion sequence; the **final** order is
  controlled exclusively by native WooCommerce; global group ordering is not required. [D31]
- **Custom Attributes remain native** and are not group members. [D32]
- **Frontend rendering is native only** (Additional Information tab); no separate GPA
  renderer, tables, group headings, Blocksy custom layer, location setting, or
  native-table suppression. [D33]
- **Uniform table styles** (`default` / `minimal` / `boxed`) applied to all attributes via
  a recommended CSS-only mechanism. [D34]
- **Independent top-level admin menu** immediately after Products (runtime-computed
  position). [D35]
- **"View Attribute" button deferred / out of scope.** [D36]
- **UI-only toggle** (no persisted flag). [D37]
- **GPA controls in Product Data → Attributes.** [D38]
- **Theme Mod `anishop_gpa_settings`.** [D39]

The previous decisions D9, D10, D14, D16, D17, D18, D22, D27 are **Superseded**; D25 and
D26 are **Obsolete**; D24 is **Modified** (reassessed). D15, D19, D20, D21, D23 are
**Preserved**. The full supersession matrix and the Phase 2A.2 corrections applicability
assessment are in §7 and §8 below.

Phase 2B (runtime verification) and Phase 3 (implementation) remain **NOT STARTED / NOT
authorized**.

## 4. Objective and Scope

**Objective.** Replace incompatible architectural assumptions with an architecture that uses
WooCommerce's native product attribute system as the single source of truth, per the
owner's clarified product vision.

**Scope (documentation only):**
1. Rewrite `docs/architecture/phase-2a-architecture-specification.md` to describe the
   native-template model as the authoritative design.
2. Update `.clinerules/decisions.md` — record new decisions D28–D39; preserve and mark the
   affected previous decisions.
3. Update `docs/PROJECT-ROADMAP.md` phase statuses to reflect the verified Git/documentation
   state.
4. Create this completion report.
5. Commit and push on a dedicated branch.

**Out of scope:** any implementation (PHP/JS/CSS), any database or live WordPress data
change, any Phase 2B runtime verification, any Phase 3 work, any catalog cleanup or
delimiter normalization (D20, D21), any fixture change, any plugin installation.

## 5. Starting Branch and Commit

- **Starting branch:** `phase-2a-2-final-architecture-consistency` (the verified final
  Phase 2A.2 branch).
- **Starting HEAD (verified):** `d17caa3f645f70567c6cc0f53b158c5c8dadce78` — confirmed to
  match both the local branch tip and `origin/phase-2a-2-final-architecture-consistency`
  via `git rev-parse` before this task began.
- **Working tree at start:** clean (verified).
- **Dedicated branch created:** `phase-2a-3-native-attribute-template-redesign` (from the
  starting HEAD).
- **`main` (local and remote) at start:** `2b4b0902fbaee70519df20f1bcc63723e1d591ba` —
  **unchanged** throughout (no merge performed).

## 6. Changed Files

**Modified:**
- `docs/architecture/phase-2a-architecture-specification.md` — rewritten in place to the
  native-template model (the authoritative Phase 2A.3 design). All previous sections
  (three-tier value model, product-to-group data contract, selected-value contract,
  custom-attribute aggregation, GPA frontend renderer, Blocksy custom layer, native-table
  hide, location setting, old Phase 2B checklist) were replaced; the new specification
  covers the product vision, group/template concept, revised data model, group editor,
  product editor integration, Load operation, native editing, ordering, Custom Attribute
  handling, native frontend rendering, uniform table styles, WooCommerce/Blocksy
  integration, admin menu placement, security, performance/caching, compatibility, testing,
  the rebuilt Phase 2B checklist, open questions, and an evidence appendix.
- `.clinerules/decisions.md` — restructured into **Active decisions** (D1–D8, D11, D12,
  D13, D15, D19, D20, D21, D23, and new **D28–D39**) and a **Superseded / Obsolete
  decisions** table (D9, D10, D14, D16, D17, D18, D22, D24, D25, D26, D27 — preserved
  verbatim with status, replacing decision, and reason), plus an updated **Undecided /
  Open** section.
- `docs/PROJECT-ROADMAP.md` — updated the project status line; added Phase 0.1 and
  Phase 1.1.1 sections (both COMPLETE); restructured the Phase 2 section to show the
  sub-phase statuses (2A.1 COMPLETE, 2A.2 COMPLETE, 2A.3 IN PROGRESS, 2B NOT STARTED).

**Created:**
- `reports/2026-11-25-phase-2a-3-native-attribute-template-redesign.md` — this completion
  report.

**Deleted:** None.

## 7. Previous Architecture Decisions — Supersession Matrix

This matrix covers **every** previous decision affected by the clarified product vision.
Status values: **Preserved** (unchanged), **Modified** (partially changed), **Superseded**
(replaced by a new decision), **Obsolete** (the feature/contract no longer exists),
**Reassess** (requires further investigation). Historical decision text is preserved in
`.clinerules/decisions.md` (Superseded / Obsolete table).

| Previous Decision ID | Previous Decision / Contract | New Status | Replacement Decision ID | Reason for Status | New Architecture Section |
|---|---|---|---|---|---|
| D9 | Group value selection = presentation layer (renders selected values without modifying native data). | **Superseded** | D28, D29, D33 | GPA is no longer a presentation layer rendering *selected* values; it is a template manager adding **native** rows, with native rendering. The "do not corrupt native data" principle is preserved via D29. | Spec §1.1, §3.4, §9 |
| D10 | "View Attribute" shop-page button — replicate reference behavior. | **Superseded** | D36 | The clarified vision has no separate GPA frontend output, so there is no clear requirement for the button; deferred / out of scope. | Spec §18.3 |
| D14 | Dual attribute support — both Global and Custom participate in groups; renderer resolves each. | **Superseded** | D28, D32, D33, D34 | Groups contain **Global only** (D28); Custom stays **native**, not a group member (D32); no separate renderer (D33). The need to support + present both kinds is preserved via D32 + D33/D34. | Spec §2.2, §8, §9, §10 |
| D15 | Parent product is the primary attribute source; `variable` does not prove variations; never infer type from variation existence. | **Preserved** | — (still active) | The parent product's native attribute data remains authoritative; product type is never inferred from variation existence. Directly applicable to the native model. | Spec §3.4, §11.1 |
| D16 | Blocksy Custom Layer selected (B1) for GPA frontend rendering. | **Superseded** | D33 | No separate GPA frontend rendering; all attributes render natively in the Additional Information tab. The custom layer is not used. | Spec §9.2, §11.2 |
| D17 | Native table visible by default + optional hide setting + fallback. | **Superseded** | D33 | The native `shop_attributes` table is the **only** output and is **never** hidden; no `hide_native_table` setting, no independent GPA table. The "no destructive modification" principle is preserved via D29/D30. | Spec §9.2, §10.4 |
| D18 | View Attribute button deferred, architecturally isolated; investigate in Phase 2. | **Superseded** | D36 | The feature is deferred / out of scope under the clarified vision (no separate GPA frontend output). The "do not install the reference plugin on production" safety note remains good practice. | Spec §18.3 |
| D19 | `persian-woocommerce` compatibility must be tested. | **Preserved** | — (still active) | Compatibility must still be tested; the CSS-only style mechanism reduces the conflict surface but does not remove the testing requirement. | Spec §11.3, §15 |
| D20 | Delimiter normalization deferred (no comma/Persian-comma splitting). | **Preserved** | — (still active) | GPA still respects actual stored values and does not split/normalize; Custom Attribute values are parsed by native `wc_get_text_attributes()` only. | Spec §8 |
| D21 | No premature data cleanup; tolerate catalog anomalies. | **Preserved** | — (still active) | GPA still tolerates catalog anomalies and performs no cleanup; reference validation is non-destructive. | Spec §3.3, §15 |
| D22 | Selected architecture direction A1 + B1 (CPT + product meta + Blocksy custom layer). | **Superseded** | D28, D29, D33, D34 | Replaced by the **native-template model**: GPA owns only the group template, inserts **native** rows, and renders **natively**. Axis C (delimiter normalization) remains deferred (D20). | Spec §1.2 |
| D23 | Permanent test-fixture policy (13143 simple, 17028 variable). | **Preserved** | — (still active) | The fixture policy and isolation boundary remain in effect for Phase 2B/3/4 testing. | Spec §16 |
| D24 | Group status policy (U11): publish/draft assignable; publish-only rendering. | **Modified** | Reassessed (open item B2-34) | The old policy tied status to product **assignment** and GPA **rendering** — neither exists now. Reassessed proposed policy: **only `publish` groups are loadable**. Recorded as an open item for owner confirmation, not a carried-forward rule. | Spec §2.3, §18.1 (Q2) |
| D25 | Custom-attribute aggregation product-status policy (U12): publish-only products contribute values. | **Obsolete** | D28 | The custom-attribute aggregation subsystem no longer exists (groups contain Global Attributes only); there is nothing for this policy to govern. | Spec §2.2 |
| D26 | Stale selected-value lifecycle (U13): revalidate at render/save; retain in storage; read-only rendering. | **Obsolete** | D28, D30, D33 | There are **no** per-product selected values now (values managed natively; `_ani_gpa_selected_values` removed). The remaining stale-**reference** concern is handled by the new reference-integrity contract (spec §3.3). | Spec §3.3 |
| D27 | Empty-group behavior: assignable with no values; no empty frontend tables; native-table hide fallback. | **Superseded** | D33 (and spec §4.4) | A group is a **template**, not a frontend table, so the "empty group renders nothing / does not suppress the native table" rules no longer apply. Preserved analogue: a group with **no attributes** is valid and inserts **no** rows on load. | Spec §4.4, §9.2 |

**Decisions not affected** (process/environment, unchanged): D1, D2, D3, D4, D5, D6, D7,
D8, D11, D12, D13.

## 8. Phase 2A.2 Corrections — Applicability Assessment

Phase 2A.2 introduced several corrections to the previous architecture. This section
classifies **every** substantive Phase 2A.2 correction under the new native-template model.
Categories: **A. Preserved** (still applicable; preserved and identified where enforced),
**B. Superseded / Obsolete** (addressed a feature/contract that no longer exists),
**C. Reassess** (may still matter; needs further investigation).

A key distinction is applied throughout: a correction may have supported a now-obsolete
feature while its **underlying security/integrity principle** remains relevant. Each item
below first determines whether the new architecture still stores or processes data that
requires the same protection.

| # | Phase 2A.2 Correction | Category | Assessment | Where Enforced / Unresolved Question |
|---|----------------------|----------|------------|--------------------------------------|
| 1 | **Safe unserialization** — `unserialize( …, [ 'allowed_classes' => false ] )` + type + structure validation; `maybe_unserialize()` does **not** restrict object instantiation (verified WP 7.1.5, `wp-includes/functions.php:653`). Originally targeted the raw-SQL `_product_attributes` aggregation path. | **A. Preserved** (re-scoped) | The original target (raw-SQL `_product_attributes` aggregation) is **obsolete** — that path no longer exists. **However**, the new architecture **still stores serialized data**: the group meta `_ani_gpa_group_attributes` (a serialized array of `pa_{slug}` strings). When GPA reads it, it must treat it as untrusted structured data with the same protection (no object instantiation, `allowed_classes => false`, type + structure validation, graceful handling). The security principle is therefore **preserved and re-scoped** to the group reference array. | Enforced in spec **§13.3** (Safe handling of serialized/structured data). No unresolved question. |
| 2 | **Empty-group behavior** (D27) — a group assigned with no selected values is administratively valid but renders nothing and does not count as renderable GPA content; no empty tables/headings. | **B. Superseded** (reinterpreted) | The old model treated a group as a **frontend table** with selected values. In the new model a group is a **template of attribute definitions**, not a frontend table, so the "empty group renders nothing / does not suppress the native table" rules no longer apply. The **preserved analogue**: a group with **no attributes** is a valid administrative state and, when loaded, inserts **no** rows. | Reinterpreted in spec **§4.4** (Group save and empty-group state). No unresolved question. |
| 3 | **Empty-value behavior** — handling of empty/stale selected values at render and save time (no empty rows; stale values skipped). | **B. Superseded / Obsolete** (GPA rule) + **verified native fact** | The GPA **selected-value** empty/stale rules are **obsolete** — there are no per-product selected values (values are managed natively). A related behavior **does** exist but is **native WooCommerce** behavior, not a GPA contract: the native save **discards attribute rows with no values** (`prepare_attributes` `empty( $options )` skip). This is documented as a **verified native fact** that GPA relies on (loaded rows persist only after values are selected), not as a GPA rule. | Native discard-empty behavior documented in spec **§6.2** (verified) and **§19.3**. Runtime re-confirmation is a Phase 2B item (B2-19). |

| 4 | **U11: group publication/status behavior** (D24) — `publish`/`draft` groups assignable; `publish`-only rendering. | **C. Reassess** | The old policy tied status to (a) product **assignment** and (b) GPA **frontend rendering** — **neither exists** in the new model (no `_ani_gpa_assigned_groups`, no GPA renderer). The group CPT still uses standard `publish`/`draft` status. The **reassessed proposed policy** is that **only `publish` groups are listed in the product-edit picker and may be loaded**. This is **not** a carried-forward rule; it is recorded as an open item for owner confirmation. | Unresolved question: confirm the "only `publish` groups are loadable" policy. Recorded as open item **B2-34** / spec **§18.1 (Q2)**; spec **§2.3**. |
| 5 | **U12: custom-attribute value aggregation + product-status scope** (D25) — only `publish` products contribute custom-attribute values to the store-wide vocabulary (status filter on `wp_posts` in the JOIN). | **B. Obsolete** | The custom-attribute aggregation subsystem (raw SQL, transient cache, product-status scope) **no longer exists** because groups contain **Global Attributes only** and draw no custom-attribute vocabulary. There is nothing for this policy to govern. | Removed. No unresolved question. (Spec **§2.2**.) |
| 6 | **U13: stale selected-value lifecycle** (D26) — revalidate at render/save; retain stale in storage; read-only rendering; no auto-cleanup. | **B. Obsolete** (selected-value lifecycle) + **A. Preserved** (re-validation principle) | The **selected-value** lifecycle is **obsolete** — there are no per-product selected values. The **underlying principle** (re-validate references against current state; non-destructive; no auto-cleanup) **remains relevant** and is **preserved** in the new **reference-integrity contract**: a group's Global Attribute reference can become stale after a rename/delete, and GPA re-validates at group save and at load, skipping stale references non-destructively. | Re-validation principle enforced in spec **§3.3** (Reference integrity and validation). No unresolved question. |
| 7 | **Associated storage / validation / security / data-integrity requirements** — nonces + capabilities on every write; output escaping; input sanitization; strict Boolean parsing; no direct DB writes; non-destructive rendering. | **A. Preserved** (general principles) / **B. Obsolete** (feature-specific contracts) | The **general** security and integrity principles (nonces + capabilities, output escaping, input sanitization, strict Boolean parsing, no direct DB writes, non-destructive behavior) are **preserved** and apply to the new architecture's write paths (group save, Load REST endpoint, settings save). The **feature-specific** contracts tied to removed features (product-meta save handler for `_ani_gpa_assigned_groups`/`_ani_gpa_selected_values`, the render-time re-validation pipeline, the aggregation query guards) are **obsolete** because those features no longer exist. | General principles enforced in spec **§13** (Validation and security). Feature-specific contracts removed. No unresolved question. |

**Summary:** No generally-applicable security or data-integrity safeguard was discarded
without justification. The safe-unserialization principle (item 1) and the re-validation /
non-destructive principle (item 6) are **preserved** and re-scoped to the data the new
architecture actually stores and processes (the group reference array). The feature-specific
contracts tied to removed features (custom-attribute aggregation, selected values, GPA
frontend rendering) are **superseded/obsolete**. The group-status policy (item 4) is
**reassessed** and recorded as an open item.

## 9. Data Model Changes

**Previous model (superseded):**
- CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat`.
- Group meta `_ani_gpa_group_values` (ordered **value references** — attribute + value +
  value_id).
- Product meta `_ani_gpa_assigned_groups` (ordered group IDs) + `_ani_gpa_selected_values`
  (per-group selected value references).
- Three-tier A/B/C value model (group vocabulary / product values / selected values).
- Custom-attribute aggregation (raw SQL + transient).

**New model (authoritative):**
- CPT `ani_gpa_group` (group template). **No** `ani_gpa_group_cat` taxonomy in the core
  (group categories are an open item, §18.1 Q1).
- Group meta **`_ani_gpa_group_attributes`** — a serialized array of **`pa_{slug}` strings**
  (ordered Global Attribute references). This replaces `_ani_gpa_group_values`.
- **No** product meta. `_ani_gpa_assigned_groups` and `_ani_gpa_selected_values` are
  **removed**. The product's native `_product_attributes` is the single source of truth.
- **No** three-tier value model. **No** custom-attribute aggregation.

**Reference key:** the Global Attribute **taxonomy name** (`pa_{slug}`) is the reference
key (not the attribute ID, not terms). It is the exact value the native
`woocommerce_add_attribute` action expects, is directly validatable
(`taxonomy_exists` + `wc_attribute_taxonomy_id_by_name`), and the label/ID are derived at
display time. See spec §3.2.

**Integrity:** references can become stale after an attribute rename/delete (verified WC
behavior — the taxonomy no longer exists). Validation at group save and at load; stale
references are skipped/flagged non-destructively. See spec §3.3.

## 10. Frontend Architecture Changes

**Previous (superseded):** GPA rendered its own grouped tables via a Blocksy custom layer
(B1), with group headings, a configurable location, optional native-table suppression, and
a separate frontend renderer (`render.php`).

**New (authoritative):**
- **No** separate GPA frontend renderer, tables, group headings, group-category headings,
  or grouping by source group.
- **No** custom Blocksy product layer; **no** `blocksy:woocommerce:product:custom:layer`;
  **no** `blocksy:woocommerce:product-single:layout` filter.
- **No** configurable GPA frontend location; **no** native-table suppression.
- **All** attributes (GPA-loaded Global, manual Global, manual Custom) render through the
  **standard WooCommerce/Blocksy Additional Information tab** via the verified native
  render chain (`wc_display_product_attributes` → `product-attributes.php` →
  `<table class="woocommerce-product-attributes shop_attributes">`).
- The **only** GPA frontend footprint is the **uniform table style** (CSS-only, recommended):
  a `body_class` filter (single product pages, non-`default` style) + a conditional
  stylesheet. `default` enqueues nothing.
- The displayed order follows the product's **native** attribute order.

See spec §9 (Frontend rendering) and §10 (Uniform table presentation).

## 11. Product Editor Integration Design

The GPA interface is integrated **inside** WooCommerce **Product Data → Attributes**
([DECISION D38]), coexisting with the native interface.

- **Placement:** rendered via `woocommerce_product_options_attributes`, which fires at the
  **end** of the `#product_attributes` panel (after the native rows + "Save attributes"
  button), inside the Attributes tab. [WC 11.3.1 SOURCE — html-product-data-attributes.php:69]
- **UI-only toggle:** "Create group for this product" reveals the group-select + Load
  controls; it is **not** a persisted flag. [D37]
- **Group select + Load:** a `<select>` of `publish` groups + a Load button. Load fetches
  the group's ordered `pa_{slug}` list (nonce-protected REST, `edit_product` cap) and, for
  each attribute, calls the **native** `woocommerce_add_attribute` AJAX action (reusing WC's
  `add-attribute` nonce) to obtain the native row HTML, appends it to
  `#product_attributes .product_attributes`, re-indexes, initializes select controls, and
  refreshes the save button. [D29]
- **Native insertion:** the loaded rows are **structurally identical** to natively-added
  Global Attribute rows (same markup, hidden `attribute_names[i]` / `attribute_position[i]`,
  `attribute_values[i][]` term multiselect). No parallel data structure. [D29]
- **Duplicate handling:** a group attribute whose `pa_{slug}` row already exists is
  **skipped** (not duplicated); skipped/stale attributes are reported. Mirrors the native
  UI's `selectedAttributes` disable behavior. [REQ]
- **Multiple groups:** each Load appends its attributes (subject to duplicate handling).
  [D29]
- **Native coexistence:** loaded attributes are edited/saved with **native** controls only
  (select terms, add values, remove, reorder, manual add Global/Custom). GPA does not
  intercept or modify the native save lifecycle. [D29]
- **Ordering:** the group's order sets the **initial** insertion sequence; the **final**
  order is controlled exclusively by the native editor. [D31]
- **Save behavior (verified native fact):** the native save **discards** attribute rows with
  no values (`prepare_attributes` `empty( $options )` skip). Loaded rows persist **only**
  after the admin selects values and saves. [WC 11.3.1 SOURCE]
- **Save Attributes button:** the enable/disable behavior when a loaded row has no value is
  an **unverified** technical question (static source evidence exists); the architecture
  does **not** depend on it. Phase 2B item (B2-20). [2B-VERIFY]

See spec §5 (Product editor integration) and §6 (Native WooCommerce attribute editing).

## 12. Technical Evidence

All evidence was gathered on **2026-11-25** by **static source inspection** of the installed
WordPress 7.1.5 / WooCommerce 11.3.1 / Blocksy 2.1.62 (read-only). No live database writes,
no site changes, no fixture changes. Full detail is in the specification's Evidence Appendix
(§19). Key items:

| Claim | Source / location | Verification type |
|-------|-------------------|-------------------|
| Global Attribute registry APIs (`wc_get_attribute_taxonomies`, `wc_attribute_taxonomy_name`, `wc_attribute_taxonomy_id_by_name`, `wc_get_attribute`) | `woocommerce/includes/wc-attribute-functions.php:55,148,186,199,509` | Static source inspection [WC 11.3.1 SOURCE] |
| Attribute **rename** migrates the taxonomy + product `_product_attributes` + variation meta; `pa_{old}` no longer exists | `wc-attribute-functions.php:656-714` | Static source inspection [WC 11.3.1 SOURCE] |
| Attribute **delete** removes the registry row, all terms, and unregisters the taxonomy | `wc-attribute-functions.php:777` | Static source inspection [WC 11.3.1 SOURCE] |
| Registry cache invalidation on create/update/delete | `wc-attribute-functions.php` (`delete_transient` + `invalidate_cache_group`) | Static source inspection [WC 11.3.1 SOURCE] |
| `woocommerce_product_options_attributes` fires at the end of the `#product_attributes` panel (Attributes tab) | `includes/admin/meta-boxes/views/html-product-data-attributes.php:69` | Static source inspection [WC 11.3.1 SOURCE] |
| Native row structure (`data-taxonomy`, hidden `attribute_names[i]`/`attribute_position[i]`, `attribute_values[i][]`, `checkbox`-classed checkboxes) | `html-product-attribute.php`, `html-product-attribute-inner.php` | Static source inspection [WC 11.3.1 SOURCE] |
| Native save lifecycle (`woocommerce_process_product_meta` → `save()` → `prepare_attributes()` → `set_attributes()` → `save()`) | `class-wc-meta-box-product-data.php:333,339,234` | Static source inspection [WC 11.3.1 SOURCE] |
| **Native save discards attribute rows with no values** (`if ( empty( $options ) ) { continue; }`) | `class-wc-meta-box-product-data.php` (`prepare_attributes`) | Static source inspection [WC 11.3.1 SOURCE] |
| Native "Add global attribute" reuses the `woocommerce_add_attribute` AJAX action (nonce `add-attribute`, cap `edit_products`) | `assets/js/admin/meta-boxes-product.js:692`, `includes/class-wc-ajax.php:779` | Static source inspection [WC 11.3.1 SOURCE] |
| Native duplicate prevention (`selectedAttributes` disables already-added Global Attributes) | `meta-boxes-product.js:583-597` | Static source inspection [WC 11.3.1 SOURCE] |
| "Save attributes" button enable/disable logic (`maybe_disable_save_button` / `is_attribute_or_variation_empty`) | `assets/js/admin/meta-boxes.js:5,33` | Static source inspection [WC 11.3.1 SOURCE] — **runtime behavior UNVERIFIED** (B2-20) |
| Frontend render chain (`wc_display_product_attributes` → `product-attributes.php` → `<table class="woocommerce-product-attributes shop_attributes">`) | `wc-template-functions.php:4261`, `templates/single-product/product-attributes.php` (v9.3.0) | Static source inspection [WC 11.3.1 SOURCE] + previously LIVE-VERIFIED 2026-11-25 |
| Blocksy renders the Additional Information tab via `woocommerce_product_tabs` → callback → `woocommerce_product_additional_information()` | `blocksy/inc/components/woocommerce/single/tabs.php` | Static source inspection [STATIC CODE EVIDENCE] + previously LIVE-VERIFIED 2026-11-25 |
| Admin menu ordering: `$_wp_last_object_menu = 25`; CPT position `is_int( menu_position ) ? menu_position : ++$_wp_last_object_menu`; Products CPT sets **no** `menu_position` (auto-assigned); WooCommerce menu at `55.5` | `wp-admin/menu.php:121,140-142`, `class-wc-post-types.php:367-421`, `class-wc-admin-menus.php:97` | Static source inspection [WP CORE SOURCE] [WC 11.3.1 SOURCE] |
| `maybe_unserialize()` does not restrict object instantiation (default `allowed_classes`) | `wp-includes/functions.php:653` (WP 7.1.5) | Static source inspection [WP CORE SOURCE] (Phase 2A.2, preserved) |

**Distinguished:** verified facts (above) vs. assumptions (none presented as fact) vs.
unverified implementation proposals (the CSS-only table-style mechanism is a [RECOMMEND];
the Save Attributes button runtime behavior is [2B-VERIFY]) vs. Phase 2B verification
requirements (§13 below).

## 13. Open Questions

These are the **owner-input** open questions carried into Phase 2B (mirrors spec §18.1).
None is a hard blocker for the documentation task; all are recorded as non-blocking.

| # | Question | Status |
|---|----------|--------|
| Q1 | **Group categories** — are they in scope? (ROADMAP §1.2 lists them; the clarified Phase 2A.3 vision does not mention them.) The core architecture is independent of them. | [UNRESOLVED] (B2-33) |
| Q2 | **Group status policy** — confirm "only `publish` groups are loadable" (reassessed from D24/U11; the old publish/draft-assignable + publish-only-render policy no longer applies because there is no assignment or GPA rendering). | [UNRESOLVED] (B2-34) |
| Q3 | **Table-style visual design** — finalize the `minimal` / `boxed` CSS within the house palette. | [OPEN] (B2-35) |
| Q4 | **Admin menu fractional offset** — confirm the offset after the Phase 2B collision check. | [OPEN] (B2-36) |

**Explicitly NOT open (resolved by the redesign):** the "View Attribute" shop-page button
(deferred / out of scope, D36); the three-tier A/B/C value model; `_ani_gpa_assigned_groups`;
`_ani_gpa_selected_values`; custom-attribute aggregation; the GPA frontend renderer;
native-table suppression; the Blocksy custom layer; and the GPA frontend location setting —
all **superseded/obsolete**, not open questions (see `.clinerules/decisions.md`).

## 14. Phase 2B Verification Requirements

The following must be verified at runtime in **Phase 2B** before Phase 3 implementation
(all currently **pending**; mirrors spec §18.2). Phase 2B and Phase 3 are **NOT STARTED /
NOT authorized**.

1. **Load row insertion** — the exact `i` index computation for sequential multi-row
   insertion via the native `woocommerce_add_attribute` action, and select2
   re-initialization of the inserted rows (B2-14, B2-18).
2. **Save Attributes button** — the enable/disable behavior when a loaded row has no value,
   and whether GPA-inserted rows trigger `maybe_disable_save_button` (B2-20). The
   architecture does **not** depend on this.
3. **Native save discard** — confirm loaded rows persist only after values are selected
   (B2-19).
4. **Admin menu position** — the live Products position and that the chosen offset places
   GPA immediately after it with no collision (B2-2, B2-3, B2-36).
5. **CSS-only table style** — the exact selectors and body-class scoping against live
   Blocksy 2.1.62 + WC 11.3.1 markup (B2-24, B2-26).
6. **`persian-woocommerce` compatibility** — runtime coexistence of the CSS-only style +
   native render (B2-27, D19).
7. **WooCommerce 11.3.1 re-confirmation** — the depended-on behaviors
   (`woocommerce_add_attribute`, `prepare_attributes`, `shop_attributes` template) (B2-28).
8. **Blocksy Free compatibility** — native Additional Information tab rendering (B2-29).
9. **Native coexistence** — reordering, remove, and manual add (Global + Custom) coexist
   with loaded attributes (B2-21).

The full itemized checklist (B2-1 … B2-36) is in spec §17.

## 15. Roadmap Status Changes

`docs/PROJECT-ROADMAP.md` was updated to reflect the verified Git/documentation state:

| Phase | Status before | Status after |
|-------|---------------|--------------|
| Phase 0 | COMPLETE | COMPLETE (unchanged) |
| Phase 0.1 | (not separately listed) | **COMPLETE** (added) |
| Phase 1 | COMPLETE | COMPLETE (unchanged) |
| Phase 1.1 | COMPLETE | COMPLETE (unchanged) |
| Phase 1.1.1 | (not separately listed) | **COMPLETE** (added) |
| Phase 2A.1 | COMPLETE | COMPLETE (unchanged) |
| Phase 2A.2 | COMPLETE | COMPLETE (unchanged) |
| **Phase 2A.3** | (not listed) | **IN PROGRESS** (this task) |
| Phase 2B | NOT STARTED | NOT STARTED (unchanged) |
| Phase 3 | NOT STARTED | NOT STARTED (unchanged) |

The project status line was updated to reflect that Phase 2A.3 (native-attribute template
redesign) is the current in-progress phase. No phase was advanced beyond its verified state;
Phase 2B and Phase 3 remain **NOT STARTED / NOT authorized**.

## 16. No Implementation / No Data Change Confirmation

This task was **documentation only**. The following are explicitly confirmed:

- **No implementation:** no PHP, JS, or CSS was written or modified. No module files were
  created or changed under `blocksy-child` or anywhere in the WordPress installation.
- **No database writes:** no WP-CLI mutating command was run; no option, post, term, meta,
  or setting was created, updated, or deleted.
- **No live WordPress change:** no theme, plugin, or site configuration was changed; no
  plugin was installed, activated, or deactivated; no theme was activated.
- **No fixture change:** the designated test fixtures (13143, 17028) and their backups were
  **not** touched.
- **No catalog cleanup / delimiter normalization:** per D20/D21, no attribute values were
  split, normalized, or migrated.
- **No Blocksy Pro dependency:** none introduced (none exists).
- **No merge:** `main` (local and remote) was **not** merged into or advanced; it remains at
  `2b4b0902fbaee70519df20f1bcc63723e1d591ba`.

All filesystem access was confined to the two authorized paths (the workspace and the target
WordPress installation, read-only).

## 17. QA Findings — Obsolete Term Search Results

A repository-wide search was run for obsolete active terms (excluding `reports/`, which is
the intended historical/supersession context). The terms searched: `_ani_gpa_assigned_groups`,
`_ani_gpa_selected_values`, `_ani_gpa_group_values`, "three-tier A/B/C value model",
"Custom Attribute aggregation", "selected product attribute values", "GPA frontend tables",
"GPA group headings", "native table suppression", "Blocksy custom layer", "GPA frontend
location", "product-specific GPA rendering caches".

**Result:** In the **in-scope** files (`docs/architecture/phase-2a-architecture-specification.md`,
`.clinerules/decisions.md`, `docs/PROJECT-ROADMAP.md`), every reference to an obsolete term
appears **only in a historical / supersession / "removed" context** (e.g. the spec's
"Removed from the active architecture" note, the decisions' Superseded/Obsolete table, the
roadmap's historical Phase 2A.1 description). No obsolete term is asserted as an **active
requirement** in those files.

**However**, the search surfaced **active references to the old architecture in files that
are outside this task's assigned scope**. Per the project's scope-control rules, these are
**documented here and require owner authorization** to fix; they were **not** modified in
this task.

### 17.1 Discovered active references (require authorization — NOT modified)

| # | File | Line(s) | Obsolete reference | In assigned scope? | Assessment |
|---|------|---------|--------------------|--------------------|------------|
| 1 | `.clinerules/project-rules.md` | 28–29 | "use CPT `ani_gpa_group` **and `ani_gpa_group_cat` (taxonomy)**"; "Product meta: **`_ani_gpa_assigned_groups`** (array) and **`_ani_gpa_selected_values`** (associative array)" | **No** | **Active rule** contradicting D28 (no `ani_gpa_group_cat` in core) and D30 (product meta removed). Needs update to the native-template data model. |
| 2 | `.clinerules/memory-bank.md` | 24–26 | "Taxonomy: `ani_gpa_group_cat`"; "Product meta: `_ani_gpa_assigned_groups` (array), `_ani_gpa_selected_values` (associative array)" | **No** | **Active data-model** description contradicting D28/D30. Needs update. |
| 3 | `.clinerules/memory-bank.md` | 48 | "Architecture direction (selected, not implemented): **A1 + B1 (Blocksy custom layer)** … See `decisions.md` D22." | **No** | **Stale "Current State"** — D22 is now **Superseded** by D28/D29/D33/D34. Needs update to the native-template model. |
| 4 | `.clinerules/memory-bank.md` | 47, 50–51 | "Phase 2 **NOT STARTED**"; "main at `2b4b090`; Phase 1 + Phase 1.1 … not merged"; "Next: … Phase 2 architecture design" | **No** | **Stale "Current State"** — does not reflect Phase 2A.1/2A.2/2A.3. Needs update. |
| 5 | `docs/PROJECT-ROADMAP.md` | 254–257 | Phase 4 "Required tests": "**value selection**, per-product group sorting"; "shop page **'View Attribute' button**" | **Yes** (file) — but this specific change was **not** in the assigned scope (which covered phase statuses only) | Phase 4 test list references **superseded** functionality (value selection, per-product group sorting — D31; View Attribute button — D36). Needs update to the native-template test set. |

### 17.2 Historical references (acceptable — no action needed)

- `docs/research/*.md` — Phase 1 research documents (historical).
- `docs/woocommerce-attributes-dev-notes.md` — Phase 1 dev notes (historical).
- `docs/PROJECT-ROADMAP.md:193–196` — historical Phase 2A.1 description.
- `.clinerules/technical-conventions.md:17,34` — the Blocksy custom-layer hooks are listed
  as used by **existing** child-theme modules (e.g. `anishop-inline-variations`
  key-features), **not** by GPA. This is correct and is **not** an inconsistency.
- `docs/architecture/phase-2a-architecture-specification.md` and `.clinerules/decisions.md`
  — all obsolete-term references are in supersession/"removed" context (verified).

**Recommendation:** authorize a follow-up (chore) task to update items 1–5 above so the
permanent rules/memory and the Phase 4 test list reflect the native-template model. This
task intentionally did **not** modify those files to respect the assigned scope.

## 18. Git Information

- **Starting branch:** `phase-2a-2-final-architecture-consistency` (the verified final
  Phase 2A.2 branch).
- **Starting HEAD (verified):** `d17caa3f645f70567c6cc0f53b158c5c8dadce78` — confirmed to
  match both the local branch tip and `origin/phase-2a-2-final-architecture-consistency`
  via `git rev-parse` before this task began.
- **Working tree at start:** clean (verified).
- **Dedicated branch created:** `phase-2a-3-native-attribute-template-redesign` (from the
  starting HEAD).
- **`main` (local and remote) at start and end:** `2b4b0902fbaee70519df20f1bcc63723e1d591ba`
  — **unchanged** throughout (no merge performed).

### 18.1 Commits

- **Work commit SHA:** `80ad6c24b0dc67c4ae57907e2f2f1126e58b58b9` — the commit containing all
  substantive changes (rewritten specification, updated decisions, updated roadmap, and this
  report).
- **Metadata commit SHA:** the follow-up commit that records the work-commit SHA and the
  push/working-tree verification in this section (the branch HEAD after this task).

> The work commit SHA is the commit containing all substantive changes; the metadata commit
> is the follow-up commit containing this final report. Both are on
> `phase-2a-3-native-attribute-template-redesign`.

### 18.2 Push and Working-Tree Verification

- **Remote branch:** `origin/phase-2a-3-native-attribute-template-redesign` — pushed and
  verified via `git ls-remote origin`.
- **Local HEAD == Remote HEAD:** verified after the final push — `git rev-parse HEAD` and
  `git ls-remote origin phase-2a-3-native-attribute-template-redesign` resolve to the same
  commit (the branch is fully pushed and in sync). The work commit is
  `80ad6c24b0dc67c4ae57907e2f2f1126e58b58b9`; the branch HEAD is the follow-up commit that
  records this verification.
- **Working tree:** clean (verified after commit — `git status --short` empty).
- **`main` unchanged:** local and remote `main` both remain at
  `2b4b0902fbaee70519df20f1bcc63723e1d591ba` (verified via `git rev-parse main` and
  `git ls-remote origin main`).

## 19. Changed / Verified / Not Verified / Risks

### Changed
- `docs/architecture/phase-2a-architecture-specification.md` — rewritten to the
  native-template model (authoritative Phase 2A.3 design).
- `.clinerules/decisions.md` — restructured; new decisions D28–D39; affected previous
  decisions preserved and marked Superseded/Obsolete/Modified.
- `docs/PROJECT-ROADMAP.md` — phase statuses aligned (2A.1/2A.2 COMPLETE, 2A.3 IN PROGRESS,
  2B NOT STARTED); Phase 0.1 and 1.1.1 added.
- `reports/2026-11-25-phase-2a-3-native-attribute-template-redesign.md` — this report
  (created).

### Verified
- Starting Git state (branch, HEAD, clean tree, `main` unchanged) via `git rev-parse` /
  `git status`.
- All obsolete-term references in the **in-scope** files are in historical/supersession
  context only (QA search, §17).
- Consistency across the specification, `.clinerules/decisions.md`,
  `docs/PROJECT-ROADMAP.md`, and this report (cross-checked).
- All technical evidence is static source inspection of the installed WP 7.1.5 / WC 11.3.1 /
  Blocksy 2.1.62 (read-only); no runtime claims made.

### Not Verified (deferred to Phase 2B)
- All runtime behaviors in the Phase 2B checklist (B2-1 … B2-36), including Load row
  insertion index computation, Save Attributes button behavior, admin menu live position,
  CSS-only table-style selectors, and `persian-woocommerce` runtime compatibility.
- The discovered out-of-scope active references (§17.1) were **not** modified and are not
  verified as fixed (they require owner authorization).

### Risks & Open Questions
- **Out-of-scope stale references** (§17.1): `.clinerules/project-rules.md` (lines 28–29),
  `.clinerules/memory-bank.md` (lines 24–26, 47–51), and `docs/PROJECT-ROADMAP.md` Phase 4
  test list (lines 254–257) still reference the old architecture. These were **not**
  modified (outside the assigned scope) and **require owner authorization** to update.
- **Owner-input open questions** (§13): group categories (Q1), group status policy (Q2),
  table-style visual design (Q3), admin menu fractional offset (Q4). None is a hard
  blocker for the documentation task.
- **No implementation risk introduced:** this task changed documentation only; no code,
  data, or live-site change occurred.

## 20. End-of-Task Stop

Work has stopped after completing the Phase 2A.3 documentation redesign, committing and
pushing the changes on `phase-2a-3-native-attribute-template-redesign`, and verifying the
final Git state. **Phase 2B and Phase 3 were NOT started** (not authorized). Awaiting the
project owner's next instruction.
