@props(['faqs', 'title' => 'Questions people ask', 'alt' => false])

@if ($faqs->isNotEmpty())
    <section @class(['section', 'section-alt' => $alt]) aria-labelledby="faq-title">
        <div class="container faq-wrap">
            <div class="section-head reveal">
                <span class="kicker">FAQ</span>
                <h2 id="faq-title">{{ $title }}</h2>
                <p>Can't find your answer? Chat with us on WhatsApp.</p>
            </div>

            <div class="faq reveal">
                @foreach ($faqs as $faq)
                    <details class="faq-item" @if ($loop->first) open @endif>
                        <summary>
                            <span>{{ $faq->question }}</span>
                            <i aria-hidden="true"></i>
                        </summary>
                        <div class="faq-answer">{!! nl2br(e($faq->answer)) !!}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
