# 0002. Multi-tenancy via Single Database with tenant_id Discriminator

- **Status:** Accepted
- **Date:** 2026-05-27
- **Deciders:** Zeeshan Ahmed Raja (sole maintainer, v0.1.0)
- **Consulted:** —
- **Informed:** Future contributors via this ADR; SRS §3.2.2 (FR-011 through FR-015); arc42 §2.1 TC-13.

---

## Context

Skontro is a multi-tenant SaaS-style application that can also be self-hosted. Multi-tenancy is a foundational architectural decision: it shapes the data model, the authentication and authorization layer, the migration strategy, the backup strategy, and the operational footprint.

Three patterns dominate the literature:

1. **Database-per-tenant** — each tenant gets its own database. Strong isolation, large operational overhead (migrations N times, backups N times).
2. **Schema-per-tenant** — each tenant gets its own schema within a single Postgres database. Medium isolation, medium operational overhead.
3. **Single database with `tenant_id` discriminator** — every tenant-owned row carries a `tenant_id` column; isolation is enforced in application code. Light operational overhead, isolation depends on application correctness.

The decision must support:

- **Operational simplicity.** One database to back up, one set of migrations to run, one connection pool. Aligns with OC-2 (solo maintainer) and OC-3 (~€15/month budget).
- **Reasonable isolation guarantees.** Cross-tenant data leakage would be catastrophic both legally (GDPR/DSGVO) and reputationally.
- **A path to scale.** v0.1 reference deployment supports up to ~50 tenants. Beyond that, sharding strategies may need development — but the decision today should not foreclose them.

---

## Decision

Skontro will use **single database with a `tenant_id` discriminator column** on every tenant-owned table, enforced by:

1. A **`BelongsToTenant` Eloquent trait** applied to every tenant-owned model, which:
    - Adds a global scope filtering all queries by the authenticated user's tenant
    - Automatically sets `tenant_id` on `creating` events to the authenticated user's tenant

2. **Service-level guarantees in the authentication layer** that bind every authenticated request to exactly one tenant via the Sanctum token's owning user.

3. **Direct cross-tenant ID lookups return HTTP 404, not 403.** This is a deliberate choice: 403 leaks the existence of the resource (the recipient learns a resource with that ID exists somewhere); 404 does not.

4. **An integration test in the test suite for every tenant-owned model**, asserting that User A from Tenant 1 cannot read, modify, or delete records of User B from Tenant 2.

5. **Per-tenant atomic sequences for customer / invoice / expense numbers** (FR-013). Implemented via a `numbering_sequences` table with row-level locks on increment, scoped to `(tenant_id, document_type, year)`.

---

## Consequences

### Positive

- **One database.** One `pg_dump` to back up, one set of migrations to run, one connection pool to size. Operational cost scales with usage, not with tenant count.
- **Cross-tenant queries are trivially possible for reporting.** Aggregate metrics (e.g., "across all tenants on this instance, how many invoices were issued in March?") are simple SQL — no cross-database UNION ALL gymnastics.
- **Tractable for a solo maintainer.** No tenant onboarding automation that creates databases. No tenant-aware migration runner.
- **Cost-efficient.** Hetzner CX22 can comfortably host ~50 tenants in a single Postgres database.

### Negative

- **Isolation depends on application correctness.** A bug in the global scope, in a manual query that bypasses Eloquent, or in a raw SQL statement, can leak data across tenants.
    - *Mitigation:* The `BelongsToTenant` trait is the only sanctioned access path. PHPStan level 8 flags raw queries. Code review checklist includes a "did this touch tenant data?" item. CI integration tests cover cross-tenant isolation for every tenant-owned model.
- **No per-tenant encryption at rest.** All tenants share the same database encryption (which is at the storage layer, not per-row).
    - *Mitigation:* Tenants with stronger isolation needs are documented as needing the self-host option, where they get their own deployment entirely.
- **Schema changes affect all tenants simultaneously.** A migration that introduces a new column applies to all tenants at once.
    - *Mitigation:* Migrations are backward-compatible by convention (additive changes preferred; destructive changes happen in multi-step migrations).
- **Scale ceiling.** Beyond ~50 tenants or a particular tenant becoming very large, the single-DB model may need to be sharded.
    - *Mitigation:* The `tenant_id` discriminator makes future sharding strategies viable — extract a tenant by selecting all rows with that `tenant_id`, dump them, and restore into a new database. The application code does not need to change if the connection-resolution layer learns to pick a database per `tenant_id`.

### Risks accepted

- If a future architectural review concludes that per-tenant isolation needs are stronger than the application-enforced model provides, a migration to schema-per-tenant or database-per-tenant is possible but expensive. Acceptable: for the v0.1 target user (Mittelstand businesses self-hosting or using a hosted instance with reasonable expectations), application-enforced isolation is sufficient.

---

## Alternatives Considered

### Alternative A — Database-per-tenant

- **Pro:** Strongest possible isolation. Per-tenant backups and restores. Per-tenant scale tuning.
- **Con:** N times the operational overhead. Migrations must be applied tenant-by-tenant. Tenant onboarding requires automated database provisioning. Cross-tenant reporting is hard. Connection pooling complicated.
- **Verdict:** Rejected. The operational cost is incompatible with OC-2 (solo maintainer).

### Alternative B — Schema-per-tenant within a single Postgres database

- **Pro:** Postgres schemas provide stronger logical separation than the discriminator model. Per-tenant `search_path` enforces isolation at the session level.
- **Con:** Postgres has a soft upper limit on schema count (thousands work, but tooling like `pg_dump` becomes slow). Migrations must be applied per schema. Search across tenants requires explicit cross-schema queries.
- **Verdict:** Rejected for v0.1. Could be revisited if a tenant emerges with strong-isolation requirements that the discriminator model cannot satisfy.

### Alternative C — Hybrid (small tenants pooled, large tenants on their own database)

- **Pro:** The advantages of both other models in principle.
- **Con:** Requires a tenant-routing layer in the application that can route a request to the right database. Code complexity, error surface area, and operational tooling all expand.
- **Verdict:** Rejected for v0.1. Worth revisiting if a tenant with strong-isolation needs becomes a real customer.

---

## References

- Laravel multi-tenancy patterns — `https://laravel.com/docs/11.x` (search: multi-tenancy)
- Spatie laravel-multitenancy — `https://github.com/spatie/laravel-multitenancy`
- SRS §3.2.2 (FR-011 through FR-015) — multi-tenancy functional requirements
- arc42 §2.1 TC-13 — technical constraint
- arc42 §4.4 — solution-strategy reference

---

## Revision History

| Version | Date | Author | Change |
|---|---|---|---|
| 1.0 | 2026-05-27 | Zeeshan Ahmed Raja | Initial decision recorded. |
