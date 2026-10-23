<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A foreign currency the business buys and sells, with today's naira rates.
 *
 * "Buy" is what the business pays for one unit; "sell" is what it charges.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string|null $buy_rate
 * @property string|null $sell_rate
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'name', 'symbol', 'buy_rate', 'sell_rate', 'is_active', 'sort_order'])]
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'buy_rate' => 'decimal:2',
            'sell_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Only the currencies shown on the website, in the owner's order.
     *
     * @param  Builder<Currency>  $query
     */
    #[Scope]
    protected function shown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('code');
    }

    /**
     * Whether the owner has entered at least one rate for this currency.
     */
    public function hasRates(): bool
    {
        return $this->buy_rate !== null || $this->sell_rate !== null;
    }

    /**
     * Format a naira rate for display, e.g. "₦1,580" or "₦1,580.50".
     */
    public static function naira(?string $rate): string
    {
        if ($rate === null) {
            return '—';
        }

        $decimals = (float) $rate === floor((float) $rate) ? 0 : 2;

        return '₦'.number_format((float) $rate, $decimals);
    }
}
