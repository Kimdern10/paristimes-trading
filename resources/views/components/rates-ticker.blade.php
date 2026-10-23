@php
    use App\Models\Currency;

    $tickerCurrencies = Currency::query()->shown()->get()->filter->hasRates();
@endphp

@if ($tickerCurrencies->isNotEmpty())
    {{-- The list is printed twice so the scrolling loop has no gap; the copy is hidden from screen readers. --}}
    <div class="ticker" aria-label="Today's rates">
        <div class="ticker-track">
            @foreach ([false, true] as $isCopy)
                <ul class="ticker-list" @if ($isCopy) aria-hidden="true" @endif>
                    @foreach ($tickerCurrencies as $currency)
                        <li>
                            <span class="ticker-code">{{ $currency->symbol }} {{ $currency->code }}</span>
                            <span>Buy <strong>{{ Currency::naira($currency->buy_rate) }}</strong></span>
                            <span>Sell <strong>{{ Currency::naira($currency->sell_rate) }}</strong></span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
@endif
