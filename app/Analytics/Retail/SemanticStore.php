<?php

namespace App\Analytics\Retail;

use App\Models\Store;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Materialization;
use Illuminate\Database\Eloquent\Model;

class SemanticStore extends Model implements AnalyticsModel
{
    use Analytics;

    public function materialization(): Materialization
    {
        return Materialization::Ephemeral;
    }

    public function computes(): string
    {
        return 'select s.store_id, s.sqft, s.country, s.region, s.is_active, s.ds, '
            ."case when s.country in ('MU', 'ZA') then 'AFRICA' "
            ."when s.country in ('GB', 'FR') then 'EUROPE' else 'OTHER' end as country_group, "
            ."case when s.sqft < 1000 then 'small' "
            ."when s.sqft < 5000 then 'medium' else 'large' end as floor_size "
            .'from '.$this->ref(Store::class).' s';
    }
}
