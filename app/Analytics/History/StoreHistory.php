<?php

namespace App\Analytics\History;

use App\Models\Store;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\SnapshotQuery;
use Illuminate\Database\Eloquent\Model;

/**
 * Slowly changing dimension: how each store's attributes moved over time.
 */
class StoreHistory extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['store_id']];
    }

    public function computes(): SnapshotQuery
    {
        return $this->from(Store::class)
            ->select('store_id', 'sqft', 'country', 'region', 'is_active')
            ->snapshot(trackedBy: ['store_id'], whenChanged: ['sqft', 'region', 'is_active']);
    }
}
