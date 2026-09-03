<?php

namespace App\Analytics\Accounting;

use App\Models\Account;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\EphemeralQuery;
use Illuminate\Database\Eloquent\Model;

class StgAccount extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): EphemeralQuery
    {
        return $this->from(Account::class)
            ->select('id as account_id', 'code as account_code', 'name as account_name', 'type as account_type')
            ->ephemeral();
    }
}
