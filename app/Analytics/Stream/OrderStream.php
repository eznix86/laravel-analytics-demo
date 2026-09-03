<?php

namespace App\Analytics\Stream;

use App\Models\Order;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\IncrementalQuery;
use Illuminate\Database\Eloquent\Model;

class OrderStream extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['customer_id']];
    }

    public function computes(): IncrementalQuery
    {
        return $this->from(Order::class)
            ->select('id', 'customer_id', 'amount', 'status')
            ->incremental(since: 'id');
    }
}
