<?php

use App\Analytics\CustomerRevenue;
use App\Analytics\MonthlyOrders;
use App\Analytics\Retail\SemanticStore;
use App\Models\Order;
use Eznix86\LaravelAnalytics\Exceptions\NotQueryable;
use Eznix86\LaravelAnalytics\Exceptions\OutsideCompilation;
use Eznix86\LaravelAnalytics\Exceptions\ReadOnlyModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->artisan('analytics:sync')->assertSuccessful();
});

it('aggregates orders per customer per month', function () {
    // Arrange
    $ada = CustomerRevenue::query()->where('name', 'Ada')->orderBy('month')->get();

    // Act
    $revenue = $ada->pluck('revenue')->map(fn ($value): int => (int) $value)->all();

    // Assert
    expect($revenue)->toBe([300, 450]);
});

it('carries the previous month through the window function', function () {
    // Arrange
    $ada = CustomerRevenue::query()->where('name', 'Ada')->orderBy('month')->get();

    // Act, Assert
    expect($ada->first()->previous_revenue)->toBeNull()
        ->and((int) $ada->last()->previous_revenue)->toBe(300);
});

it('never lets a cancelled order reach the mart', function () {
    // Arrange
    $cancelled = Order::query()->where('status', 'cancelled')->sum('amount');

    // Act
    $total = (int) CustomerRevenue::query()->sum('revenue');

    // Assert
    expect($cancelled)->toBeGreaterThan(0)
        ->and($total)->toBe(800);
});

it('joins an analytics model to a plain Eloquent source', function () {
    // Arrange, Act
    $countries = CustomerRevenue::query()->pluck('country')->unique()->sort()->values()->all();

    // Assert
    expect($countries)->toBe(['GB', 'MU']);
});

it('picks up new source rows on the next sync', function () {
    // Arrange
    Order::query()->insert([
        ['customer_id' => 2, 'amount' => 777, 'status' => 'paid', 'placed_at' => '2026-03-05 10:00:00'],
    ]);

    // Act
    $this->artisan('analytics:sync')->assertSuccessful();

    // Assert
    expect((int) CustomerRevenue::query()->sum('revenue'))->toBe(1577);
});

it('leaves no scratch relations behind after a rebuild', function () {
    // Arrange
    $this->artisan('analytics:sync')->assertSuccessful();

    // Act
    $relations = collect(DB::connection()->getSchemaBuilder()->getTableListing())
        ->merge(collect(DB::connection()->getSchemaBuilder()->getViews())->pluck('name'));

    // Assert
    expect($relations->filter(fn (string $name): bool => str_contains($name, '__')))->toBeEmpty();
});

it('refuses a write to a rebuilt relation', function () {
    // Arrange
    $revenue = new CustomerRevenue;
    $revenue->revenue = 1;

    // Act, Assert
    expect(fn (): bool => $revenue->save())->toThrow(ReadOnlyModel::class);
});

it('refuses to query an ephemeral model', function () {
    // Arrange, Act, Assert
    expect(fn (): int => MonthlyOrders::query()->count())->toThrow(NotQueryable::class);
});

it('refuses to resolve refs outside compilation', function () {
    // Arrange
    $computes = fn (): string|Query => (new SemanticStore)->computes();

    // Act, Assert
    expect($computes)->toThrow(OutsideCompilation::class);
});

it('reports freshness from the recorded sync', function () {
    // Arrange, Act, Assert
    expect(CustomerRevenue::isStale())->toBeFalse()
        ->and(CustomerRevenue::lastSyncedAt())->not->toBeNull();
});
