<?php

namespace Tests\Feature\Admin;

use App\Models\License;
use App\Models\Product;
use App\Models\User;
use App\Services\LicenseKeyGenerator;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LicenseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Test Admin',
            'email' => 'admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->adminUser);

        $this->product = Product::create([
            'name'   => 'Test Product',
            'slug'   => 'test-product',
            'api_key'=> 'pk_test123',
            'status' => 'active',
        ]);
    }

    public function test_can_view_license_index()
    {
        $response = $this->get(route('admin.licenses.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.licenses.index');
    }

    public function test_can_view_license_create_form()
    {
        $response = $this->get(route('admin.licenses.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.licenses.create');
        $response->assertViewHas('products');
    }

    public function test_can_create_single_license()
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.licenses.store'), [
                'product_id'     => $this->product->id,
                'license_model'  => 'per-device',
                'quantity'       => 1,
                'customer_name'  => 'Test Customer',
                'customer_email' => 'test@example.com',
                'notes'          => 'Test license',
            ]);

        $response->assertRedirect(route('admin.licenses.batch-created'));

        $this->assertDatabaseHas('licenses', [
            'product_id'     => $this->product->id,
            'license_model'  => 'per-device',
            'customer_name'  => 'Test Customer',
            'customer_email' => 'test@example.com',
            'notes'          => 'Test license',
        ]);
    }

    public function test_can_create_floating_license_with_max_seats()
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.licenses.store'), [
                'product_id'    => $this->product->id,
                'license_model' => 'floating',
                'quantity'      => 1,
                'max_seats'     => 5,
            ]);

        $response->assertRedirect(route('admin.licenses.batch-created'));

        $this->assertDatabaseHas('licenses', [
            'product_id'    => $this->product->id,
            'license_model' => 'floating',
            'max_seats'     => 5,
        ]);
    }

    public function test_can_create_batch_licenses()
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.licenses.store'), [
                'product_id'    => $this->product->id,
                'license_model' => 'per-user',
                'quantity'      => 3,
            ]);

        $response->assertRedirect(route('admin.licenses.batch-created'));

        $this->assertSame(3, License::count());
    }

    public function test_can_view_license_details()
    {
        $licenseKeyGenerator = app(LicenseKeyGenerator::class);
        $key = $licenseKeyGenerator->generate();

        $license = License::create([
            'product_id'    => $this->product->id,
            'key_hash'      => $licenseKeyGenerator->hashKey($key),
            'key_last4'     => $licenseKeyGenerator->getKeyLast4($key),
            'license_model' => 'per-device',
            'status'        => 'inactive',
        ]);

        $response = $this->get(route('admin.licenses.show', $license));

        $response->assertStatus(200);
        $response->assertViewIs('admin.licenses.show');
        $response->assertViewHas('license');
    }

    public function test_validates_required_fields_for_license_creation()
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.licenses.store'), []);

        $response->assertSessionHasErrors([
            'product_id',
            'license_model',
            'quantity',
        ]);
    }

    public function test_validates_max_seats_required_for_floating_license()
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('admin.licenses.store'), [
                'product_id'    => $this->product->id,
                'license_model' => 'floating',
                'quantity'      => 1,
                // thiếu max_seats
            ]);

        $response->assertSessionHasErrors(['max_seats']);
    }

    public function test_can_export_licenses_to_csv()
    {
        $licenseKeyGenerator = app(LicenseKeyGenerator::class);
        $key = $licenseKeyGenerator->generate();

        License::create([
            'product_id'    => $this->product->id,
            'key_hash'      => $licenseKeyGenerator->hashKey($key),
            'key_last4'     => $licenseKeyGenerator->getKeyLast4($key),
            'license_model' => 'per-device',
            'status'        => 'inactive',
        ]);

        $response = $this->get(route('admin.licenses.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            'Key,Product,Model,Status,Expiry Date,Created At',
            $response->getContent()
        );
    }
}