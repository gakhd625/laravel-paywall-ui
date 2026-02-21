# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

Please report security issues to the maintainers privately (e.g. via email or private issue) rather than in public issue trackers. Include steps to reproduce and impact. We will acknowledge and work on a fix as soon as possible.

## Security Considerations (Package)

- **XSS:** All user-facing output from the component (id, class, title, message) is sanitized or escaped. Use Blade `{{ }}` for any custom views.
- **Path traversal:** Custom view paths are validated; `..` and `\` are rejected. Optionally use `security.allowed_view_prefixes` to restrict to safe view names.
- **Enforcement:** The UI lockout is for user experience only. Always use the provided middleware (or equivalent server-side checks) to enforce access to sensitive routes and APIs.
- **API:** If using the message API, use HTTPS and rate limiting; the package rate-limits fetches per URL.
