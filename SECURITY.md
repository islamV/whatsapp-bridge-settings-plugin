# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability, please do NOT open a public issue.

Instead, send a private report to the repository maintainer or open a security advisory at:

https://github.com/islamV/whatsapp-bridge-settings-plugin/security/advisories

Please include:

- Description of the vulnerability
- Steps to reproduce
- Possible impact
- Suggested fix (if any)

You will receive a response within 48 hours. We appreciate your responsible disclosure.

## Security Measures

- API tokens are encrypted at rest using Laravel's Crypt facade
- Full tokens are never displayed in the UI after saving
- Error messages never expose credentials or tokens
- Phone numbers are masked in logs
- All external HTTP requests use configurable timeouts
