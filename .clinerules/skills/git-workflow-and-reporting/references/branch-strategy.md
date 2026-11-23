# Branch Strategy

Mandatory branch rules for this project. Authoritative policy: `.clinerules/project-rules.md` (§Branch and Git management, §Git Workflow & Repository).

## Branch naming

- **Phase branches:** `phase-{N}-{slug}` — one per independent project phase.
  - Examples: `phase-1-investigation`, `phase-2-architecture`, `phase-3-implementation`.
- **Chore branches:** `chore-{slug}` — for maintenance and documentation tasks that are not a numbered phase.
  - Examples: `chore-project-workflow-standardization`, `chore-docs-audit-fixes`.
- Slugs are lowercase, hyphen-separated, and descriptive.

## Branch base selection

- Every new branch is created from the **latest `main`**.
- Run `git fetch origin` first, then create the branch from local `main` (which must match `origin/main`).
- Never base a branch on another feature/chore branch unless explicitly authorized.

## Isolation of independent tasks

- Each independent task/phase works on its **own** branch.
- A branch contains **only** the changes for its task.
- Do not mix unrelated work into a branch.

## Protecting `main`

- `main` always reflects the **last owner-approved state**.
- **Never** commit directly to `main`.
- **Never** merge into `main` without explicit project-owner authorization.
- A phase/chore branch merges into `main` only after the task is complete **and** the owner has approved it.

## Handling existing branches and uncommitted changes

- **Never** modify, merge, rebase, reset, or delete an existing branch without explicit authorization.
- Before creating a branch, inspect `git status`. If there are uncommitted changes:
  - Preserve them (do not discard/overwrite).
  - Use `git stash push -m "pre-task: <task>"`, or scope commits to authorized files only.
  - If a safe separation is not possible, stop and report the blocker.
- Do not incorporate unrelated modifications into a task commit.

## Prohibited operations

- **Never** force-push (`git push --force` / `+ref`).
- **Never** delete branches without explicit authorization.
- **Never** reset, overwrite, or discard user changes.
- Do **not** create or merge a Pull Request unless explicitly requested or required by an established workflow.
