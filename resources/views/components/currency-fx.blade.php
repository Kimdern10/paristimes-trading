@props(['variant' => 'rise'])

{{-- Decorative moving background: glowing blobs and floating currency symbols. "rise" floats up (home), "drift" floats sideways (inner pages). --}}
<div {{ $attributes->merge(['class' => 'hero-fx fx-'.$variant]) }} aria-hidden="true">
    <span class="blob blob-1"></span>
    <span class="blob blob-2"></span>
    <span class="blob blob-3"></span>
    <span class="grain"></span>
    @foreach (['$', '£', '€', '₦', '$', '€', '₦'] as $index => $symbol)
        <span class="float-sym sym-{{ $index + 1 }}">{{ $symbol }}</span>
    @endforeach
</div>
