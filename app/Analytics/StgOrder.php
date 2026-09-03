<?php

namespace App\Analytics;

use App\Models\Order;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\ViewQuery;
use Illuminate\Database\Eloquent\Model;

class StgOrder extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): ViewQuery
    {
        return $this->from(Order::class)
            ->where('status', '<>', 'cancelled')
            ->select('id', 'customer_id', 'amount', 'placed_at')
            ->view();
    }
}
