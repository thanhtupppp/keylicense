<?php

namespace App\Jobs;

use App\Mail\LicenseIssuedMail;
use App\Models\LicenseKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ExpiringSoonNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $licenses = LicenseKey::query()
            ->with('entitlement.customer')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->get();

        foreach ($licenses as $license) {
            $email = $license->entitlement?->customer?->email;

            if (! $email) {
                continue;
            }

            Mail::to($email)->send(new LicenseIssuedMail(
                'expiring-soon',
                $license->key_display,
                $license->entitlement,
            ));
        }
    }
}
