<?php

namespace App\Analytics\Retail;

use Eznix86\LaravelAnalytics\Expressions\Expression;

use function Eznix86\LaravelAnalytics\cast;
use function Eznix86\LaravelAnalytics\raw;

/**
 * Every metric in the retail semantic layer is defined exactly once, here.
 * Rollup models choose the dimensions; none of them redefine the arithmetic.
 */
final class Metrics
{
    public static function totalTransactions(string $alias = 't'): string
    {
        return "count(distinct {$alias}.transaction_id)";
    }

    public static function totalCustomers(string $alias = 't'): string
    {
        return "count(distinct {$alias}.customer_id)";
    }

    public static function revenue(string $alias = 't'): string
    {
        return "sum({$alias}.quantity * {$alias}.price)";
    }

    public static function transPerCust(string $alias = 't'): Expression
    {
        return raw(
            '%s / nullif(%s, 0)',
            cast(self::totalTransactions($alias), 'decimal(18,4)'),
            self::totalCustomers($alias),
        );
    }

    public static function totalStores(string $alias = 's'): string
    {
        return "count(distinct {$alias}.store_id)";
    }
}
