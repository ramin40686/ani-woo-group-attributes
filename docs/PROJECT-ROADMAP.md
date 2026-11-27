# Project Roadmap

Advanced WooCommerce Product Group Attributes manager in the free version of the Blocksy WordPress theme

Project status: **Phase 0, 0.1, 1, 1.1, 1.1.1, 2A.1, 2A.2, 2A.3 complete — Phase 2B and Phase 3 NOT STARTED**
Project type: WordPress / Blocksy / WooCommerce
Target website: Anishop
Target URL: http://anishop.local

⸻

## 1. Project Objective

Implement a complete, lightweight, maintainable WooCommerce **Product Group Attributes manager** in the free version of the Blocksy WordPress theme.

The final functionality should provide an experience comparable to the official **WooCommerce Group Attributes** plugin (reference package in `docs/references/`).

> **Phase 2A.3 clarification (2026-11-25 — authoritative):** GPA is a **reusable Global WooCommerce Attribute Template Manager**. A GPA group is a template of **Global Attribute definitions** (not values); loading a group inserts those definitions into the product's **native** WooCommerce attribute collection; the product's native attribute data is the **single source of truth**; and **all** product attributes (GPA-loaded, manual Global, and Custom) are rendered through the **standard WooCommerce/Blocksy Additional Information tab**. The original reference-plugin-based feature list below was superseded by this clarification (decisions D28–D39; `docs/architecture/phase-2a-architecture-specification.md`).

The module integrates with WooCommerce product administration (Product Data → Attributes) and the standard WooCommerce/Blocksy frontend product presentation (the Additional Information tab on the single product page), rendered inside the Blocksy child theme. GPA introduces no separate frontend output.

### Required functionality

1. **Group attribute management (admin)** — submenu under WooCommerce Products, immediately after Attributes (label: **گروه ویژگی‌های آنی**):
   - Create, edit, delete attribute groups.
   - Name the attribute group and select the existing **Global Attribute definitions** to include in the group.
   - Drag-and-drop the group's attributes to set their order (defines the initial insertion order when the group is loaded).
   - List and manage all created group attributes.
2. **Group attribute categories (admin):** [RESOLVED — D40, 2026-11-27] — Not in scope for the initial version. No `ani_gpa_group_cat` taxonomy. Groups remain a flat collection.
3. **Product-level group loading (product edit screen)** — Product Data → Attributes:
   - UI-only "Create group for this product" toggle (reveals the group select + Load controls; no persisted flag).
   - Group select + Load: loading a group inserts its Global Attribute definitions into the product's native attribute collection (native `woocommerce_add_attribute` row insertion).
   - Multiple groups may be loaded into the same product (duplicate attributes are skipped).
   - After loading, the admin uses **native** WooCommerce controls to select terms/values, edit, remove, add (Global or Custom), and reorder attributes.
4. **General settings** — Theme Mod `anishop_gpa_settings`:
   - Table style: `default` / `minimal` / `boxed` — applied uniformly to **all** product attributes (Global and Custom, GPA-loaded and manual).
   - No frontend location setting (fixed to the native Additional Information tab); no "View Attribute" shop-page button (deferred / out of scope, D36).
5. **Cross-cutting requirements:**
   - Error handling.
   - Responsive desktop and mobile behavior.
   - Persian RTL support.
   - Minimal resource consumption.

### Core principles

1. Prefer reusing compatible existing Blocksy Free functionality.
2. Do not depend on Blocksy Pro at runtime.
3. Do not modify Blocksy core files.
4. Keep the implementation isolated in the child theme.
5. Avoid unnecessary plugins and JavaScript frameworks.
6. Prioritize performance, maintainability, and compatibility.
7. Do not implement anything before its architecture has been investigated and approved.

⸻

## 2. Project Environment

Independent workspace: `/home/ramin/Downloads/woo-group-attributes/`

WordPress website: `http://anishop.local`

WordPress installation directory: `/home/ramin/Local Sites/anishop/app/public/`

Blocksy child theme: `/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/`

Reference package:
- `/home/ramin/Downloads/woo-group-attributes/docs/references/group-attributes-reference.md`
- `/home/ramin/Downloads/woo-group-attributes/docs/references/WooCommerce Group Product Attributes Plugin.pdf`
- `/home/ramin/Downloads/woo-group-attributes/docs/references/screenshots/`

⸻

## 3. Development Phases

### Phase 0 – Independent Workspace

Status: **COMPLETE (2026-11-15/16)**

Objective: Create a clean, self-contained Cline workspace for this project.

Tasks:

* Inspect and initialize the existing Cline Memory Bank.
* Inspect applicable Cline rules.
* Identify relevant project knowledge and development conventions.
* Extract only information relevant to this project.
* Summarize large files instead of copying them.
* Exclude unrelated projects, historical discussions, obsolete instructions, and unnecessary files.
* Create a project-specific Memory Bank.
* Preserve necessary reference materials.

