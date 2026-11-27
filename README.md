# Componenta Auth Session App

Invocation-only `#[CurrentSession]` integration for Componenta DI.

The attribute resolves the current `AuthSession` directly from the current
PSR-7 request. There is intentionally no `CurrentSessionId` or
`CurrentSessionReference`; use `$session->uuid`.

## Requirements

- PHP 8.5.13+;
- `componenta/auth-session` 2.x;
- `componenta/config` 4.x;
- `componenta/di` 6.x;
- PSR-7 2.x.

The current request supplies the session for each invocation. Caller-provided
parameter values cannot replace it; missing or malformed request state fails
explicitly, and constructor injection is rejected. Reusing the DI container
does not retain a session between requests.

See [the dependency compatibility notes](docs/CONFIG4-COMPATIBILITY.md) for
verification and upgrade requirements.
