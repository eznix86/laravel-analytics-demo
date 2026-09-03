<?php

namespace App\Analytics\Accounting;

use App\Models\Account;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Eznix86\LaravelAnalytics\Testing\Expectation;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\raw;

class RunningTotalByAccount extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['account_id', 'txn_date'], ['account_code']];
    }

    public function freshness(): ?string
    {
        return '25 hours';
    }

    public function expectations(): array
    {
        return [
            Expectation::unique('id'),
            Expectation::notNull('account_id', 'txn_date', 'adjusted_amount'),
            Expectation::acceptedValues('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']),
            Expectation::relationship('account_id', Account::class),
        ];
    }

    public function computes(): Query
    {
        return $this->from(AdjustedJournalEntries::class, 'j')
            ->join(StgAccount::class, 'a', 'a.account_id', 'j.account_id')
            ->select(
                'j.id',
                'j.txn_date',
                'j.account_id',
                'a.account_code',
                'a.account_name',
                'a.account_type',
                'j.adjusted_amount',
                'j.description',
                raw('sum(j.adjusted_amount) over (partition by j.account_id '
                    .'order by j.txn_date, j.id rows unbounded preceding)')->as('running_balance'),
            );
    }
}
