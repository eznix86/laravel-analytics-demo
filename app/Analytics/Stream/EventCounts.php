<?php

namespace App\Analytics\Stream;

use App\Models\Event;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\IncrementalQuery;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

/**
 * A unique key on the grain, so restated days replace rather than duplicate.
 */
class EventCounts extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    public function expectations(): array
    {
        return [
            Expectation::unique('day', 'name'),
            Expectation::expression('total > 0'),
        ];
    }

    public function computes(): IncrementalQuery
    {
        return $this->from(Event::class)
            ->per(date_trunc('day', 'happened_at')->as('day'), 'name')
            ->measure('total', 'count(*)')
            ->incremental(replacing: ['day', 'name'], since: 'day');
    }
}
