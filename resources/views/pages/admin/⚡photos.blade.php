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

    /**
     * Each photo's caption, keyed by photo id, saved when the box loses focus.
     *
     * @var array<int, string>
     */
    public array $captions = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadCaptions();
    }

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
     * Save the photo as soon as it has finished uploading.
     */
    public function updatedPhoto(): void
    {
        $this->addPhoto(app(ImageOptimizer::class));
    }

    /**
     * Compress the photo to WebP and add it to the website, first in the list.
     */
    public function addPhoto(ImageOptimizer $optimizer): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $path = $optimizer->store($this->photo);

        DB::transaction(function () use ($path): void {
            Photo::query()->increment('sort_order');
            Photo::query()->create(['path' => $path, 'caption' => '', 'sort_order' => 0]);
        });

        $this->reset('photo');
        unset($this->photos);
        $this->loadCaptions();

        Flux::toast(variant: 'success', text: __('Photo added to the website. You can type a caption under it.'));
    }

    /**
     * Save a caption when its box loses focus.
     */
    public function updatedCaptions(mixed $value, string $key): void
    {
        $this->validate(["captions.{$key}" => ['nullable', 'string', 'max:120']], attributes: ["captions.{$key}" => 'caption']);

        Photo::query()->whereKey((int) $key)->update(['caption' => trim((string) $value)]);
        unset($this->photos);

        Flux::toast(variant: 'success', text: __('Caption saved.'));
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
        $this->loadCaptions();

        Flux::toast(text: __('Photo deleted.'));
    }

    /**
     * Fill the caption boxes from the database.
     */
    private function loadCaptions(): void
    {
        $this->captions = $this->photos->mapWithKeys(fn (Photo $photo): array => [$photo->id => $photo->caption])->all();
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Shop photos') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Real photos of the shop and team build trust. The first photo appears on the home page; all of them appear on the About page.') }}</flux:text>
        </div>
        <flux:button icon="globe-alt" :href="route('about')" target="_blank">{{ __('See them on the website') }}</flux:button>
    </div>

    <flux:card class="mt-6 space-y-3">
        <x-image-upload model="photo" :label="__('Add a photo')" :hint="__('Choose a photo from your phone or computer. It is shrunk and saved straight away.')" />
        <div wire:loading wire:target="photo">
            <flux:text>{{ __('Saving the photo…') }}</flux:text>
        </div>
    </flux:card>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->photos as $item)
            <flux:card wire:key="photo-{{ $item->id }}" class="overflow-hidden p-0!">
                <img src="{{ $item->smallUrl() }}" alt="{{ $item->caption }}" class="aspect-[4/3] w-full object-cover">
                <div class="space-y-3 p-4">
                    @if ($loop->first)
                        <flux:badge color="amber" size="sm">{{ __('On the home page') }}</flux:badge>
                    @endif
                    <flux:input wire:model.blur="captions.{{ $item->id }}" :aria-label="__('Caption')" :placeholder="__('Type a caption (optional)')" size="sm" />
                    <div class="flex gap-2">
                        @unless ($loop->first)
                            <flux:button size="sm" wire:click="makeFirst({{ $item->id }})">{{ __('Show first') }}</flux:button>
                        @endunless
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete({{ $item->id }})" wire:confirm="{{ __('Delete this photo?') }}">{{ __('Delete') }}</flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="col-span-full flex flex-col items-center gap-2 py-10 text-center">
                <flux:icon.photo class="size-10 text-zinc-400" />
                <flux:heading>{{ __('No photos yet') }}</flux:heading>
                <flux:text>{{ __('The website shows the logo until you add one. Photos of the shop front and counter work best.') }}</flux:text>
            </flux:card>
        @endforelse
    </div>
</section>
