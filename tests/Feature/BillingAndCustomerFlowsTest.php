<?php

use App\Models\Customer;
use App\Models\PlanPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Concerns\AdminAuthFixtures;
use Tests\Support\AbuseDetectionFixtures;
use Tests\Support\BillingFixtures;
use Tests\Support\WebhookFixtures;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    /** @var \Tests\TestCase $this */
    $admin = AdminAuthFixtures::createAdmin();
    $this->withHeaders(AdminAuthFixtures::authHeaders($admin));
});

// ... remaining tests unchanged ...
