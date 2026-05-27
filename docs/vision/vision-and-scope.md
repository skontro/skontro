# Skontro — Vision and Scope

!!! info "Living Document"
    This is a living document. Material changes require an updated CHANGELOG entry and a v-bumped revision history (see the Revision History table at the end of this document). Last updated: **2026-05-27** (v1.0).

This document defines the business case, target users, scope, and success criteria for Skontro. It is the source of truth for "what we are building and why" and anchors the Software Requirements Specification (SRS), Architecture Decision Records (ADRs), and arc42 architecture document.

---

## 1. Business Requirements

### 1.1 Background and Problem Statement

Germany is mid-way through the largest mandated shift in B2B billing the country has seen since the introduction of the Umsatzsteuergesetz. The Wachstumschancengesetz (BGBl. 2024 I Nr. 108, passed by the Bundesrat on 22 March 2024) introduces a phased obligation around the structured electronic invoice (the e-Rechnung): from **1 January 2025**, every German B2B business must be technically capable of *receiving* an EN 16931-compliant invoice; from **1 January 2027**, businesses with prior-year turnover above **€800,000** must *issue* their invoices in a structured electronic format; from **1 January 2028**, the issue obligation extends to all businesses, including the Kleinunternehmer under §19 UStG. After applicable transition periods, a paper invoice or a plain PDF without the embedded XML payload no longer qualifies the recipient for VAT deduction. Roughly 3.3 million Mittelstand SMEs sit in front of an eight-to-thirty-two month adoption window.

The commercial software market for German bookkeeping is well-developed and almost entirely closed. sevDesk (acquired by Cegid in 2024), Lexware and Lexoffice (Haufe Group), FastBill, Billomat, Easybill, Candis, Moss, Buchhaltungsbutler, and SAP Business One together cover most paid options. Pricing ranges from roughly €15 per company per month at the entry tier to €120 and above for multi-user, multi-entity configurations. Every one of them is closed-source and SaaS-only. For privacy-conscious SMEs, regulated industries, businesses with thin margins, and operators who simply prefer to keep customer data inside their own infrastructure, this is an unsatisfactory market. Customer records, invoices, and the entire ten-year audit trail end up locked into proprietary stores under foreign cloud jurisdictions.

The open-source side of the market is not yet a substitute. ERPNext, Odoo Community Edition, Dolibarr, and Akaunting are general-purpose accounting and invoicing systems with active communities and credible engineering, but none ship German compliance upstream. DATEV export, ZUGFeRD and XRechnung generation, GoBD-aligned archival, the SKR03 and SKR04 charts of accounts, and the §14 UStG mandatory-field discipline all need to be added by the adopter, often by commissioning a partner extension at a cost that approaches the commercial SaaS alternatives over a two-year horizon.

The compliance primitives themselves exist as open source. `horstoeko/zugferd` for PHP, the Mustang Project for Java, and `e-invoice-eu` for Node.js generate and parse EN 16931-conformant XML against the current FeRD specification. They are libraries, not products. They require an application around them: a tenancy model, an invoice lifecycle, a customer record, an expense workflow, a Steuerberater export, an admin UI, an operations runbook. That is the gap Skontro addresses.

Beyond the compliance core, there is a second layer of unaddressed pain in SME bookkeeping. Receipt entry remains overwhelmingly manual and consumes hours per week per bookkeeper. Cash-flow forecasting is either absent or naïve in most SME accounting tools. Expense categorisation is manual, inconsistent, and rarely revisited. Customer late-payment risk is computable from the invoice ledger but is rarely surfaced. These problems are technically tractable with current ML methods, but no German-compliant tool today ships them in the box.

### 1.2 Business Opportunity

A regulatory deadline driving mass adoption, a clear gap in open-source German tooling, and a set of unaddressed ML applications combine into a useful opening. Skontro takes that opening: an open-source mini-ERP that ships EN 16931 e-invoicing out of the box, is MIT-licensed and self-hostable, includes ML capabilities as native features from v0.3, and serves as a credible reference implementation that an engineer can defend in an interview.

### 1.3 Business Objectives

