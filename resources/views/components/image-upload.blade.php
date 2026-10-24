@props([
    'model',
    'label',
    'accept' => 'image/jpeg,image/png,image/webp',
    'hint' => null,
])

{{-- File input for Livewire that shrinks big phone photos in the browser before uploading (see resources/js/app.js). --}}
<flux:field x-data="imageUpload('{{ $model }}')">
    <flux:label>{{ $label }}</flux:label>
    <input
        type="file"
        x-ref="input"
        x-on:change="pick($event)"
        accept="{{ $accept }}"
        class="block w-full text-sm text-zinc-700 file:me-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-4 file:py-2 file:text-white dark:text-zinc-200 dark:file:bg-white dark:file:text-zinc-800"
    />
    @if ($hint)
        <flux:description>{{ $hint }}</flux:description>
    @endif
    <div x-show="uploading" x-cloak class="flex items-center gap-3">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
            <div class="h-full rounded-full bg-accent transition-all" x-bind:style="`width: ${progress}%`"></div>
        </div>
        <flux:text size="sm" x-text="`${progress}%`"></flux:text>
    </div>
    <flux:text x-show="failed" x-cloak size="sm" class="text-red-600! dark:text-red-400!">
        {{ __('The upload failed. Please try a smaller file or check your internet connection.') }}
    </flux:text>
    <flux:error :name="$model" />
</flux:field>
