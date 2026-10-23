<?php

use App\Models\Photo;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Shop photos')] class extends Component {
    use WithFileUploads;

    public ?TemporaryUploadedFile $photo = null;

    public string $caption = '';

    /**
     * The photos shown on the website, first one on the home page.
     *
     * @return Collection<int, Photo>
     */
    #[Computed]
    public function photos(): Collection
    {
        return Photo::query()->orderBy('sort_order')->orderByDesc('id')->get();
    }

    /**
     * Compress the photo to WebP and add it to the website.
     */
    public function upload(ImageOptimizer $optimizer): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        Photo::query()->create([
            'path' => $optimizer->store($this->photo),
            'caption' => trim($this->caption),
            'sort_order' => (int) Photo::query()->min('sort_order') - 1,
        ]);

        $this->reset('photo', 'caption');
        unset($this->photos);

        Flux::toast(variant: 'success', text: __('Photo added to the website.'));
    }

    /**
     * Show a photo first, so it appears on the home page.
     */
    public function makeFirst(int $id): void
    {
        Photo::query()->whereKey($id)->update(['sort_order' => (int) Photo::query()->min('sort_order') - 1]);

        unset($this->photos);
    }

    /**
     * Delete a photo and its files.
     */
    public function delete(int $id): void
    {
        $photo = Photo::query()->findOrFail($id);
        $photo->deleteFiles();
        $photo->delete();

        unset($this->photos);

        Flux::toast(text: __('Photo deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <flux:heading size="xl" level="1">{{ __('Shop photos') }}</flux:heading>
    <flux:text class="mt-2">{{ __('Real photos of the shop and team build trust. The first photo appears on the home page; all of them appear on the About page.') }}</flux:text>

    <flux:card class="mt-6">
        <form wire:submit="upload" class="space-y-5">
            <flux:field>
                <flux:label>{{ __('Photo') }}</flux:label>
                <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-700 file:me-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-4 file:py-2 file:text-white dark:text-zinc-200 dark:file:bg-white dark:file:text-zinc-800" />
                <flux:description>{{ __('JPG, PNG or WebP, up to 8 MB. It is resized and compressed for you.') }}</flux:description>
                <flux:error name="photo" />
            </flux:field>

            <div wire:loading wire:target="photo">
                <flux:text>{{ __('Uploading…') }}</flux:text>
            </div>

            @if ($photo?->isPreviewable() && ! $errors->has('photo'))
                <img src="{{ $photo->temporaryUrl() }}" alt="" class="max-h-48 rounded-lg">
            @endif

            <flux:input wire:model="caption" :label="__('Caption (optional)')" placeholder="Our shop at 41 Ashogbon Street" />

            <flux:button variant="primary" type="submit" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="photo, upload">{{ __('Add photo') }}</flux:button>
        </form>
    </flux:card>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->photos as $item)
            <flux:card wire:key="photo-{{ $item->id }}" class="overflow-hidden p-0!">
                <img src="{{ $item->smallUrl() }}" alt="{{ $item->caption }}" class="aspect-[4/3] w-full object-cover">
                <div class="space-y-3 p-4">
                    @if ($loop->first)
                        <flux:badge color="amber" size="sm">{{ __('On the home page') }}</flux:badge>
                    @endif
                    <flux:text>{{ $item->caption ?: __('No caption') }}</flux:text>
                    <div class="flex gap-2">
                        @unless ($loop->first)
                            <flux:button size="sm" wire:click="makeFirst({{ $item->id }})">{{ __('Show first') }}</flux:button>
                        @endunless
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Delete this photo?') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:text>{{ __('No photos yet. The website shows the logo until you add one.') }}</flux:text>
        @endforelse
    </div>
</section>
