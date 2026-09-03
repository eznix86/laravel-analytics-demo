<?php

use App\Analytics\Accounting\TrialBalance;
use App\Analytics\Retail\TransByStoreDay;
use Eznix86\LaravelAnalytics\Testing\Runner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->artisan('analytics:sync', ['--connection' => config('database.default')])->assertSuccessful();
});

it('passes every expectation against correctly built data', function () {
    // Arrange, Act
    $exitCode = Artisan::call('analytics:test', ['--connection' => config('database.default')]);
    $output = Artisan::output();

    // Assert
    expect($exitCode)->toBe(0)
        ->and($output)->toContain('expectations passed')
        ->and($output)->not->toContain('FAIL');
});

it('catches a duplicate that breaks the grain of a model', function () {
    // Arrange
    $row = (array) DB::table('analytics_trans_by_store_day')->first();

    DB::table('analytics_trans_by_store_day')->insert([$row]);

    // Act
    $failures = app(Runner::class)->failures(new TransByStoreDay);

    // Assert
    expect($failures)->toHaveCount(1)
        ->and($failures[0]->expectation->describe())->toContain('are unique together')
        ->and($failures[0]->offendingRows)->toBe(1);
});

it('catches a value the model should never produce', function () {
    // Arrange
    DB::table('analytics_trial_balance')->insert([[
        'account_code' => '9999',
        'account_name' => 'Impossible',
        'account_type' => 'asset',
        'total_debit' => -1,
        'total_credit' => 0,
        'balance' => -1,
    ]]);

    // Act
    $exitCode = Artisan::call('analytics:test', ['model' => 'TrialBalance']);

    // Assert
    expect($exitCode)->toBe(1)
        ->and(Artisan::output())->toContain('FAIL');
});

it('clears the failure once the model is rebuilt', function () {
    // Arrange
    DB::table('analytics_trial_balance')->insert([[
        'account_code' => '9999',
        'account_name' => 'Impossible',
        'account_type' => 'asset',
        'total_debit' => -1,
        'total_credit' => 0,
        'balance' => -1,
    ]]);

    expect(app(Runner::class)->failures(new TrialBalance))->not->toBeEmpty();

    // Act
    $this->artisan('analytics:sync', ['model' => 'TrialBalance', '--only' => true])->assertSuccessful();

    // Assert
    expect(app(Runner::class)->failures(new TrialBalance))->toBeEmpty();
});
