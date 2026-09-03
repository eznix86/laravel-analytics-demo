<?php

namespace App\Analytics\Imported;

use App\Models\Order;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\ImportQuery;
use Illuminate\Database\Eloquent\Model;

class ImportedOrders extends Model implements AnalyticsModel
{
    use Analytics;

    protected $connection = 'warehouse';

    protected $table = 'imported_orders';

    public function computes(): ImportQuery
    {
        return $this->from(Order::class)
            ->select('id', 'customer_id', 'amount')
            ->import(replacing: ['id'], appendOnly: 'id');
    }
}
