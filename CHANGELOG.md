# Changelog

All notable changes to `padosoft/laravel-rebel-bridge-laragear-2fa` will be documented in this
file. This project adheres to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] — 2026-06-04

### Added

- `LaragearTotpStepUpDriver` — implements `StepUpDriver` with key `laragear_totp`, assurance
  AAL2, non-phishing-resistant, AMR `['otp','totp']`. `start()` returns `null` (authenticator app
  generates code client-side). `verify()` validates TOTP codes and single-use recovery codes via
  laragear/two-factor's `validateTwoFactorCode()` API.
- `Contracts\TwoFactorValidator` seam — thin interface wrapping laragear's validation surface,
  enabling fully offline testing without laragear installed.
- `Support\LaragearTwoFactorValidator` — production implementation backed by laragear v4's
  `TwoFactorAuthenticatable` contract. Feature-detected and bound only when laragear is installed.
- `Testing\FakeTwoFactorValidator` — deterministic in-memory fake: configurable enabled users,
  valid TOTP codes (not consumed), and single-use recovery codes.
- `Support\LaragearBridge` — `installed(): bool` feature-detector checking for laragear's
  `TwoFactorAuthenticatable` contract class.
- `RebelLaragear2faBridgeServiceProvider` — wires the validator binding and driver registration;
  config-gated (`drivers.laragear_totp`) and feature-gated (laragear installed).
- Config file `rebel-bridge-laragear-2fa.php` with `drivers.laragear_totp` (default `true`) and
  `use_recovery_codes` (default `true`).
- Audit telemetry: emits `stepup.laragear_totp.verified`, `stepup.laragear_totp.failed`, and
  `stepup.laragear_totp.recovery_code.used` via core `AuditLogger` → `rebel_auth_events`. OTPs
  and secrets are never logged.
- 22 Pest tests (offline, via `FakeTwoFactorValidator`): key/assurance, isAvailable, start,
  verify TOTP + recovery, single-use, fail-closed `\Throwable`, config gates, audit telemetry,
  no-secret-in-audit.
- CI matrix: PHP 8.3/8.4/8.5 × Laravel 12/13; PHPStan level max; Pint preset `laravel`.
- `CLAUDE.md`, `AGENTS.md`, `.claude/skills/rebel-package-dev/` batteries.

[Unreleased]: https://github.com/padosoft/laravel-rebel-bridge-laragear-2fa/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/padosoft/laravel-rebel-bridge-laragear-2fa/releases/tag/v0.1.0
