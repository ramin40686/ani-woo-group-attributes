---
name: git-workflow-and-reporting
description: Mandatory Git workflow and task-reporting procedure for this project. Use at the start of every task to inspect Git state, create or select the correct branch, handle working-tree changes safely, commit and push with verification, and produce the required end-of-task report.
---

# Git Workflow & Reporting

Operational procedure for applying the mandatory project rules in `.clinerules/project-rules.md` (§Mandatory Project Execution Rules). **`project-rules.md` is the authoritative source for the mandatory rules.** This skill is the step-by-step "how" for applying them; it does not restate the policy in full.

## When to use this skill

- At the **start of every task** (before any change).
- Before creating or selecting a branch.
- Before committing or pushing.
- When producing an end-of-task report.
- Whenever a task touches Git state, branches, or reports.

## 1. Mandatory pre-task Git inspection

Before doing anything, inspect and record:

1. Current branch: `git branch --show-current`
2. Working tree: `git status` (identify any uncommitted changes)
3. Local and remote `main` HEAD: run `git fetch origin`, then `git --no-pager log --oneline -1 main` and `git --no-pager log --oneline -1 origin/main`
4. Relevant previous commits: `git --no-pager log --oneline -15 --all --decorate`
5. Relevant project decisions: `.clinerules/decisions.md`
6. Relevant roadmap entries: `docs/PROJECT-ROADMAP.md`
7. Previous reports related to the task: `reports/`

Purpose: prevent duplicate work, unauthorized changes, and conflicts with previous decisions.

## 2. Branch creation and selection

- **Never** work on `main`.
- Phase work → branch `phase-{N}-{slug}` from `main`.
- Maintenance / documentation work → branch `chore/{slug}` from `main`.
- Base every new branch on the latest `main` (after `git fetch origin`).
- See `references/branch-strategy.md` for the full rules (base selection, isolation, protecting `main`, handling existing branches and uncommitted changes).

## 3. Safe handling of existing working-tree changes

- If `git status` shows uncommitted changes that are **not** part of the assigned task:
  - Do **not** discard, overwrite, or silently commit them.
  - Preserve them (e.g. `git stash push -m "pre-task: <task>"`, or leave them and scope commits to authorized files only).
  - If a safe separation is not possible, **stop and report the blocker**.
- Never include unrelated changes in a task commit.

## 4. Commit preparation

- Review the **complete diff**: `git --no-pager diff` and `git --no-pager diff --staged`.
- Stage **only** authorized files: `git add <specific files>` (avoid `git add -A` / `git add .`).
- Check for secrets, credentials, temporary files, generated clutter, and unrelated modifications.
- Use a descriptive message following the repo convention: `type: description` (types seen: `docs:`, `chore:`).
- See `references/commit-conventions.md` for the full rules.

## 5. Push and remote verification

- Push the task branch: `git push -u origin <branch>`.
- **Verify** the remote branch and pushed commit:
  - `git ls-remote origin <branch>`
  - `git --no-pager log --oneline -1 origin/<branch>`
- **Never** claim a successful push without verification.
- If push fails: record the actual error, preserve local work, do **not** change credentials or Git config to bypass it, do **not** claim it was pushed, and stop to report.

## 6. Report creation

- Create `reports/YYYY-MM-DD-{task-slug}.md` using `references/reporting-template.md`.
- The report must reflect **actual repository state and verified evidence**, not assumptions.
- Distinguish Implemented / Tested / Verified / Not tested / Not verified.
- Include the final commit hash and remote push status.
- Commit and push the report with the task changes.

## 7. End-of-task verification

Before finishing, verify (see the task's Final Verification Checklist):

- Correct branch created from `main`.
- Only authorized files changed; no production code, snapshots, or unrelated files touched.
- Full diff reviewed; no secrets or credentials.
- Report accurate.
- Changes committed and pushed; remote branch and final commit verified.
- Working tree clean (except documented pre-existing user changes).

## 8. Mandatory stop conditions

After completing the assigned task:

1. Finish the report.
2. Commit and push the authorized changes.
3. Verify the final Git state.
4. Provide a concise completion summary.
5. **Stop** and wait for the project owner's next instruction.

Do **not** automatically begin another phase or task. Do **not** merge into `main` without explicit owner authorization.

## References

- `references/branch-strategy.md` — branch naming, base selection, isolation, protecting `main`.
- `references/commit-conventions.md` — commit messages, granularity, diff inspection, push verification.
- `references/reporting-template.md` — reusable end-of-task report template.
