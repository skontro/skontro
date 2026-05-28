# Compliance Overview

!!! warning "Reference implementation, not certified accounting software"
    Skontro is an open-source software project provided under the MIT License (see [LICENSE](https://github.com/skontro/skontro/blob/main/LICENSE)). It implements German invoicing and bookkeeping requirements to the best of the maintainer's understanding of the relevant standards and regulations. It is **not** certified by a Wirtschaftsprüfer or any other independent body. Production use for regulated bookkeeping requires verification by the operator's Steuerberater. The MIT License disclaims all warranties, including fitness for any particular purpose.

This document summarises which German and European compliance regimes Skontro addresses and where the detail lives.

## The regulatory landscape

Germany's electronic invoicing landscape changed in 2024 with the **Wachstumschancengesetz** (BGBl. 2024 I Nr. 108), which phases in mandatory structured electronic invoicing for B2B business in three steps:

| Date | Obligation | Affected businesses |
|---|---|---|
| 1 January 2025 | **Receive obligation** | All German B2B businesses must be technically capable of receiving EN 16931-compliant e-invoices |
| 1 January 2027 | **Issue obligation, phase 1** | Businesses with prior-year turnover above €800,000 |
| 1 January 2028 | **Issue obligation, phase 2** | All businesses, including Kleinunternehmer under §19 UStG |

After the applicable transition period, plain PDFs and paper invoices no longer qualify for VAT deduction by the recipient — only structured EN 16931-compliant formats do. This single regulatory change drives most of Skontro's design.

## Compliance regimes covered

Skontro addresses five compliance regimes. Each has its own detailed document.

| Regime | Scope | Document |
|---|---|---|
| **EN 16931 e-invoicing** | Structured invoice format mandated by the Wachstumschancengesetz. Skontro generates the hybrid PDF/A-3 + XML variant (ZUGFeRD 2.1, EN16931 profile). | [E-Rechnung →](e-rechnung.md) |
| **ZUGFeRD 2.1** | The specific hybrid PDF/A-3 + XML standard used for outbound invoices. | [ZUGFeRD →](zugferd.md) |
| **GoBD** | German rules for digital bookkeeping: immutability, traceability, retention, audit access. Aligned with 2024 and 2025 BMF updates. | [GoBD →](gobd.md) |
| **§14 UStG mandatory fields** | The fields every German invoice must contain to qualify for VAT deduction. | [VAT handling →](vat-handling.md) |
| **VAT rates and special cases** | 19% standard (§12 (1) UStG), 7% reduced (§12 (2) UStG), 0% / exempt (§4 UStG), reverse-charge (§13b UStG), Kleinunternehmer (§19 UStG). | [VAT handling →](vat-handling.md) |

## What Skontro does

For each regime, Skontro:

- **EN 16931 / ZUGFeRD 2.1.** Generates conformant hybrid PDF/A-3 + XML invoices for all issued invoices, using the maintained PHP library `horstoeko/zugferd`. Validates output against the official KoSIT validator on every CI build over a curated test corpus.
- **GoBD.** Treats issued invoices as immutable; records all state transitions with timestamp and actor in an audit trail; retains invoices and source receipts indefinitely (no automatic purge); v0.2 adds an append-only audit log meeting the GoBD principle of unalterability.
- **§14 UStG.** Includes all mandatory fields in every issued invoice: full names and addresses, Steuernummer or USt-ID, sequential invoice number, invoice date, service date or period, quantity and description, net amount per VAT rate, applicable VAT rate, VAT amount, gross amount, any agreed price reductions, and applicable special-case references (reverse-charge, Kleinunternehmer).
- **VAT.** Supports the four German VAT rates (19%, 7%, 0%, reverse-charge §13b) for v0.1. Adds Kleinunternehmer §19 UStG mode in v0.2. Computes VAT per line item with line-level rounding per EN 16931 BR-CO-17 — never on the document subtotal, which would silently fail validation on multi-rate invoices.

## What Skontro does not do

Honest scope boundaries that every reader should understand:

- **Certified GoBD or HGB conformance.** Skontro implements the spirit of GoBD but is not certified. Production use requires the operator's Steuerberater to confirm the implementation meets the operator's specific obligations.
- **Tax filing automation.** Skontro produces DATEV-compatible exports (v0.2+) that the Steuerberater can import. Skontro does not file with the Finanzamt.
- **VIES online VAT-ID verification.** v0.1 validates VAT-ID format only. Online verification against the EU VIES service is added in v0.2.
- **Payroll (Lohnbuchhaltung).** Out of scope. Skontro is an invoicing and expense ERP, not a payroll system.
- **Real-time reporting to authorities (CTC).** Skontro does not implement Continuous Transaction Control reporting. If Germany adopts a CTC model in the future, the architecture supports adding it, but it is not in current scope.
- **Banking integrations beyond SEPA export.** Direct PSD2 / FinTS / HBCI integration is intentionally not pursued.

## How compliance is verified

| Mechanism | Frequency | Detail |
|---|---|---|
| **KoSIT validation** | Every CI build | Test corpus of at least 10 invoices covering single-line, multi-rate, foreign-customer, no-service-period, and large-quantity edge cases. Build fails on any validation error. |
| **PDF/A-3 conformance** | Every CI build | verapdf validator confirms the hybrid PDF/A-3 + XML output meets ISO 19005-3:2012. |
| **§14 UStG field coverage** | Unit test suite | Tests assert each mandatory field appears in both the rendered PDF and the embedded XML for every test invoice. |
| **GoBD immutability** | Integration tests | Tests assert that issued invoices cannot be modified, that cancellation requires a reason, and that all state transitions are recorded. |
| **DATEV CSV format** | Integration tests (v0.2+) | Tests assert generated exports parse cleanly when fed to a DATEV import simulator. |
| **External review** | Discretionary | The maintainer welcomes external review by Steuerberater or Wirtschaftsprüfer. No formal certification programme is planned. |

## References

- Wachstumschancengesetz — BGBl. 2024 I Nr. 108
- §14 UStG, §19 UStG, §12 (1)/(2) UStG, §4 UStG, §13b UStG — Umsatzsteuergesetz
- §147 AO — Abgabenordnung, retention periods
- GoBD — BMF-Schreiben vom 28. November 2019 (foundational), 11. März 2024 (data access update), July 2025 (e-invoicing alignment)
- EN 16931 — European standard for electronic invoicing
- ZUGFeRD 2.1 — FeRD specification
- KoSIT — Koordinierungsstelle für IT-Standards, XRechnung specification and reference validator
- `horstoeko/zugferd` — PHP library used for ZUGFeRD generation
