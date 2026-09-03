<?php

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);
});

function storeHistory(): Collection
{
    return DB::table('analytics_store_history')->orderBy('store_id')->orderBy('valid_from')->get();
}

it('opens one version per source row on the first build', function () {
    // Arrange, Act
    $history = storeHistory();

    // Assert
    expect($history)->toHaveCount(Store::query()->count())
        ->and($history->whereNull('valid_to'))->toHaveCount(Store::query()->count());
});

it('adds nothing when no watched column changed', function () {
    // Arrange
    Store::query()->where('store_id', 1)->update(['country' => 'MU']);

    // Act
    Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);

    // Assert
    expect(storeHistory())->toHaveCount(Store::query()->count());
});

it('closes the previous version and opens a new one when a watched column changes', function () {
    // Arrange
    Store::query()->where('store_id', 2)->update(['sqft' => 6000, 'region' => 'Manchester']);

    // Act
    Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);

    // Assert
    $versions = storeHistory()->where('store_id', 2)->values();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->valid_to)->not->toBeNull()
        ->and((int) $versions[0]->sqft)->toBe(3000)
        ->and($versions[1]->valid_to)->toBeNull()
        ->and((int) $versions[1]->sqft)->toBe(6000);
});

it('tracks a boolean flip through the null safe comparison', function () {
    // Arrange
    Store::query()->where('store_id', 1)->update(['is_active' => false]);

    // Act
    Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);

    // Assert
    expect(storeHistory()->where('store_id', 1))->toHaveCount(2);
});

it('keeps exactly one open version per key across several changes', function () {
    // Arrange
    foreach ([4000, 5000, 6000] as $sqft) {
        Store::query()->where('store_id', 3)->update(['sqft' => $sqft]);
        Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);
    }

    // Act
    $versions = storeHistory()->where('store_id', 3);

    // Assert
    expect($versions)->toHaveCount(4)
        ->and($versions->whereNull('valid_to'))->toHaveCount(1)
        ->and((int) $versions->firstWhere('valid_to', null)->sqft)->toBe(6000);
});

it('opens a version for a store the source has never had', function () {
    // Arrange
    Store::query()->insert([
        ['store_id' => 9, 'sqft' => 100, 'country' => 'DE', 'region' => 'Berlin', 'is_active' => true, 'ds' => '2026-01-01'],
    ]);

    // Act
    Artisan::call('analytics:sync', ['model' => 'StoreHistory', '--only' => true]);

    // Assert
    expect(storeHistory()->where('store_id', 9))->toHaveCount(1);
});
