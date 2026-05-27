# 2. Constraints

Constraints are non-negotiable. They define the boundaries of the architectural design space. Where a constraint is technically negotiable but operationally costly to change, it is documented here with the cost in plain terms.

## 2.1 Technical Constraints

| ID | Constraint | Source | Notes |
|---|---|---|---|
| TC-1 | PHP 8.3 or later (backend) | [ADR 0001](../adr/0001-stack-choice.md) | Required by Laravel 11. |
| TC-2 | Laravel 11 framework | ADR 0001 | Sets routing, middleware, ORM, queue, and authentication conventions. |
| TC-3 | Node.js 20 LTS (frontend tooling) | ADR 0001 | Vite, Vue 3, build pipeline. |
| TC-4 | Vue 3 + TypeScript + Pinia + Vue Router (SPA) | ADR 0001 | Frontend stack; no Inertia, no server-rendered pages in v0.1 (decision: a clean SPA API client is needed for future ML and customer-portal integrations). |
| TC-5 | PostgreSQL 14 or later | ADR 0001, SRS IR-005 | `jsonb` required from v0.2 for inbound XRechnung XML storage. Tested on 14, 15, 16. |
| TC-6 | Redis 7 or later | ADR 0001, SRS IR-006 | Used for cache, session storage, queue backend. |
| TC-7 | S3-compatible object storage | ADR 0001, SRS IR-007 | MinIO in dev; AWS S3, Hetzner Object Storage, or Backblaze B2 in production. |
| TC-8 | Python 3.12 + FastAPI (ML service, v0.3+) | ADR 0001, SRS IR-008 | Optional service introduced in v0.3; absent from v0.1 and v0.2 deployments. |
| TC-9 | Docker Engine 24+ and Docker Compose v2+ (deployment) | ADR 0001, SRS NFR-036 | No Kubernetes requirement for v0.1. |
| TC-10 | Browser support: Chrome, Firefox, Safari, Edge (latest two stable) | SRS IR-004 | No IE11 support, no legacy browser polyfills. |
| TC-11 | Responsive design from 375px viewport | SRS NFR-022 | iPhone 13 mini and equivalent. No native mobile apps. |
| TC-12 | All monetary amounts stored as integer cents | SRS FR-026 | Floating-point arithmetic causes BR-CO-17 validation failures. |
| TC-13 | Multi-tenancy via single-database `tenant_id` discriminator | [ADR 0002](../adr/0002-multi-tenancy.md) | Documented and recorded in ADR 0002. |
| TC-14 | All API endpoints prefixed `/api/v1` | SRS IR-010 | Versioning via URL prefix; breaking changes require major-version bump and ≥6 month deprecation window. |
| TC-15 | Production HTTPS only; HSTS header with max-age 31556952 | SRS IR-011, SRS NFR-012 | Caddy or equivalent at edge. |

## 2.2 Organizational Constraints

| ID | Constraint | Source | Notes |
|---|---|---|---|
| OC-1 | MIT license | [LICENSE](https://github.com/skontro/skontro/blob/main/LICENSE) | All contributions are MIT-licensed. No CLA required. |
| OC-2 | Solo maintainer at v0.1, building part-time (~2 hours per day) | V&S §3.1 maintainer stakeholder profile | Architecture must be tractable for one person. No microservice sprawl. |
| OC-3 | Operational budget ~€15 per month (production demo) | V&S §1.6 R-6 | Reference deployment fits on Hetzner CX22 (€5/month) plus storage and DNS. |
| OC-4 | No paid certifications or compliance audits in budget | V&S §2.3 limitations | Skontro is and will remain a reference implementation, not certified accounting software. |
| OC-5 | All work in public on GitHub from the first commit | Project methodology | Includes commit messages, PR discussions, issues. Build-in-public discipline. |

## 2.3 Conventions

| ID | Convention | Source | Notes |
|---|---|---|---|
| CV-1 | Conventional Commits 1.0.0 | SRS NFR-033 | Enforced via commitlint + husky commit-msg hook. |
| CV-2 | arc42 template for architecture documentation | This document | 12 sections, populated incrementally as the build progresses. |
| CV-3 | MkDocs Material theme, search lang `en, de` | `mkdocs.yml` | Docs site at `docs.skontro.dev`. |
| CV-4 | draw.io (`.drawio.svg`) for high-fidelity architecture diagrams | This document | Saved as `.drawio.svg` so the diagram is both editable in draw.io and rendered directly by browsers. |
| CV-5 | Mermaid for sequence, state, and ER diagrams inside Markdown | `mkdocs.yml` mermaid2 plugin | Code blocks tagged ` ```mermaid ` render inline. |
| CV-6 | RFC 2119 keywords (SHALL / SHOULD / MAY) in requirements documents | SRS §1.2 | Applied in SRS; this arc42 document uses descriptive voice. |
| CV-7 | LaTeX (Tectonic) for formal PDFs (SRS, SDD, future formal docs) | SRS preamble | Source `.tex` and compiled `.pdf` both committed. |
| CV-8 | Architecture Decision Records (Michael Nygard format) | SRS NFR-034 | Stored in `docs/adr/`, numbered sequentially. |
| CV-9 | German UI default, English fallback | SRS IR-003, NFR-020 | Compliance-relevant terminology (Rechnung, USt-ID, etc.) always in German regardless of UI locale. |

## 2.4 Constraint Impact Notes

A few constraints meaningfully shape decisions in later sections and are worth calling out:

**TC-12 (cents, not floats)** is the reason the data model uses `BIGINT` columns for amounts and the application code uses the `Brick/Money` library throughout. This decision propagates into the JSON API contract, the ZUGFeRD XML output, the database schema, and the test corpus. See SRS FR-026 and FR-031.

**TC-13 (single-DB multi-tenancy)** is recorded as ADR 0002, alongside this milestone. The alternative — schema-per-tenant or database-per-tenant — was considered and rejected; reasoning is in the ADR.

**OC-2 (solo, part-time)** shapes Section 4 (Solution Strategy). The architecture deliberately avoids microservice sprawl. There is one backend (Laravel monolith), one frontend (Vue SPA), and from v0.3 one optional ML service (Python FastAPI). Three deployable units total — no more.
