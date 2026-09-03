<?php

use App\Analytics\Accounting\RunningTotalByAccount;
use App\Analytics\Accounting\TrialBalance;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->artisan('analytics:sync', ['--connection' => config('database.default')])->assertSuccessful();
});

it('accumulates a running balance in transaction order within an account', function () {
    // Arrange
    $cash = RunningTotalByAccount::query()
        ->where('account_code', '1000')
        ->orderBy('txn_date')
        ->orderBy('id')
        ->get();

    // Act
    $balances = $cash->pluck('running_balance')->map(fn ($value): int => (int) $value)->all();

    // Assert
    expect($balances)->toBe([500_000, 455_000, 575_000]);
});

it('keeps the running balance inside its own account partition', function () {
    // Arrange
    $receivable = RunningTotalByAccount::query()
        ->where('account_code', '1200')
        ->orderBy('txn_date')
        ->orderBy('id')
        ->get();

    // Act
    $balances = $receivable->pluck('running_balance')->map(fn ($value): int => (int) $value)->all();

    // Assert
    expect($balances)->toBe([120_000, 0]);
});

it('excludes unposted entries from the ledger', function () {
    // Arrange
    $draft = JournalEntry::query()->where('posted', false)->firstOrFail();
    $draftLines = JournalLine::query()->where('journal_entry_id', $draft->id)->count();

    // Act
    $ledgerLines = RunningTotalByAccount::query()->count();

    // Assert
    expect($draftLines)->toBe(2)
        ->and($ledgerLines)->toBe(JournalLine::query()->count() - $draftLines);
});

it('produces a trial balance where debits equal credits', function () {
    // Arrange
    $rows = TrialBalance::query()->get();

    // Act
    $debits = (int) $rows->sum('total_debit');
    $credits = (int) $rows->sum('total_credit');

    // Assert
    expect($debits)->toBeGreaterThan(0)
        ->and($debits)->toBe($credits);
});

it('agrees between the trial balance and the final running balance of each account', function () {
    // Arrange
    $trial = TrialBalance::query()->pluck('balance', 'account_code')
        ->map(fn ($value): int => (int) $value);

    // Act
    $closing = RunningTotalByAccount::query()
        ->orderBy('txn_date')
        ->orderBy('id')
        ->get()
        ->groupBy('account_code')
        ->map(fn ($rows): int => (int) $rows->last()->running_balance);

    // Assert
    expect($trial->count())->toBe(5)
        ->and($closing->all())->toEqual($trial->all());
});

it('rebuilds the ledger when a new entry is posted', function () {
    // Arrange
    JournalEntry::query()->insert([
        ['id' => 6, 'txn_date' => '2026-03-01', 'description' => 'March rent', 'posted' => true],
    ]);

    JournalLine::query()->insert([
        ['journal_entry_id' => 6, 'account_id' => 6, 'debit' => 45_000, 'credit' => 0],
        ['journal_entry_id' => 6, 'account_id' => 1, 'debit' => 0, 'credit' => 45_000],
    ]);

    // Act
    $this->artisan('analytics:sync', ['--connection' => config('database.default')])->assertSuccessful();

    // Assert
    $cash = RunningTotalByAccount::query()->where('account_code', '1000')
        ->orderBy('txn_date')->orderBy('id')->get();

    expect((int) $cash->last()->running_balance)->toBe(530_000)
        ->and($cash)->toHaveCount(4);
});
