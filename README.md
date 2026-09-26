# Componenta Auth Session App

Invocation-only `#[CurrentSession]` integration for Componenta DI.

The attribute resolves the current `AuthSession` directly from the current
PSR-7 request. There is intentionally no `CurrentSessionId` or
`CurrentSessionReference`; use `$session->uuid`.
