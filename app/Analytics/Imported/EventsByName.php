<?php

namespace App\Analytics\Imported;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class EventsByName extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): Query
    {
        return $this->from(ImportedEvents::class)
            ->per('name', date_trunc('day', 'happened_at')->as('day'))
            ->measure('total', 'count(*)');
    }
}
