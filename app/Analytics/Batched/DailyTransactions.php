<?php

namespace App\Analytics\Batched;

use App\Models\Transaction;
use Eznix86\LaravelAnalytics\BatchSize;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\MicrobatchQuery;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\date_trunc;

class DailyTransactions extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['created_at']];
    }

    public function expectations(): array
    {
        return [Expectation::expression('total > 0')];
    }

    public function computes(): MicrobatchQuery
    {
        return $this->from(Transaction::class, 't')
            ->grain(date_trunc('day', 't.created_at'))
            ->measure('created_at', 'min(t.created_at)')
            ->measure('total', 'count(*)')
            ->measure('revenue', 'sum(t.quantity * t.price)')
            ->microbatch('created_at', BatchSize::Month, begin: '2026-01-01');
    }
}
