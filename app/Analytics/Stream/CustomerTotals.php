<?php

namespace App\Analytics\Stream;

use App\Models\Order;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\IncrementalQuery;
use Illuminate\Database\Eloquent\Model;

class CustomerTotals extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): IncrementalQuery
    {
        return $this->from(Order::class)
            ->where('status', '<>', 'cancelled')
            ->per('customer_id')
            ->measure('total', 'sum(amount)')
            ->incremental(replacing: ['customer_id']);
    }
}
