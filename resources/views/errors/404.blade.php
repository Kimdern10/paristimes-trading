<x-layouts::site title="Page not found">
    <section class="not-found">
        <x-currency-fx variant="drift" class="fx-soft" />
        <div class="container">
            <div class="code">404</div>
            <h1>We couldn't find that page</h1>
            <p>The link may be old or mistyped. You can go back to the home page or chat with us on WhatsApp.</p>
            <div class="hero-actions">
                <a class="btn btn-gold" href="{{ route('home') }}" wire:navigate.hover>Go to the home page</a>
                <a class="btn btn-whatsapp" href="{{ App\Models\Setting::whatsappUrl() }}" target="_blank" rel="noopener">
                    <x-site-icon name="whatsapp" />
                    Chat on WhatsApp
                </a>
            </div>
        </div>
    </section>
</x-layouts::site>
