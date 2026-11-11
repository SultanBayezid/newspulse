# Security Policy

## Supported versions

| Version | Supported |
|---|---|
| 1.0.x | Yes |

## Reporting a vulnerability

If you discover a security vulnerability in NewsPulse, please report it privately:

- Email: **sultanbayezidai@gmail.com**

Please include a description of the issue, steps to reproduce it, and any proof-of-concept.
Do **not** open a public GitHub issue for security problems.

You will receive an acknowledgement within 72 hours. Once the issue is verified, a fix will be
prepared and released as soon as practicable, and credit will be given in the release notes
unless you prefer to remain anonymous.

## Hardening for production

- Serve the application over HTTPS and set `APP_ENV=production`, `APP_DEBUG=false`.
- Restrict `CORS_ALLOWED_ORIGINS` to your own frontend domain.
- Use strong database credentials and do not expose MySQL/Redis ports publicly.
- Keep dependencies updated (`composer update` / `npm update`) and apply framework security
  patches promptly.
- Run the queue worker and scheduler as separate, supervised processes.
