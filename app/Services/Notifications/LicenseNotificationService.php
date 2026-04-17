<?php

namespace App\Services\Notifications;

use App\Mail\LicenseIssuedMail;
use App\Mail\LicenseRevokedMail;
use App\Models\Entitlement;
use App\Models\LicenseKey;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;

class LicenseNotificationService
{
    public function __construct(private readonly Mailer $mailer)
    {
    }

    public function sendIssuedLicense(string $email, string $licenseKey, string $keyDisplay, Entitlement $entitlement): void
    {
        $this->sendWithRetry($email, new LicenseIssuedMail($licenseKey, $keyDisplay, $entitlement));
    }

    public function sendRevokedLicense(string $email, LicenseKey $license): void
    {
        $this->sendWithRetry($email, new LicenseRevokedMail($license));
    }

    private function sendWithRetry(string $email, object $mailable): void
    {
        $attempts = 3;
        $lastException = null;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $this->mailer->to($email)->send($mailable);

                return;
            } catch (\Throwable $e) {
                $lastException = $e;

                Log::warning('mail_send_failed', [
                    'email' => $email,
                    'attempt' => $i,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if ($lastException) {
            throw $lastException;
        }
    }
}
