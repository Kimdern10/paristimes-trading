<?php

use App\Models\Currency;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The home page hero: headline, the big flip-board rate and the rates board.
 * It checks for new rates every minute while it is on screen, so visitors
 * see the admin's changes without refreshing the page.
 */
new class extends Component
{
    /**
     * The currencies shown on the website.
     *
     * @return Collection<int, Currency>
     */
    #[Computed]
    public function currencies(): Collection
    {
        return Currency::query()->shown()->get();
    }

    /**
     * When the owner last saved the rates.
     */
    #[Computed]
    public function updatedAt(): ?CarbonImmutable
    {
        return Setting::ratesUpdatedAt();
    }

    /**
     * Rates for the flip-board and the calculator, as plain data for JavaScript.
     *
     * @return list<array{code: string, name: string, symbol: string, buy: string|null, sell: string|null, buyText: string, sellText: string}>
     */
    #[Computed]
    public function rateData(): array
    {
        return $this->currencies->map(fn (Currency $currency): array => [
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'buy' => $currency->buy_rate,
            'sell' => $currency->sell_rate,
            'buyText' => Currency::naira($currency->buy_rate),
            'sellText' => Currency::naira($currency->sell_rate),
        ])->values()->all();
    }
}; ?>

@php
    $business = Setting::get('business_name');
    $highlight = 'Foreign Currency Exchange';
    $heroTitle = str_replace(e($highlight), '<span class="gold">'.e($highlight).'</span>', e(Setting::get('hero_title')));
    $headline = $this->currencies->first(fn (Currency $currency): bool => $currency->sell_rate !== null);
@endphp

<section class="hero" wire:poll.60s.visible>
    <x-currency-fx />

    {{-- Rates for the calculator and flip-board; Livewire updates this when rates change. --}}
    <script type="application/json" id="live-rates-data">@json($this->rateData)</script>

    <div class="container hero-grid">
        <div>
            <span class="eyebrow"><x-site-icon name="map-pin" /> {{ Setting::get('address_area') }}</span>
            <h1 wire:ignore>{!! $heroTitle !!}</h1>

            @if ($headline)
                <div class="hero-rate" wire:ignore data-hero-rate>
                    <div class="hero-rate-tabs" role="tablist" aria-label="Choose a currency">
                        @foreach ($this->currencies->filter(fn (Currency $currency): bool => $currency->sell_rate !== null) as $currency)
                            <button type="button" role="tab" data-code="{{ $currency->code }}" aria-selected="{{ $currency->is($headline) ? 'true' : 'false' }}">{{ $currency->code }}</button>
                        @endforeach
                    </div>
                    <p class="hero-rate-label">
                        We sell <span data-hero-symbol>{{ $headline->symbol }}</span>1 <span data-hero-code>{{ $headline->code }}</span> at
                    </p>
                    <x-flip class="flip-xl" :value="Currency::naira($headline->sell_rate)" data-hero-flip />
                    <p class="hero-rate-sub">We buy at <strong data-hero-buy>{{ Currency::naira($headline->buy_rate) }}</strong></p>
                </div>
            @else
                <p class="hero-text">{{ Setting::get('hero_text') }}</p>
            @endif

            <div class="hero-actions">
                <a class="btn btn-whatsapp" href="{{ Setting::whatsappUrl('Hello '.$business.', I would like to exchange currency. What are your rates today?') }}" target="_blank" rel="noopener">
                    <x-site-icon name="whatsapp" />
                    Chat with us now on WhatsApp
                </a>
                <a class="btn btn-outline" href="{{ Setting::phoneUrl() }}">
                    <x-site-icon name="phone" />
                    {{ Setting::get('phone') }}
                </a>
            </div>
        </div>

        @if ($this->currencies->isNotEmpty())
            <x-rates-board :currencies="$this->currencies" :updated-at="$this->updatedAt" />
        @endif
    </div>
</section>
