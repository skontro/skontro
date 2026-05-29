# 0005. Enforce the Invoice Lifecycle with an Explicit State Machine

- **Status:** Accepted
- **Date:** 2026-05-29
- **Deciders:** Zeeshan Ahmed Raja (sole maintainer, v0.1.0)
- **Consulted:** —
- **Informed:** Future contributors via this ADR; SRS FR-032.

---

## Context

An invoice has a legally-meaningful lifecycle: `draft → issued → sent →
(partially_paid | paid)`, with any non-paid state able to be cancelled, and
`paid`/`cancelled` terminal. The transitions are not cosmetic. Issuing mints a
gapless tax-document number and freezes the line items; a paid invoice must not be
silently reversed; an invoice cannot jump from draft straight to sent without
being issued first.

Encoding this lifecycle as ad-hoc `if` checks scattered across a controller is the
default path, and it is the wrong one. Legality would be expressed in several
places, each able to drift from the others; a reviewer could not see the whole
lifecycle in one glance; and a future "tidy-up" could quietly forbid a legal move
(for example recording a second partial payment) or permit an illegal one (for
example cancelling a paid invoice). SRS FR-032 anticipated this and called for an
ADR on the invoice state machine.

A second, related question is **which HTTP status** an illegal transition returns.
A malformed request body is a 422 (validation). But "send a draft" is a
well-formed request that conflicts with the resource's current state — a
different category of error that the client should be able to distinguish.

---

## Decision

The invoice lifecycle is enforced by a dedicated `InvoiceStateMachine` service
whose legal transitions live in **one transition table**. A single method,
`assertCanTransition(from, to)`, is the only gate; it throws
`InvalidTransitionException` on an illegal move. Controllers never re-encode
legality — they call the gate.

The transition table:

| From | Allowed to |
|---|---|
| `draft` | `issued`, `cancelled` |
| `issued` | `sent`, `partially_paid`, `paid`, `cancelled` |
| `sent` | `partially_paid`, `paid`, `cancelled` |
| `partially_paid` | `partially_paid`, `paid`, `cancelled` |
| `paid` | — (terminal) |
| `cancelled` | — (terminal) |

Supporting decisions:

- **`InvalidTransitionException` renders as HTTP 409 Conflict**, not 422, carrying
  the current and attempted states in the body. The request is well-formed; it
  conflicts with the resource's state. The exception renders itself, so the
  controller stays clean.
- **Line items freeze on issue.** `InvoiceState::allowsLineEditing()` returns true
  only for `draft`; an update to any other state is rejected with 409. Immutability
  of an issued tax document is a property of the state, not a controller check.
- **`partially_paid → partially_paid` is intentionally legal.** Recording a further
  partial payment that still does not settle the invoice is a real transition to
  the same state; the table includes it so a future change does not accidentally
  forbid a second partial payment.
- **`paid` and `cancelled` are terminal, and `paid → cancelled` is absent**, so a
  settled invoice cannot be cancelled (FR-034).

---

## Consequences

### Positive

- **One auditable source of truth.** The whole lifecycle is one table a reviewer
  reads in a glance, and one place a test exercises as a full transition matrix
  (every legal move permitted, representative illegal ones rejected).
- **Controllers stay thin.** They authorize via the gate and never duplicate
  legality; the SPA's action buttons and any future caller defer to the same rule.
- **409 vs 422 is meaningful.** The client can tell a state conflict from a bad
  payload and show a precise message; the 409 body names the current and attempted
  states.
- **Immutability is structural.** Because editability is a property of the state,
  freezing issued invoices needs no per-field guard.

### Negative

- **One more service to call.** Every transition must route through
  `assertCanTransition` rather than flipping a column directly. This is the
  intended cost: the discipline is what keeps legality in one place.

### Risks accepted

- The table is hand-maintained. A wrong edit could permit an illegal transition.
  Mitigation: the transition-matrix test pins every legal and a representative set
  of illegal transitions, so a bad edit fails the suite.

---

## Alternatives Considered

### Alternative A — Scattered `if` checks in the controller

- **Pro:** No new abstraction; the obvious first approach.
- **Con:** Legality expressed in many places that drift; no single view of the
  lifecycle; easy to forbid a legal move or permit an illegal one during a later
  refactor.
- **Verdict:** Rejected. This is precisely what FR-032 asked for an ADR to avoid.

### Alternative B — A third-party state-machine package

- **Pro:** Batteries-included transitions, events, guards.
- **Con:** A dependency and its conventions for a transition table that fits in a
  few lines; heavier than the problem warrants at v0.1.
- **Verdict:** Rejected. The hand-rolled table is smaller, fully owned, and
  trivially testable. Revisit only if the lifecycle grows guards/events.

### Alternative C — 422 for illegal transitions

- **Pro:** One error category for everything a controller rejects.
- **Con:** Conflates a well-formed-but-conflicting request with a malformed one;
  the client cannot distinguish them.
- **Verdict:** Rejected. 409 is the correct semantic for a state conflict.

---

## References

- SRS FR-032 — invoice state machine (this ADR is the one it forecast)
- SRS FR-033 — issue: mint number, lock lines, dispatch document job
- SRS FR-034 — cancel with reason; a paid invoice cannot be cancelled
- arc42 §5.5 — Invoicing core building block
- arc42 §6.2 — the invoice lifecycle runtime view
- EN 16931 BR-CO-17 — line-level VAT rounding (the related correctness rule)

---

## Revision History

| Version | Date | Author | Change |
|---|---|---|---|
| 1.0 | 2026-05-29 | Zeeshan Ahmed Raja | Initial decision recorded. |
