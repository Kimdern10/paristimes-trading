@props(['currencies', 'updatedAt' => null])

@php
    use App\Models\Currency;
    use App\Models\Setting;

    $isToday = $updatedAt?->isToday() ?? false;
@endphp

<div {{ $attributes->merge(['class' => 'rates-card']) }} data-tilt>
    <div class="rates-head">
        <h2>Today's rates</h2>
        @if ($updatedAt)
            <span @class(['live-dot', 'is-stale' => ! $isToday])>
                <time datetime="{{ $updatedAt->toIso8601String() }}">
                    {{ $isToday ? 'Updated today, '.$updatedAt->format('g:ia') : 'Updated '.$updatedAt->format('j M, g:ia') }}
                </time>
            </span>
        @endif
    </div>

    <table class="rates-table">
        <thead>
            <tr>
                <th scope="col">Currency</th>
                <th scope="col">We buy</th>
                <th scope="col">We sell</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($currencies as $currency)
                <tr>
                    <td>
                        <span class="cur">
                            <span class="cur-badge">{{ $currency->symbol ?: mb_substr($currency->code, 0, 1) }}</span>
                            <span>
                                <span class="cur-code">{{ $currency->code }}</span>
                                <span class="cur-name">{{ $currency->name }}</span>
                            </span>
                        </span>
                    </td>
                    @if ($currency->hasRates())
                        <td><span @if ($currency->buy_rate !== null) data-count="{{ $currency->buy_rate }}" data-prefix="₦" @endif>{{ Currency::naira($currency->buy_rate) }}</span></td>
                        <td><span @if ($currency->sell_rate !== null) data-count="{{ $currency->sell_rate }}" data-prefix="₦" @endif>{{ Currency::naira($currency->sell_rate) }}</span></td>
                    @else
                        <td colspan="2"><span class="rate-ask">Ask on WhatsApp</span></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="rates-note">{{ Setting::get('rates_note') }}</p>

    <a class="btn btn-gold" href="{{ Setting::whatsappUrl('Hello '.Setting::get('business_name').', please confirm your exchange rates for today.') }}" target="_blank" rel="noopener">
        <x-site-icon name="whatsapp" />
        Confirm today's rate
    </a>
</div>
