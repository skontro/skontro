# 1. Introduction and Goals

!!! info "About this document"
    This document describes the software architecture of **Skontro** following the [arc42](https://arc42.org) template. It is published alongside the [Vision and Scope](../vision/vision-and-scope.md) (the "why and for whom") and the [Software Requirements Specification](../formal/srs/srs.pdf) (the "what shall it do"). This arc42 document answers "how is it built and how does it hang together."

## 1.1 Requirements Overview

Skontro is an open-source self-hostable mini-ERP for the German Mittelstand. The system addresses the regulatory shift toward mandatory electronic invoicing introduced by the Wachstumschancengesetz (BGBl. 2024 I Nr. 108): all German B2B businesses must be able to receive EN 16931-compliant e-invoices from **1 January 2025**, all businesses with prior-year turnover above €800,000 must issue them from **1 January 2027**, and all businesses including Kleinunternehmer under §19 UStG from **1 January 2028**.

Existing commercial software (sevDesk, Lexware, Candis, Moss) is closed-source SaaS in the €15–€120 per company per month range. Existing open-source ERPs (ERPNext, Odoo Community, Dolibarr) do not include German compliance in upstream distributions. Skontro fills this gap.

A condensed feature summary follows. The full requirements catalogue is in the [SRS PDF](../formal/srs/srs.pdf).

| Capability | Reference |
|---|---|
| Multi-tenant authentication, RBAC (owner / admin / accountant / viewer) | SRS §3.2.1, §3.2.2 |
| Customer and product/service catalogue with EU country support | SRS §3.2.3, §3.2.4 |
| Invoice lifecycle (draft → issued → sent → paid/partially paid → cancelled) | SRS §3.2.5 |
| EN 16931 / ZUGFeRD 2.1 hybrid PDF/A-3 + XML generation, KoSIT-validated | SRS §3.2.6 |
| Expense tracking with receipt upload (PDF/JPG/PNG/WebP/HEIC), SHA-256 deduplication | SRS §3.2.7 |
| Dashboard with revenue, open/overdue invoices, 12-month timeline, top customers | SRS §3.2.8 |
| Tenant settings: company info, USt-ID, Steuernummer, Handelsregister, IBAN/BIC | SRS §3.2.9 |
| DATEV export, inbound XRechnung parsing, USt-VA preview (v0.2+) | V&S §2.2 |
| ML layer: receipt OCR, expense categorization, cash-flow forecasting (v0.3+) | V&S §2.2 |
| Self-hosting via `docker compose up`; no Kubernetes requirement | ADR 0001 |

## 1.2 Quality Goals

The architecture optimizes for three quality attributes, in this priority order. Every architectural decision is evaluated against these.

| # | Goal | Scenario | Rationale |
|---|---|---|---|
| 1 | **Compliance correctness** | Every issued invoice passes the official KoSIT validator (the German XRechnung/ZUGFeRD reference validator) with zero errors. Validation runs on every CI build against a curated test corpus. | The whole project's value proposition rests on producing legally usable invoices. A wrong invoice is worse than no invoice — it can trigger tax-deduction rejection by the recipient. |
| 2 | **Maintainability** | A developer joining the project can navigate from "I need to change how VAT is calculated" to the relevant code in under 5 minutes. Test coverage is ≥70% line / ≥60% branch. PHPStan level 8 passes with no baseline. ESLint and Prettier clean. | Skontro is a solo-maintainer project. Future contributors and future-me must be able to make changes without rediscovering the system every time. |
| 3 | **Demonstrability** | The full documentation set (V&S, SRS, arc42, compliance documents, ADRs, API reference, operations runbook) is published at `docs.skontro.dev`, kept current with every release, and downloadable as PDFs where appropriate (SRS, SDD, formal documents). The reference deployment runs at `app.skontro.dev` with ≥99% uptime measured over rolling 30-day windows. | Skontro exists in part as a portfolio piece. Engineering rigour is the message; the rigour must be visible from a single click on a public URL. |

These three goals occasionally conflict. **Compliance correctness wins.** Example: if a refactor would simplify the codebase (maintainability +) but introduce any risk to invoice generation (compliance −), the refactor is rejected or paired with extensive test coverage of the invoice pipeline.

## 1.3 Stakeholders

| Role | Contact | Expectations of the architecture |
|---|---|---|
| Project maintainer (Zeeshan Ahmed Raja) | hauptraja@github | A buildable, testable, deployable system that demonstrates senior-level engineering practices and serves as Berlin-job-search portfolio evidence. |
| Mittelstand business owner | Indirect via downstream adopters | A self-hostable system that produces legally usable invoices, doesn't require monthly payments, keeps customer data on infrastructure they control, and runs without specialized ops staff. |
| Steuerberater (tax advisor) | Indirect via clients | DATEV CSV export that imports cleanly into DATEV Unternehmen Online (v0.2). Audit-trail integrity that holds up in case of a Betriebsprüfung. |
| In-house bookkeeper / accountant | Indirect via tenant admin | Speed and predictability on routine entry. German UI. Undo capability. Receipt deduplication so the same expense isn't logged twice by mistake. |
| Self-host operator | Indirect via deployment guide | A `docker compose up` that works, a documented backup/restore procedure, observable system state via logs and metrics. |
| Open-source contributor | GitHub | Clear contribution guidelines, responsive maintainer, ADRs explaining the non-obvious decisions, test suite that runs locally without exotic setup. |
| Compliance reviewer (BMF, KoSIT, Wirtschaftsprüfer) | Indirect via audit | Documented EN 16931 / ZUGFeRD conformance, §14 UStG mandatory fields verification, GoBD archival policy, explicit disclaimer that Skontro is a reference implementation, not certified software. |
