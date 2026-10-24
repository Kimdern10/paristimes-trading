@php
    use App\Enums\TransactionType;
    use App\Models\Currency;
    use App\Models\Setting;

    $business = Setting::get('business_name');
@endphp

<x-layouts::site>
    <livewire:live-rates />

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
                    <p>Pick a currency and type an amount. It always uses our latest rate. Then send the details to us on WhatsApp to lock it in.</p>
                </div>

                <form class="calc reveal" data-calc data-business="{{ $business }}" data-whatsapp="{{ Setting::whatsappUrl() }}">
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

    <div class="marquee-band" aria-hidden="true">
        <div class="marquee-track">
            @foreach ([1, 2] as $copy)
                <span>Dollars <i>✦</i> Pounds <i>✦</i> Euros <i>✦</i> Naira <i>✦</i> Fast <i>✦</i> Secure <i>✦</i> Idumota <i>✦</i>&nbsp;</span>
            @endforeach
        </div>
    </div>

    @if ($dealStats['count'] > 0)
        <section class="section deals-section">
            <div class="container">
                <div class="section-head center reveal">
                    <span class="kicker">Recent deals</span>
                    <h2>Real exchanges, done fast</h2>
                    <p>A few of the deals we completed recently. Customer details are always kept private.</p>
                </div>

                <div class="stats reveal">
                    <div class="stat">
                        <span class="stat-value" data-count="{{ $dealStats['count'] }}">{{ number_format($dealStats['count']) }}</span>
                        <span class="stat-label">deals completed</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" data-count="{{ round($dealStats['volume']) }}" data-prefix="₦">₦{{ number_format($dealStats['volume']) }}</span>
                        <span class="stat-label">exchanged in naira</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value" data-count="{{ $currencies->count() }}">{{ $currencies->count() }}</span>
                        <span class="stat-label">currencies traded</span>
                    </div>
                </div>

                <ul class="deals reveal-stagger">
                    @foreach ($recentDeals as $deal)
                        <li class="deal reveal">
                            <span @class(['deal-type', 'is-buy' => $deal->type === TransactionType::Buy])>{{ $deal->type->shortLabel() }}</span>
                            <span class="deal-amount">{{ $deal->formattedAmount() }} <small>{{ $deal->currency_code }}</small></span>
                            <span class="deal-rate">at {{ Currency::naira($deal->rate) }}</span>
                            <time class="deal-date" datetime="{{ $deal->traded_on->toDateString() }}">{{ $deal->traded_on->isToday() ? 'Today' : $deal->traded_on->format('j M Y') }}</time>
                            @if ($deal->hasPublicReceipt())
                                <a class="deal-receipt" href="{{ route('deals.receipt', $deal) }}" @if ($deal->receiptIsPdf()) target="_blank" rel="noopener" @else data-lightbox data-lightbox-caption="Receipt: {{ $deal->type->shortLabel() }} {{ $deal->formattedAmount() }} {{ $deal->currency_code }}, {{ $deal->traded_on->format('j M Y') }}" @endif>
                                    <x-site-icon name="card" />
                                    View receipt
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($trends !== [])
        <x-rate-trend :trends="$trends" />
    @endif

    <section class="section">
        <div class="container">
            <div class="split">
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
                    <p class="prose-lead">We buy and sell US Dollars, British Pounds and Euros for individuals, traders and businesses, with competitive rates and fast, secure transactions.</p>
                    <ul class="check-list">
                        <li><x-site-icon name="check" /> Trusted local business in Lagos</li>
                        <li><x-site-icon name="check" /> Competitive exchange rates</li>
                        <li><x-site-icon name="check" /> Secure deals, paid at our shop or online with Paystack or Flutterwave</li>
                        <li><x-site-icon name="check" /> Easy WhatsApp communication</li>
                    </ul>
                </div>
            </div>

            <div class="section-head how-head reveal">
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

    <x-reviews-section :reviews="$reviews" />

    <x-faq-list :faqs="$faqs" alt />

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
                    <a class="btn btn-whatsapp" href="{{ Setting::whatsappUrl('Hello '.$business.', I would like to exchange currency. What are your rates today?') }}" target="_blank" rel="noopener">
                        <x-site-icon name="whatsapp" />
                        Chat with us on WhatsApp
                    </a>
                    <a class="btn btn-gold" href="{{ Setting::mapsUrl() }}" target="_blank" rel="noopener">
                        <x-site-icon name="map-pin" />
                        Get directions
                    </a>
                    <a class="btn btn-outline" href="{{ Setting::phoneUrl() }}">
                        <x-site-icon name="phone" />
                        Call {{ Setting::get('phone') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts::site>
