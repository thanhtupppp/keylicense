<?php

namespace App\Services;

use App\Exceptions\SeatNotFoundException;
use App\Models\FloatingSeat;
use App\Models\License;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

class HeartbeatService
{
    /**
     * Update the heartbeat timestamp for a floating seat.
     *
     * @param License $license The license to update heartbeat for
     * @param string $fingerprintHash The SHA-256 hash of the device fingerprint
     * @return void
     * @throws SeatNotFoundException If no floating seat is found for the given license and fingerprint
     */
    public function heartbeat(License $license, string $fingerprintHash): void
    {
        $seat = FloatingSeat::where('license_id', $license->id)
            ->where('device_fp_hash', $fingerprintHash)
            ->first();

        if (! $seat) {
            throw new SeatNotFoundException();
        }

        $seat->update([
            'last_heartbeat_at' => now(),
        ]);

        AuditLogger::log('heartbeat.received', $license, [
            'device_fingerprint_hash' => $fingerprintHash,
            'severity' => 'info',
            'result' => 'success',
            'actor_name' => 'Hệ thống',
        ]);
    }

    /**
     * Release all stale floating seats that haven't sent a heartbeat in over 10 minutes.
     *
     * @return int The number of seats released
     */
    public function releaseStaleSeats(): int
    {
        $staleThreshold = now()->subMinutes(10);

        return FloatingSeat::where('last_heartbeat_at', '<', $staleThreshold)
            ->delete();
    }
}
