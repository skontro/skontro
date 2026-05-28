# ZUGFeRD 2.1 — Hybrid Invoice Format

!!! warning "Reference implementation, not certified accounting software"
    See the [Compliance Overview](overview.md) for the full disclaimer.

## What ZUGFeRD is

**ZUGFeRD** — Zentraler User Guide des Forums elektronische Rechnung Deutschland — is a hybrid invoice format developed by the German Forum elektronische Rechnung Deutschland (FeRD). The format combines:

- A **human-readable PDF/A-3 document** (the visual invoice anyone can open)
- A **structured XML payload** (`factur-x.xml`) embedded as an attachment within the same PDF file

The result: one file that satisfies both regulatory machine-readability requirements (the embedded XML) and human-readability needs (the PDF rendering). Recipients with bookkeeping automation extract the XML; recipients without automation open the PDF normally.

ZUGFeRD 2.1 aligns with the **EN 16931** European standard and uses the same data model as the French **Factur-X** specification (the two are technically equivalent at the EN16931 profile level).

## Profile choice

ZUGFeRD 2.1 defines five profiles, in increasing order of completeness:

| Profile | Use case | Skontro support |
|---|---|---|
| MINIMUM | Reporting only, not a legal invoice | Not used |
| BASIC WL | Same as MINIMUM, without line items | Not used |
| BASIC | Simple invoices, limited fields | Not used |
| **EN16931** (the "Comfort" profile) | Aligned with EN 16931 mandatory fields | **Used by Skontro** |
| EXTENDED | EN16931 + optional fields | Not used in v0.1 |

Skontro generates the **EN16931 profile** exclusively. It is sufficient for all B2B and B2G use cases that the Wachstumschancengesetz mandate covers, and it is the profile validated by the KoSIT reference validator.

## File structure

Every Skontro-generated invoice is one file with this structure:

```text
invoice.pdf  (PDF/A-3, ISO 19005-3:2012)
├── Visible page content    (the human-readable invoice)
├── XMP metadata            (PDF/A-3 conformance markers, ZUGFeRD profile)
└── Embedded attachment
    └── factur-x.xml        (EN 16931 XML payload, ZUGFeRD 2.1 EN16931 profile)
```

The attachment is associated with the document via the PDF/A-3 `AFRelationship` property, set to `Source`. This is the relationship value mandated by the ZUGFeRD specification — it signals to processing software that the XML is the canonical machine-readable source for the invoice data.

## Generation pipeline

The Skontro generation pipeline runs in five stages, all asynchronous via the queue:

1. **InvoiceCalculator.** Computes per-line VAT (line-level rounding per EN 16931 BR-CO-17), per-rate subtotals, and the document total. Outputs a normalised structure.
2. **Blade template rendering.** A Laravel Blade template produces HTML that renders the invoice visually. The template uses the tenant's company info, USt-ID, IBAN, bank details, and the invoice line items.
3. **Browsershot (Chromium) → PDF.** The HTML is rendered to a PDF by a headless Chromium instance via the Browsershot package.
4. **Ghostscript → PDF/A-3.** The standard PDF is converted to PDF/A-3 conformance, with the sRGB ICC profile embedded.
5. **horstoeko/zugferd → XML embedding.** The library generates the `factur-x.xml` from the normalised invoice structure and embeds it into the PDF/A-3 as an attachment with the correct `AFRelationship`.

Each stage has a typed exception class. A failure at any stage logs the structured error and retries with backoff. Total pipeline latency target: p95 < 3 seconds for a typical 10-line invoice (SRS NFR-004).

## Unit code handling

EN 16931 requires that quantity units use **UN/ECE Recommendation 20** codes — a standard list of unit identifiers. Skontro maintains the following mapping from internal German unit labels to UN/ECE codes:

| Internal label | UN/ECE Rec. 20 code | Meaning |
|---|---|---|
| Stück | H87 | piece |
| Stunde | HUR | hour |
| Kilogramm | KGM | kilogram |
| Meter | MTR | metre |
| Quadratmeter | MTK | square metre |
| Tag | DAY | day |
| pauschal | LS | lump sum |

These seven units cover the overwhelming majority of small-business invoice line items. Additional units can be added as needs arise; the mapping is in one place in the code.

## VAT calculation — line-level rounding (BR-CO-17)

**EN 16931 Business Rule BR-CO-17** mandates: VAT is calculated **per line item**, rounded to two decimal places, then summed. It is **never** calculated on the document subtotal.

The difference matters on multi-rate invoices. Consider an invoice with two lines:

- Line 1: net €99.99, 19% VAT
- Line 2: net €99.99, 7% VAT

**Line-level rounding (correct, per BR-CO-17):**

- Line 1 VAT = round(99.99 × 0.19, 2) = 19.00
- Line 2 VAT = round(99.99 × 0.07, 2) = 7.00
- Total VAT = 26.00

**Document-level rounding (wrong, fails BR-CO-17):**

- Subtotal = 199.98
- (199.98 × 0.19 + 199.98 × 0.07) ÷ 2 — does not make sense for multi-rate; the document-level approach assumes single-rate, and applying it naively produces a different penny on edge cases.

The 1-cent difference fails KoSIT validation. Skontro's `InvoiceCalculator` uses line-level rounding exclusively, with a unit-test corpus that proves the difference for at least three multi-rate edge cases. See SRS FR-031.

## Validation

Two validation gates apply to every generated invoice:

- **KoSIT validator** confirms EN 16931 conformance, including all the BR-* business rules. Runs in CI on the test corpus.
- **verapdf validator** confirms PDF/A-3 conformance (ISO 19005-3:2012). Runs in CI on the test corpus.

Both must pass for the build to be green.

## Limitations

- **Profile lock.** Skontro generates only the EN16931 profile. EXTENDED profile additional fields are not used. If a future use case requires them, the choice can be revisited (a forthcoming ADR would record it).
- **PDF rendering fidelity.** The PDF rendering uses Browsershot (headless Chromium). For most invoices this produces a clean, neutral layout. Highly customised brand layouts beyond what the Blade template supports are not in v0.1 scope.
- **No PEPPOL transmission.** Skontro generates the file but does not transmit it via the PEPPOL network. The user is responsible for delivery in v0.1; v0.2 adds SMTP email delivery.

## References

- ZUGFeRD 2.1 specification — `https://www.ferd-net.de/standards/zugferd-2.1/`
- Factur-X (equivalent French specification) — `http://fnfe-mpe.org/factur-x/`
- EN 16931 — European standard
- PDF/A-3 — ISO 19005-3:2012
- UN/ECE Recommendation 20 — unit codes
- `horstoeko/zugferd` PHP library — `https://github.com/horstoeko/zugferd`
- KoSIT validator — `https://github.com/itplr-kosit/validator`
- verapdf PDF/A validator — `https://verapdf.org`
- SRS FR-041 through FR-048