- **BO-1.** Ship a working v0.1.0 release with all functional requirements (FR-001 through FR-062, defined in the forthcoming SRS) implemented and tested, by **Q3 2026**.
- **BO-2.** Achieve a **≥99% pass rate** on the official KoSIT validator for every EN 16931 / ZUGFeRD invoice produced by the continuous-integration test corpus, measured per CI run from v0.1.0 onwards.
- **BO-3.** Operate a public production demo at **`app.skontro.dev`** with **≥99% measured uptime** over rolling 30-day windows once deployed.
- **BO-4.** Integrate three distinct ML capabilities by v0.3: receipt OCR with auto-categorisation, cash-flow forecasting, and expense anomaly detection.
- **BO-5.** Maintain a complete documentation set at **`docs.skontro.dev`** — Vision and Scope, SRS (PDF), SDD (PDF), arc42 architecture document, compliance documents, operations runbook, and API reference — and keep it current with every release.
- **BO-6.** Demonstrate, through the documentation rigour and engineering process, the kind of work expected in regulated German B2B software development, and generate qualified inbound interest from Berlin technology employers. The portfolio motivation is part of the project's purpose and is named explicitly here so the reader does not have to infer it.

### 1.4 Vision Statement

For **German Mittelstand businesses**, who need **affordable, compliant, and self-hostable invoicing and bookkeeping software ahead of the 2027 and 2028 e-Rechnung mandate**, **Skontro** is a **modern open-source mini-ERP** that **provides EN 16931-compliant e-invoicing, DATEV export, GoBD-aligned archival, and ML-assisted bookkeeping out of the box**. Unlike **commercial SaaS alternatives**, our product is **MIT-licensed, self-hostable, costs nothing per seat, and keeps customer data under the customer's own control**.

### 1.5 Success Metrics

| Metric | Target | Measured By | Target Date |
|---|---|---|---|
| GitHub stars on `skontro/skontro` | ≥500 | GitHub API | 2026-12-31 |
| KoSIT validation pass rate on CI test corpus | ≥99% | CI pipeline (KoSIT validator) | continuous from v0.1.0 |
| Documentation site monthly unique visitors | ≥300 | Plausible Analytics (privacy-first, no cookie banner) | 2026-12-31 |
| Production demo uptime, rolling 30 days | ≥99% | Uptime Kuma | continuous from production deploy |
| External contributor pull requests merged | ≥5 | GitHub | 2026-12-31 |
| Qualified inbound employer messages on LinkedIn, weekly rolling average | ≥5 | Manual tally | by end Q3 2026 |
| Maintainer placed into a Berlin engineering role | 1 | Self-report | by end Q3 2026 |

### 1.6 Risks

| ID | Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|---|
| R-1 | The e-Rechnung specification changes before v0.1 ships | L | H | Track the BMF and KoSIT release feeds weekly; the XML generation layer goes through `horstoeko/zugferd`, which absorbs most upstream changes |
| R-2 | A major incumbent open-sources comparable German compliance modules | L | M | Differentiate via the ML layer in v0.3 and via positioning as a reference implementation rather than a commercial competitor |
| R-3 | Scope creep delays v0.1.0 past the planned timeline | M | H | Strict MoSCoW prioritisation in the SRS; an explicit "out of scope" list in §2.3; tagged releases at each internal milestone enforce shipping cadence |
| R-4 | Generated invoices pass KoSIT but fail in real-world exchange | L | H | Round-trip parsing tests against a curated edge-case corpus in CI; an explicit disclaimer that Skontro is a reference implementation, not certified software |
| R-5 | Solo-maintainer fatigue halts the project mid-build | M | H | Documented build plan with a realistic ~2-hour-per-day pace; daily public commits from launch; one decision-log entry per evening for accountability |
| R-6 | Hosting costs exceed the €15/month budget | L | L | Hetzner CX22-class hardware is sufficient for v0.1 demo load; the demo can be downscaled to static rendering or shuttered if abandoned |
| R-7 | Legal exposure from over-claiming compliance | L | M | An explicit "reference implementation, not certified" disclaimer on every compliance page; MIT licence disclaims warranty |
| R-8 | ML feature accuracy disappoints in v0.3 | M | M | ML is framed as assistive and human-in-the-loop, with confidence scores surfaced and no auto-commit of unreviewed categorisations to the books |
| R-9 | Steuerberater rejects the DATEV export format in practice | L | H | Validate exports against real DATEV import in a sandbox before declaring the format stable; track the DATEV-Schnittstellen specification version explicitly |
| R-10 | The Mittelstand market does not adopt OSS at the scale predicted | M | M | The project's value as a reference implementation and as a portfolio piece does not depend on mass adoption; success metrics weight engineering rigour over install count |

