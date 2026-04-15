<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\ApiKey;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminToken = 'admin-dev-token-123';
        $clientApiKey = 'client-dev-key-123';

        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@internal.local'],
            [
                'full_name' => 'Platform Admin',
                'password_hash' => Hash::make('secret-password'),
                'role' => 'super_admin',
                'is_active' => true,
                'api_token' => hash('sha256', $adminToken),
            ]
        );

        ApiKey::query()->updateOrCreate(
            ['name' => 'default-client-key'],
            [
                'api_key' => hash('sha256', $clientApiKey),
                'scope' => 'client',
                'is_active' => true,
            ]
        );

        $this->command?->info('Admin bearer token (dev): '.$adminToken);
        $this->command?->info('Client X-API-Key (dev): '.$clientApiKey);
    }
}
