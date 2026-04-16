<?php

use App\Services\Billing\PlatformConfigService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PlatformConfigService::class)->ensureDefaults();
    }

    public function down(): void
    {
        // intentionally left blank: defaults are safe to recreate
    }
};
