# Skontro

> Open-source German-compliant mini-ERP for the Mittelstand.

![CI](https://img.shields.io/badge/CI-pending-lightgrey)
![Coverage](https://img.shields.io/badge/coverage-pending-lightgrey)
![License: MIT](https://img.shields.io/badge/license-MIT-blue)
![Docs](https://img.shields.io/badge/docs-pending-lightgrey)

## Overview

Skontro is a lightweight, opinionated ERP built for German small and
mid-sized businesses (the Mittelstand). It targets the workflows that
every German SME actually does — quotes, orders, invoices with full
EN 16931 / ZUGFeRD e-invoicing, expense capture, and DATEV-compatible
export — without the implementation cost of an SAP or Microsoft
Dynamics. The stack is Laravel 11, Vue 3, and PostgreSQL, with a
future Python FastAPI ML layer for document understanding.

## Documentation

Full documentation lives at **[docs.skontro.dev](https://docs.skontro.dev)**
*(live by Day 5)*.

## Quickstart

```bash
git clone https://github.com/hauptraja/skontro.git && cd skontro
docker compose up -d
open http://localhost:8000   # full setup walk-through lands Day 19
```

## Roadmap

| Version | Theme                                              | Target          |
| ------- | -------------------------------------------------- | --------------- |
| v0.1.0  | Invoicing core, EN 16931 / ZUGFeRD, auth, basic UI | **July 2026**   |
| v0.2    | DATEV export, XRechnung profile, customer portal   | Q4 2026         |
| v0.3    | ML document understanding (FastAPI service)        | Q1 2027         |
| v0.4    | Internationalization, multi-tenant SaaS portal     | Q2 2027         |

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md). Security disclosures go through
[SECURITY.md](SECURITY.md).

## Maintainer

**Zeeshan Ahmed Raja** — Berlin, Germany
General contact: `hello@skontro.dev`
Security: `security@skontro.dev`
Conduct: `conduct@skontro.dev`

## License

Released under the [MIT License](LICENSE).

---
