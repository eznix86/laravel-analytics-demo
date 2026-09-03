<?php

namespace Tests\Fixtures\CrossConnection;

use App\Models\Order;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Illuminate\Database\Eloquent\Model;

class Reaches extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    public function computes(): string
    {
        return 'select customer_id, amount from '.$this->ref(Order::class);
    }
}
