# WooCommerce Product Group Attributes Manager — Project Context

## What is this project?

Implement a complete, lightweight, maintainable WooCommerce **Product Group Attributes manager** as a module of the free Blocksy child theme, for the **Anishop** store (`http://anishop.local`).

The final functionality should provide an experience comparable to the official **WooCommerce Group Attributes** plugin (reference package: `docs/references/`), without any runtime dependency on that plugin or on Blocksy Pro.

## Ultimate Objective

Let Anishop store admins organize WooCommerce product attributes into named, sortable **groups** (optionally organized into **group categories**), assign those groups to products, choose which attribute values each product displays, and render the selected attributes on the frontend — all managed from the WordPress admin and rendered inside the Blocksy child theme.

## Mandatory Functionality (PROJECT-ROADMAP.md §1)

1. **Group attribute management (admin):** create/edit/delete attribute groups; name a group; select existing attribute values to display under it; assign a group category; drag-and-drop value sorting; list all groups.
2. **Group category management (admin):** create/edit/delete group categories; attach categories to groups.
3. **Product-level group loading (product edit screen):** "create group for this product" toggle; load groups one by one ("Manage Attribute Groups" + Load); load all groups of a category at once ("Load Group Categories"); select which attribute values display on the product page; drag-and-drop group sorting per product.
4. **General settings:** table style, location, table colors; optional "View Attribute" button on the shop page.
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
| **1** | Technical investigation: live child-theme architecture, native WooCommerce attribute architecture, reference plugin behavior (PDF + screenshots), options A/B/C. Evidence-based report + recommended architecture. **No implementation.** | **PREPARED — awaiting owner authorization (not started)** |
| **2** | Architecture design and approval (technical architecture, component responsibilities, file structure, PHP/JS/CSS plans, AJAX/REST strategy, security, performance, WooCommerce compatibility, mobile/RTL, testing strategy) | NOT STARTED |
| **3** | Full implementation (all mandatory features; incremental; verify each component; isolated in child theme; must not disrupt existing modules) | NOT STARTED |
| **4** | Integration and testing in the real Anishop environment (admin CRUD, product metabox, frontend rendering, RTL, desktop/mobile, variable products, custom attributes, catalog visibility, empty/error states, performance, module compatibility) | NOT STARTED |
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
