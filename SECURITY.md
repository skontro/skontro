# Security Policy

The Skontro maintainers take security seriously. Thank you for helping keep
Skontro and its users safe.

## Supported Versions

Only the latest minor release line receives security updates. Skontro is
pre-1.0; expect frequent breaking changes until v1.0.0.

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |
| < 0.1   | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub
issues, discussions, or pull requests.**

### Preferred channel: GitHub Private Vulnerability Reporting

The fastest, most secure way to reach us is GitHub's built-in private
vulnerability reporting feature. From the Skontro repository:

1. Go to the **Security** tab.
2. Click **Report a vulnerability**.
3. Fill in the form with as much detail as you can.

This creates a private advisory visible only to the maintainers and to you.

### Alternative channel: Email

If you cannot use GitHub's private reporting, email:

**security@skontro.dev**

Please include:

- A description of the vulnerability and its potential impact.
- Steps to reproduce, or a proof-of-concept.
- Affected version(s) and configuration.
- Your name / handle if you want public credit in the advisory.

### What to expect

- **Acknowledgement** within **5 business days** of your report.
- **Triage and assessment** within 15 business days.
- **Coordinated disclosure window** of up to **90 days** from initial
  acknowledgement. We will work with you on the disclosure timeline and
  request CVE assignment where appropriate.
- **Credit** in the published security advisory unless you prefer to remain
  anonymous.

### Scope

In scope:

- The Skontro application code in this repository.
- Official Docker images and deployment manifests published under this
  project.
- Documentation that, if followed, would lead to an insecure deployment.

Out of scope:

- Third-party dependencies (please report upstream; we will track and
  patch).
- Self-hosted deployments that deviate from the documented setup.
- Social engineering, physical attacks, or attacks requiring privileged
  local access.

## No Bounty Program

Skontro is a volunteer-driven open-source project. We do **not** operate
a paid bug bounty program. We are deeply grateful for responsible
disclosure and will gladly credit reporters in advisories and release
notes.

## Warranty

Skontro is distributed under the MIT License and comes with **no warranty**.
This security policy describes a good-faith process, not a contractual
obligation. See [LICENSE](LICENSE) for details.
