<?php

namespace App\Analytics\Stream;

use App\Models\Event;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\IncrementalQuery;
use Illuminate\Database\Eloquent\Model;

class EventStream extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    public function indexes(): array
    {
        return [['happened_at']];
    }

    public function computes(): IncrementalQuery
    {
        return $this->from(Event::class)
            ->select('id', 'name', 'source', 'happened_at')
            ->incremental(since: 'id');
    }
}
