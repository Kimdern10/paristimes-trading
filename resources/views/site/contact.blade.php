@php
    use App\Models\Setting;

    $business = Setting::get('business_name');
    $whatsappUrl = Setting::whatsappUrl('Hello '.$business.', I would like to exchange currency. What are your rates today?');
@endphp

<x-layouts::site title="Contact us" description="Visit {{ $business }} at {{ Setting::address() }}, call {{ Setting::get('phone') }} or chat with us on WhatsApp.">
    <section class="page-hero">
        <x-currency-fx variant="drift" />
        <div class="container">
            <span class="eyebrow"><x-site-icon name="phone" /> Contact</span>
            <h1>Talk to us today</h1>
            <p>The fastest way to reach us is WhatsApp. Tap the button and we will reply with today's rate.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="contact-grid reveal">
                <div class="contact-card">
                    <span class="card-icon"><x-site-icon name="whatsapp" /></span>
                    <h2>WhatsApp</h2>
                    <p class="big">{{ Setting::get('whatsapp') }}</p>
                    <p>Click to chat instantly.</p>
                    <a class="btn btn-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                        <x-site-icon name="whatsapp" />
                        Chat on WhatsApp
                    </a>
                </div>

                <div class="contact-card">
                    <span class="card-icon"><x-site-icon name="phone" /></span>
                    <h2>Phone</h2>
                    <p class="big">{{ Setting::get('phone') }}</p>
                    <p>Call us during business hours.</p>
                    <a class="btn btn-ghost" href="{{ Setting::phoneUrl() }}">
                        <x-site-icon name="phone" />
                        Call now
                    </a>
                </div>

                <div class="contact-card">
                    <span class="card-icon"><x-site-icon name="map-pin" /></span>
                    <h2>Address</h2>
                    <p class="big">{{ Setting::get('address_street') }}</p>
                    <p>{{ Setting::get('address_area') }}</p>
                    <a class="btn btn-ghost" href="{{ Setting::mapsUrl() }}" target="_blank" rel="noopener">
                        <x-site-icon name="map-pin" />
                        Get directions
                    </a>
                </div>
            </div>

            @if (filled(Setting::get('opening_hours')) || filled(Setting::get('email')))
                <div class="cards reveal contact-extra">
                    @if (filled(Setting::get('opening_hours')))
                        <div class="card">
                            <span class="card-icon"><x-site-icon name="clock" /></span>
                            <h3>Opening hours</h3>
                            <p>{{ Setting::get('opening_hours') }}</p>
                        </div>
                    @endif
                    @if (filled(Setting::get('email')))
                        <div class="card">
                            <span class="card-icon"><x-site-icon name="mail" /></span>
                            <h3>Email</h3>
                            <p><a href="mailto:{{ Setting::get('email') }}">{{ Setting::get('email') }}</a></p>
                        </div>
                    @endif
                </div>
            @endif

            <div class="map reveal" data-map="{{ Setting::mapEmbedUrl() }}" data-map-title="Map showing {{ Setting::address() }}">
                <x-currency-fx variant="drift" class="fx-soft" />
                <div class="map-inner">
                    <span class="card-icon"><x-site-icon name="map-pin" /></span>
                    <h2>{{ Setting::get('address_street') }}, {{ Setting::get('address_area') }}</h2>
                    <p>The map loads from Google when you tap the button.</p>
                    <button type="button" class="btn btn-gold">
                        <x-site-icon name="map-pin" />
                        Show map
                    </button>
                </div>
            </div>
        </div>
    </section>

    <x-faq-list :faqs="$faqs" alt />

    <x-cta-band title="Get today's rate in minutes" />
</x-layouts::site>
