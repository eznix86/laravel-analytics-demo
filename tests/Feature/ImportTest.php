<?php

use App\Models\Event;
use Eznix86\LaravelAnalytics\Graph\Resolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate:fresh', [
        '--database' => 'warehouse',
        '--path' => 'database/migrations/warehouse',
    ])->assertSuccessful();

    Event::query()->insert([
        ['name' => 'signup', 'source' => 'web', 'happened_at' => '2026-01-10 09:00:00'],
        ['name' => 'checkout', 'source' => 'web', 'happened_at' => '2026-01-11 12:00:00'],
    ]);

    $this->seed();
    $this->artisan('analytics:sync')->assertSuccessful();
});

it('copies rows from the sqlite warehouse onto the application connection', function () {
    // Arrange
    $imported = DB::table('imported_events')->orderBy('id')->get();

    // Act
    $names = $imported->pluck('name')->all();

    // Assert
    expect($imported)->toHaveCount(2)
        ->and($names)->toBe(['signup', 'checkout']);
});

it('keeps the timestamp intact across the two drivers', function () {
    // Arrange
    $row = DB::table('imported_events')->orderBy('id')->first();

    // Act
    $happenedAt = substr((string) $row->happened_at, 0, 19);

    // Assert
    expect($happenedAt)->toBe('2026-01-10 09:00:00');
});

it('brings over only the new rows on a later run', function () {
    // Arrange
    DB::table('imported_events')->where('id', 1)->delete();
    Event::query()->insert([['name' => 'refund', 'source' => 'web', 'happened_at' => '2026-01-12 09:00:00']]);

    // Act
    $this->artisan('analytics:sync')->assertSuccessful();

    // Assert
    expect(DB::table('imported_events')->orderBy('id')->pluck('id')->all())->toBe([2, 3]);
});

it('imports in the other direction too, from the application connection into the warehouse', function () {
    // Arrange
    $orders = DB::table('orders')->count();

    // Act
    $imported = DB::connection('warehouse')->table('imported_orders')->count();

    // Assert
    expect($orders)->toBeGreaterThan(0)
        ->and($imported)->toBe($orders);
});

it('lets another analytics model build on top of an imported table', function () {
    // Arrange
    $rows = DB::table('analytics_events_by_name')->orderBy('name')->get();

    // Act
    $totals = $rows->pluck('total', 'name')->map(fn ($total): int => (int) $total)->all();

    // Assert
    expect($totals)->toBe(['checkout' => 1, 'signup' => 1]);
});

it('builds the import before the model that reads it', function () {
    // Arrange
    $order = collect(app(Resolver::class)->resolve())->map(fn ($node): string => $node->name())->values();

    // Act
    $importedAt = $order->search('ImportedEvents');
    $downstreamAt = $order->search('EventsByName');

    // Assert
    expect($importedAt)->not->toBeFalse()
        ->and($downstreamAt)->toBeGreaterThan($importedAt);
});
