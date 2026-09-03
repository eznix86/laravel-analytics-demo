<?php

namespace App\Analytics;

use App\Models\Customer;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\raw;

class CustomerRevenue extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['customer_id', 'month'], ['country']];
    }

    public function freshness(): ?string
    {
        return '25 hours';
    }

    public function computes(): Query
    {
        return $this->from(MonthlyOrders::class, 'm')
            ->join(Customer::class, 'c', 'c.id', 'm.customer_id')
            ->select(
                'm.customer_id',
                'c.name',
                'c.country',
                'm.month',
                'm.revenue',
                raw('lag(m.revenue) over (partition by m.customer_id order by m.month)')->as('previous_revenue'),
            );
    }
}
