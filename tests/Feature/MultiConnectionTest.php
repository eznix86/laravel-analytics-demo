<?php

use App\Analytics\CustomerRevenue;
use App\Analytics\Warehouse\DailyEvents;
use App\Models\Event;
use Eznix86\LaravelAnalytics\Exceptions\ConnectionMismatch;
use Eznix86\LaravelAnalytics\Graph\Node;
use Eznix86\LaravelAnalytics\Graph\Resolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->artisan('migrate:fresh', [
        '--database' => 'warehouse',
        '--path' => 'database/migrations/warehouse',
    ])->assertSuccessful();

    Event::query()->insert([
        ['name' => 'signup', 'source' => 'web', 'happened_at' => '2026-01-10 09:00:00'],
        ['name' => 'signup', 'source' => 'web', 'happened_at' => '2026-01-10 17:00:00'],
        ['name' => 'signup', 'source' => 'bot', 'happened_at' => '2026-01-10 18:00:00'],
        ['name' => 'checkout', 'source' => 'web', 'happened_at' => '2026-01-11 12:00:00'],
    ]);

    $this->seed();
});

it('resolves one independent graph per connection', function () {
    // Arrange
    $resolver = app(Resolver::class);

    // Act
    $connections = collect($resolver->resolve())
        ->map(fn (Node $node): string => (string) $node->connection)
        ->unique()
        ->sort()
        ->values()
        ->all();

    // Assert
    expect($connections)->toBe(collect([config('database.default'), 'warehouse'])->sort()->values()->all());
});

it('builds both connections in a single sync', function () {
    // Arrange, Act
    $this->artisan('analytics:sync')->assertSuccessful();

    // Assert
    expect(CustomerRevenue::query()->count())->toBe(3)
        ->and(DailyEvents::query()->count())->toBe(2);
});

it('builds only the connection it is given', function () {
    // Arrange, Act
    Artisan::call('analytics:sync', ['--connection' => 'warehouse']);
    $output = Artisan::output();

    // Assert
    expect(DailyEvents::query()->count())->toBe(2)
        ->and($output)->toContain('DailyEvents')
        ->and($output)->not->toContain('CustomerRevenue')
        ->and($output)->not->toContain('TrialBalance');
});

it('applies an ephemeral filter inside the sqlite warehouse', function () {
    // Arrange
    $this->artisan('analytics:sync', ['--connection' => 'warehouse'])->assertSuccessful();

    // Act
    $signups = DailyEvents::query()->where('name', 'signup')->value('total');

    // Assert
    expect(Event::query()->where('source', 'bot')->count())->toBe(1)
        ->and((int) $signups)->toBe(2);
});

it('records freshness against the connection the model lives on', function () {
    // Arrange, Act
    $this->artisan('analytics:sync', ['--connection' => 'warehouse'])->assertSuccessful();

    // Assert
    expect(DailyEvents::lastSyncedAt())->not->toBeNull();
});

it('rejects a sqlite model that reaches into the postgres application database', function () {
    // Arrange
    config()->set('analytics.path', base_path('tests/Fixtures/CrossConnection'));
    config()->set('analytics.namespace', 'Tests\\Fixtures\\CrossConnection');

    // Act
    $resolve = fn (): array => app(Resolver::class)->resolve();

    // Assert
    expect($resolve)->toThrow(
        ConnectionMismatch::class,
        sprintf('Reaches (warehouse) references Order (%s)', config('database.default')),
    );
});
