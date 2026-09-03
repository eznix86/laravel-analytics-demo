<?php

namespace App\Analytics\Accounting;

use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

class TrialBalance extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['account_code']];
    }

    public function expectations(): array
    {
        return [
            Expectation::unique('account_code'),
            Expectation::expression('total_debit >= 0 and total_credit >= 0'),
        ];
    }

    public function computes(): Query
    {
        return $this->from(AdjustedJournalEntries::class, 'j')
            ->join(StgAccount::class, 'a', 'a.account_id', 'j.account_id')
            ->per('a.account_code', 'a.account_name', 'a.account_type')
            ->measure('total_debit', 'sum(j.debit)')
            ->measure('total_credit', 'sum(j.credit)')
            ->measure('balance', 'sum(j.adjusted_amount)');
    }
}
