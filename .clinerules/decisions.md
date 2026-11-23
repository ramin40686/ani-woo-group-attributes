# Group Product Attributes — Decisions

## Decided

| # | Decision | Source / date |
|---|----------|---------------|
| D1 | **Two-path filesystem boundary:** all read/write access restricted to (1) the active workspace and (2) the target WordPress installation `/home/ramin/Local Sites/anishop/app/public/`. The old workspace (`/home/ramin/Downloads/test-novamira/`) is history only and must not be accessed. | Owner decision, 2026-11-16 (previous Search Box project, Phase 1 close-out) |
| D2 | **Canonical workspace path:** `/home/ramin/Downloads/woo-group-attributes/` — supersedes the stale path `/home/ramin/Downloads/ani-group-product-attributes/` recorded in the permanent rule. | Owner decision, 2026-11-23 |
| D3 | **Main rules file renamed** to `project-rules.md` (was `ani-group-product-attributes-devlompent.md`); all references updated. | Owner decision, 2026-11-23 |
| D4 | **Tooling:** WordPress Studio MCP + WP-CLI only. Novamira MCP is not configured in this workspace and is removed from the environment docs. | Owner decision, 2026-11-23 |
| D5 | **`temp/` cleanup:** all 21 stale diagnostic files (leftovers of the previous project) deleted. | Owner decision, 2026-11-23 |
| D6 | **Phase status:** this project is at end of Phase 0 / start of Phase 1. Phase 1 (investigation only, no implementation) approved to begin after the documentation fixes. | Owner decision, 2026-11-23 |
| D7 | **Blocksy parent + companion updated to 2.1.62** before project start (live: WP 7.1.5, WooCommerce 11.3.1). | Owner action, 2026-11-23 |
| D8 | **Project repository:** `https://github.com/ramin40686/ani-woo-group-attributes` (created by owner, initially empty). Full read+write access verified via HTTPS on 2026-11-23 (SSH not configured — host key missing). The access check left one test commit (`1394a60`, `.gitkeep`, branch `main`); GitHub refuses to delete the default branch, so it remains as the repo's first commit. | Owner action + verified, 2026-11-23 |
| D9 | **Group value selection = presentation layer (Q8):** the module renders the selected attribute values on the product page **without modifying native WooCommerce attribute data** (no changes to `_product_attributes`, visibility, or variation semantics). | Owner decision, 2026-11-23 |
| D10 | **"View Attribute" shop-page button (Q9):** replicate the **reference plugin's behavior** — the exact behavior (modal / link / other) will be determined during Phase 1 investigation and documented in the Phase 1 report. | Owner decision, 2026-11-23 |
| D11 | **Test commit kept:** the access-check commit `1394a60` (`.gitkeep`) remains as the repo's first commit (delegated decision; harmless and preserves the access-verification record). | Cline decision (delegated by owner), 2026-11-23 |
| D12 | **Git workflow rules:** (1) every phase works on its own branch `phase-{N}-{slug}` created from `main`; (2) a phase branch merges into `main` only after the phase is complete **and** owner-approved; (3) a copy of the latest live `blocksy-child` theme is kept in the repo at `snapshots/blocksy-child/` and refreshed on change; (4) every end-of-task report is published to the repo's `reports/` folder on GitHub (in addition to Cline chat + VS Code workspace) and must list all changed files. | Owner decision, 2026-11-23 |
| D13 | **Phase 1 Authorization Clarification:** Decision D6 is superseded to the extent that completion of the documentation corrections alone does **not** authorize the start of Phase 1. Phase 1 requires **separate, explicit authorization** from the project owner. Completion of preparatory documentation, workflow standardization (Phase 0.1), or any other maintenance task must **not** be interpreted as authorization to begin Phase 1. **Current state: Phase 1 is NOT authorized to begin until the project owner explicitly approves it.** | Owner decision, 2026-11-23 (clarifies and supersedes the relevant interpretation of D6) |

## Undecided

- (none yet)
