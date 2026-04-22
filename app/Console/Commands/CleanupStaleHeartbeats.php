<?php

namespace App\Console\Commands;

use App\Services\HeartbeatService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupStaleHeartbeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heartbeats:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release stale floating seats that haven\'t sent heartbeat in over 10 minutes';

    /**
     * Execute the console command.
     */
    public function handle(HeartbeatService $heartbeatService): int
    {
        $this->info('Starting stale heartbeat cleanup...');

        try {
            $releasedCount = $heartbeatService->releaseStaleSeats();

            $this->info("Heartbeat cleanup completed. Released {$releasedCount} stale seats.");

            if ($releasedCount > 0) {
                Log::info('Stale floating seats released', [
                    'released_count' => $releasedCount,
                ]);
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup stale heartbeats: {$e->getMessage()}");

            Log::error('Failed to cleanup stale heartbeats', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
