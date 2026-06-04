<?php

declare(strict_types=1);

namespace Padosoft\Rebel\Bridge\Laragear2fa\Drivers;

use Illuminate\Contracts\Config\Repository;
use Padosoft\Rebel\Bridge\Laragear2fa\Contracts\TwoFactorValidator;
use Padosoft\Rebel\Core\Assurance\Aal;
use Padosoft\Rebel\Core\Assurance\AssuranceLevel;
use Padosoft\Rebel\Core\Audit\AuditEvent;
use Padosoft\Rebel\Core\Contracts\AuditLogger;
use Padosoft\Rebel\StepUp\Contracts\StepUpDriver;
use Padosoft\Rebel\StepUp\StepUpContext;

/**
 * Step-up driver backed by laragear/two-factor's TOTP implementation.
 *
 * The authenticator app (Google Authenticator, Authy, etc.) generates the 6-digit
 * code client-side, so {@see start()} returns null — no server-side challenge is
 * needed. {@see verify()} delegates to laragear's {@see TwoFactorValidator::validate()}
 * which checks the TOTP code first, then falls back to a single-use recovery code.
 *
 * Assurance level: **AAL2** — the user has an active session (something they know) PLUS
 * a TOTP possession factor (something they have). TOTP is NOT phishing-resistant because
 * a real-time phishing attack can intercept a valid 30-second window.
 *
 * Audit telemetry:
 *  - `stepup.laragear_totp.verified` — successful TOTP or recovery-code redemption.
 *  - `stepup.laragear_totp.failed`   — failed attempt (wrong code / 2FA not enabled).
 *  - `stepup.laragear_totp.recovery_code.used` — recovery code consumed (subset of .verified).
 *
 * Fail-closed: any {@see \Throwable} during validation is caught and treated as failure.
 * OTPs/secrets are NEVER recorded in audit metadata.
 */
final class LaragearTotpStepUpDriver implements StepUpDriver
{
    public function __construct(
        private readonly TwoFactorValidator $validator,
        private readonly AuditLogger $audit,
        private readonly Repository $config,
    ) {}

    public function key(): string
    {
        return 'laragear_totp';
    }

    public function assurance(): AssuranceLevel
    {
        return new AssuranceLevel(Aal::Aal2, phishingResistant: false, amr: ['otp', 'totp']);
    }

    public function isAvailableFor(StepUpContext $context): bool
    {
        try {
            return $this->validator->isEnabled($context->subject);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Returns null: the authenticator app generates the code locally — no server-side
     * challenge or OTP delivery is required.
     */
    public function start(StepUpContext $context): ?string
    {
        return null;
    }

    public function verify(StepUpContext $context, string $input, ?string $reference): bool
    {
        $useRecoveryCodes = (bool) $this->config->get('rebel-bridge-laragear-2fa.use_recovery_codes', true);

        try {
            // Attempt TOTP first (no recovery codes yet).
            $totpOnly = $this->validator->validate($context->subject, $input, false);

            if ($totpOnly) {
                $this->auditVerified($context);

                return true;
            }

            if ($useRecoveryCodes) {
                // Try recovery code (single-use — laragear marks it consumed on match).
                $recoveryUsed = $this->validator->validate($context->subject, $input, true);

                if ($recoveryUsed) {
                    $this->auditRecoveryCodeUsed($context);
                    $this->auditVerified($context);

                    return true;
                }
            }
        } catch (\Throwable) {
            // Fall through to failure audit below.
        }

        $this->auditFailed($context);

        return false;
    }

    // -------------------------------------------------------------------------
    // Audit helpers — never include the code or secret in metadata.
    // -------------------------------------------------------------------------

    private function auditVerified(StepUpContext $context): void
    {
        $id = $context->subject->getAuthIdentifier();

        $this->audit->record(new AuditEvent(
            type: 'stepup.laragear_totp.verified',
            subjectType: $context->subject::class,
            subjectId: is_scalar($id) ? (string) $id : null,
            channel: 'totp',
            amr: ['otp', 'totp'],
        ));
    }

    private function auditFailed(StepUpContext $context): void
    {
        $id = $context->subject->getAuthIdentifier();

        $this->audit->record(new AuditEvent(
            type: 'stepup.laragear_totp.failed',
            subjectType: $context->subject::class,
            subjectId: is_scalar($id) ? (string) $id : null,
            channel: 'totp',
            amr: ['otp', 'totp'],
        ));
    }

    private function auditRecoveryCodeUsed(StepUpContext $context): void
    {
        $id = $context->subject->getAuthIdentifier();

        $this->audit->record(new AuditEvent(
            type: 'stepup.laragear_totp.recovery_code.used',
            subjectType: $context->subject::class,
            subjectId: is_scalar($id) ? (string) $id : null,
            channel: 'totp',
            amr: ['recovery_code'],
        ));
    }
}
