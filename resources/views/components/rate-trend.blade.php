@props(['trends'])

@php
    use App\Support\RateChart;
@endphp

<section class="section section-alt" aria-labelledby="trend-title">
    <div class="container">
        <div class="section-head reveal">
            <span class="kicker">Rate history</span>
            <h2 id="trend-title">How the rates moved</h2>
            <p>Our buy and sell rates over the last {{ RateChart::DAYS }} days.</p>
        </div>

        <div class="trend reveal" data-trend>
            <div class="trend-tabs" role="tablist" aria-label="Choose a currency">
                @foreach ($trends as $index => $trend)
                    <button type="button" role="tab" id="trend-tab-{{ $trend['currency']->code }}" aria-controls="trend-panel-{{ $trend['currency']->code }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" tabindex="{{ $index === 0 ? 0 : -1 }}">
                        {{ $trend['currency']->code }}
                    </button>
                @endforeach
            </div>

            @foreach ($trends as $index => $trend)
                @php($chart = $trend['chart'])
                <div class="trend-panel" role="tabpanel" id="trend-panel-{{ $trend['currency']->code }}" aria-labelledby="trend-tab-{{ $trend['currency']->code }}" @if ($index > 0) hidden @endif>
                    <div class="trend-summary">
                        <div>
                            <span class="trend-label">{{ $trend['currency']->name }} sell rate</span>
                            <strong class="trend-latest">{{ $chart['latest'] ?? '—' }}</strong>
                        </div>
                        @if ($chart['change'] !== null)
                            <span @class(['trend-change', 'is-up' => $chart['change'] > 0, 'is-down' => $chart['change'] < 0])>
                                {{ $chart['change'] > 0 ? '▲' : ($chart['change'] < 0 ? '▼' : '•') }} {{ abs($chart['change']) }}% since {{ $chart['from'] }}
                            </span>
                        @endif
                    </div>

                    <svg class="trend-chart" viewBox="0 0 {{ RateChart::WIDTH }} {{ RateChart::HEIGHT }}" preserveAspectRatio="none" role="img" aria-label="{{ $trend['currency']->code }} rates from {{ $chart['from'] }} to {{ $chart['to'] }}, between {{ $chart['min'] }} and {{ $chart['max'] }}">
                        <defs>
                            <linearGradient id="trend-fill-{{ $trend['currency']->code }}" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="currentColor" stop-opacity="0.28" />
                                <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        @if ($chart['area'] !== '')
                            <path class="trend-area" d="{{ $chart['area'] }}" fill="url(#trend-fill-{{ $trend['currency']->code }})" />
                        @endif
                        <polyline class="trend-line trend-buy" points="{{ $chart['buy'] }}" />
                        <polyline class="trend-line trend-sell" points="{{ $chart['sell'] }}" />
                    </svg>

                    <div class="trend-axis">
                        <span>{{ $chart['from'] }}</span>
                        <span class="trend-legend"><i class="dot-sell"></i> Sell <i class="dot-buy"></i> Buy <span class="trend-range">· Low {{ $chart['min'] }} · High {{ $chart['max'] }}</span></span>
                        <span>{{ $chart['to'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
