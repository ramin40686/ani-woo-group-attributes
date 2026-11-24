# ani-woo-group-attributes

WooCommerce **Product Group Attributes** manager — developed as an isolated module of the **Blocksy child theme** for the Anishop store (`http://anishop.local`, Persian RTL).

> This repository is the project **workspace**: rules, documentation, reference material, task reports, and a snapshot of the live child theme. The implementation target is `wp-content/themes/blocksy-child/inc/ani-group-product-attributes/` (see `snapshots/blocksy-child/` for the current theme state).

## Repository layout

| Path | Purpose |
|------|---------|
| `.clinerules/` | AI-assistant rules: project rules, decisions, environment, memory bank, context, conventions, skills |
| `docs/` | Project roadmap, WooCommerce attribute dev notes, reference plugin docs + screenshots |
| `reports/` | End-of-task reports (one file per task: `YYYY-MM-DD-{task-slug}.md`) |
| `snapshots/blocksy-child/` | Copy of the live child theme (refreshed whenever the live theme changes) |

## Git workflow

- `main` always reflects the **last owner-approved state**.
- Every phase starts a new branch: `phase-{N}-{slug}` (e.g. `phase-1-investigation`), created from `main`.
- A phase branch is merged into `main` **only after** the phase is complete and the owner has approved it.
- End-of-task reports are committed to `reports/` and pushed so the owner and external AI assistants can read them here.

## Status

**Phase 0, Phase 1 (investigation), and Phase 1.1 (decisions finalization + testing policy) are complete. Phase 2 (architecture design) is NOT STARTED — awaiting explicit project-owner authorization.** See `docs/PROJECT-ROADMAP.md`.

Selected architecture direction (for Phase 2 design, not yet implemented): **A1** (CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat` + product meta + Theme Mod) + **B1** (Blocksy custom-layer rendering). Delimiter normalization (C) is deferred. See `.clinerules/decisions.md` (D14–D23).
