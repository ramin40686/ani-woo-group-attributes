# Project Roadmap

Advanced WooCommerce Product Group Attributes manager in the free version of the Blocksy WordPress theme

Project status: **Phase 0 + Phase 1 + Phase 1.1 complete — Phase 2 (architecture design) NOT STARTED, awaiting explicit owner authorization**
Project type: WordPress / Blocksy / WooCommerce
Target website: Anishop
Target URL: http://anishop.local

⸻

## 1. Project Objective

Implement a complete, lightweight, maintainable WooCommerce **Product Group Attributes manager** in the free version of the Blocksy WordPress theme.

The final functionality should provide an experience comparable to the official **WooCommerce Group Attributes** plugin (reference package in `docs/references/`).

The module integrates with WooCommerce product administration (product data panel / product edit screen) and frontend product presentation (single product page and shop page), rendered inside the Blocksy child theme.

### Required functionality

1. **Group attribute management (admin)** — Products → Group Attributes:
   - Create, edit, delete attribute groups.
   - Name the attribute group and select the existing attribute values to display under this group.
   - Create or select the group category for this group.
   - Drag-and-drop the attribute values to sort them in the preferred order.
   - List and manage all created group attributes.
2. **Group attribute categories (admin)** — Products → Group Attributes Categories:
   - Create, edit, delete group categories.
   - Attach categories to group attributes in each group's settings.
3. **Product-level group loading (product edit screen)** — Product Data → Attributes:
   - "Create group for this product" toggle.
   - "Manage Attribute Groups" select box: load multiple groups one by one (the Load button loads all attributes associated with the selected group).
   - "Load Group Categories" field: load multiple group attributes at once (all groups of a category).
   - After loading, select the attribute values to display on the product page.
   - Drag-and-drop the attribute groups to sort them per product.
4. **General settings** — WooCommerce → Settings → Group Attributes:
   - Choose the table style and location.
   - Customize the table colors.
   - Enable the "View Attribute" button on the shop page.
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

### Phase 2 – Architecture Design and Approval

Status: **NOT STARTED — awaiting explicit owner authorization**

Objective: Define the final implementation before coding.

Deliverables:

* Technical architecture overview (components + data flow).
* Component responsibilities (CPT, taxonomy, admin UI, product metabox, frontend renderer, settings).
* File structure inside `inc/ani-group-product-attributes/`.
* Data model: CPT `ani_gpa_group`, taxonomy `ani_gpa_group_cat`, product meta keys.
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

* Admin: group CRUD, categories, value/group sorting (desktop).
* Product metabox: load groups one-by-one and by category, value selection, per-product group sorting.
* Frontend rendering: single product page (desktop + mobile).
* Frontend rendering: shop page "View Attribute" button.
* RTL and Persian text (all UI surfaces).
* Variable products (attributes linked to variations).
* Custom (non-taxonomy) attributes.
* Catalog visibility / hidden products.
* Empty states (no groups, no values, no products).
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
