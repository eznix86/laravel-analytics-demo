<?php

namespace App\Analytics\Retail;

use App\Models\Visit;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\EphemeralQuery;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

/**
 * Visits live at a different grain to transactions. Pre-aggregating both sides to
 * store-day keeps the conversion join from fanning out.
 */
class StoreDayVisits extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): EphemeralQuery
    {
        return $this->from(Visit::class, 'v')
            ->per(date_trunc('day', 'v.ts')->as('created_at_day'), 'v.store_id')
            ->measure('total_visits', 'count(*)')
            ->ephemeral();
    }
}
