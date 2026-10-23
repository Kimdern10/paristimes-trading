<x-layouts::site title="Privacy policy" description="How {{ App\Models\Setting::get('business_name') }} handles your personal information.">
    <section class="page-hero">
        <div class="container">
            <h1>Privacy policy</h1>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="prose">
                {!! $policy !!}
            </div>
        </div>
    </section>
</x-layouts::site>
