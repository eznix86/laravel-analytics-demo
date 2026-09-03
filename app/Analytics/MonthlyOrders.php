<?php

namespace App\Analytics;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\EphemeralQuery;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class MonthlyOrders extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): EphemeralQuery
    {
        return $this->from(StgOrder::class)
            ->per('customer_id', date_trunc('month', 'placed_at')->as('month'))
            ->measure('revenue', 'sum(amount)')
            ->ephemeral();
    }
}
