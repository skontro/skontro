# 0003. Adopt Conventional Commits 1.0.0 with commitlint Enforcement

- **Status:** Accepted
- **Date:** 2026-05-28
- **Deciders:** Zeeshan Ahmed Raja (sole maintainer, v0.1.0)
- **Consulted:** —
- **Informed:** Future contributors via this ADR; SRS NFR-033; CONTRIBUTING.md.

---

## Context

Skontro will accumulate hundreds of commits across the build phase and beyond. The commit history is one of the primary artefacts of an open-source project. Good commit messages make:

- The git log itself readable as a project history
- Generating CHANGELOGs automatic
- Generating release notes automatic
- Code review faster (the reviewer sees intent in the subject line)
- Onboarding new contributors easier (they can see the convention by reading the log)

Bad commit messages — "fixed stuff", "wip", "update", "yolo" — make the log worthless. The cost is paid by everyone who ever reads the project, including future-me.

---

## Decision

Skontro adopts **Conventional Commits 1.0.0** as the commit-message convention, enforced by a **commitlint commit-msg Git hook** managed via **husky**.

### The convention

```text
<type>(<optional scope>): <description>

<optional body>

<optional footer(s)>
```

**Types** (lowercase):

- `feat` — a new user-facing feature
- `fix` — a bug fix
- `docs` — documentation-only changes
- `style` — code-style changes (formatting, missing semicolons, etc.; no logic change)
- `refactor` — code change that neither fixes a bug nor adds a feature
- `perf` — performance improvement
- `test` — adding or correcting tests
- `build` — changes to build system or external dependencies
- `ci` — changes to CI configuration
- `chore` — other changes that don't fit the above categories

**Subject**:

- Imperative mood: "add login" not "added login" or "adds login"
- Lowercase first letter
- No period at end
- 50 characters or less ideally

**Scope** (optional): a one-word indicator of the area of change, in parentheses. Examples: `feat(auth): ...`, `docs(arc42): ...`, `fix(invoicing): ...`. Common scopes: `auth`, `invoicing`, `expenses`, `dashboard`, `compliance`, `arc42`, `srs`, `adr`, `vision`, `home`, `vscode`, `ci`, `deps`.

**Breaking changes** are marked by adding `!` after the type/scope or by including `BREAKING CHANGE:` in the footer. Major version bumps follow.

### Enforcement

Three layers:

1. **commitlint** (`@commitlint/cli` + `@commitlint/config-conventional`) validates every commit message against the convention.
2. **husky** manages a `commit-msg` Git hook that runs commitlint on every `git commit`.
3. **CI check** runs commitlint over the PR's commit range to catch any commits that bypassed the local hook (e.g., from a fresh clone where husky hasn't installed yet).

Non-conforming commits are rejected at the local hook; if one slips through, CI catches it on the PR.

---

## Consequences

### Positive

- **Readable history.** `git log --oneline` is genuinely useful, not a wall of "wip" and "stuff".
- **Automatic CHANGELOG generation.** Tools like `conventional-changelog` can produce a CHANGELOG section from any commit range. Skontro's CHANGELOG is currently maintained manually, but the convention keeps the option open.
- **Automatic semantic-version bumping.** `feat` commits suggest a minor bump; `fix` suggest a patch; `feat!` or `BREAKING CHANGE:` suggest a major. Useful in release automation later.
- **Clearer PR titles.** PR titles follow the same convention, making the merged log of PRs equally readable.
- **Discipline cost is low.** The rules are small enough to internalize in an afternoon.

### Negative

- **One-time setup per fresh clone.** Anyone contributing must run `npm install` (or equivalent) for the husky hook to install. The CI fallback covers misses.
- **Occasional friction.** A hurried commit gets rejected for a missing type prefix; the contributor has to fix the message and commit again. Mild irritation, no real cost.

### Risks accepted

- If a contributor consistently bypasses the hook (e.g., `git commit --no-verify`), CI catches it but the contributor's local history may have non-conforming commits before they push. The squash-merge default cleans this up at merge time; the merged commit on `main` always uses the PR title, which is the controlled point.

---

## Alternatives Considered

### Alternative A — No convention (free-form commits)

- **Pro:** Zero friction.
- **Con:** History becomes worthless within months. Contributors learn no useful skill from the project's commit style.
- **Verdict:** Rejected.

### Alternative B — Gitmoji (emoji prefixes)

- **Pro:** Visually distinctive.
- **Con:** Emoji rendering inconsistent across terminals and Git hosting providers. Less machine-parsable. Niche convention; fewer engineers know it on sight.
- **Verdict:** Rejected.

### Alternative C — A custom internal convention

- **Pro:** Tailored to the project's specifics.
- **Con:** No tooling support; would have to write custom validators; no transferable skill for contributors.
- **Verdict:** Rejected.

---

## References

- Conventional Commits 1.0.0 — `https://www.conventionalcommits.org/en/v1.0.0/`
- commitlint — `https://commitlint.js.org/`
- husky — `https://typicode.github.io/husky/`
- SRS NFR-033 — Conventional Commits requirement
- CONTRIBUTING.md — contributor guidance

---

## Revision History

| Version | Date | Author | Change |
|---|---|---|---|
| 1.0 | 2026-05-28 | Zeeshan Ahmed Raja | Initial decision recorded. |
