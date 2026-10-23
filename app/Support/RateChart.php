<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\RateSnapshot;
use Illuminate\Support\Collection;

/**
 * Builds a small SVG line chart of a currency's buy and sell rates over the
 * last few weeks. Drawn on the server, so no chart library is needed.
 */
class RateChart
{
    public const int WIDTH = 640;

    public const int HEIGHT = 220;

    public const int DAYS = 30;

    /**
     * Get a chart for each shown currency that has at least two days of history.
     *
     * @param  Collection<int, Currency>  $currencies
     * @return list<array{currency: Currency, chart: array<string, mixed>}>
     */
    public static function forCurrencies(Collection $currencies): array
    {
        $history = RateSnapshot::query()
            ->whereIn('currency_code', $currencies->pluck('code'))
            ->where('recorded_on', '>=', now()->subDays(self::DAYS)->toDateString())
            ->orderBy('recorded_on')
            ->get()
            ->groupBy('currency_code');

        $charts = [];

        foreach ($currencies as $currency) {
            $chart = self::build($history->get($currency->code, collect()));

            if ($chart !== null) {
                $charts[] = ['currency' => $currency, 'chart' => $chart];
            }
        }

        return $charts;
    }

    /**
     * Turn a currency's daily snapshots into SVG points and labels.
     *
     * @param  Collection<int, RateSnapshot>  $snapshots
     * @return array{buy: string, sell: string, area: string, min: string, max: string, from: string, to: string, change: float|null, latest: string|null}|null
     */
    public static function build(Collection $snapshots): ?array
    {
        $snapshots = $snapshots->values();

        if ($snapshots->count() < 2) {
            return null;
        }

        $values = $snapshots->flatMap(fn (RateSnapshot $snapshot): array => [$snapshot->buy_rate, $snapshot->sell_rate])
            ->filter(fn (?string $value): bool => $value !== null)
            ->map(fn (string $value): float => (float) $value);

        $low = (float) $values->min();
        $high = (float) $values->max();
        $padding = max(($high - $low) * 0.15, 1.0);
        $low -= $padding;
        $high += $padding;

        $step = self::WIDTH / ($snapshots->count() - 1);
        $y = fn (float $value): float => round(self::HEIGHT - (($value - $low) / ($high - $low)) * self::HEIGHT, 1);

        $line = function (string $column) use ($snapshots, $step, $y): string {
            return $snapshots
                ->map(fn (RateSnapshot $snapshot, int $index): ?string => $snapshot->{$column} === null
                    ? null
                    : round($index * $step, 1).','.$y((float) $snapshot->{$column}))
                ->filter()
                ->implode(' ');
        };

        $sell = $line('sell_rate');
        $firstSell = $snapshots->first()->sell_rate;
        $lastSell = $snapshots->last()->sell_rate;

        return [
            'buy' => $line('buy_rate'),
            'sell' => $sell,
            'area' => $sell === '' ? '' : 'M'.str_replace(' ', ' L', $sell).' L'.self::WIDTH.','.self::HEIGHT.' L0,'.self::HEIGHT.' Z',
            'min' => Currency::naira((string) $values->min()),
            'max' => Currency::naira((string) $values->max()),
            'from' => $snapshots->first()->recorded_on->format('j M'),
            'to' => $snapshots->last()->recorded_on->format('j M'),
            'change' => $firstSell !== null && $lastSell !== null && (float) $firstSell > 0
                ? round(((float) $lastSell - (float) $firstSell) / (float) $firstSell * 100, 1)
                : null,
            'latest' => $lastSell !== null ? Currency::naira($lastSell) : null,
        ];
    }
}
