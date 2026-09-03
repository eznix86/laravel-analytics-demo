<?php

use App\Analytics\Accounting\RunningTotalByAccount;
use App\Analytics\Retail\TransByStoreDay;
use App\Analytics\Warehouse\DailyEvents;
use App\Models\Event;
use Eznix86\LaravelAnalytics\Models\AnalyticsRun;
use Eznix86\LaravelAnalytics\RunStatus;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * No RefreshDatabase here: parallel sync builds in subprocesses, which open their own
 * connections and cannot see an uncommitted test transaction.
 */
beforeEach(function (): void {
    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    Artisan::call('migrate:fresh', [
        '--database' => 'warehouse',
        '--path' => 'database/migrations/warehouse',
        '--force' => true,
    ]);

    Event::query()->insert([
        ['name' => 'signup', 'source' => 'web', 'happened_at' => '2026-01-10 09:00:00'],
        ['name' => 'signup', 'source' => 'bot', 'happened_at' => '2026-01-10 18:00:00'],
    ]);
});

afterEach(function (): void {
    foreach (['Analytics/Late', 'Analytics/Broken'] as $scaffolded) {
        File::deleteDirectory(app_path($scaffolded));
    }

    Schema::dropIfExists('late_feed');
    DB::statement('drop table if exists analytics_late_feed');

    // this file commits, so leave a clean database for the transactional test files
    Artisan::call('migrate:fresh', ['--force' => true]);
});

it('builds every model when run with parallel workers', function () {
    // Arrange, Act
    $exitCode = Artisan::call('analytics:sync', ['--parallel' => 4]);

    // Assert
    expect($exitCode)->toBe(0)
        ->and(RunningTotalByAccount::query()->count())->toBe(8)
        ->and(TransByStoreDay::query()->count())->toBe(5)
        ->and(DailyEvents::query()->count())->toBe(1);
});

it('produces the same rows in parallel as in series', function () {
    // Arrange
    Artisan::call('analytics:sync');

    $series = [
        RunningTotalByAccount::query()->orderBy('id')->pluck('running_balance')->all(),
        TransByStoreDay::query()->orderBy('created_at_day')->orderBy('store_id')->pluck('total_transactions')->all(),
    ];

    // Act
    Artisan::call('analytics:sync', ['--parallel' => 4]);

    $parallel = [
        RunningTotalByAccount::query()->orderBy('id')->pluck('running_balance')->all(),
        TransByStoreDay::query()->orderBy('created_at_day')->orderBy('store_id')->pluck('total_transactions')->all(),
    ];

    // Assert
    expect($series[0])->not->toBeEmpty()
        ->and($parallel)->toEqual($series);
});

it('resumes a failed parallel run without rebuilding what already succeeded', function () {
    // Arrange
    $path = app_path('Analytics/Late/LateFeed.php');
    File::ensureDirectoryExists(dirname($path));
    file_put_contents($path, <<<'PHP'
    <?php

    namespace App\Analytics\Late;

    use App\Analytics\Retail\SemanticTransaction;
    use Eznix86\LaravelAnalytics\Concerns\Analytics;
    use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
    use Illuminate\Database\Eloquent\Model;

    class LateFeed extends Model implements AnalyticsModel
    {
        use Analytics;

        public function computes(): string
        {
            return 'select t.store_id, l.note from '.$this->ref(SemanticTransaction::class).' t, late_feed l';
        }
    }
    PHP);

    expect(Artisan::call('analytics:sync', ['--parallel' => 4]))->toBe(1);

    $succeeded = AnalyticsRun::query()->where('status', RunStatus::Success)->count();

    Schema::create('late_feed', function (Blueprint $table): void {
        $table->id();
        $table->string('note');
    });
    DB::table('late_feed')->insert([['note' => 'arrived']]);

    // Act
    $exitCode = Artisan::call('analytics:sync', ['--continue' => true, '--parallel' => 4]);
    $output = Artisan::output();

    // Assert
    expect($exitCode)->toBe(0)
        ->and($succeeded)->toBeGreaterThan(0)
        ->and($output)->toContain('Resuming run')
        ->and(AnalyticsRun::query()->distinct()->pluck('run_id'))->toHaveCount(1)
        ->and(DB::table('analytics_late_feed')->count())->toBe(8);

});

it('reports a failure exit code when a model cannot build', function () {
    // Arrange
    $path = app_path('Analytics/Broken/Broken.php');
    File::ensureDirectoryExists(dirname($path));
    file_put_contents($path, <<<'PHP'
    <?php

    namespace App\Analytics\Broken;

    use Eznix86\LaravelAnalytics\Concerns\Analytics;
    use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
    use Illuminate\Database\Eloquent\Model;

    class Broken extends Model implements AnalyticsModel
    {
        use Analytics;

        public function computes(): string
        {
            return 'select 1 from a_table_that_does_not_exist';
        }
    }
    PHP);

    // Act
    $exitCode = Artisan::call('analytics:sync', ['--parallel' => 4]);

    // Assert
    expect($exitCode)->toBe(1);
});