---

## 2. Scope and Limitations

### 2.1 Scope of Initial Release (v0.1.0)

- **Authentication and access** — registration, login, logout, session and token management, password policy, role-based access control (owner / admin / accountant / viewer).
- **Multi-tenancy** — single-database design with `tenant_id` discriminator, full data isolation, per-tenant sequential invoice numbering.
- **Customer management** — company and individual customer types, search, filter, sort, soft delete, USt-ID validation, EU country support, address handling.
- **Product and service catalog** — pricing in cents, VAT-rate assignment (19% / 7% / 0%), German units (Stück, Stunde, Kilogramm, Meter, Quadratmeter, Tag, pauschal), archival.
- **Invoice lifecycle** — draft → issued → sent → partially paid → paid → cancelled; line items with per-line VAT rounding per EN 16931 BR-CO-17; payment terms; service periods; notes.
- **E-invoicing** — EN 16931 / ZUGFeRD 2.1 hybrid PDF/A-3 + XML generation, KoSIT-validated in CI, including every §14 UStG mandatory field.
- **Expense tracking** — vendor, date, category, VAT, payment method; receipt upload with SHA-256 deduplication; supported types PDF, JPG, PNG, WebP, HEIC; 10 MB cap.
- **Dashboard** — revenue (current month and current year), open invoices, overdue invoices, expenses, cash position estimate, 12-month timeline, top five customers.
- **Tenant settings** — company info, tax info (USt-ID, Steuernummer, Handelsregister), banking (IBAN / BIC), invoicing defaults.
- **Self-hosting** — `docker compose up` one-command local deployment; a documented production deployment runbook in the docs site.
- **Documentation** — Vision and Scope, SRS (PDF), SDD (PDF), arc42 architecture document, compliance documents, OpenAPI specification, operations runbook — all published at `docs.skontro.dev`.

### 2.2 Scope of Subsequent Releases

**v0.2 — roughly three weeks after v0.1.0:**

- DATEV CSV export (ASCII format, SKR03 and SKR04 mappings).
- Inbound XRechnung and ZUGFeRD parsing (paste or upload — auto-creates an expense draft).
- Umsatzsteuer-Voranmeldung preview.
- SEPA `pain.001` batch payment file export.
- Email invoice delivery via real SMTP.
- Full GoBD-aligned append-only audit log.
- Kleinunternehmer §19 UStG mode.
- Password reset and email verification.
- Multi-user invitation flow.

**v0.3 — the ML layer, roughly four weeks after v0.2:**

- Receipt OCR using LayoutLMv3, fine-tuned on SROIE and CORD datasets.
- Auto-categorisation via DistilBERT, fine-tuned on German expense descriptions.
- Cash-flow forecasting with Prophet (90-day horizon, fan chart with confidence intervals).
- Transaction anomaly detection using Isolation Forest.
- LLM-powered "ask your books" assistant via tool-calling, without raw SQL access.
- Separate `ml-service/` Python FastAPI Docker container.

**v0.4 — roughly three weeks after v0.3:**

- Full internationalisation (German default, English fallback covered end to end).
- Customer and vendor portal.
- Recurring invoices.
- Multi-currency.
- API documentation polish; public Postman collection.

### 2.3 Limitations and Exclusions

- **Certified GoBD and HGB compliance.** Skontro implements the spirit of GoBD: immutability of issued invoices, append-only audit log (v0.2), and ten-year archival per §147 AO. Production use for regulated bookkeeping still requires verification by the customer's Steuerberater and may require an audit by a qualified Wirtschaftsprüfer. Skontro is and will remain a reference implementation, not certified accounting software.
- **Payroll (Lohnbuchhaltung).** Outside the scope of an invoicing and expense ERP.
- **Banking integrations beyond SEPA file export.** Direct bank-account integration via PSD2 / FinTS / HBCI is intentionally not pursued.
- **Tax filing automation.** Skontro produces DATEV-compatible exports; the Steuerberater files.
- **Multi-company consolidations** beyond per-tenant separation.
- **Languages beyond German and English.** Additional locales are welcome from contributors but are not prioritised by the maintainer.
- **Mobile-native applications.** The web app is responsive from a 375 px viewport; no native iOS or Android apps are planned.

---

## 3. Business Context

### 3.1 Stakeholder Profiles

