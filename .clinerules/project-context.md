# WooCommerce Product Group Attributes Manager — Project Context

## What is this project?

Implement a complete, lightweight, maintainable WooCommerce **Product Group Attributes manager** as a module of the free Blocksy child theme, for the **Anishop** store (`http://anishop.local`).

The final functionality should provide an experience comparable to the official **WooCommerce Group Attributes** plugin (reference package: `docs/references/`), without any runtime dependency on that plugin or on Blocksy Pro.

## Ultimate Objective

Let Anishop store admins define reusable **groups** of existing Global WooCommerce Attribute definitions (templates), load those groups into products so the group's attributes are inserted into the product's **native** WooCommerce attribute collection, and manage the actual attribute values through the **native** WooCommerce product editor. All product attributes (GPA-loaded, manual Global, and Custom) are presented through the **standard WooCommerce/Blocksy Additional Information tab** — all managed from the WordPress admin and rendered inside the Blocksy child theme. (Phase 2A.3 clarification, decisions D28–D39.)

## Mandatory Functionality (PROJECT-ROADMAP.md §1 — updated for the Phase 2A.3 native-template model)

1. **Group attribute management (admin)** — submenu under WooCommerce Products, immediately after Attributes (label: **گروه ویژگی‌های آنی**): create/edit/delete groups; name a group; select existing **Global Attribute definitions** to include; drag-and-drop attribute ordering within a group; list all groups.
2. **Group category management (admin):** [RESOLVED — D40, 2026-11-27] — Not in scope for the initial version. No `ani_gpa_group_cat` taxonomy. Groups remain a flat collection.
3. **Product-level group loading (product edit screen)** — Product Data → Attributes: UI-only "create group for this product" toggle; group select + Load (inserts the group's Global Attribute definitions into the product's native attribute collection); multiple groups may be loaded (duplicates skipped); values, edits, removals, additions, and reordering are then done through **native** WooCommerce controls.
4. **General settings:** table style (`default` / `minimal` / `boxed`) applied uniformly to all product attributes; no location setting; no "View Attribute" button (deferred / out of scope, D36).
5. **Cross-cutting:** error handling; responsive desktop + mobile; Persian RTL; minimal resource consumption.

## Core Principles (PROJECT-ROADMAP.md §1)

1. Prefer reusing compatible existing Blocksy Free functionality.
2. Do not depend on Blocksy Pro at runtime.
3. Do not modify Blocksy core files.
4. Keep the implementation isolated in the child theme.
5. Avoid unnecessary plugins and JavaScript frameworks.
6. Prioritize performance, maintainability, and compatibility.
7. Do not implement anything before its architecture has been investigated and approved.

## Phases (PROJECT-ROADMAP.md §3)

| Phase | Objective | Status |
|-------|-----------|--------|
| **0** | Independent workspace migration | **COMPLETE (2026-11-15/16)** |
| **0.1** | Workflow standardization | **COMPLETE (2026-11-23)** |
| **1** | Technical investigation: live child-theme architecture, native WooCommerce attribute architecture, reference plugin behavior (PDF + screenshots), options A/B/C. Evidence-based report + recommended architecture. **No implementation.** | **COMPLETE (2026-11-24)** |
| **1.1** | Decisions finalization + permanent test-environment policy | **COMPLETE (2026-11-24)** |
| **1.1.1** | Pre-merge finalization | **COMPLETE (2026-11-24)** |
| **2** | Architecture design and approval (technical architecture, component responsibilities, file structure, PHP/JS/CSS plans, AJAX/REST strategy, security, performance, WooCommerce compatibility, mobile/RTL, testing strategy) | **IN PROGRESS — 2A.1, 2A.2, 2A.3 COMPLETE (2026-11-25); 2B NOT STARTED** |
| **3** | Full implementation (all mandatory features; incremental; verify each component; isolated in child theme; must not disrupt existing modules) | NOT STARTED |
| **4** | Integration and testing in the real Anishop environment (admin CRUD, Product Data → Attributes loading, frontend rendering, RTL, desktop/mobile, variable products, custom attributes, catalog visibility, empty/error states, performance, module compatibility) | NOT STARTED |
| **5** | Optimization and finalization (remove unused code, optimize assets, security/escaping review, performance review, compatibility, documentation, rollback instructions, final report) | NOT STARTED |

## Definition of Done (PROJECT-ROADMAP.md §5)

1. A fully functional Group Product Attributes manager is available in the Blocksy child theme (admin + product metabox + frontend).
2. All mandatory features (ROADMAP §1) work.
3. Desktop and mobile behavior is verified.
4. RTL and Persian presentation work correctly.
5. No Blocksy Pro runtime dependency exists.
6. Existing child-theme modules remain functional.
7. Performance and security checks are completed.
8. The implementation is documented and maintainable.

## Decision & Approval Rules (PROJECT-ROADMAP.md §4)

- Each phase must have a clear deliverable.
- Do not silently advance to the next phase.
- Do not start implementation before architecture approval.
- Do not make speculative code changes.
- Report blockers instead of entering repetitive analysis loops.
- When a decision requires the project owner's input, present the specific question and available evidence.
- Prefer small, verifiable tasks over large autonomous tasks.
