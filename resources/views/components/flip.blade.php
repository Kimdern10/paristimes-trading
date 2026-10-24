@props(['value'])

{{--
    A rate shown as airport-style flip tiles (see resources/js/site.js).
    The text is readable without JavaScript and by screen readers. When
    Livewire changes data-flip, the tiles flip to the new value; wire:ignore
    stops Livewire from redrawing the tiles themselves.
--}}
<span {{ $attributes->merge(['class' => 'flip']) }} data-flip="{{ $value }}">
    <span class="flip-cells" wire:ignore aria-hidden="true">{{ $value }}</span>
    <span class="sr-only">{{ $value }}</span>
</span>
