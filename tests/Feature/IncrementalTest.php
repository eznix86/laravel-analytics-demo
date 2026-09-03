<?php

use App\Analytics\Stream\CustomerTotals;
use App\Analytics\Stream\OrderStream;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->artisan('analytics:sync', ['--connection' => config('database.default')])->assertSuccessful();
});

it('builds the whole relation on the first run', function () {
    // Arrange, Act
    $rows = OrderStream::query()->count();

    // Assert
    expect($rows)->toBe(Order::query()->count());
});

it('appends only rows that arrived since the last run', function () {
    // Arrange
    $before = OrderStream::query()->count();

    Order::query()->insert([
        ['customer_id' => 9, 'amount' => 700, 'status' => 'paid', 'placed_at' => '2026-04-01 10:00:00'],
    ]);

    // Act
    $this->artisan('analytics:sync', ['model' => 'OrderStream'])->assertSuccessful();

    // Assert
    expect(OrderStream::query()->count())->toBe($before + 1)
        ->and(OrderStream::query()->where('customer_id', 9)->exists())->toBeTrue();
});

it('appends nothing when no new rows arrived', function () {
    // Arrange
    $before = OrderStream::query()->count();

    // Act
    $this->artisan('analytics:sync', ['model' => 'OrderStream'])->assertSuccessful();

    // Assert
    expect(OrderStream::query()->count())->toBe($before);
});

it('replaces a restated row instead of duplicating it', function () {
    // Arrange
    $before = (int) CustomerTotals::query()->where('customer_id', 1)->value('total');

    Order::query()->insert([
        ['customer_id' => 1, 'amount' => 1000, 'status' => 'paid', 'placed_at' => '2026-04-01 10:00:00'],
    ]);

    // Act
    $this->artisan('analytics:sync', ['model' => 'CustomerTotals'])->assertSuccessful();

    // Assert
    expect(CustomerTotals::query()->where('customer_id', 1)->count())->toBe(1)
        ->and((int) CustomerTotals::query()->where('customer_id', 1)->value('total'))->toBe($before + 1000);
});

it('reports the appended row count rather than the table size', function () {
    // Arrange
    Order::query()->insert([
        ['customer_id' => 9, 'amount' => 700, 'status' => 'paid', 'placed_at' => '2026-04-01 10:00:00'],
    ]);

    // Act
    Artisan::call('analytics:sync', ['model' => 'OrderStream', '--only' => true, '--porcelain' => true]);

    // Assert
    [, $rows] = explode("\t", trim(Artisan::output()));

    expect((int) $rows)->toBe(1);
});

it('discards rows that no longer belong under a full refresh', function () {
    // Arrange
    DB::table('analytics_order_stream')->insert([
        ['id' => 9999, 'customer_id' => 42, 'amount' => 1, 'status' => 'paid'],
    ]);

    // Act
    $this->artisan('analytics:sync', ['--full-refresh' => true, '--connection' => config('database.default')])
        ->assertSuccessful();

    // Assert
    expect(OrderStream::query()->where('customer_id', 42)->exists())->toBeFalse()
        ->and(OrderStream::query()->count())->toBe(Order::query()->count());
});

it('keeps declared indexes on the incremental relation', function () {
    // Arrange
    $indexes = match (DB::connection()->getDriverName()) {
        'pgsql' => DB::select("select indexname as name from pg_indexes where tablename = 'analytics_order_stream'"),
        'mysql' => DB::select('show index from analytics_order_stream'),
        default => DB::select("select name from sqlite_master where type = 'index' and tbl_name = 'analytics_order_stream'"),
    };

    // Act, Assert
    expect($indexes)->not->toBeEmpty();
});

it('leaves no staging relation behind after an append', function () {
    // Arrange
    Order::query()->insert([
        ['customer_id' => 9, 'amount' => 700, 'status' => 'paid', 'placed_at' => '2026-04-01 10:00:00'],
    ]);

    // Act
    $this->artisan('analytics:sync', ['model' => 'OrderStream'])->assertSuccessful();

    // Assert
    $relations = collect(DB::connection()->getSchemaBuilder()->getTableListing());

    expect($relations->filter(fn (string $name): bool => str_contains($name, '__inc_')))->toBeEmpty();
});
