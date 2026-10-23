@php
    use App\Models\Setting;

    $business = Setting::get('business_name');
@endphp

<x-layouts::site title="About us" description="{{ $business }} is a Lagos-based foreign currency exchange business in Idumota, serving individuals, traders and businesses.">
    <section class="page-hero">
        <x-currency-fx variant="drift" />
        <div class="container">
            <span class="eyebrow"><x-site-icon name="users" /> About us</span>
            <h1>A trusted currency exchange in the heart of Idumota</h1>
            <p>{{ $business }} is a Lagos-based business specializing in foreign currency exchange.</p>
        </div>
    </section>

    <section class="section">
        <div class="container split">
            <div class="reveal">
                <span class="kicker">Who we are</span>
                <h2>Quick and reliable exchange for Lagos</h2>
                <p class="prose-lead">Located in the heart of Idumota, we serve individuals, traders, and businesses who need quick and reliable currency exchange services.</p>
                <p class="prose-lead">We understand how important secure transactions are, and we make customer satisfaction our top priority.</p>
            </div>

            <div class="brand-panel reveal">
                <x-logo-mark />
                <div>
                    <p>Our goal is to provide</p>
                </div>
                <div class="brand-values"><span>Trust</span><span>Speed</span><span>Transparency</span></div>
            </div>
        </div>
    </section>

    <section class="section section-alt">
        <div class="container">
            <div class="section-head center reveal">
                <span class="kicker">Our promise</span>
                <h2>What you can expect from us</h2>
            </div>
            <div class="cards cards-3 reveal">
                <div class="card">
                    <span class="card-icon"><x-site-icon name="shield" /></span>
                    <h3>Trust</h3>
                    <p>Safe and verified transactions in a trusted business environment.</p>
                </div>
                <div class="card">
                    <span class="card-icon"><x-site-icon name="bolt" /></span>
                    <h3>Speed</h3>
                    <p>Instant processing, so you are not kept waiting.</p>
                </div>
                <div class="card">
                    <span class="card-icon"><x-site-icon name="chart" /></span>
                    <h3>Transparency</h3>
                    <p>Clear rates agreed before every deal, with no hidden charges.</p>
                </div>
            </div>
        </div>
    </section>

    @if ($photos->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head reveal">
                    <span class="kicker">Our shop</span>
                    <h2>Come and see us</h2>
                </div>
                <div class="gallery reveal">
                    @foreach ($photos as $photo)
                        <figure>
                            <img src="{{ $photo->smallUrl() }}" srcset="{{ $photo->smallUrl() }} 800w, {{ $photo->url() }} 1600w" sizes="(min-width: 1000px) 370px, (min-width: 640px) 50vw, 100vw" alt="{{ $photo->caption ?: 'Photo of '.$business }}" loading="lazy">
                            @if (filled($photo->caption))
                                <figcaption>{{ $photo->caption }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-band title="Talk to us today" />
</x-layouts::site>
