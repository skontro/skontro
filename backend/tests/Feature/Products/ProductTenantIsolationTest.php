<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('requesting another tenant\'s product returns 404, not 403', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $foreign = Product::factory()->forTenant($otherAdmin->tenant)->create();

    actingAs($admin)->getJson("/api/v1/products/{$foreign->uuid}")->assertNotFound();
});

test('you cannot archive another tenant\'s product', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $foreign = Product::factory()->forTenant($otherAdmin->tenant)->create();

    actingAs($admin)->postJson("/api/v1/products/{$foreign->uuid}/archive")->assertNotFound();
    expect($foreign->fresh()->is_active)->toBeTrue();
});

test('the list never includes another tenant\'s products', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    Product::factory()->forTenant($admin->tenant)->count(2)->create();
    Product::factory()->forTenant($otherAdmin->tenant)->count(4)->create();

    actingAs($admin)->getJson('/api/v1/products')->assertJsonCount(2, 'data');
});

test('unauthenticated requests are rejected', function () {
    \Pest\Laravel\getJson('/api/v1/products')->assertUnauthorized();
});
