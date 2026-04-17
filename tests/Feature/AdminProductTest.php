<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\AdminAuthFixtures;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('lists products', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    Product::query()->create([
        'code' => 'prod-1',
        'name' => 'Product One',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/products')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.products');
});

it('searches and filters products', function (): void {
    $admin = AdminAuthFixtures::createAdmin();

    Product::query()->create([
        'code' => 'prod-alpha',
        'name' => 'Alpha Product',
        'description' => 'Alpha description',
        'category' => 'core',
        'status' => 'active',
    ]);

    Product::query()->create([
        'code' => 'prod-beta',
        'name' => 'Beta Product',
        'description' => 'Beta description',
        'category' => 'addon',
        'status' => 'inactive',
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->getJson('/api/v1/admin/products?search=Alpha&category=core&status=active')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.products')
        ->assertJsonPath('data.products.0.code', 'prod-alpha');
});

it('creates a product', function (): void {
    $admin = AdminAuthFixtures::createAdmin();

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->postJson('/api/v1/admin/products', [
            'code' => 'prod-2',
            'name' => 'Product Two',
            'status' => 'active',
        ])
        ->assertCreated()
        ->assertJsonPath('data.product.code', 'prod-2');
});

it('updates a product', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-3',
        'name' => 'Product Three',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->patchJson('/api/v1/admin/products/'.$product->id, [
            'name' => 'Product Three Updated',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.product.name', 'Product Three Updated');
});

it('deletes a product', function (): void {
    $admin = AdminAuthFixtures::createAdmin();
    $product = Product::query()->create([
        'code' => 'prod-4',
        'name' => 'Product Four',
        'description' => null,
        'category' => null,
        'status' => 'active',
    ]);

    $this->withHeaders(AdminAuthFixtures::authHeaders($admin))
        ->deleteJson('/api/v1/admin/products/'.$product->id)
        ->assertSuccessful()
        ->assertJsonPath('data.deleted', true);

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});
