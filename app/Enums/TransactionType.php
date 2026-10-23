<?php

namespace App\Enums;

/**
 * Which way a deal went, from the business's side.
 */
enum TransactionType: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    /**
     * Label used in the admin, e.g. "We bought".
     */
    public function label(): string
    {
        return match ($this) {
            self::Buy => 'We bought',
            self::Sell => 'We sold',
        };
    }

    /**
     * Short label used on the website, e.g. "Bought".
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Buy => 'Bought',
            self::Sell => 'Sold',
        };
    }

    /**
     * Read the type from a spreadsheet cell such as "buy", "Bought" or "SELL".
     */
    public static function fromText(string $text): ?self
    {
        return match (strtolower(trim($text))) {
            'buy', 'bought', 'b' => self::Buy,
            'sell', 'sold', 's' => self::Sell,
            default => null,
        };
    }
}
