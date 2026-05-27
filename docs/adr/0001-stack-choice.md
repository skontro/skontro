# 0001. Adopt Laravel 11 + Vue 3 + PostgreSQL + Python ML Microservice

- **Status:** Accepted
- **Date:** 2026-05-27
- **Deciders:** Zeeshan Ahmed Raja (sole maintainer, v0.1.0)
- **Consulted:** —
- **Informed:** Future contributors via this ADR

---

## Context

Skontro is an open-source, self-hostable mini-ERP targeting German Mittelstand SMEs, with first-class support for the EN 16931 / ZUGFeRD 2.1 e-invoicing standards and (from v0.3) ML-assisted bookkeeping.

The maintainer's professional background is full-stack work in PHP / Laravel and JavaScript / Vue, with concurrent MSc Data Science studies (Python, PyTorch, classical ML). The Python ecosystem is required for the ML work planned in v0.3 (LayoutLMv3 for receipt OCR, Prophet for forecasting, transformer-based categorization). The remainder of the application is conventional multi-tenant CRUD with a document-generation pipeline — work where Laravel's ergonomics and ecosystem (Eloquent, Sanctum, Spatie roles, Browsershot, `horstoeko/zugferd`) are decisive.

The decision must support:

1. **Velocity for a small team.** Limited engineering capacity in early phases.
2. **First-class ZUGFeRD 2.1 generation.** PHP has the most mature open-source library for this (`horstoeko/zugferd`), maintained against the current FeRD specification.
3. **A clear migration path for ML.** v0.3 introduces three Python-only ML capabilities; the architecture must accommodate them without forcing a stack rewrite.
4. **Self-hostability for SMEs.** Stack choice must not impose heavyweight ops (no Kubernetes requirement, no proprietary runtime).
5. **Recruiter and engineer legibility.** Stack must be widely understood in the European SME market.

---

## Decision

Skontro will be built as a **polyglot system with three deployable units**:

1. **Backend API**: PHP 8.3 + Laravel 11. REST/JSON API under `/api/v1`. Hosts all business logic, authentication, multi-tenancy, e-invoicing pipeline, and persistence.
2. **Frontend SPA**: Vue 3 + Vite + TypeScript + Pinia + Vue Router. Tailwind CSS for styling. Communicates with the backend via the REST API only.
3. **ML Microservice** (introduced in v0.3): Python 3.12 + FastAPI. Exposes a private HTTP interface consumed only by the backend. Handles all ML inference (OCR, categorization, forecasting, anomaly detection).

Shared infrastructure:

- **Relational store:** PostgreSQL 14+ (16 recommended).
- **Cache, session, queue:** Redis 7+.
- **File storage:** S3-compatible (MinIO in development, AWS S3 or Hetzner Object Storage in production).
- **Orchestration:** Docker Compose for both development and reference production. No Kubernetes requirement.

---

## Consequences

### Positive

- **Stack alignment with maintainer expertise.** Best-in-class velocity at the layer where most code is written (CRUD, business logic, document generation).
- **Best-in-class ZUGFeRD support.** `horstoeko/zugferd` is actively maintained, widely used in the German market, and tracks the current FeRD specification.
- **Clean separation of concerns.** The ML service can evolve independently — different deploy cadence, different scaling profile, different test discipline — without polluting the business-logic codebase.
- **Productive frontend.** Vue 3 + Vite gives fast iteration and a small bundle; Pinia is simpler than alternatives for this scope.
- **Operational simplicity.** Docker Compose runs the whole system; no orchestrator required for self-host.
- **Market legibility.** Laravel + Vue is mainstream in Berlin SMEs; Python ML aligns with the broader European data-engineering market.

### Negative

- **Polyglot deployment.** Two runtimes (PHP + Python) in production from v0.3 onward. More OS packages to maintain, more attack surface, more CI complexity.
  - *Mitigation:* containerize everything; reference deployment is `docker compose up`; the ML service is optional and absent from v0.1 and v0.2.
- **Two test stacks.** Pest (PHP) + Vitest / Playwright (JS) from day one; Pytest added in v0.3.
  - *Mitigation:* CI handles them in parallel.
- **Frontend / backend coordination overhead.** API contract must be kept in sync; OpenAPI auto-generation from Scribe handles this.
- **PHP carries a credibility tax in some tech-press circles** that prefer Go, Rust, Elixir, or Node.
  - *Mitigation:* documentation rigor, ADRs, architecture document, and ML service in Python are the counter-signal; the stack is not the message.

### Risks accepted

- If a future contributor strongly prefers Node.js or Go, they may reject the stack on aesthetic grounds. Acceptable: the project is targeted, not universalist.
- If `horstoeko/zugferd` is abandoned, an in-house generator becomes necessary. Acceptable risk: the library is healthy, the spec is finite, and the PHP ecosystem has alternatives.

---

## Alternatives Considered

### Alternative A — Node.js + NestJS + Vue + PostgreSQL

- **Pro:** Single language across frontend and backend; TypeScript end-to-end.
- **Con:** ZUGFeRD library ecosystem in Node is significantly less mature than in PHP. CRUD-heavy code in Laravel ships faster for the maintainer.
- **Verdict:** Rejected. ZUGFeRD ecosystem advantage outweighs language uniformity.

### Alternative B — Django + DRF + Vue + PostgreSQL

- **Pro:** Python everywhere, including ML. Single language for the backend + ML service.
- **Con:** Forces Python at every layer including the parts where Laravel's ecosystem (Sanctum, Spatie roles, Browsershot, `horstoeko/zugferd`) is uniquely strong. The biggest German compliance library is PHP-only.
- **Verdict:** Rejected. German-compliance ecosystem advantage is decisive.

### Alternative C — Spring Boot + Vue + PostgreSQL

- **Pro:** Strong type system, mature enterprise tooling, the Mustang Project (Java ZUGFeRD library) is excellent.
- **Con:** Java verbosity slows solo-maintainer iteration. Boot times and resource footprint inflate development friction.
- **Verdict:** Rejected. Velocity hit is incompatible with the early-phase timeline.

### Alternative D — Phoenix / Elixir + LiveView + PostgreSQL

- **Pro:** Excellent concurrency story, mature framework, beautiful real-time UX.
- **Con:** Small ecosystem for German compliance specifically (no equivalent of `horstoeko/zugferd`). Hiring market is narrow.
- **Verdict:** Rejected. Ecosystem gap.

### Alternative E — Laravel + Inertia.js + Vue (instead of standalone SPA)

- **Pro:** Eliminates the REST API layer; simpler full-stack development; one less moving part.
- **Con:** Forecloses third-party API consumption (planned for v0.4+); harder to deploy frontend separately on a CDN; doesn't suit the future ML-service integration pattern where the backend acts as a true API.
- **Verdict:** Rejected. Long-term API surface value outweighs the simplification.

---

## References

- Laravel 11 documentation — <https://laravel.com/docs/11.x>
- Vue 3 documentation — <https://vuejs.org>
- `horstoeko/zugferd` — <https://github.com/horstoeko/zugferd>
- FeRD ZUGFeRD specification — <https://www.ferd-net.de/standards/zugferd-2.1/>

---

## Revision History

| Version | Date       | Author              | Change                    |
|---------|------------|---------------------|---------------------------|
| 1.0     | 2026-05-27 | Zeeshan Ahmed Raja  | Initial decision recorded |
