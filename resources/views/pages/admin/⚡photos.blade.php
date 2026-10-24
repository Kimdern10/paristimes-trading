<?php

use App\Models\Photo;
use App\Support\ImageOptimizer;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    public function addPhoto(ImageOptimizer $optimizer): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        $path = $optimizer->store($this->photo);

        DB::transaction(function () use ($path): void {
            Photo::query()->increment('sort_order');
            Photo::query()->create([
                'path' => $path,
                'caption' => trim($this->caption),
                'sort_order' => 0,
            ]);
        });

        $this->reset('photo', 'caption');
        unset($this->photos);

        Flux::toast(variant: 'success', text: __('Photo added to the website.'));
    }

    /**
     * Show a photo first, so it appears on the home page. The others move
     * down one place (positions never go below 0).
     */
    public function makeFirst(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Photo::query()->whereKeyNot($id)->increment('sort_order');
            Photo::query()->whereKey($id)->update(['sort_order' => 0]);
        });

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
        <form wire:submit="addPhoto" class="space-y-5">
            <x-image-upload model="photo" :label="__('Photo')" :hint="__('Any photo from your phone or computer. It is shrunk and compressed for you.')" />

            @if ($photo?->isPreviewable() && ! $errors->has('photo'))
                <img src="{{ $photo->temporaryUrl() }}" alt="" class="max-h-48 rounded-lg">
            @endif

            <flux:input wire:model="caption" :label="__('Caption (optional)')" placeholder="Our shop at 41 Ashogbon Street" />

            <flux:button variant="primary" type="submit" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="photo, addPhoto">{{ __('Add photo') }}</flux:button>
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
