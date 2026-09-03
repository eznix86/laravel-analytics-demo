<?php

namespace App\Analytics\Retail;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\cast;
use function Eznix86\LaravelAnalytics\raw;

class StoreDayConversion extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['created_at_day', 'store_id']];
    }

    public function expectations(): array
    {
        return [
            Expectation::expression('conversion > 0 and conversion <= 1'),
        ];
    }

    public function computes(): Query
    {
        return $this->from(TransByStoreDay::class, 'd')
            ->join(StoreDayVisits::class, 'v', 'v.store_id', 'd.store_id')
            ->on('v.created_at_day', 'd.created_at_day')
            ->select(
                'd.created_at_day',
                'd.store_id',
                'd.total_transactions',
                'v.total_visits',
                raw('%s / nullif(v.total_visits, 0)', cast('d.total_transactions', 'decimal(18,4)'))->as('conversion'),
            );
    }
}
