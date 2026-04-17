<?php

namespace App\Jobs;

use App\Models\Activation;
use App\Models\LicenseKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HeartbeatJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $activationId,
    ) {
    }

    public function handle(): void
    {
        $activation = Activation::query()->where('activation_code', $this->activationId)->first();

        if (! $activation) {
            return;
        }

        $activation->forceFill(['last_validated_at' => now()])->save();

        $license = LicenseKey::query()->find($activation->license_id);

        if ($license) {
            $license->forceFill(['status' => $license->status === 'issued' ? 'active' : $license->status])->save();
        }
    }
}
