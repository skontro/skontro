# Contributing to Skontro

> **Note:** This is a placeholder. The full contributor guide will live at
> [docs.skontro.dev/contributing](https://docs.skontro.dev/contributing)
> (live by Day 5 of the build plan).

## Project Mission

Skontro is an open-source German-compliant mini-ERP for the Mittelstand,
with first-class EN 16931 e-invoicing.

## Code of Conduct

This project and everyone participating in it is governed by the
[Skontro Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are
expected to uphold this code. Report unacceptable behavior to
`conduct@skontro.dev`.

## Development Setup

See the **Quickstart** section of [README.md](README.md). The detailed
environment setup guide lands at
[docs.skontro.dev/contributing](https://docs.skontro.dev/contributing)
on Day 5.

## Commit Convention

Skontro follows [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/).
The commit message header must follow the form:

```
<type>(<optional scope>): <description>
```

### Allowed types

| Type       | Use for                                                        |
| ---------- | -------------------------------------------------------------- |
| `feat`     | A new feature                                                  |
| `fix`      | A bug fix                                                      |
| `docs`     | Documentation-only changes                                     |
| `style`    | Formatting, whitespace, semicolons — no code-meaning changes   |
| `refactor` | Code change that neither fixes a bug nor adds a feature        |
| `perf`     | Performance improvement                                        |
| `test`     | Adding or correcting tests                                     |
| `build`    | Build system or external dependency changes                    |
| `ci`       | CI configuration changes                                       |
| `chore`    | Miscellaneous changes that don't modify src or test files      |

Breaking changes are marked with `!` after the type/scope (e.g.,
`feat(api)!: drop legacy invoice endpoint`) **and** a `BREAKING CHANGE:`
footer.

Example:

```
feat(invoices): add ZUGFeRD 2.3 export

Implements EN 16931 conformant XML embedding for PDF/A-3 invoices.
Closes #42.
```

## Branch Naming

Branches must use one of these prefixes:

- `feat/<short-description>` — new features
- `fix/<short-description>` — bug fixes
- `docs/<short-description>` — documentation
- `chore/<short-description>` — tooling, deps, governance

Use kebab-case after the prefix (e.g., `feat/zugferd-export`).

## Pull Request Process

1. Open the PR against `main`.
2. Ensure **CI is green** — all checks must pass.
3. Obtain at least **one approval**. Self-approval is acceptable during
   v0.1 while the project is single-maintainer; this requirement
   tightens after v0.2.
4. Squash-merge with a Conventional Commits title.
5. Delete the source branch after merge.

## Architectural Decision Records (ADRs)

Any change that meaningfully alters the architecture — new services,
data model shifts, choice of major libraries, cross-cutting patterns —
requires an ADR in `docs/adr/`. Use [MADR](https://adr.github.io/madr/)
format. Open the ADR PR before, or alongside, the implementation PR.

## Reporting Bugs

File issues on the GitHub tracker. For **security** issues, follow
[SECURITY.md](SECURITY.md) instead — do not open public issues.

---

Full contributor guide coming Day 5.
