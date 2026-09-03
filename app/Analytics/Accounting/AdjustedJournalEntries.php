<?php

namespace App\Analytics\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\ViewQuery;
use Illuminate\Database\Eloquent\Model;

use function Eznix86\LaravelAnalytics\cast;
use function Eznix86\LaravelAnalytics\raw;

class AdjustedJournalEntries extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): ViewQuery
    {
        return $this->from(JournalLine::class, 'l')
            ->join(JournalEntry::class, 'e', 'e.id', 'l.journal_entry_id')
            ->whereRaw('e.posted')
            ->select(
                'l.id',
                'e.txn_date',
                'l.account_id',
                'l.debit',
                'l.credit',
                raw('%s - %s', cast('l.debit', 'bigint'), cast('l.credit', 'bigint'))->as('adjusted_amount'),
                'e.description',
            )
            ->view();
    }
}
