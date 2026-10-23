@props([
    'title' => 'Ready to exchange?',
    'text' => 'Send us a message on WhatsApp for today\'s rate, or call us. We reply fast.',
])

@php
    use App\Models\Setting;
@endphp

<section class="cta-band">
    <div class="container reveal">
        <h2>{{ $title }}</h2>
        <p>{{ $text }}</p>
        <div class="hero-actions">
            <a class="btn btn-whatsapp" href="{{ Setting::whatsappUrl('Hello '.Setting::get('business_name').', I would like to exchange currency. What are your rates today?') }}" target="_blank" rel="noopener">
                <x-site-icon name="whatsapp" />
                Chat with us now on WhatsApp
            </a>
            <a class="btn btn-outline" href="{{ Setting::phoneUrl() }}">
                <x-site-icon name="phone" />
                Call {{ Setting::get('phone') }}
            </a>
        </div>
    </div>
</section>
