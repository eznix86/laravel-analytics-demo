<?php

namespace App\Analytics\Retail;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

class TotalStoresByCountryGroup extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): Query
    {
        return $this->from(SemanticStore::class, 's')
            ->whereRaw('s.is_active')
            ->per('s.country_group', 's.floor_size')
            ->measure('total_stores', Metrics::totalStores())
            ->measure('total_sqft', 'sum(s.sqft)');
    }
}
