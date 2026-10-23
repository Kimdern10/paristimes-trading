<?php

namespace App\Support;

use App\Models\Currency;
use App\Models\Setting;

/**
 * Today's rates written as a WhatsApp message or Status the owner can paste.
 */
class RatesMessage
{
    /**
     * Build the message from the shown currencies and business details.
     */
    public static function text(): string
    {
        $lines = [
            '*'.Setting::get('business_name').'*',
            "Today's exchange rates (".now()->format('D j M Y').')',
            '',
        ];

        foreach (Currency::query()->shown()->get()->filter->hasRates() as $currency) {
            $lines[] = "{$currency->code}: Buy ".Currency::naira($currency->buy_rate).' | Sell '.Currency::naira($currency->sell_rate);
        }

        array_push(
            $lines,
            '',
            'Rates can change during the day. Message us to confirm before you trade.',
            '📍 '.Setting::address(),
            '📞 '.Setting::get('phone'),
            '💬 '.Setting::whatsappUrl(),
            '🌐 '.route('home'),
        );

        return implode("\n", $lines);
    }
}