⸻

### Phase 0.1 – Workflow Standardization

Status: **COMPLETE (2026-11-23)** — branch `chore/project-workflow-standardization`.

Objective: Standardize the project's Git workflow, reporting, and execution rules before
Phase 1.

Tasks (all completed):

* Establish the mandatory project execution rules (owner authorization, task scope control,
  branch/Git management, reporting, commit/push, end-of-task stop condition).
* Standardize the per-phase branch model (`phase-{N}-{slug}`) and the `reports/` +
  `snapshots/blocksy-child/` repository conventions.
* Produce the completion report
  `reports/2026-11-23-phase-0-1-workflow-standardization.md`.

**No implementation. No Phase 1 start.**

⸻

### Phase 1 – Environment and Architecture Inspection

Status: **COMPLETE (2026-11-24)** — branch `phase-1-investigation`, commit `67f70ad`. Five research docs under `docs/research/` + synthesis report `reports/2026-11-24-phase-1-investigation.md`.

Objective: Understand the actual free WooCommerce attribute architecture and the WooCommerce Group Attributes plugin architecture.

Tasks:

1. Identify installed WordPress, Blocksy, Blocksy Companion, and WooCommerce versions.
2. Inspect the relevant child-theme architecture.
3. Analyze the existing free WooCommerce attribute manager architecture (native product data panel).
4. Investigate its live backend and frontend behaviour.
5. Investigate the native WooCommerce attribute architecture (storage, APIs, hooks).
6. Identify reusable PHP classes, AJAX handlers, REST endpoints, JavaScript components, and WooCommerce integrations.
7. Examine actual frontend behavior on desktop and mobile.
8. Evaluate security, performance, and compatibility constraints.
9. Study the reference plugin (PDF + screenshots) and map each required behavior.
10. Produce an evidence-based report with options A (reuse native infrastructure) / B (adapt compatible Blocksy architecture) / C (fully independent implementation) + recommended architecture.

**No implementation without approval.**

⸻

### Phase 1.1 – Decisions Finalization & Testing Policy

Status: **COMPLETE (2026-11-24)** — branch `phase-1-1-decisions-finalization`.

Objective: Transition from Phase 1 investigation to an approved, internally consistent
product direction. Documentation, decision-finalization, and test-environment preparation
only — **no implementation**.

Tasks (all completed):

1. Formalize the owner's decisions (recorded as D14–D23 in `decisions.md`).
2. Add a permanent test-environment + isolation policy to `project-rules.md`.
3. Correct technical inaccuracies in the Phase 1 docs (product-data-panel hooks,
   `wc_attributes_array_filter_visible` terminology, API signatures, Blocksy integration
   provenance, numeric discrepancies).
4. Establish the selected architecture direction **A1 + B1** (C deferred) without
   implementing it.
5. Prepare two permanent test products (Simple 13143, Variable 17028) with byte-exact
   backups and restoration verification; create the fixture registry
   `docs/testing/test-fixtures.md`.
6. Produce the completion report `reports/2026-11-24-phase-1-1-decisions-finalization.md`.

**No implementation. No Phase 2 start. No merge into `main`.**

⸻

### Phase 1.1.1 – Tax Query Verification

Status: **COMPLETE (2026-11-24)** — branch `phase-1-1-1-tax-query-verification`.

Objective: Verify the WooCommerce product-type taxonomy query correction (a `product_type`
taxonomy query using a slug must explicitly specify `field => 'slug'`) against the live
environment. Documentation/verification only — **no implementation**.

Tasks (all completed):

* Verify the `product_type` taxonomy query behavior (slug vs ID, `field => 'slug'`).
* Correct the Phase 1.1 documentation where the query was imprecise.
* Produce the completion report
  `reports/2026-11-24-phase-1-1-1-tax-query-verification.md`.

**No implementation. No Phase 2 start. No merge into `main`.**

⸻

### Phase 2 – Architecture Design and Approval

Status: **IN PROGRESS** — sub-phases 2A.1, 2A.2, and 2A.3 complete; 2B not started.

Objective: Define the final implementation before coding.

Sub-phase status:

* **Phase 2A.1 – Architecture Corrections:** **COMPLETE (2026-11-25)** — branch
  `phase-2a-1-architecture-corrections`. Corrected the Phase 2A specification (group-reference
  sequence, aggregation query, matching contract, term validation, Boolean sanitization,
  save/render revalidation).
* **Phase 2A.2 – Final Architecture Consistency Fixes:** **COMPLETE (2026-11-25)** — branch
  `phase-2a-2-final-architecture-consistency`. Strengthened the safe-unserialization
  contract, reconciled empty-group behavior, and ratified U11/U12/U13 (D24/D25/D26).
