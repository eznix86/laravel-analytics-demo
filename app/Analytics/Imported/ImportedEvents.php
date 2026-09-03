<?php

namespace App\Analytics\Imported;

use App\Models\Event;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\ImportQuery;
use Illuminate\Database\Eloquent\Model;

class ImportedEvents extends Model implements AnalyticsModel
{
    use Analytics;

    protected $table = 'imported_events';

    public function computes(): ImportQuery
    {
        return $this->from(Event::class)
            ->select('id', 'name', 'source', 'happened_at')
            ->import(replacing: ['id'], appendOnly: 'id', chunk: 500);
    }
}
