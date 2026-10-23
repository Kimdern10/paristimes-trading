<?php

namespace App\Models;

use Database\Factories\RateSnapshotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One currency's rates on one day, kept for the rate history chart.
 * The last save of the day wins.
 *
 * @property int $id
 * @property string $currency_code
 * @property Carbon $recorded_on
 * @property string|null $buy_rate
 * @property string|null $sell_rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['currency_code', 'recorded_on', 'buy_rate', 'sell_rate'])]
class RateSnapshot extends Model
{
    /** @use HasFactory<RateSnapshotFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'buy_rate' => 'decimal:2',
            'sell_rate' => 'decimal:2',
        ];
    }

    /**
     * Record today's rates for every currency that has them.
     */
    public static function recordToday(): void
    {
        $today = now()->toDateString();

        Currency::query()->get()->filter->hasRates()->each(function (Currency $currency) use ($today): void {
            $snapshot = static::query()
                ->where('currency_code', $currency->code)
                ->whereDate('recorded_on', $today)
                ->first() ?? new self(['currency_code' => $currency->code, 'recorded_on' => $today]);

            $snapshot->fill(['buy_rate' => $currency->buy_rate, 'sell_rate' => $currency->sell_rate])->save();
        });
    }
}
