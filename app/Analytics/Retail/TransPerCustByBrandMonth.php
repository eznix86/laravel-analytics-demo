<?php

namespace App\Analytics\Retail;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class TransPerCustByBrandMonth extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['created_at_month', 'brand']];
    }

    public function computes(): Query
    {
        return $this->from(SemanticTransaction::class, 't')
            ->per(date_trunc('month', 't.created_at')->as('created_at_month'), 't.brand')
            ->measure('trans_per_cust', Metrics::transPerCust());
    }
}
