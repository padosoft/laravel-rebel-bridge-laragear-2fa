---
name: rebel-package-dev
description: Use when adding or changing code in any padosoft/laravel-rebel-* package — encodes the suite's TDD loop, PHPStan-max recipes, security/telemetry rules, and the branch→PR→CI→tag/release Definition of Done.
---

# Developing a Laravel Rebel package

You are extending a package in the **Laravel Rebel** enterprise-auth suite. Follow this exactly.

## The loop (per sub-task)

1. **Write the test first** (Pest + Testbench): happy path, **auth/fail-closed**, empty state.
   Run `composer test`.
2. Implement with the conventions: `declare(strict_types=1)`, `final` classes, constructor
   promotion, English docblocks.
3. Make the gate green: `composer test` · `composer phpstan` (**level max**) · `composer pint -- --test`.
4. Commit on the feature branch. Repeat.

## PHPStan level max — fix the cause, never suppress

Forbidden: `@phpstan-ignore*`, baseline entries, `assert()`/inline `@var` to override inference.
Instead:
- Narrow before casting: `is_scalar($x) ? (string) $x : null`.
- `json_decode($s, true)` returns `array<array-key, mixed>` — type/annotate accordingly.
- No redundant `instanceof` for things the container already types correctly.
- Run with `--memory-limit=512M`.

## Security & telemetry (non-negotiable)

- **Never log OTPs, secrets, or recovery codes** in audit metadata.
- Record events through the core `AuditLogger` (persisted to `rebel_auth_events`).
- Fail closed on any `\Throwable` — return `false`, never rethrow.
- Use the `TwoFactorValidator` seam — the driver MUST NOT call laragear classes directly.

## laragear/two-factor API (v4.x)

- `$user->hasTwoFactorEnabled(): bool` — true when 2FA is enabled AND confirmed.
- `$user->validateTwoFactorCode(?string $code, bool $useRecoveryCodes): bool` — validates TOTP
  or (if `$useRecoveryCodes = true`) a single-use recovery code; marks it consumed on match.
- Feature detect: `class_exists(\Laragear\TwoFactor\Contracts\TwoFactorAuthenticatable::class)`.

## Definition of Done (per change)

- One feature branch → one PR to `main`; CI matrix **PHP 8.3/8.4/8.5 × Laravel 12/13** green.
- README + CHANGELOG updated; squash-merge.
- **Release every change:** `git tag vX.Y.Z && git push origin vX.Y.Z` + `gh release create`.
  Stay within `0.1.x` (`^0.1` excludes `0.2.0`).

## Tooling notes

- `php`/`composer` run in PowerShell (Herd), not Bash.
- Tests run offline via `FakeTwoFactorValidator` — no laragear migration/DB needed in the test
  suite.
