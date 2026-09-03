<?php

namespace App\Analytics\Warehouse;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class DailyEvents extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    public function indexes(): array
    {
        return [['day']];
    }

    public function computes(): Query
    {
        return $this->from(StgEvent::class)
            ->per(date_trunc('day', 'happened_at')->as('day'), 'name')
            ->measure('total', 'count(*)');
    }
}
