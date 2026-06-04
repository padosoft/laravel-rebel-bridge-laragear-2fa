# CLAUDE.md — AI working guide for `padosoft/laravel-rebel-bridge-laragear-2fa`

> Working on this package with an AI agent (Claude Code, Cursor, Copilot, Codex)? Read this first.
> It's the "batteries" that make vibe-coding here land on the first try. Plain Markdown — every
> tool can read it.

## What this package is

A bridge between **laragear/two-factor** and the **Laravel Rebel** enterprise-auth suite. It
exposes laragear's TOTP two-factor authentication as a Rebel step-up driver (key `laragear_totp`,
assurance AAL2), wires recovery codes (single-use), and emits full audit telemetry to the Rebel
audit trail (`rebel_auth_events`).

The bridge is designed around a seam: `Contracts\TwoFactorValidator` wraps laragear's surface so
the driver runs fully offline in tests. `Testing\FakeTwoFactorValidator` ships for that purpose.

## Non-negotiable conventions

- `declare(strict_types=1);` in every PHP file; `final` classes; constructor property promotion.
- **PHPStan level max** must stay green. Do NOT add `@phpstan-ignore`, baseline entries, or
  `assert()`/inline `@var` to silence errors — fix the root cause. Common recipes:
  - narrow `mixed` before casting: `is_scalar($x) ? (string) $x : null`;
  - `json_decode($s, true)` is `array<array-key, mixed>`;
  - no redundant `instanceof` for things the container already types.
- **Tests:** Pest, Testbench, **offline via FakeTwoFactorValidator** (no laragear needed in tests).
  Cover happy path, fail-closed, single-use recovery, `\Throwable` handling.
- **Style:** Pint preset `laravel` (`composer pint`). **Docs/comments in English.**
- Package wiring uses `spatie/laravel-package-tools` (`configurePackage` + `packageBooted`).

## Security & telemetry rules (suite-wide)

- Never store PII in cleartext: identifiers are **keyed HMACs** (core `KeyedHasher`).
  **Never log OTPs, TOTP secrets, or recovery codes** — audit metadata is sanitized by `Redactor`.
- **Telemetry completeness:** record `stepup.laragear_totp.verified`, `.failed`, and
  `.recovery_code.used` through the core `AuditLogger` contract (persists to `rebel_auth_events`,
  never session). Include `channel: 'totp'`, `amr: ['otp','totp']` (or `['recovery_code']`).

## Architecture

```
LaragearTotpStepUpDriver   ← implements StepUpDriver
  ↓ uses
TwoFactorValidator (contract/seam)
  ├── LaragearTwoFactorValidator  (production, laragear-backed)
  └── FakeTwoFactorValidator      (test / offline)

LaragearBridge::installed()      feature-detects laragear at boot
RebelLaragear2faBridgeServiceProvider  → hasConfigFile, binds validator, registers driver
```

## Laragear API used

- `TwoFactorAuthenticatable::hasTwoFactorEnabled(): bool` — checks 2FA enabled and confirmed.
- `TwoFactorAuthenticatable::validateTwoFactorCode(?string $code, bool $useRecoveryCodes): bool`
  — validates a TOTP code; falls back to single-use recovery code when `$useRecoveryCodes = true`.
- Feature detect via `class_exists(Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable::class)`.

## Config keys

| Key | Default | Description |
|-----|---------|-------------|
| `rebel-bridge-laragear-2fa.drivers.laragear_totp` | `true` | Register TOTP driver |
| `rebel-bridge-laragear-2fa.use_recovery_codes` | `true` | Accept recovery codes |

## Definition of Done (per change)

1. Red→green with Pest; `composer phpstan` (max) + `composer pint -- --test` clean.
2. One feature branch, one PR to `main`. CI matrix **PHP 8.3/8.4/8.5 × Laravel 12/13** must be green.
3. Update `README.md` + `CHANGELOG.md`. Squash-merge.
4. **Release:** `git tag vX.Y.Z && git push origin vX.Y.Z` + `gh release create`. Stay in `0.1.x`.

## Skills

This repo ships invocable skills under `.claude/skills/` — `rebel-package-dev` covers the dev
loop, PHPStan-max recipes, and the suite's security/telemetry rules.

## Session startup

1. Read this file (CLAUDE.md).
2. Read AGENTS.md (branching, DoD, guardrails).
3. Check CHANGELOG.md for current version.
