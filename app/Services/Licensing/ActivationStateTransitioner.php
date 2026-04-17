<?php

namespace App\Services\Licensing;

use App\Models\Activation;
use App\Support\Licensing\LicenseActivationStates;

class ActivationStateTransitioner
{
    public function moveToGracePeriod(Activation $activation): Activation
    {
        $activation->forceFill([
            'status' => LicenseActivationStates::GRACE_PERIOD,
        ])->save();

        return $activation->refresh();
    }

    public function expire(Activation $activation): Activation
    {
        $activation->forceFill([
            'status' => LicenseActivationStates::EXPIRED,
        ])->save();

        return $activation->refresh();
    }
}
