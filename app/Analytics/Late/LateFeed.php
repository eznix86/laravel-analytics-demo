<?php

namespace App\Analytics\Late;

use App\Analytics\Retail\SemanticTransaction;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Illuminate\Database\Eloquent\Model;

class LateFeed extends Model implements AnalyticsModel
{
    use Analytics;

    public function computes(): string
    {
        return 'select t.store_id, l.note from '.$this->ref(SemanticTransaction::class).' t, late_feed l';
    }
}