| Stakeholder | Major Value | Attitudes | Major Interests | Constraints |
|---|---|---|---|---|
| Mittelstand business owner | Compliant invoicing without €600+/year SaaS lock-in | Cautious about new tools; trusts referrals from peers and from their Steuerberater | Low total cost of ownership, data sovereignty, simple UX in German | Limited technical capacity; significant reliance on the Steuerberater for tooling decisions |
| Steuerberater (tax advisor) | Receives clean, compliant data from clients without rework | Conservative; protective of established DATEV-centric workflows | DATEV compatibility, audit-trail integrity, predictable file formats, no surprises during the Umsatzsteuer-Voranmeldung cycle | Gatekeeper power: if the Steuerberater does not accept Skontro exports, the client will not use Skontro |
| In-house bookkeeper / accountant | Hours saved per week on routine entry | Pragmatic; values reliability over novelty | Speed, accuracy, undo capability, a German user interface, clear error messages | Often already uses DATEV directly; expects parity, not regression |
| Open-source contributor | Quality engineering and a credible roadmap | Curious; merit-driven | Code quality, clear architectural decisions (ADRs), responsive maintainer review | Limited time; values a low-friction contribution path |
| Self-host operator | Deploys and runs Skontro on a customer's behalf | Practical; expects boring infrastructure | One-command deploy, documented runbook, observable system, predictable upgrade path | Often the same person as the in-house bookkeeper at small companies |
| Regulator (BMF / Finanzamt) | Tax compliance and fraud reduction | Indirect — reads compliance documentation only if an audit is triggered | EN 16931 conformance, GoBD adherence, archival integrity | No direct customer relationship; influence is regulatory rather than commercial |
| Project maintainer (Zeeshan Ahmed Raja) | Portfolio credibility and engineering employment | Highly motivated; building in public | Demonstrating senior-level engineering practices; landing a Berlin engineering role | Solo maintainer; ~2 hours per day; MSc Data Science studies running concurrently |

### 3.2 Operating Environment

The reference deployment is Docker Compose on commodity Linux servers. The baseline target is the Hetzner CX22-class node: 2 vCPU, 4 GB RAM, 40 GB NVMe, around €5 per month. No Kubernetes dependency. PostgreSQL 14 or later for the relational store. Redis 7 or later for cache, session, and queue. S3-compatible object storage for files — MinIO in development, AWS S3 or Hetzner Object Storage in production. Cloudflare for DNS and TLS edge.

Concurrency expectations for the v0.1 reference deployment: up to 100 concurrent users per tenant; up to 100,000 invoices and 200,000 expenses per tenant per year without tuning; multi-tenancy via single-database design is sufficient up to roughly 50 tenants per instance. Beyond that, a sharding strategy is required, which is out of scope for v0.1.

Browser support targets the latest two stable versions of Chrome, Firefox, Safari, and Edge. The UI is responsive from a 375 px viewport (iPhone 13 mini and equivalent). No native mobile applications.

Localisation defaults to German for the entire user interface; English is provided as a fallback. All compliance-relevant terminology is preserved in German: Rechnung, Beleg, USt-ID, Steuernummer, Handelsregister, GoBD, Wachstumschancengesetz, Kleinunternehmer.

---

## 4. References

- BMF e-Rechnung guidance — `https://www.bundesfinanzministerium.de/` (search: "E-Rechnung")
- EN 16931 — European standard for electronic invoicing (CEN)
- ZUGFeRD — FeRD landing page (currently ZUGFeRD 2.4; v0.1 targets 2.1) — `https://www.ferd-net.de/standards/zugferd`
- XRechnung — XStandards Einkauf (canonical since 2023, formerly KoSIT) — `https://xeinkauf.de/xrechnung/`
- KoSIT validator — `https://github.com/itplr-kosit/validator`
- GoBD letter — BMF letter of 28 November 2019
- §14 UStG and §19 UStG — Umsatzsteuergesetz
- §147 AO — Abgabenordnung (archival requirements)
- HGB — Handelsgesetzbuch
- Wachstumschancengesetz — BGBl. 2024 I Nr. 108
- `horstoeko/zugferd` (PHP library) — `https://github.com/horstoeko/zugferd`
- ADR 0001 — Stack Choice — [../adr/0001-stack-choice.md](../adr/0001-stack-choice.md)
- arc42 template — `https://arc42.org`

---

## Revision History

| Version | Date | Author | Change |
|---|---|---|---|
| 1.0 | 2026-05-27 | Zeeshan Ahmed Raja | Initial release |
