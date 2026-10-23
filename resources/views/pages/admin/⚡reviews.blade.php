<?php

use App\Models\Review;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customer reviews')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $detail = '';

    public int $rating = 5;

    public string $body = '';

    public bool $is_published = true;

    /**
     * Every review, in the order shown on the website.
     *
     * @return Collection<int, Review>
     */
    #[Computed]
    public function reviews(): Collection
    {
        return Review::query()->orderBy('sort_order')->latest('id')->get();
    }

    /**
     * Open the form for a new review.
     */
    public function create(): void
    {
        $this->resetForm();
        Flux::modal('review-form')->show();
    }

    /**
     * Open the form with an existing review.
     */
    public function edit(int $id): void
    {
        $review = Review::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $review->id;
        $this->name = $review->name;
        $this->detail = $review->detail;
        $this->rating = $review->rating;
        $this->body = $review->body;
        $this->is_published = $review->is_published;

        Flux::modal('review-form')->show();
    }

    /**
     * Save the review in the form.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'detail' => ['nullable', 'string', 'max:80'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'max:600'],
            'is_published' => ['boolean'],
        ]);

        Review::query()->updateOrCreate(['id' => $this->editingId], [
            ...$validated,
            'detail' => trim((string) $validated['detail']),
        ]);

        $this->resetForm();
        unset($this->reviews);

        Flux::modal('review-form')->close();
        Flux::toast(variant: 'success', text: __('Review saved.'));
    }

    /**
     * Show or hide a review on the website.
     */
    public function togglePublished(int $id): void
    {
        $review = Review::query()->findOrFail($id);
        $review->update(['is_published' => ! $review->is_published]);

        unset($this->reviews);
    }

    /**
     * Delete a review.
     */
    public function delete(int $id): void
    {
        Review::query()->whereKey($id)->delete();

        unset($this->reviews);

        Flux::toast(text: __('Review deleted.'));
    }

    /**
     * Empty the form, ready for a new review.
     */
    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'name', 'detail', 'rating', 'body', 'is_published');
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Customer reviews') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Add what real customers said, for example from a WhatsApp message. Ask them first if you can show their name.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add review') }}</flux:button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        @forelse ($this->reviews as $review)
            <flux:card wire:key="review-{{ $review->id }}" class="space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <flux:heading>{{ $review->name }}</flux:heading>
                        @if ($review->detail !== '')
                            <flux:text size="sm">{{ $review->detail }}</flux:text>
                        @endif
                    </div>
                    <span class="text-amber-500" aria-label="{{ __(':count out of 5 stars', ['count' => $review->rating]) }}">{{ str_repeat('★', $review->rating) }}<span class="text-zinc-300 dark:text-zinc-600">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                </div>
                <flux:text>{{ $review->body }}</flux:text>
                <div class="flex items-center justify-between gap-2">
                    <flux:switch :checked="$review->is_published" wire:click="togglePublished({{ $review->id }})" :label="__('On website')" align="left" />
                    <div class="flex gap-1">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $review->id }})" :aria-label="__('Edit')" />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $review->id }})" wire:confirm="{{ __('Delete this review?') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:text>{{ __('No reviews yet. The reviews section stays hidden on the website until you add one.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="review-form" class="w-full md:w-[30rem]">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit review') : __('Add review') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Customer name')" placeholder="Mrs Bisi A." />
                <flux:input wire:model="detail" :label="__('Detail (optional)')" placeholder="Trader, Idumota" />
            </div>
            <flux:select wire:model="rating" :label="__('Stars')">
                @foreach ([5, 4, 3, 2, 1] as $stars)
                    <flux:select.option :value="$stars">{{ str_repeat('★', $stars) }} ({{ $stars }})</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="body" :label="__('What they said')" rows="4" />
            <flux:switch wire:model="is_published" :label="__('Show on the website')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
