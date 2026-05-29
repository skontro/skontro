# Requirements

The Software Requirements Specification (SRS) for Skontro follows the ISO/IEC/IEEE 29148:2018 structure. The authoritative version is the LaTeX-compiled PDF.

## Download

[:material-file-pdf-box:{ .lg .middle } **Software Requirements Specification (PDF, v1.0)**](../formal/srs/srs.pdf){ .md-button .md-button--primary }

Approximately 40 pages. Contains:

- 62 Functional Requirements (FR-001 through FR-062)
- 45 Non-Functional Requirements (NFR-001 through NFR-045) covering performance, security, usability, reliability, maintainability, portability, and compliance
- 10 Interface Requirements (IR-001 through IR-011)
- Complete Requirements Traceability Matrix

## Categories

- **Authentication and user management:** FR-001 through FR-010
- **Multi-tenancy:** FR-011 through FR-015
- **Customer management:** FR-016 through FR-022
- **Product and service catalog:** FR-023 through FR-028
- **Invoicing core:** FR-029 through FR-040
- **E-invoicing (ZUGFeRD):** FR-041 through FR-048
- **Expense tracking:** FR-049 through FR-053
- **Dashboard:** FR-054 through FR-057
- **Tenant settings:** FR-058 through FR-062

## Implementation status

The authoritative Requirements Traceability Matrix lives in the SRS PDF. This
table tracks the build status of the requirements realized so far; it is updated
honestly as each milestone lands, and a requirement stays `TBD` until code and
tests actually exist for it.

| Requirements | Area | Status | Realized by |
|---|---|---|---|
| FR-001 – FR-010 | Authentication & user management | Implemented | Registration, login, logout, current-user endpoints; cookie-session auth. Tests: `tests/Feature/Auth/*`. |
| FR-011, FR-012, FR-014 | Multi-tenancy: model, scoping, stamping, isolation | Implemented | `BelongsToTenant` trait + `TenantScope` global scope; `ResolveTenant` middleware. Tests: `tests/Unit/Tenancy/TenantScopeTest.php`, `tests/Feature/Tenancy/CrossTenantAccessTest.php`. |
| FR-013 | Atomic per-tenant, annually-resetting document numbering | Implemented | `SequenceGenerator` + `number_sequences` row lock (`SELECT ... FOR UPDATE`); `DocumentType` enum. Tests: `tests/Feature/Numbering/*`. |
| FR-015 | Cross-tenant resource access (404-not-403) | Implemented | Inherited via `BelongsToTenant` + route-model binding through the tenant scope; no ownership check in controllers. Tests: `tests/Feature/Customers/CustomerTenantIsolationTest.php`. |
| FR-016 – FR-020 | Customer management: create, read, update, soft-delete + restore, paginated search | Implemented | `CustomerController` (CRUD, soft-delete, search) behind auth + tenant + role. Tests: `tests/Feature/Customers/*`, `tests/Unit/Models/CustomerTest.php`. |
| FR-021 | VAT-ID validation per country | Implemented (format + DE checksum; VIES online verification deferred to v0.2) | `VatId` rule. Tests: `tests/Unit/Rules/VatIdTest.php`. |
| FR-022 | EU country support | Implemented | `Country` enum, 27 ISO 3166-1 alpha-2 member states. |
| FR-023 | Create product/service | Implemented | `ProductController` + `StoreProductRequest` behind auth + tenant + role. Tests: `tests/Feature/Products/ProductCrudTest.php`. |
| FR-024 | German VAT rates 19/7/0 | Implemented | `VatRate` enum (int-backed); decimal-string multipliers for `brick/money`. Tests: `tests/Unit/Enums/VatRateTest.php`, validation test in `ProductCrudTest.php`. |
| FR-025 | Seven units → UN/ECE Rec 20 codes | Implemented | `Unit` enum carrying `uneceCode()` (H87/HUR/KGM/MTR/MTK/DAY/LS). Tests: `tests/Unit/Enums/UnitTest.php`. |
| FR-026 | Money as integer cents (no floats) | Implemented | `unit_price_cents` `BIGINT` + `MoneyCast`/`Money` over `brick/money`. Evidence: the `information_schema` column-type assertion and the float-rejection test (`tests/Unit/Money/MoneyCastTest.php`, `tests/Unit/Models/ProductTest.php`). |
| FR-027 | Archive / unarchive (not delete) | Implemented | `is_active` flag + archive/unarchive endpoints; no destroy route (the 405 test proves it). Tests: `tests/Feature/Products/ProductCrudTest.php`. |
| FR-028 | Optional SKU | Implemented (optional; **not** unique-constrained in v0.1) | Nullable `sku` column; a partial unique index on `(tenant_id, sku)` is the path if per-tenant uniqueness is ever required. |
| FR-029 – FR-062 | Invoicing, e-invoicing, expenses, dashboard, settings | TBD | Subsequent milestones. (FR-040 per-customer payment terms has its data field laid on the customer record.) |

Security NFRs realized alongside the above: NFR-009 (session security), NFR-010
(Sanctum), NFR-011 (password hashing), NFR-013 (generic auth-failure messaging).

## Compliance basis

The SRS references:

- EN 16931 — European standard for electronic invoicing
- Wachstumschancengesetz (BGBl. 2024 I Nr. 108) — the legislative driver
- §14 UStG, §19 UStG — German VAT Act
- GoBD — German digital bookkeeping rules
- ZUGFeRD 2.1 — hybrid PDF/A-3 + XML format specification
