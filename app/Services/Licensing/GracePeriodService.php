<?php

namespace App\Services\Licensing;

use App\Models\Activation;
use App\Support\Licensing\LicenseActivationStates;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class GracePeriodService
{
    public function sweep(int $graceDays = 7): int
    {
        $now = Carbon::now();
        $count = 0;

        Activation::query()
            ->whereIn('status', [LicenseActivationStates::ACTIVE, LicenseActivationStates::GRACE_PERIOD])
            ->chunkById(100, function (Collection $activations) use ($now, $graceDays, &$count): void {
                foreach ($activations as $activation) {
                    if ($this->shouldExpire($activation, $now, $graceDays)) {
                        $activation->forceFill([
                            'status' => LicenseActivationStates::EXPIRED,
                        ])->save();

                        $count++;
                    }
                }
            });

        return $count;
    }

    private function shouldExpire(Activation $activation, CarbonInterface $now, int $graceDays): bool
    {
        if (! $activation->last_validated_at instanceof CarbonInterface) {
            return false;
        }

        return $activation->last_validated_at->copy()->addDays($graceDays)->lessThanOrEqualTo($now);
    }
}
