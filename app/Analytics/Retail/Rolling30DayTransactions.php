<?php

namespace App\Analytics\Retail;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\raw;

class Rolling30DayTransactions extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['store_id', 'created_at_day']];
    }

    public function computes(): Query
    {
        return $this->from(TransByStoreDay::class, 'd')
            ->select(
                'd.created_at_day',
                'd.store_id',
                'd.total_transactions',
                raw('sum(d.total_transactions) over (partition by d.store_id '
                    .'order by d.created_at_day rows between 29 preceding and current row)')->as('transactions_30_day'),
            );
    }
}
