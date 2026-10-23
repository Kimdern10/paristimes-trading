@php
    use App\Models\Setting;
@endphp

<x-layouts::site title="Services" description="We buy and sell US Dollars, British Pounds and Euros in Idumota, Lagos. Fast transactions, secure deals and online payment with Paystack or Flutterwave.">
    <section class="page-hero">
        <div class="container">
            <span class="eyebrow"><x-site-icon name="exchange" /> Services</span>
            <h1>Currency exchange made simple</h1>
            <p>Competitive rates, fast transactions and secure deals for individuals, traders and businesses.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="cards reveal">
                <div class="card">
                    <span class="card-icon"><x-site-icon name="exchange" /></span>
                    <h3>Foreign currency exchange</h3>
                    <p>We buy and sell major currencies including:</p>
                    <ul>
                        @forelse ($currencies as $currency)
                            <li>{{ $currency->name }} ({{ $currency->code }})</li>
                        @empty
                            <li>US Dollars (USD)</li>
                            <li>British Pounds (GBP)</li>
                            <li>Euros (EUR)</li>
                        @endforelse
                    </ul>
                </div>

                <div class="card">
                    <span class="card-icon"><x-site-icon name="bolt" /></span>
                    <h3>Fast transactions</h3>
                    <ul>
                        <li>Instant processing</li>
                        <li>No unnecessary delays</li>
                    </ul>
                </div>

                <div class="card">
                    <span class="card-icon"><x-site-icon name="shield" /></span>
                    <h3>Secure deals</h3>
                    <ul>
                        <li>Safe and verified transactions</li>
                        <li>Trusted business environment</li>
                    </ul>
                </div>

                <div class="card">
                    <span class="card-icon"><x-site-icon name="card" /></span>
                    <h3>Online payment available</h3>
                    <p>We accept secure payments through:</p>
                    <div class="pay-badges">
                        <span class="pay-badge">Paystack</span>
                        <span class="pay-badge">Flutterwave</span>
                    </div>
                    <p class="small-print">Chat with us on WhatsApp first to agree the rate and get payment details.</p>
                </div>
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
            <div class="hero-actions actions-center">
                <a class="btn btn-ghost" href="{{ route('home') }}#calculator">
                    Check today's rates
                    <x-site-icon name="arrow-right" />
                </a>
            </div>
        </div>
    </section>

    <x-cta-band />
</x-layouts::site>
