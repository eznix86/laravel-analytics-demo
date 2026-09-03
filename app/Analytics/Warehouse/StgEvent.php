<?php

namespace App\Analytics\Warehouse;

use App\Models\Event;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\EphemeralQuery;
use Illuminate\Database\Eloquent\Model;

class StgEvent extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    public function computes(): EphemeralQuery
    {
        return $this->from(Event::class)
            ->where('source', '<>', 'bot')
            ->select('name', 'happened_at')
            ->ephemeral();
    }
}
