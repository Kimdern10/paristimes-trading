<?php

use App\Models\Faq;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Questions (FAQ)')] class extends Component {
    public ?int $editingId = null;

    public string $question = '';

    public string $answer = '';

    public bool $is_published = true;

    /**
     * Every question, in the order shown on the website.
     *
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    /**
     * Open the form for a new question.
     */
    public function create(): void
    {
        $this->resetForm();
        Flux::modal('faq-form')->show();
    }

    /**
     * Open the form with an existing question.
     */
    public function edit(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->is_published = $faq->is_published;

        Flux::modal('faq-form')->show();
    }

    /**
     * Save the question in the form. New questions go to the end.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:2000'],
            'is_published' => ['boolean'],
        ]);

        if ($this->editingId === null) {
            $validated['sort_order'] = (int) Faq::query()->max('sort_order') + 1;
        }

        Faq::query()->updateOrCreate(['id' => $this->editingId], $validated);

        $this->resetForm();
        unset($this->faqs);

        Flux::modal('faq-form')->close();
        Flux::toast(variant: 'success', text: __('Question saved.'));
    }

    /**
     * Move a question up or down the list.
     */
    public function move(int $id, int $direction): void
    {
        $ordered = $this->faqs->values();
        $index = $ordered->search(fn (Faq $faq): bool => $faq->id === $id);
        $target = $index === false ? null : $index + ($direction < 0 ? -1 : 1);

        if ($target === null || $target < 0 || $target >= $ordered->count()) {
            return;
        }

        $moved = $ordered->pull($index);
        $ordered->splice($target, 0, [$moved]);
        $ordered->values()->each(fn (Faq $faq, int $position) => $faq->update(['sort_order' => $position + 1]));

        unset($this->faqs);
    }

    /**
     * Show or hide a question on the website.
     */
    public function togglePublished(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);
        $faq->update(['is_published' => ! $faq->is_published]);

        unset($this->faqs);
    }

    /**
     * Delete a question.
     */
    public function delete(int $id): void
    {
        Faq::query()->whereKey($id)->delete();

        unset($this->faqs);

        Flux::toast(text: __('Question deleted.'));
    }

    /**
     * Empty the form, ready for a new question.
     */
    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'question', 'answer', 'is_published');
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Questions (FAQ)') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Questions customers often ask. They appear on the home and contact pages.') }}</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add question') }}</flux:button>
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($this->faqs as $faq)
            <flux:card wire:key="faq-{{ $faq->id }}" class="space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <flux:heading>{{ $faq->question }}</flux:heading>
                    <div class="flex shrink-0 gap-1">
                        <flux:button size="sm" variant="ghost" icon="arrow-up" wire:click="move({{ $faq->id }}, -1)" :disabled="$loop->first" :aria-label="__('Move up')" />
                        <flux:button size="sm" variant="ghost" icon="arrow-down" wire:click="move({{ $faq->id }}, 1)" :disabled="$loop->last" :aria-label="__('Move down')" />
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $faq->id }})" :aria-label="__('Edit')" />
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $faq->id }})" wire:confirm="{{ __('Delete this question?') }}" :aria-label="__('Delete')" />
                    </div>
                </div>
                <flux:text>{{ $faq->answer }}</flux:text>
                <flux:switch :checked="$faq->is_published" wire:click="togglePublished({{ $faq->id }})" :label="__('On website')" align="left" />
            </flux:card>
        @empty
            <flux:text>{{ __('No questions yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:modal name="faq-form" class="w-full md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit question') : __('Add question') }}</flux:heading>
            <flux:input wire:model="question" :label="__('Question')" placeholder="Do I need ID to exchange money?" />
            <flux:textarea wire:model="answer" :label="__('Answer')" rows="5" />
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
