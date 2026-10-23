@php
    use App\Models\Setting;

    $business = Setting::get('business_name');
    $highlight = 'Foreign Currency Exchange';
    $heroTitle = str_replace(e($highlight), '<span class="gold">'.e($highlight).'</span>', e(Setting::get('hero_title')));

    $calculatorRates = $currencies->map(fn ($currency) => [
        'code' => $currency->code,
        'symbol' => $currency->symbol,
        'buy' => $currency->buy_rate,
        'sell' => $currency->sell_rate,
    ])->values();
@endphp

<x-layouts::site>
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <span class="eyebrow"><x-site-icon name="map-pin" /> {{ Setting::get('address_area') }}</span>
                <h1>{!! $heroTitle !!}</h1>
                <p class="hero-text">{{ Setting::get('hero_text') }}</p>

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

                <ul class="hero-points">
                    <li><x-site-icon name="check" /> Fast transactions</li>
                    <li><x-site-icon name="check" /> Competitive rates</li>
                    <li><x-site-icon name="check" /> Secure deals</li>
                </ul>
            </div>

            @if ($currencies->isNotEmpty())
                <x-rates-board :currencies="$currencies" :updated-at="$ratesUpdatedAt" />
            @endif
        </div>
    </section>

    <div class="trust-strip">
        <div class="container">
            <div class="trust-grid">
                <div class="trust-item">
                    <x-site-icon name="store" />
                    <div><strong>Trusted local business</strong><span>Our shop is in Idumota, Lagos Island</span></div>
                </div>
                <div class="trust-item">
                    <x-site-icon name="bolt" />
                    <div><strong>Fast transactions</strong><span>Instant processing, no delays</span></div>
                </div>
                <div class="trust-item">
                    <x-site-icon name="chart" />
                    <div><strong>Competitive rates</strong><span>Fair buy and sell prices daily</span></div>
                </div>
                <div class="trust-item">
                    <x-site-icon name="shield" />
                    <div><strong>Secure and reliable</strong><span>Safe, verified transactions</span></div>
                </div>
            </div>
        </div>
    </div>

    @if ($currencies->isNotEmpty())
        <section class="section" id="calculator">
            <div class="container">
                <div class="section-head reveal">
                    <span class="kicker">Rate calculator</span>
                    <h2>See how much you will get</h2>
                    <p>Pick a currency and type an amount. Then send the details to us on WhatsApp to lock in the rate.</p>
                </div>

                <form class="calc reveal" data-calc data-rates='@json($calculatorRates)' data-business="{{ $business }}" data-whatsapp="{{ Setting::whatsappUrl() }}">
                    <div class="calc-fields">
                        <div class="field">
                            <fieldset>
                                <legend>I want to</legend>
                                <div class="toggle">
                                    <input type="radio" name="mode" id="mode-sell" value="sell" checked>
                                    <label for="mode-sell">Sell foreign currency</label>
                                    <input type="radio" name="mode" id="mode-buy" value="buy">
                                    <label for="mode-buy">Buy foreign currency</label>
                                </div>
                            </fieldset>
                        </div>

                        <div class="field">
                            <label for="calc-currency">Currency</label>
                            <select id="calc-currency" name="currency">
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->code }}">{{ $currency->code }} ({{ $currency->name }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label for="calc-amount">Amount</label>
                            <input id="calc-amount" name="amount" type="text" inputmode="decimal" value="100" autocomplete="off">
                        </div>
                    </div>

                    <div class="calc-result" aria-live="polite">
                        <span class="calc-label" data-calc-label>You get about</span>
                        <span class="calc-amount" data-calc-amount>&mdash;</span>
                        <span class="calc-rate" data-calc-rate></span>
                        <a class="btn btn-whatsapp" href="{{ Setting::whatsappUrl() }}" target="_blank" rel="noopener" data-calc-whatsapp>
                            <x-site-icon name="whatsapp" />
                            Send to us on WhatsApp
                        </a>
                        <span class="calc-rate">This is an estimate. We confirm the final rate before every deal.</span>
                    </div>
                </form>
            </div>
        </section>
    @endif

    <section class="section section-alt">
        <div class="container split">
            <div class="reveal">
                <span class="kicker">What we do</span>
                <h2>Professional foreign currency exchange</h2>
                <p class="prose-lead">At {{ $business }}, we provide professional foreign currency exchange services with competitive rates and fast transactions.</p>
                <p class="prose-lead">We help individuals and businesses exchange currencies safely and efficiently.</p>
                <div class="hero-actions">
                    <a class="btn btn-ghost" href="{{ route('services') }}">
                        Our services
                        <x-site-icon name="arrow-right" />
                    </a>
                </div>
            </div>

            <div class="cards reveal">
                <div class="card">
                    <span class="card-icon"><x-site-icon name="exchange" /></span>
                    <h3>Buy and sell</h3>
                    <p>US Dollars, British Pounds and Euros.</p>
                </div>
                <div class="card">
                    <span class="card-icon"><x-site-icon name="bolt" /></span>
                    <h3>Fast service</h3>
                    <p>Instant processing with no unnecessary delays.</p>
                </div>
                <div class="card">
                    <span class="card-icon"><x-site-icon name="shield" /></span>
                    <h3>Secure deals</h3>
                    <p>Safe and verified transactions in a trusted place.</p>
                </div>
                <div class="card">
                    <span class="card-icon"><x-site-icon name="card" /></span>
                    <h3>Online payment</h3>
                    <p>Pay securely with Paystack or Flutterwave.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container split">
            <div class="reveal">
                @if ($photos->isNotEmpty())
                    <div class="photo-panel">
                        <img src="{{ $photos->first()->url() }}" srcset="{{ $photos->first()->smallUrl() }} 800w, {{ $photos->first()->url() }} 1600w" sizes="(min-width: 960px) 560px, 100vw" alt="{{ $photos->first()->caption ?: 'Our shop in Idumota' }}" loading="lazy">
                    </div>
                @else
                    <div class="brand-panel">
                        <x-logo-mark />
                        <div class="brand-values"><span>Trust</span><span>Speed</span><span>Transparency</span></div>
                    </div>
                @endif
            </div>

            <div class="reveal">
                <span class="kicker">Why choose us</span>
                <h2>Exchange with people you can trust</h2>
                <ul class="check-list">
                    <li><x-site-icon name="check" /> Trusted local business in Lagos</li>
                    <li><x-site-icon name="check" /> Fast transactions</li>
                    <li><x-site-icon name="check" /> Competitive exchange rates</li>
                    <li><x-site-icon name="check" /> Secure and reliable service</li>
                    <li><x-site-icon name="check" /> Easy WhatsApp communication</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="section-head center reveal">
                <span class="kicker">How it works</span>
                <h2>Exchange in three simple steps</h2>
            </div>
            <div class="steps reveal">
                <div class="step">
                    <h3>Message us on WhatsApp</h3>
                    <p>Tell us the currency and amount you want to buy or sell.</p>
                </div>
                <div class="step">
                    <h3>Agree on the rate</h3>
                    <p>We confirm today's rate and the total in naira. No hidden charges.</p>
                </div>
                <div class="step">
                    <h3>Complete the exchange</h3>
                    <p>Visit our shop in Idumota or pay online with Paystack or Flutterwave.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="visit reveal">
                <div>
                    <span class="kicker">Visit us</span>
                    <h2>Find us in Idumota</h2>
                    <ul class="visit-list">
                        <li><x-site-icon name="map-pin" /> <span>{{ Setting::get('address_street') }}<br>{{ Setting::get('address_area') }}</span></li>
                        <li><x-site-icon name="phone" /> <span>{{ Setting::get('phone') }}</span></li>
                        @if (filled(Setting::get('opening_hours')))
                            <li><x-site-icon name="clock" /> <span>{{ Setting::get('opening_hours') }}</span></li>
                        @endif
                    </ul>
                </div>
                <div class="visit-actions">
                    <a class="btn btn-gold" href="{{ Setting::mapsUrl() }}" target="_blank" rel="noopener">
                        <x-site-icon name="map-pin" />
                        Get directions
                    </a>
                    <a class="btn btn-outline" href="{{ Setting::phoneUrl() }}">
                        <x-site-icon name="phone" />
                        Call us
                    </a>
                </div>
            </div>
        </div>
    </section>

    <x-cta-band />
</x-layouts::site>
