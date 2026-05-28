# 0004. Adopt Diátaxis-structured Documentation Powered by MkDocs Material

- **Status:** Accepted
- **Date:** 2026-05-28
- **Deciders:** Zeeshan Ahmed Raja (sole maintainer, v0.1.0)
- **Consulted:** —
- **Informed:** Future contributors via this ADR; SRS NFR-034.

---

## Context

Documentation is a first-class deliverable for Skontro. The V&S §1.3 BO-5 commits to publishing a full documentation set; the arc42 §1.2 quality goal of demonstrability rests on documentation visibility. The documentation must serve four distinct purposes:

- **Tutorials** — guided learning, "getting started" for new users
- **How-to guides** — step-by-step task completion for someone who already understands the system
- **Reference** — exhaustive technical reference (API, schema, configuration)
- **Explanation / concepts** — discussion of why things work the way they do (the arc42 document, ADRs, compliance documents)

A single undifferentiated "documentation page" tends to fail all four purposes. A page that tries to be both a tutorial and a reference confuses readers in both modes.

---

## Decision

Skontro's documentation is structured according to the **Diátaxis framework** (Daniele Procida, `https://diataxis.fr`), published with **MkDocs Material**, deployed to **`docs.skontro.dev`** via GitHub Actions on every push to `main`.

The Diátaxis four-quadrant split is:

| | Practical (action-oriented) | Theoretical (cognition-oriented) |
|---|---|---|
| **Learning** (acquisition of skill) | Tutorials | Explanation / Concepts |
| **Working** (application of skill) | How-to guides | Reference |

Each documentation page is classified into exactly one quadrant and written in the voice appropriate to that quadrant.

### Site structure

Top-level navigation reflects the four-way split plus the formal deliverables:

```text
docs.skontro.dev/
├── /            (homepage with feature cards)
├── vision/      (Vision and Scope)
├── requirements/ (overview + PDF download)
├── architecture/ (arc42 12 sections — primarily explanation)
├── compliance/  (compliance documents — explanation + reference)
├── adr/         (Architecture Decision Records — explanation)
├── api/         (API reference — auto-generated from OpenAPI)
├── operations/  (deployment, runbook, monitoring, incident response — how-to)
├── testing/     (test plan, future test guides — explanation + how-to)
├── tutorials/   (future: "Set up Skontro for the first time" — tutorial)
├── guides/      (future: "How to generate a ZUGFeRD invoice via API" — how-to)
├── concepts/    (future: "Understanding multi-tenancy in Skontro" — explanation)
└── formal/      (LaTeX-compiled PDFs — SRS, SDD, etc.)
```

### Tooling

- **Static-site generator:** MkDocs with the **Material for MkDocs** theme.
- **Markdown extensions:** `pymdownx.superfences`, `pymdownx.tabbed`, `pymdownx.details`, `pymdownx.snippets`, admonitions, table of contents.
- **Diagram rendering:** `mkdocs-mermaid2-plugin` for Mermaid blocks in Markdown; draw.io `.drawio.svg` files for high-fidelity diagrams.
- **Search:** built-in MkDocs search, configured for English and German.
- **Image zoom:** `mkdocs-glightbox`.
- **API reference rendering:** `mkdocs-render-swagger-plugin` (commented out until the OpenAPI spec exists).
- **Deployment:** `mkdocs gh-deploy` from GitHub Actions on every push to `main`. Site lives on the `gh-pages` branch.

### Diagrams

Two diagram tools, chosen per use case:

- **Mermaid** for diagrams that are best maintained as text: sequence diagrams, state machines, ER diagrams, simple flowcharts. Lives inline in Markdown.
- **draw.io** (saved as `.drawio.svg`) for high-fidelity architecture diagrams: system context (arc42 §3), building block view (arc42 §5), deployment view (arc42 §7). Lives in `docs/diagrams/`.

The `.drawio.svg` extension is intentional: the file is both an editable draw.io document (open in draw.io) and a renderable SVG (open in any browser).

---

## Consequences

### Positive

- **Pages know what they are.** A reader looking for "how do I configure SMTP" gets a how-to page that answers exactly that and does not lecture about why SMTP exists. A reader wanting to understand multi-tenancy gets an explanation page that does not jump straight into API examples.
- **Writing is easier.** When the page's purpose is clear, the appropriate voice follows. Tutorials use "you", how-to guides use imperatives, reference uses declarative third-person, explanation uses a discursive voice.
- **MkDocs Material is mature and well-supported.** The theme has been in active development since 2016. The plugin ecosystem covers most needs.
- **Free hosting via GitHub Pages.** Zero operational cost.
- **Markdown source.** Plain text, version-controlled with the code. Pull requests can include both code and documentation changes.
- **Build-in-public friendly.** A polished docs site visible at a custom domain is part of the demonstrability quality goal (arc42 §1.2).

### Negative

- **MkDocs is Python-based.** Adds a Python dependency to the docs build pipeline (`docs/requirements.txt`). The trade-off is acceptable; the alternative (a JS-based generator) would not be simpler given the rest of the stack.
- **The four-quadrant discipline takes practice.** A page sometimes wants to be a tutorial *and* a reference. The discipline is: split it, or pick one.

### Risks accepted

- If a future contributor disagrees strongly with Diátaxis and wants Sphinx, GitBook, or Docusaurus instead — switching is expensive (rewriting the navigation structure, rewriting some pages whose voice doesn't translate). Acceptable: this is a one-time choice with high lock-in. Mitigation: the source is Markdown, so most content survives a tool change unedited.

---

## Alternatives Considered

### Alternative A — Sphinx (Python-based, reStructuredText)

- **Pro:** Strong support for technical documentation; extensive cross-reference support; widely used in Python ecosystems.
- **Con:** reStructuredText is less familiar than Markdown. The default theme is dated; the popular Read the Docs theme is also dated. Material for MkDocs has a more modern aesthetic.
- **Verdict:** Rejected. Markdown wins on familiarity.

### Alternative B — Docusaurus (React-based)

- **Pro:** Strong for projects with a large blog component; React-based extensibility.
- **Con:** Heavier build pipeline; Node.js + React dependency for docs alone; aesthetic less aligned with technical documentation (more product-marketing style).
- **Verdict:** Rejected. Heavier than needed.

### Alternative C — GitBook (proprietary SaaS)

- **Pro:** Polished editing experience for non-technical contributors.
- **Con:** Proprietary; vendor lock-in; less control over the build pipeline; conflicts with the open-source ethos.
- **Verdict:** Rejected.

### Alternative D — Plain README + GitHub wiki

- **Pro:** Zero build pipeline.
- **Con:** Cannot host PDF downloads cleanly; no search; no custom domain; no theme; no plugin ecosystem. Falls short of the demonstrability quality goal.
- **Verdict:** Rejected.

---

## References

- Diátaxis framework — `https://diataxis.fr`
- MkDocs — `https://www.mkdocs.org/`
- Material for MkDocs — `https://squidfunk.github.io/mkdocs-material/`
- arc42 §1.2 — demonstrability quality goal
- SRS NFR-034 — ADRs for architectural changes

---

## Revision History

| Version | Date | Author | Change |
|---|---|---|---|
| 1.0 | 2026-05-28 | Zeeshan Ahmed Raja | Initial decision recorded. |
