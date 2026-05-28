# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

Entries are grouped under the following change types:

- **Added** for new features.
- **Changed** for changes in existing functionality.
- **Deprecated** for soon-to-be removed features.
- **Removed** for now removed features.
- **Fixed** for any bug fixes.
- **Security** for vulnerability fixes.

## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [0.0.2] - 2026-05-28

### Added

- Vision and Scope document v1.0 published at <https://docs.skontro.dev/vision/vision-and-scope/>: business case, six SMART business objectives, Karl-Wiegers-format vision statement, seven success metrics, ten enumerated risks, scope of v0.1.0 through v0.4, explicit out-of-scope list, seven stakeholder profiles, operating environment, references.
- Software Requirements Specification v1.0 published as LaTeX-compiled PDF at `docs/formal/srs/srs.pdf` (~38 pages, 117 numbered requirements: 62 functional, 45 non-functional, 10 interface) following ISO/IEC/IEEE 29148:2018 structure; includes complete Requirements Traceability Matrix.
- Requirements site section at <https://docs.skontro.dev/requirements/> links to the SRS PDF.
- arc42 architecture document sections 1–4 published at <https://docs.skontro.dev/architecture/01-introduction-and-goals/>: introduction & goals (three priority-ordered quality goals — compliance correctness, maintainability, demonstrability — and seven stakeholder profiles), constraints (15 technical, 5 organizational, 9 convention), context & scope (business + technical context, Mermaid system context diagram, external entity table), solution strategy (three-deployable-unit decomposition, approach to each quality goal, organizational decisions).
- ADR 0002 — Multi-tenancy: single database with `tenant_id` discriminator, with three alternatives evaluated (database-per-tenant, schema-per-tenant, hybrid).
- ADR 0003 — Conventional Commits 1.0.0 with commitlint enforcement via husky commit-msg hook and CI fallback.
- ADR 0004 — Diátaxis-structured documentation powered by MkDocs Material, deployed to `docs.skontro.dev` via GitHub Actions.
- Five compliance documents published at <https://docs.skontro.dev/compliance/>: overview (regulatory landscape, scope, verification mechanisms), e-Rechnung (Wachstumschancengesetz mandate, ZUGFeRD 2.1 EN16931 profile, KoSIT validation), ZUGFeRD 2.1 (hybrid PDF/A-3 + XML pipeline, UN/ECE Rec. 20 unit codes, BR-CO-17 line-level rounding), GoBD (BMF letters of 28 November 2019, 11 March 2024, and the July 2025 e-invoicing alignment; nine core principles; §147 AO retention), VAT handling (§14 UStG mandatory fields, §12 19%/7% rates, §4 exempt, §13b reverse-charge, §19 Kleinunternehmer, VIES roadmap, DATEV export). Every document carries the "reference implementation, not certified accounting software" disclaimer.

### Changed

- Homepage feature cards updated to surface Vision & Scope (1 Jan 2027 / 2028 deadlines), Requirements (SRS PDF), Architecture (sections 1–4), and Compliance (five-document suite, "reference implementation" framing).
- `docs/adr/index.md` lists all four ADRs.
- `mkdocs.yml` navigation adds entries for ADRs 0003 and 0004.

### Fixed

- Reference URLs for ZUGFeRD and XRechnung in V&S §4 and ADR 0001 — `ferd-net.de` dropped the version-pinned path; KoSIT XRechnung pages migrated to `xeinkauf.de` in 2023.

## [0.0.1] - 2026-05-27

### Added

- Repository initialized with MIT license.
- Governance files: `CODE_OF_CONDUCT.md` (Contributor Covenant 2.1, contact `conduct@skontro.dev`), `CONTRIBUTING.md`, `SECURITY.md` (private vulnerability reporting via `security@skontro.dev`, 90-day disclosure), `CHANGELOG.md`.
- Tooling configuration: multi-language `.gitignore` (Node, PHP/Laravel, Python, LaTeX, OS, IDE, draw.io, MkDocs `site/`), `.editorconfig` (UTF-8, LF, language-specific indents).
- GitHub configuration: issue templates (bug, feature, compliance question with standards dropdown), PR template with documentation-update checklist and self-review checklist, `CODEOWNERS` (`* @hauptraja`), Dependabot for npm/composer/pip/github-actions/docker.
- GitHub Actions workflow for documentation site: builds with `mkdocs build --strict` on PR, deploys with `mkdocs gh-deploy --force` on push to `main`; Python 3.12 with pip cache keyed on `docs/requirements.txt`.
- VS Code workspace settings: recommended extensions, format-on-save defaults with language-specific indents, Laravel + Vue debug profiles.
- MkDocs Material documentation site scaffolding deployed at <https://docs.skontro.dev>: full navigation tree (vision, requirements, architecture/arc42 12 sections, compliance, API, operations, testing, ADRs), system+light+dark palette toggle, Inter + JetBrains Mono fonts, Mermaid diagram support, English + German search.
- ADR 0001 — Stack Choice: Laravel 11 + Vue 3 + PostgreSQL + Python FastAPI ML service, with five alternatives evaluated.
- Custom domain `docs.skontro.dev` configured via `docs/CNAME` with HTTPS enforcement (TLS provisioning auto-completes within 24 hours of domain verification).

[Unreleased]: https://github.com/skontro/skontro/compare/v0.0.2...HEAD
[0.0.2]: https://github.com/skontro/skontro/releases/tag/v0.0.2
[0.0.1]: https://github.com/skontro/skontro/releases/tag/v0.0.1
