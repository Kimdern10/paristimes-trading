@props(['reviews'])

@if ($reviews->isNotEmpty())
    <section class="section" aria-labelledby="reviews-title">
        <div class="container">
            <div class="section-head center reveal">
                <span class="kicker">Customer reviews</span>
                <h2 id="reviews-title">What our customers say</h2>
            </div>

            <div class="reviews">
                @foreach ($reviews as $review)
                    <figure class="review">
                        <div class="review-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                            {{ str_repeat('★', $review->rating) }}<span>{{ str_repeat('★', 5 - $review->rating) }}</span>
                        </div>
                        <blockquote>{{ $review->body }}</blockquote>
                        <figcaption>
                            <span class="review-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ $review->name }}</strong>
                                @if ($review->detail !== '')
                                    <small>{{ $review->detail }}</small>
                                @endif
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
