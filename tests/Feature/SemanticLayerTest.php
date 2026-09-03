<?php

use App\Analytics\Retail\Rolling30DayTransactions;
use App\Analytics\Retail\SemanticTransaction;
use App\Analytics\Retail\StoreDayConversion;
use App\Analytics\Retail\TotalStoresByCountryGroup;
use App\Analytics\Retail\TransByCountryGroupMonth;
use App\Analytics\Retail\TransByStoreDay;
use App\Analytics\Retail\TransPerCustByBrandMonth;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->artisan('analytics:sync', ['--connection' => config('database.default')])->assertSuccessful();
});

it('keeps a ratio metric in decimals instead of truncating it to an integer', function () {
    // Arrange
    $january = TransPerCustByBrandMonth::query()
        ->where('brand', 'Nike')
        ->orderBy('created_at_month')
        ->first();

    // Act
    $perCustomer = round((float) $january->trans_per_cust, 4);

    // Assert
    expect($perCustomer)->toBe(1.5);
});

it('excludes inactive stores from every rollup', function () {
    // Arrange
    $inactiveTransactions = Transaction::query()->where('store_id', 4)->count();

    // Act
    $semantic = SemanticTransaction::query()->count();

    // Assert
    expect($inactiveTransactions)->toBe(1)
        ->and($semantic)->toBe(Transaction::query()->count() - $inactiveTransactions)
        ->and(TransByStoreDay::query()->where('store_id', 4)->exists())->toBeFalse();
});

it('reports the same total from every rollup of the shared metric', function () {
    // Arrange
    $expected = SemanticTransaction::query()->count();

    // Act
    $byStoreDay = (int) TransByStoreDay::query()->sum('total_transactions');
    $byCountryGroup = (int) TransByCountryGroupMonth::query()->sum('total_transactions');

    // Assert
    expect($byStoreDay)->toBe($expected)
        ->and($byCountryGroup)->toBe($expected);
});

it('rolls the same metric up along a derived dimension', function () {
    // Arrange
    $africa = TransByCountryGroupMonth::query()->where('country_group', 'AFRICA')->firstOrFail();

    // Act
    $perCustomer = round((float) $africa->trans_per_cust, 4);

    // Assert
    expect((int) $africa->total_transactions)->toBe(4)
        ->and($perCustomer)->toBe(1.3333);
});

it('joins visits at a matching grain instead of fanning out the transaction rows', function () {
    // Arrange
    $day = StoreDayConversion::query()->where('store_id', 1)->orderBy('created_at_day')->first();

    // Act
    $conversion = round((float) $day->conversion, 4);

    // Assert
    expect((int) $day->total_transactions)->toBe(3)
        ->and((int) $day->total_visits)->toBe(10)
        ->and($conversion)->toBe(0.3)
        ->and(StoreDayConversion::query()->count())->toBe(TransByStoreDay::query()->count());
});

it('accumulates the rolling window inside each store partition', function () {
    // Arrange
    $storeOne = Rolling30DayTransactions::query()->where('store_id', 1)->orderBy('created_at_day')->get();
    $storeTwo = Rolling30DayTransactions::query()->where('store_id', 2)->orderBy('created_at_day')->get();

    // Act
    $one = $storeOne->pluck('transactions_30_day')->map(fn ($v): int => (int) $v)->all();
    $two = $storeTwo->pluck('transactions_30_day')->map(fn ($v): int => (int) $v)->all();

    // Assert
    expect($one)->toBe([3, 4])
        ->and($two)->toBe([1, 3]);
});

it('derives store dimensions that do not exist in the source table', function () {
    // Arrange
    $rows = TotalStoresByCountryGroup::query()->orderBy('country_group')->orderBy('floor_size')->get();

    // Act
    $groups = $rows->map(fn ($r): string => $r->country_group.'/'.$r->floor_size)->all();

    // Assert
    expect($groups)->toBe(['AFRICA/small', 'EUROPE/large', 'EUROPE/medium'])
        ->and((int) $rows->sum('total_stores'))->toBe(3);
});
