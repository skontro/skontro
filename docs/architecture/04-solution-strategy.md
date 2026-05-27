# 4. Solution Strategy

This section records the cornerstone decisions that shape the architecture. Each one is brief — the detailed structural view is in §5 (Building Block View), the runtime view is in §6, and the deployment view is in §7. Where a decision is large enough to deserve its own document, an Architecture Decision Record (ADR) is linked.

## 4.1 Technology Decisions

The fundamental stack choice is recorded in [ADR 0001](../adr/0001-stack-choice.md). In summary:

| Layer | Choice | Rationale (one-line summary) |
|---|---|---|
| Backend | PHP 8.3, Laravel 11 | Maintainer expertise; mature German-compliance library ecosystem (`horstoeko/zugferd`); velocity for solo development. |
| Frontend | Vue 3, TypeScript, Vite, Pinia, Vue Router, Tailwind CSS | Modern SPA stack with low ceremony; clean separation from the backend enables future ML service and customer-portal integrations. |
| Relational store | PostgreSQL 14+ | First-class `jsonb` support (used from v0.2 for inbound XRechnung XML); reliable; widely operated. |
| Cache / queue | Redis 7+ | Standard Laravel choice; one tool for cache, session, and queue. |
| Object storage | S3-compatible | MinIO in dev; vendor-neutral in production (AWS, Hetzner, Backblaze all work via the same SDK). |
| Documentation site | MkDocs Material | Markdown source; fast static deploy via GitHub Actions to GitHub Pages. |
| Formal documents | LaTeX + Tectonic | SRS and SDD are LaTeX-compiled PDFs; source and PDF committed together. |
| ML service (v0.3+) | Python 3.12, FastAPI, PyTorch | Required because German invoicing tooling lives in PHP but ML libraries live in Python. Bridged via a private HTTP interface. |
| Deployment | Docker Compose | One-command deployment; no Kubernetes; portable across hosting providers. |

Five alternatives (NestJS, Django, Spring Boot, Phoenix, Laravel + Inertia) are evaluated in ADR 0001's "Alternatives Considered" section.

## 4.2 Top-level Decomposition

Skontro is structured as **three deployable units**:

1. **Backend API** (Laravel monolith)
    - REST/JSON API under `/api/v1`
    - All business logic: authentication, RBAC, multi-tenancy, customer and product CRUD, invoice lifecycle, ZUGFeRD generation pipeline, expense handling
    - Single PHP-FPM service, scaled horizontally if needed; SQLite-style simplicity for v0.1, no microservice extraction planned

2. **Frontend SPA** (Vue + Vite static build)
    - Single-page app served from a static-files location (nginx in dev, CDN-fronted in production)
    - No SSR for v0.1; no server-rendered HTML for application pages
    - Communicates with the backend exclusively via REST

3. **ML Service** (Python FastAPI, introduced v0.3)
    - Private HTTP API consumed only by the backend
    - Houses all ML models (LayoutLMv3 for receipt OCR, DistilBERT for categorization, Prophet for forecasting, Isolation Forest for anomaly detection)
    - Stateless — receives inputs, returns inferences; no persistent storage of its own beyond model weights baked into the container image

This three-unit structure is deliberate and conservative. The architecture explicitly avoids microservice sprawl:

- All business logic stays in one place (the Laravel monolith)
- The ML service has a tight, narrow contract — it is not an excuse to fragment business logic
- There is no separate "auth service," "invoice service," or "billing service"
- Cross-cutting concerns (logging, error reporting, request tracing) are coordinated centrally in the Laravel application

The reasoning is OC-2 from §2.2: this is a solo-maintainer, part-time project. The cost of every additional deployable unit (more configuration, more CI surface area, more deployment coordination, more eventual consistency to reason about) is paid by one person. Three units is the minimum that achieves both the PHP-for-business-logic and Python-for-ML separation; anything less would force one of them into the wrong runtime.

## 4.3 Approach to Key Quality Goals

The three quality goals from §1.2 drive specific architectural choices.

### Compliance correctness (priority 1)

- **Library choice.** ZUGFeRD generation uses `horstoeko/zugferd` — the maintained, widely-used PHP library for EN 16931 / FeRD conformance. The library does the heavy lifting; the application code wires invoice data into the library's input format. This isolates the EN 16931 logic in one well-tested place.
- **Validation in CI.** Every CI build runs the KoSIT validator over a curated corpus of generated invoices. The corpus includes edge cases: single-line invoices, multi-rate invoices (19% + 7%), foreign-customer invoices, invoices with service periods, invoices with large quantities. A failed KoSIT validation fails the build.
- **Line-level VAT calculation.** Per EN 16931 BR-CO-17, VAT is computed per line item, rounded, then summed — never on the document subtotal. This is recorded as SRS FR-031 and tested with multi-rate invoice cases that prove the difference between line-level and document-level rounding.
- **Monetary type discipline.** Per SRS FR-026, all monetary amounts are stored as integer cents. No floats anywhere in the data model, the API contract, or the business logic.
- **Immutability after issuance.** Per SRS FR-032, invoice line items are immutable once the invoice transitions out of draft. Modifications require cancellation and reissue.

### Maintainability (priority 2)

- **Single backend codebase.** All business logic in one Laravel application. No service mesh, no shared libraries across multiple deployables (except the future ML service, which has a deliberately narrow contract).
- **Static analysis at level 8.** PHPStan at maximum strictness in CI. No baseline file (every reported issue is a real issue). Forces clean type annotations from the start.
- **Test pyramid.** Pest (PHP) for backend unit and feature tests. Vitest (JS) for frontend unit tests. Playwright (or Cypress, decided later) for end-to-end. Target coverage: ≥70% line, ≥60% branch.
- **Conventional Commits enforced.** commitlint hook on commit-msg. The commit log itself becomes documentation.
- **ADRs for every architectural decision.** Stored in `docs/adr/`, linked from this document and from the SRS where relevant.
- **Diátaxis-structured documentation.** Tutorials, how-to guides, reference, and concepts are kept separate; the docs site navigation enforces this.

### Demonstrability (priority 3)

- **Live docs site on a custom domain.** `docs.skontro.dev` with HTTPS, MkDocs Material, search in English and German, dark mode toggle. Updated on every push to main via GitHub Actions.
- **Live production demo (target).** `app.skontro.dev`, ≥99% uptime, monitored by Uptime Kuma. Public read-only credentials may be published to allow direct exploration of the running system.
- **PDF deliverables.** SRS, SDD, and other formal documents committed as LaTeX-compiled PDFs, downloadable directly from the docs site.
- **Build in public from day one.** All commits, PRs, issues, and Discussions on a public GitHub repository. Decision log kept privately by the maintainer but the public record is sufficient for retracing.

## 4.4 Organizational Decisions

Several non-technical decisions shape the build:

- **License: MIT** — chosen for permissiveness and broad community compatibility. (No ADR; this is universal for permissive OSS.)
- **Single-database multi-tenancy via `tenant_id` discriminator** — see [ADR 0002](../adr/0002-multi-tenancy.md).
- **Conventional Commits 1.0.0 and a husky commit-msg hook to enforce it** — see ADR 0003 (forthcoming in the next milestone).
- **Diátaxis-based documentation structure** — see ADR 0004 (forthcoming in the next milestone).
- **Solo-maintainer cadence: ~2 hours per day, daily commits, weekly tagged releases during the build phase** — this is methodology, not architecture per se, but it constrains the architecture (see OC-2 in §2.2).
