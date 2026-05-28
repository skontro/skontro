# VAT Handling — §14 UStG Fields and German VAT Rates

!!! warning "Reference implementation, not certified accounting software"
    See the [Compliance Overview](overview.md) for the full disclaimer.

## §14 UStG — Mandatory invoice fields

Every German invoice must contain the fields specified in **§14 UStG** (Umsatzsteuergesetz) to qualify for VAT deduction by the recipient. Missing or incorrect fields cause the recipient's Finanzamt to deny the deduction during an audit.

Skontro produces invoices containing all §14 UStG mandatory fields:

| Field | Where Skontro stores it | Where it appears in output |
|---|---|---|
| Full name and address of the issuing business | Tenant settings | PDF header + XML SupplierTradeParty |
| Full name and address of the recipient | Customer record | PDF body + XML BuyerTradeParty |
| Steuernummer or USt-ID of the issuer | Tenant settings (one of the two required; both may be present) | PDF footer + XML SpecifiedTaxRegistration |
| Issue date (Rechnungsdatum) | Invoice record | PDF header + XML IssueDateTime |
| Unique sequential invoice number | Per-tenant atomic sequence (R-YYYY-NNNNN) | PDF header + XML ID |
| Description and quantity of goods or services | Invoice line items | PDF body table + XML IncludedSupplyChainTradeLineItem |
| Service date or service period | Invoice record (optional) | PDF body + XML BillingSpecifiedPeriod |
| Net amount per VAT rate | Calculated from line items | PDF totals + XML ApplicableTradeTax (one per rate) |
| Applicable VAT rate (per item) | Per-line VAT rate on each line | PDF body + XML CategoryCode + RateApplicablePercent |
| VAT amount (per rate) | Calculated per line, summed per rate, with line-level rounding | PDF totals + XML ApplicableTradeTax |
| Gross amount | Calculated from net + VAT | PDF totals + XML SpecifiedMonetarySummation |
| Any agreed price reductions (Skonto, rebates) | Invoice line items, optional fields | PDF body + XML AppliedTradePaymentDiscountTerms |
| Reverse-charge notice if applicable (§13b UStG) | Conditional based on transaction type | PDF body note + XML CategoryCode "AE" |
| Kleinunternehmer notice if applicable (§19 UStG) | Conditional, v0.2+ | PDF body note + XML CategoryCode "E" |

## German VAT rates

Germany operates three statutory VAT rates plus a reverse-charge mechanism. Skontro supports all of them.

### 19% — standard rate (§12 (1) UStG)

The default rate. Applies to most goods and services not explicitly assigned to a lower rate. Examples: electronics, professional services, software licenses, consulting.

### 7% — reduced rate (§12 (2) UStG)

Applies to specific categories enumerated in Annex 2 of the UStG. Examples: most food items, books, newspapers, hotel stays, public transport, cultural events.

The classification of a product as 7% versus 19% is the business's responsibility. Skontro provides the rate as a per-product field; selecting the correct rate per product is the operator's job (typically in consultation with the Steuerberater).

### 0% — exempt (§4 UStG)

Various exemptions enumerated in §4 UStG. Examples: medical services, education, rental of residential property, insurance. For exempt transactions, no VAT is charged.

In Skontro, a 0% rate means the line carries no VAT amount but appears in the EN 16931 XML with `CategoryCode = "E"` (exempt). This is distinct from a missing line — it explicitly records that the transaction is exempt rather than rated.

### Reverse-charge (§13b UStG)

For certain B2B transactions — particularly cross-border EU and specific domestic categories — the recipient (not the issuer) is responsible for the VAT. The invoice shows net amounts only; a notice on the invoice records that reverse-charge applies.

In Skontro, an invoice can be marked as reverse-charge. The generation pipeline omits the VAT amount, sets `CategoryCode = "AE"` (reverse-charge) in the EN 16931 XML, and adds a German-language notice on the PDF rendering. The operator is responsible for correctly identifying which transactions qualify for reverse-charge.

## Kleinunternehmer (§19 UStG)

Small businesses below a turnover threshold (currently ~€22,000 in the previous calendar year) qualify as **Kleinunternehmer** under §19 UStG. Kleinunternehmer do not charge VAT on their invoices. Their invoices include a notice indicating their Kleinunternehmer status.

After the 1 January 2028 mandate, Kleinunternehmer must also issue EN 16931-compliant e-invoices, but with 0% VAT throughout.

**v0.1 does not include a Kleinunternehmer mode.** v0.2 adds:

- A tenant-level toggle to enable Kleinunternehmer mode
- Suppression of VAT calculation on issued invoices for Kleinunternehmer tenants
- An automatic German-language notice on the invoice: "Gemäß §19 UStG wird keine Umsatzsteuer berechnet" or equivalent

## Line-level VAT rounding (BR-CO-17)

EN 16931 Business Rule **BR-CO-17** mandates: VAT is computed per line, rounded to two decimal places per line, then summed. The document-level total is the sum of the per-line rounded amounts. It is **not** computed on the document subtotal.

Skontro implements this in the `InvoiceCalculator`:

```text
For each line item:
    line_net    = quantity × unit_price_cents
    line_vat    = round(line_net × vat_rate / 100, 2)
    line_gross  = line_net + line_vat

For each distinct VAT rate appearing on the invoice:
    rate_net   = sum of line_net for lines at this rate
    rate_vat   = sum of line_vat for lines at this rate (already rounded)

Document totals:
    subtotal       = sum of line_net across all lines
    total_vat      = sum of line_vat across all lines
    total          = subtotal + total_vat
```

Unit tests assert this matches what KoSIT expects on at least three multi-rate edge cases.

## VAT-ID validation

Every customer can have an optional **USt-ID** (VAT identification number, format DE + 9 digits in Germany; equivalent format per country in the rest of the EU).

**v0.1 validates format only.** The format check confirms the prefix matches the country code, the number is the correct length for that country, and the checksum (where defined by the country's pattern) computes correctly.

**v0.2 adds online verification via the EU VIES service.** The customer's USt-ID is checked against the official EU registry. If VIES returns "not valid," the user is warned but not blocked (the user may choose to proceed if they trust the recipient and accept the audit risk).

## DATEV export (v0.2+)

The dominant German bookkeeping software is DATEV. Skontro will produce DATEV-compatible CSV exports starting in v0.2, supporting both SKR03 (Standardkontenrahmen für Industrieunternehmen) and SKR04 (für Steuerberater).

The export pipeline:

1. Tenant configures the chart of accounts (SKR03 or SKR04)
2. Tenant maps internal expense categories to SKR03/SKR04 account codes
3. Tenant exports a date range
4. The CSV is in DATEV format (semicolon-separated, CP1252 or UTF-8 encoding, fields per DATEV specification)
5. The Steuerberater imports into DATEV Unternehmen Online

## Limitations

- **No multi-VAT-jurisdiction support.** v0.1 supports German VAT only. Cross-border EU OSS (One Stop Shop) reporting is not in v0.1 or v0.2 scope.
- **No automatic VAT classification.** The operator selects the correct VAT rate per product. Skontro does not infer rates from product descriptions.
- **No automatic reverse-charge detection.** The operator marks an invoice as reverse-charge when applicable.

## References

- §14, §13b, §19, §12 (1)/(2), §4 UStG — Umsatzsteuergesetz
- EN 16931 Business Rule BR-CO-17 — line-level VAT calculation
- DATEV — DATEV CSV format specification (proprietary; available via DATEV)
- VIES — VAT Information Exchange System (EU)
- SRS FR-021, FR-024, FR-031, FR-046 — VAT-handling requirements
