# AGENTS.md — operational rules for `padosoft/laravel-rebel-bridge-laragear-2fa`

> This file is the **work contract** for every `laravel-rebel-*` repo.
> At the start of EVERY session: read CLAUDE.md, then this file.

## Stack & targets

- **Laravel 12 + 13**, **PHP 8.3 + 8.4 + 8.5**. Constraint: `illuminate/support: ^12.0|^13.0`, `php: ^8.3`.
- Testbench `^10.0|^11.0`, **Pest 4**, **Larastan 3** (PHPStan **level max**), **Pint** (preset `laravel`).
- Namespace PSR-4: `Padosoft\Rebel\Bridge\Laragear2fa\`. Composer name: `padosoft/laravel-rebel-bridge-laragear-2fa`.
- `laragear/two-factor ^4.0` is **require-dev + suggest** — never a hard require.

## Branching & PR — ONE PR per macro-task

- One branch per macro-task: `feat/<macro>` from `main`.
- Sub-tasks are **local commits** on the macro branch. No PR per sub-task.
- When macro is complete: push → **ONE PR `feat/<macro>` → main** → CI gate → merge → tag/release.
- Commit: clear English message; `Co-Authored-By` as per harness rules.

## Definition of Done

### Local loop per sub-task

1. Implement + **guardrails**:
   - **Pest** for all logic;
   - only code (no UI) → no Playwright.
2. Green locally: `composer test` · `composer phpstan` (max) · `composer pint -- --test`.
3. Commit locally. Update CHANGELOG.md.

### GitHub gate (one per PR)

1. `git push` branch; `gh pr create` (feat/<macro> → main).
2. Wait for **CI all green**.
3. Green + 0 open comments → `gh pr merge --squash`. Then `git tag vX.Y.Z` + `gh release create`.

## Guardrails — mandatory, not optional

Every sub-task has: precise objective, implementation details, guardrails (PHP unit tests always).
Nothing is "done" without green tests.

## Security (design-lock)

- Never OTP/secret in logs or audit metadata.
- Fail closed on any `\Throwable` — always return `false`, never throw up the stack.
- `isEnabled` + `validate` via the `TwoFactorValidator` seam (never call laragear directly from the driver).
- Recovery codes are single-use: laragear marks them consumed via `validateTwoFactorCode`.

## Banner & assets

Banner at `resources/screenshoots/Laravel-Rebel-banner.png` (source: `Downloads\laravel-rebel\Laravel-Rebel-banner.png`).

## Tooling notes

- `php`/`composer` run in PowerShell (Herd), not Bash.
- Tests run offline via `FakeTwoFactorValidator` — no laragear database or TOTP engine needed.
- PHPStan analyses only `src/` — test files are excluded (Pest closures confuse Larastan).
