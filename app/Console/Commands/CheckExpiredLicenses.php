<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredLicenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for active licenses that have expired and transition them to expired status';

    /**
     * Execute the console command.
     */
    public function handle(LicenseService $licenseService): int
    {
        $this->info('Starting expired license check...');

        // Find all active licenses with expiry_date in the past
        $expiredLicenses = License::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->get();

        $expiredCount = 0;
        $errorCount = 0;

        foreach ($expiredLicenses as $license) {
            try {
                $licenseService->expire($license);
                $expiredCount++;

                $this->line("Expired license: {$license->key_last4} (ID: {$license->id})");

                Log::info('License expired by scheduler', [
                    'license_id' => $license->id,
                    'key_last4' => $license->key_last4,
                    'expiry_date' => $license->expiry_date,
                ]);
            } catch (\Exception $e) {
                $errorCount++;

                $this->error("Failed to expire license {$license->key_last4} (ID: {$license->id}): {$e->getMessage()}");

                Log::error('Failed to expire license', [
                    'license_id' => $license->id,
                    'key_last4' => $license->key_last4,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Expired license check completed. Processed: {$expiredCount}, Errors: {$errorCount}");

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