* **Phase 2A.3 – Native Attribute Template Redesign:** **COMPLETE (2026-11-25)** — branch
  `phase-2a-3-native-attribute-template-redesign`, commit `d531d2e`. Redesigned the
  architecture to the **native-template model** (groups = templates of Global Attribute
  definitions; Load adds native attribute rows; native rendering; no product meta).
  Supersedes the Phase 2A / 2A.1 / 2A.2 value-selection architecture. Report:
  `reports/2026-11-25-phase-2a-3-native-attribute-template-redesign.md`.
* **Phase 2B – Runtime Verification:** **NOT STARTED** — will verify the [2B-VERIFY] items
  in the Phase 2A.3 specification (§17) before Phase 3.

Deliverables:

* Technical architecture overview (components + data flow).
* Component responsibilities (CPT, group editor, Product Data → Attributes integration, native frontend rendering, settings).
* File structure inside `inc/ani-group-product-attributes/`.
* Data model: CPT `ani_gpa_group` + group meta `_ani_gpa_group_attributes` (ordered `pa_{slug}` references) + Theme Mod `anishop_gpa_settings`; no GPA product meta; no category taxonomy in the core (D40 — resolved, not in scope).
* PHP plan (classes, hooks, save handlers).
* JavaScript plan (dependency-free vanilla JS, drag-and-drop, AJAX group loading).
* CSS plan (`.ani-gpa-` prefix, RTL, house style).
* AJAX/REST strategy.
* Security plan (nonces, capabilities, sanitization, escaping).
* Performance plan (query caching, conditional asset loading).
* WooCommerce compatibility plan (taxonomy vs custom attributes, variable products).
* Mobile/RTL plan.
* Testing strategy.

The preferred approach is the simplest architecture that provides all required functionality without unnecessary dependencies.

**Wait for explicit approval.**

⸻

### Phase 3 – Full Implementation

Status: NOT STARTED

Objective: Build the complete WooCommerce Group Product Attributes module.

All mandatory features listed in Section 1 must be included in the initial implementation.

Development must follow the approved architecture.

The implementation must remain isolated in the Blocksy child theme and must not disrupt existing custom modules.

Use incremental implementation and verify each component before proceeding.

⸻

### Phase 4 – Integration and Testing

Status: NOT STARTED

Test the feature in the actual Anishop environment.

Required tests:

* Admin: group CRUD, Global Attribute selection, in-group attribute ordering (desktop). (Group categories are not in scope — D40.)
* Product Data → Attributes: load one group, load multiple groups, duplicate handling, native value selection, native reordering.
* Frontend rendering: single product page Additional Information tab (desktop + mobile).
* RTL and Persian text (all UI surfaces).
* Variable products (attributes linked to variations).
* Custom (non-taxonomy) attributes (uniform presentation).
* Empty states (no groups, groups with no attributes, products with no attributes).
* Error states (AJAX failure, permission denied).
* Fast input changes / drag-and-drop stability.
* Keyboard navigation (admin UI).
* Performance (page weight, query count, asset loading).
* Module compatibility (no regression in existing child-theme modules).

Fix verified defects before declaring completion.

⸻

### Phase 5 – Optimization and Finalization

Status: NOT STARTED

Tasks:

* Remove unused code.
* Optimize asset loading.
* Review security and escaping.
* Review PHP and JavaScript performance.
* Verify compatibility.
* Document configuration and usage.
* Prepare rollback instructions.
* Produce a final implementation report.

⸻

## 4. Decision and Approval Rules

* Each phase must have a clear deliverable.
* Do not silently advance to the next phase.
* Do not start implementation before architecture approval.
* Do not make speculative code changes.
* Report blockers instead of entering repetitive analysis loops.
* When a decision requires the project owner's input, present the specific question and available evidence.
* Prefer small, verifiable tasks over large autonomous tasks.
* Git workflow: each phase works on its own branch (`phase-{N}-{slug}`) and is merged into `main` only after the phase is complete and owner-approved (see `project-rules.md` §Git Workflow & Repository).
* End-of-task reports are published to the repo's `reports/` folder on GitHub and must list all changed files.

⸻

## 5. Project Definition of Done

The project is complete when:

1. A fully functional Group Product Attributes manager is available in the Blocksy child theme (admin + product metabox + frontend).
2. All mandatory features (Section 1) work.
3. Desktop and mobile behavior is verified.
4. RTL and Persian presentation work correctly.
5. No Blocksy Pro runtime dependency exists.
6. Existing child-theme modules remain functional.
7. Performance and security checks are completed.
8. The implementation is documented and maintainable.
