# Group Product Attributes — Environment

## Paths

| Item | Path |
|------|------|
| **Independent workspace** | `/home/ramin/Downloads/woo-group-attributes/` |
| **Target website** | `http://anishop.local` |
| **WordPress installation** | `/home/ramin/Local Sites/anishop/app/public/` |
| **Blocksy child theme** | `/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/` |
| Blocksy parent (live) | `/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy/` |
| **Module target (Phase 3)** | `wp-content/themes/blocksy-child/inc/ani-group-product-attributes/` |
| **Project repository (GitHub)** | `https://github.com/ramin40686/ani-woo-group-attributes` — HTTPS; the workspace is the repo's working copy (baseline pushed 2026-11-23) |
| **Child-theme snapshot (repo)** | `snapshots/blocksy-child/` — copy of the live child theme; refresh whenever the live theme changes |
| **Task reports (repo)** | `reports/` — end-of-task reports published to GitHub (`YYYY-MM-DD-{task-slug}.md`) |

> The WordPress installation and child theme are **external project targets**, not part of this Cline workspace.

## Verified Environment Details

| Item | Value | Verified |
|------|-------|----------|
| WordPress | **7.1.5** | **LIVE-VERIFIED 2026-11-23** (wp-cli `core version`) |
| PHP | **8.3.36** (Local by Flywheel) | LIVE-VERIFIED 2026-11-15 (server `X-Powered-By` header) |
| WooCommerce | **11.3.1** | **LIVE-VERIFIED 2026-11-23** (wp-cli `plugin list`) |
| Blocksy (parent) | **2.1.62** (updated from 2.1.60 on 2026-11-23) | **LIVE-VERIFIED 2026-11-23** (wp-cli `theme list`) |
| blocksy-companion | **2.1.62** (active) | **LIVE-VERIFIED 2026-11-23** |
| persian-woocommerce | **10.0.7** (active) | **LIVE-VERIFIED 2026-11-23** |
| blocksy-child | **v1.1.1** — "Anishop Blocksy Child", Template: `blocksy`, Text Domain: `anishop` | LIVE-VERIFIED 2026-11-15 (style.css header) |
| Front page | page 33 «خانه» (`show_on_front=page`, `page_on_front=33`) | [DOCUMENTED 2026-10-10] + LIVE-VERIFIED 2026-11-15 |
| Site locale | `fa_IR` (Persian, RTL) | [DOCUMENTED 2026-10-10] + LIVE-VERIFIED 2026-11-15 |
| DB | MySQL (`localhost`, prefix `wp_`) | [DOCUMENTED 2026-10-10] — re-verify in Phase 1 |

## Tooling

- **WP-CLI** — installed and ready (`/home/ramin/.local/bin/wp`); **read-only by default** (see `project-rules.md` §Safety).
- **WordPress Studio MCP** (`wordpress-studio`) — primary MCP toolset: `site_info`, `wp_cli`, `take_screenshot`, `inspect_design`, `validate_blocks`, `need_for_speed`, `rank_me_up`. Prefer MCP tools over raw shell where an equivalent exists.
- **CDP/headless testing** — Python CDP scripts were the established pattern in the old workspace; recreate harnesses in `temp/` as needed for Phase 4 testing (old harnesses removed 2026-11-23, decision D5).
- **Local by Flywheel** — local environment; **Live Link** enables real-device (physical iPhone/Android) testing. The owner has an **iPhone 12 Pro** used for real-mobile verification.

## Cline Configuration

- **Workspace rules:** all 6 `.clinerules/*.md` files in this workspace are auto-loaded by Cline: `project-rules.md` · `decisions.md` · `environment.md` · `memory-bank.md` · `project-context.md` · `technical-conventions.md`.
- **Global rule bank:** Cline setting `clinerules.ruleBankPath` = `/home/ramin/Documents/Cline/Rules/` — 5 global rules auto-loaded in **every** workspace: `studio-mcp.md` · `wordpress-coding-standards.md` · `wordpress-security-and-db.md` · `wordpress-assets-and-ajax.md` · `wordpress-best-practices.md`.
- **Skills:** `wordpress-development` + `frontend-design` (`.clinerules/skills/`) — both discovered and invocable by Cline.
- **MCP:** `wordpress-studio` configured (`/home/ramin/.local/bin/studio mcp`).
