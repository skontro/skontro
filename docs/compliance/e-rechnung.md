# E-Rechnung — German Electronic Invoicing Mandate

!!! warning "Reference implementation, not certified accounting software"
    See the [Compliance Overview](overview.md) for the full disclaimer. This document describes how Skontro addresses the German e-Rechnung mandate. Production use requires verification by the operator's Steuerberater.

## What changed

The **Wachstumschancengesetz** (Growth Opportunities Act), passed by the Bundesrat on 22 March 2024 and published as BGBl. 2024 I Nr. 108, introduced mandatory structured electronic invoicing for German B2B transactions. The mandate replaces the previous regime where plain PDFs (with prior recipient consent) qualified as VAT-deductible invoices. After applicable transition periods, only structured EN 16931-compliant formats qualify.

The timeline:

| Date | Obligation |
|---|---|
| 1 January 2025 | All German B2B businesses must be **able to receive** EN 16931-compliant e-invoices |
| 1 January 2027 | Businesses with prior-year turnover above €800,000 must **issue** e-invoices |
| 1 January 2028 | All businesses, including Kleinunternehmer under §19 UStG, must issue e-invoices |

A "B2B transaction" here means a transaction between businesses established in Germany. B2C (sales to private individuals) and cross-border B2B are governed by separate rules.

## What counts as an e-invoice under EN 16931

The European standard EN 16931 defines a structured invoice that can be processed without human intervention. The format must:

- Be machine-readable in a structured form (XML)
- Contain all fields required by the European Core Invoice (EN 16931 data model)
- Conform to one of the recognised syntaxes — in Germany, the relevant ones are **XRechnung** (pure XML, mandatory for B2G) and **ZUGFeRD 2.0.1 or later** (hybrid PDF/A-3 + XML)
- Pass the official KoSIT validator

Three things do **not** count under the post-2025 regime:

- Plain PDF (without embedded XML)
- Image scans of paper invoices
- Word, Excel, or other proprietary office formats

## What Skontro generates

For every issued invoice, Skontro produces a single file: a **hybrid PDF/A-3 + XML** following the **ZUGFeRD 2.1 EN16931 profile**.

- The **PDF/A-3 part** is a human-readable rendering of the invoice — what the recipient sees if they open the file with any PDF reader.
- The **embedded XML** is the structured EN 16931 representation, named `factur-x.xml` as required by the ZUGFeRD specification.

The single file satisfies both legal needs at once: the recipient who wants to read it sees a normal invoice; the recipient's automation pipeline can extract the XML and post it directly to their books.

A separate XRechnung-only output is not part of v0.1 (it is added in v0.2 alongside DATEV export). For B2G transactions where XRechnung is specifically required, v0.1 produces a ZUGFeRD file that contains the same EN 16931 XML payload, but with a PDF wrapper — most German government recipients accept this.

## Field coverage

Every issued invoice includes all fields required by **§14 UStG**:

| Field | Source |
|---|---|
| Full name and address of the issuing business | Tenant settings |
| Full name and address of the recipient | Customer record |
| Steuernummer or USt-ID of the issuer | Tenant settings |
| Invoice date (Rechnungsdatum) | Invoice record |
| Invoice number (unique, sequential) | Atomic per-tenant sequence (FR-013) |
| Description and quantity of goods or services | Invoice line items |
| Service date or service period | Invoice record |
| Net amount per VAT rate | Calculated from line items |
| Applicable VAT rate | Per-line VAT rate |
| VAT amount | Calculated per line, rounded per BR-CO-17 |
| Gross amount | Calculated total |
| Any agreed price reductions (Skonto) | Invoice line items (optional) |
| If applicable: reverse-charge notice (§13b UStG) | Conditional based on customer |
| If applicable: Kleinunternehmer notice (§19 UStG) | Conditional, v0.2+ |

See the [SRS](../formal/srs/srs.pdf), requirements FR-029 through FR-048, for the precise behaviour at each point in the pipeline.

## Validation in CI

Every CI build runs the **KoSIT validator** — the official German XRechnung / ZUGFeRD reference validator — against a curated test corpus. The corpus includes:

- Single-line invoice (the trivial case)
- Multi-rate invoice mixing 19% and 7%
- Multi-rate invoice with three different VAT rates
- Foreign customer (EU country code other than DE)
- Foreign customer in a non-EU jurisdiction
- Invoice with explicit service period
- Invoice with large quantities (testing edge cases in number formatting)
- Invoice in reverse-charge mode (§13b)
- Invoice with Skonto (early-payment discount)
- Long line-item description (testing XML escaping)

A failed KoSIT validation fails the CI build. The target is ≥99% pass rate per V&S §1.5 success metric.

## How recipients consume the file

Three reading paths, all from the same file:

1. **Open it as a PDF.** The PDF/A-3 layer contains the human-readable rendering. Any modern PDF reader displays it normally.
2. **Extract the embedded XML.** The recipient's bookkeeping software extracts `factur-x.xml` from the PDF attachments and posts the data to their books programmatically.
3. **Validate it.** The recipient (or their software) can re-validate the file against EN 16931 / KoSIT to verify it was produced correctly.

## Limitations and exclusions

- **Peppol network distribution.** Skontro generates the file but does not transmit via the Peppol network. The user is responsible for delivery — email, download, or in v0.2 onwards, direct SMTP send.
- **Continuous Transaction Control (CTC).** No real-time submission to the Finanzamt. If Germany adopts a CTC model in the future, the architecture supports adding it.
- **Inbound XRechnung parsing.** v0.1 emits invoices but does not parse incoming XRechnung. v0.2 adds inbound parsing for the expense flow.

## References

- Wachstumschancengesetz — BGBl. 2024 I Nr. 108
- EN 16931 — European standard for electronic invoicing
- ZUGFeRD 2.1 specification — FeRD
- XRechnung specification — KoSIT
- KoSIT validator — `https://github.com/itplr-kosit/validator`
- `horstoeko/zugferd` PHP library — `https://github.com/horstoeko/zugferd`
- BMF guidance on e-Rechnung — `https://www.bundesfinanzministerium.de/` (search: "E-Rechnung")
- SRS FR-041 through FR-048 — implementation requirements
