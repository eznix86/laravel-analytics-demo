<?php

namespace App\Analytics\Retail;

use App\Models\Product;
use App\Models\Transaction;
use Eznix86\LaravelAnalytics\Concerns\Analytics;
use Eznix86\LaravelAnalytics\Contracts\AnalyticsModel;
use Eznix86\LaravelAnalytics\Query;
use Illuminate\Database\Eloquent\Model;

/**
 * The join every rollup needs, resolved once at build time instead of per query.
 */
class SemanticTransaction extends Model implements AnalyticsModel
{
    use Analytics;

    public function indexes(): array
    {
        return [['store_id'], ['brand'], ['created_at']];
    }

    public function computes(): Query
    {
        return $this->from(Transaction::class, 't')
            ->join(Product::class, 'p', 'p.product_id', 't.product_id')
            ->join(SemanticStore::class, 's', 's.store_id', 't.store_id')
            ->whereRaw('s.is_active')
            ->select(
                't.transaction_id',
                't.customer_id',
                't.store_id',
                't.product_id',
                't.quantity',
                't.price',
                't.created_at',
                'p.category',
                'p.brand',
                's.country',
                's.region',
                's.country_group',
                's.floor_size',
                's.sqft',
            );
    }
}
