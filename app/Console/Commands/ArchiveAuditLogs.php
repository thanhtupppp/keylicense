<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ArchiveAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit-logs:archive {--dry-run : Show what would be archived without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive (delete) audit logs older than 365 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting audit log archive...');

        $cutoffDate = now()->subDays(365);
        $isDryRun = $this->option('dry-run');

        // Find audit logs older than 365 days
        $oldLogsQuery = AuditLog::where('created_at', '<', $cutoffDate);

        $oldLogsCount = $oldLogsQuery->count();

        if ($oldLogsCount === 0) {
            $this->info('No audit logs older than 365 days found.');
            return self::SUCCESS;
        }

        $this->info("Found {$oldLogsCount} audit logs older than 365 days (before {$cutoffDate->toDateString()}).");

        if ($isDryRun) {
            $this->warn('DRY RUN: No logs will be actually deleted.');

            // Show some sample logs that would be deleted
            $sampleLogs = $oldLogsQuery->limit(5)->get(['id', 'event_type', 'created_at']);

            $this->table(
                ['ID', 'Event Type', 'Created At'],
                $sampleLogs->map(fn($log) => [
                    $log->id,
                    $log->event_type,
                    $log->created_at->toDateTimeString()
                ])->toArray()
            );

            if ($oldLogsCount > 5) {
                $this->line("... and " . ($oldLogsCount - 5) . " more logs.");
            }

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && !$this->confirm("Are you sure you want to delete {$oldLogsCount} audit logs?")) {
            $this->info('Archive operation cancelled.');
            return self::SUCCESS;
        }

        try {
            $deletedCount = $oldLogsQuery->delete();

            $this->info("Audit log archive completed. Deleted {$deletedCount} old logs.");

            Log::info('Audit logs archived', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to archive audit logs: {$e->getMessage()}");

            Log::error('Failed to archive audit logs', [
                'error' => $e->getMessage(),
                'cutoff_date' => $cutoffDate->toDateString(),
            ]);

            return self::FAILURE;
        }
    }
}
