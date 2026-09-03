<?php

namespace App\Analytics\Retail;

use App\Models\Store;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class TransByStoreDay extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['created_at_day', 'store_id']];
    }

    public function expectations(): array
    {
        return [
            Expectation::unique('created_at_day', 'store_id'),
            Expectation::expression('total_transactions > 0'),
            Expectation::relationship('store_id', Store::class, 'store_id'),
        ];
    }

    public function computes(): Query
    {
        return $this->from(SemanticTransaction::class, 't')
            ->per(date_trunc('day', 't.created_at')->as('created_at_day'), 't.store_id')
            ->measure('total_transactions', Metrics::totalTransactions())
            ->measure('revenue', Metrics::revenue());
    }
}
