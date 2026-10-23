@props([
    'title' => null,
    'description' => null,
])

@php
    use App\Models\Setting;

    $business = Setting::get('business_name');
    $pageTitle = filled($title) ? $title.' | '.$business : $business.' | Foreign Currency Exchange in Idumota, Lagos';
    $pageDescription = $description ?: Setting::get('hero_text');
    $whatsappUrl = Setting::whatsappUrl('Hello '.$business.', I would like to exchange currency. What are your rates today?');

    $links = [
        'home' => 'Home',
        'about' => 'About us',
        'services' => 'Services',
        'contact' => 'Contact',
    ];

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FinancialService',
        'name' => $business,
        'description' => Setting::get('hero_text'),
        'url' => route('home'),
        'telephone' => '+'.Setting::internationalNumber(Setting::get('phone')),
        'image' => asset('images/share.png'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => Setting::get('address_street'),
            'addressLocality' => Setting::get('address_area'),
            'addressRegion' => 'Lagos',
            'addressCountry' => 'NG',
        ],
        'areaServed' => 'Lagos',
    ];
@endphp

<!DOCTYPE html>
<html lang="en-NG">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $business }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/share.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">

        <meta name="theme-color" content="#0a1a3d">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.png" type="image/png" sizes="64x64">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/site.css', 'resources/js/site.js'])
        @livewireStyles

        <script type="application/ld+json">@json($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    </head>
    <body>
        <a class="skip-link" href="#main">Skip to content</a>
        <div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>

        <header class="site-header" data-header>
            <div class="container">
                <nav class="nav" aria-label="Main">
                    <a class="logo" href="{{ route('home') }}" wire:navigate.hover aria-label="{{ $business }}, home">
                        <x-logo-mark />
                        <span class="logo-text">
                            <span class="logo-name">PARISTIMES TRADING</span>
                            <span class="logo-sub">ENTERPRISES</span>
                        </span>
                    </a>

                    <ul class="nav-links">
                        @foreach ($links as $route => $label)
                            <li><a href="{{ route($route) }}" wire:navigate.hover @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>

                    <a class="btn btn-whatsapp btn-sm nav-cta" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                        <x-site-icon name="whatsapp" />
                        Chat on WhatsApp
                    </a>

                    <button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu">
                        <x-site-icon name="menu" class="icon-menu" />
                        <x-site-icon name="close" class="icon-close" />
                    </button>
                </nav>

                <div class="mobile-menu" id="mobile-menu">
                    <ul>
                        @foreach ($links as $route => $label)
                            <li><a href="{{ route($route) }}" wire:navigate.hover @if (request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                    <a class="btn btn-whatsapp" href="{{ $whatsappUrl }}" target="_blank" rel="noopener">
                        <x-site-icon name="whatsapp" />
                        Chat on WhatsApp
                    </a>
                </div>
            </div>
            <x-rates-ticker />
        </header>

        <main id="main">
            {{ $slot }}
        </main>

        <footer class="site-footer">
            <div class="container">
                <div class="footer-grid">
                    <div>
                        <a class="logo" href="{{ route('home') }}" wire:navigate.hover aria-label="{{ $business }}, home">
                            <x-logo-mark />
                            <span class="logo-text">
                                <span class="logo-name">PARISTIMES TRADING</span>
                                <span class="logo-sub">ENTERPRISES</span>
                            </span>
                        </a>
                        <p class="footer-about">Foreign currency exchange in the heart of Idumota, Lagos Island. We buy and sell US Dollars, British Pounds and Euros.</p>
                    </div>

                    <div>
                        <h2>Pages</h2>
                        <ul>
                            @foreach ($links as $route => $label)
                                <li><a href="{{ route($route) }}" wire:navigate.hover>{{ $label }}</a></li>
                            @endforeach
                            <li><a href="{{ route('privacy') }}" wire:navigate.hover>Privacy policy</a></li>
                        </ul>
                    </div>

                    <div>
                        <h2>Visit or call us</h2>
                        <ul>
                            <li><a href="{{ Setting::mapsUrl() }}" target="_blank" rel="noopener">{{ Setting::get('address_street') }}<br>{{ Setting::get('address_area') }}</a></li>
                            <li><a href="{{ Setting::phoneUrl() }}">Call {{ Setting::get('phone') }}</a></li>
                            <li><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp {{ Setting::get('whatsapp') }}</a></li>
                            @if (filled(Setting::get('email')))
                                <li><a href="mailto:{{ Setting::get('email') }}">{{ Setting::get('email') }}</a></li>
                            @endif
                            @if (filled(Setting::get('opening_hours')))
                                <li>{{ Setting::get('opening_hours') }}</li>
                            @endif
                            @if (filled(Setting::get('facebook_url')))
                                <li><a href="{{ Setting::get('facebook_url') }}" target="_blank" rel="noopener">Facebook</a></li>
                            @endif
                            @if (filled(Setting::get('instagram_url')))
                                <li><a href="{{ Setting::get('instagram_url') }}" target="_blank" rel="noopener">Instagram</a></li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <span>&copy; {{ now()->year }} {{ $business }}. All rights reserved.</span>
                    @if (filled(Setting::get('cac_number')))
                        <span>Registered business: {{ Setting::get('cac_number') }}</span>
                    @endif
                </div>
            </div>
        </footer>

        <a class="wa-float" href="{{ $whatsappUrl }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
            <x-site-icon name="whatsapp" />
            <span>Chat</span>
        </a>

        @livewireScripts
    </body>
</html>
