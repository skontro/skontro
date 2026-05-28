# GoBD — Digital Bookkeeping Principles

!!! warning "Reference implementation, not certified accounting software"
    See the [Compliance Overview](overview.md) for the full disclaimer. GoBD compliance is the operator's responsibility, not Skontro's. Skontro implements the spirit of the principles; verification against the operator's specific obligations is the role of the Steuerberater.

## What GoBD is

**GoBD** — Grundsätze zur ordnungsmäßigen Führung und Aufbewahrung von Büchern, Aufzeichnungen und Unterlagen in elektronischer Form sowie zum Datenzugriff — are administrative principles issued by the German Federal Ministry of Finance (BMF) governing how businesses must create, process, store, and provide digital access to tax-relevant records.

The principles are not a standalone statute. They specify how the obligations in the German **Abgabenordnung (AO)** — sections §145, §146, §147 — apply to digital records. In practice, however, the GoBD are binding for any business subject to those AO sections, which is essentially every commercial business in Germany.

The current foundational version is the **BMF letter dated 28 November 2019** (IV A 4 — S 0316 / 19 / 10003 :001), which replaced the original 2014 GoBD. The 2019 version was updated by the **BMF letter dated 11 March 2024** (concerning data access during audits) and further aligned with Germany's e-invoicing mandate in July 2025.

## The core principles

GoBD enumerates a small set of principles. The ones relevant to Skontro:

| Principle | German term | Skontro implementation |
|---|---|---|
| **Orderliness** | Ordnungsmäßigkeit | Bookings recorded systematically; documents categorised consistently; audit trail per state transition. |
| **Completeness** | Vollständigkeit | Every issued invoice and every recognised expense recorded; no silent deletes; soft-delete preserves history. |
| **Accuracy** | Richtigkeit | Bookings recorded in real time as documents are issued; monetary calculations in cents (no floating-point); VAT computed per line item per BR-CO-17. |
| **Timeliness** | Zeitgerechtigkeit | Documents recorded at issuance, not back-dated. Issue date and recording date both retained. |
| **Order** | Ordnung | Each document has a unique, sequential identifier (R-YYYY-NNNNN for invoices, K-YYYY-NNNNN for customers, E-YYYY-NNNNN for expenses). |
| **Unalterability** | Unveränderbarkeit | Issued invoices are immutable. Cancellation requires a reason and produces an audited state transition. The append-only audit log lands in v0.2. |
| **Traceability and verifiability** | Nachvollziehbarkeit | Every state transition recorded with timestamp, actor, old and new state. Cancellations carry a free-text reason. |
| **Availability** | Verfügbarkeit | Source files (receipt PDFs, generated invoice PDFs, embedded XML) preserved indefinitely. Retention follows §147 AO. |
| **Machine readability** | Maschinelle Auswertbarkeit | All data exportable in machine-readable formats (CSV, JSON, XML). DATEV CSV export available from v0.2. |

The 2025 BMF amendment, aligning GoBD with the e-Rechnung mandate, clarifies that **electronic invoices must be archived in their original machine-readable XML format** — not only as the human-readable PDF rendering. Skontro's hybrid PDF/A-3 + XML format satisfies this naturally: the file contains both the XML and the PDF in a single archival artifact.

## Retention periods

**§147 (3) AO** specifies the retention periods for tax-relevant documents. The two that matter for Skontro:

| Document type | Retention | Source |
|---|---|---|
| Books, records, invoices, balance sheets | **10 years** | §147 (3) AO |
| Trade letters, other tax-relevant correspondence | **6 years** | §147 (3) AO |

Skontro classifies all issued invoices and recognised expenses under the 10-year rule. The retention starts at the end of the calendar year in which the document was created — so an invoice issued in March 2026 must be retained until at least 31 December 2036.

## How Skontro supports GoBD compliance

| GoBD requirement | Skontro mechanism |
|---|---|
| Immutability of issued invoices | Invoice state machine: once an invoice moves out of `draft`, line items are frozen. Modifications require cancellation + reissue. |
| Audit trail of changes | Every state transition recorded with `actor_id`, `timestamp`, `old_state`, `new_state`. Cancellations require a reason. |
| Original file preservation | Generated PDF/A-3 + XML invoices and uploaded receipts stored in S3-compatible storage, keyed by SHA-256 hash. Files never overwritten. |
| Deduplication of receipts | Upload of a receipt with the same SHA-256 hash is rejected with a warning indicating the existing expense(s) that reference it. Prevents accidental double-recording. |
| 10-year retention | No automatic data purge. Backup procedures (NFR-025, NFR-026) document the operator's role in preserving backups for the full retention period. |
| Machine-readable export | DATEV CSV export in v0.2; raw JSON export of all bookkeeping records available via the API at any time. |
| Audit access | The tenant owner can grant temporary read-only access to a Steuerberater or auditor by issuing a Sanctum token scoped to read operations. |

## What is the operator's responsibility, not Skontro's

GoBD compliance is ultimately the responsibility of the business that operates the books, not of the software that records them. Specifically, the operator is responsible for:

- **Verfahrensdokumentation.** A written description of the bookkeeping process — which software is used, how documents flow, who has access, what backup and retention measures are in place. GoBD requires this documentation; Skontro does not generate it (a future template may be provided). The operator's Steuerberater typically helps prepare it.
- **Backups.** Skontro is the application; the operator is responsible for backing up the database, the S3-compatible storage bucket, and the application configuration. NFR-025 and NFR-026 document the recommended approach; the operator carries it out.
- **Access controls.** The operator decides who has which role (owner, admin, accountant, viewer) and removes access when employees leave. Skontro enforces role-based authorisation; the operator manages the role assignments.
- **Steuerberater coordination.** Periodic export, review, and submission of data to the Steuerberater is the operator's process. Skontro produces the exports; the operator hands them over.

## Limitations and exclusions

Honest scope boundaries:

- **No formal GoBD certification.** Skontro is not audited or certified by any independent body. The principles are implemented to the maintainer's understanding of the BMF guidance; production use for tax-relevant bookkeeping requires confirmation from the operator's Steuerberater.
- **The append-only audit log lands in v0.2.** v0.1 records state transitions in the invoice record itself but does not yet write a separate, append-only audit log table. v0.2 adds this.
- **Verfahrensdokumentation template not provided.** Each operator prepares their own with their Steuerberater. A future Skontro release may include a starter template.
- **Cash register integration (KassenSichV / TSE).** Out of scope. Skontro is an invoicing and expense ERP, not a point-of-sale system.

## References

- GoBD foundational letter — BMF-Schreiben vom 28. November 2019 (IV A 4 — S 0316 / 19 / 10003 :001)
- GoBD data access update — BMF-Schreiben vom 11. März 2024
- GoBD e-invoicing alignment — BMF clarification, July 2025
- §145, §146, §147 AO — Abgabenordnung
- arc42 Section 4 — Solution Strategy approach to compliance
- SRS NFR-045 — 10-year archival requirement
- SRS FR-032, FR-053 — invoice and expense immutability
