<?php

use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;
use App\Support\ImageOptimizer;
use App\Support\TransactionImporter;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('Transactions')] class extends Component {
    use WithFileUploads, WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $typeFilter = '';

    public ?int $editingId = null;

    public string $traded_on = '';

    public string $type = 'sell';

    public string $currency_code = '';

    public string $amount = '';

    public string $rate = '';

    public string $customer_name = '';

    public string $note = '';

    public bool $is_public = true;

    public ?TemporaryUploadedFile $receipt = null;

    public ?TemporaryUploadedFile $csv = null;

    /**
     * Problems found in the last spreadsheet import.
     *
     * @var list<string>
     */
    public array $importErrors = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->resetForm();
    }

    /**
     * The currencies the owner trades, for the form's dropdown.
     *
     * @return Collection<int, Currency>
     */
    #[Computed]
    public function currencies(): Collection
    {
        return Currency::query()->orderBy('sort_order')->orderBy('code')->get();
    }

    /**
     * The deals in the list, newest first, filtered by the search box.
     *
     * @return LengthAwarePaginator<int, Transaction>
     */
    #[Computed]
    public function transactions(): LengthAwarePaginator
    {
        return Transaction::query()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('currency_code', 'like', '%'.$this->search.'%')
                        ->orWhere('note', 'like', '%'.$this->search.'%');
                });
            })
            ->when(TransactionType::tryFrom($this->typeFilter), fn ($query, TransactionType $type) => $query->where('type', $type))
            ->latest('traded_on')
            ->latest('id')
            ->paginate(15);
    }

    /**
     * Totals shown at the top of the page.
     *
     * @return array{count: int, volume: string, month: int}
     */
    #[Computed]
    public function totals(): array
    {
        return [
            'count' => Transaction::query()->count(),
            'volume' => Currency::naira((string) Transaction::query()->sum('naira_total')),
            'month' => Transaction::query()->where('traded_on', '>=', now()->startOfMonth()->toDateString())->count(),
        ];
    }

    /**
     * The naira total for what is typed in the form so far.
     */
    #[Computed]
    public function formTotal(): string
    {
        if (! is_numeric($this->amount) || ! is_numeric($this->rate)) {
            return '—';
        }

        return Currency::naira(Transaction::nairaTotal($this->amount, $this->rate));
    }

    /**
     * Go back to the first page when the search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Go back to the first page when the filter changes.
     */
    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Empty the search box and filter so every deal shows again.
     */
    public function clearFilters(): void
    {
        $this->reset('search', 'typeFilter');
        $this->resetPage();
        $this->clearComputed();
    }

    /**
     * Fill in the saved rate when the currency changes.
     */
    public function updatedCurrencyCode(): void
    {
        $this->useSavedRate();
    }

    /**
     * Fill in the saved rate when buy/sell changes.
     */
    public function updatedType(): void
    {
        $this->useSavedRate();
    }

    /**
     * Open the form for a new deal.
     */
    public function create(): void
    {
        $this->resetForm();
        Flux::modal('transaction-form')->show();
    }

    /**
     * Open the form with an existing deal.
     */
    public function edit(int $id): void
    {
        $transaction = Transaction::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $transaction->id;
        $this->traded_on = $transaction->traded_on->toDateString();
        $this->type = $transaction->type->value;
        $this->currency_code = $transaction->currency_code;
        $this->amount = $transaction->amount;
        $this->rate = $transaction->rate;
        $this->customer_name = $transaction->customer_name;
        $this->note = $transaction->note;
        $this->is_public = $transaction->is_public;
        $this->receipt = null;

        Flux::modal('transaction-form')->show();
    }

    /**
     * Save the deal in the form.
     */
    public function save(ImageOptimizer $optimizer): void
    {
        $this->currency_code = strtoupper(trim($this->currency_code));

        $validated = $this->validate([
            'traded_on' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'currency_code' => ['required', 'alpha', 'size:3'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'rate' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_public' => ['boolean'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ], attributes: [
            'traded_on' => 'date',
            'currency_code' => 'currency',
        ]);

        $transaction = $this->editingId !== null
            ? Transaction::query()->findOrFail($this->editingId)
            : new Transaction;

        $transaction->fill([
            'traded_on' => $validated['traded_on'],
            'type' => $validated['type'],
            'currency_code' => $validated['currency_code'],
            'amount' => $validated['amount'],
            'rate' => $validated['rate'],
            'naira_total' => Transaction::nairaTotal($validated['amount'], $validated['rate']),
            'customer_name' => trim((string) $validated['customer_name']),
            'note' => trim((string) $validated['note']),
            'is_public' => $validated['is_public'],
        ]);

        if ($this->receipt !== null) {
            $transaction->deleteReceipt();
            $transaction->receipt_path = $this->receipt->getMimeType() === 'application/pdf'
                ? $this->receipt->store('receipts', Transaction::RECEIPT_DISK)
                : $optimizer->store($this->receipt, 'receipts', Transaction::RECEIPT_DISK);
        }

        $isNew = ! $transaction->exists;
        $transaction->save();

        $this->resetForm();
        $this->clearComputed();

        if ($isNew) {
            $this->clearFilters();
        }

        Flux::modal('transaction-form')->close();
        Flux::toast(variant: 'success', text: __('Transaction saved.'));
    }

    /**
     * Show or hide a deal on the website.
     */
    public function togglePublic(int $id): void
    {
        $transaction = Transaction::query()->findOrFail($id);
        $transaction->update(['is_public' => ! $transaction->is_public]);

        $this->clearComputed();
    }

    /**
     * Delete a deal and its receipt.
     */
    public function delete(int $id): void
    {
        $transaction = Transaction::query()->findOrFail($id);
        $transaction->deleteReceipt();
        $transaction->delete();

        $this->clearComputed();

        Flux::toast(text: __('Transaction deleted.'));
    }

    /**
     * Add many past deals from a CSV file.
     */
    public function import(TransactionImporter $importer): void
    {
        $this->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], attributes: ['csv' => 'file']);

        $saved = $importer->import($this->csv->getRealPath());
        $this->importErrors = array_slice($importer->errors, 0, 15);

        if ($saved === 0) {
            return;
        }

        $this->reset('csv');
        $this->clearComputed();
        $this->resetPage();

        Flux::toast(variant: 'success', text: trans_choice(':count transaction imported.|:count transactions imported.', $saved));
    }

    /**
     * Download an empty spreadsheet in the right layout.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo TransactionImporter::template();
        }, 'transactions-template.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Put today's saved rate in the form for the chosen currency and type.
     */
    private function useSavedRate(): void
    {
        $currency = $this->currencies->firstWhere('code', $this->currency_code);
        $saved = $this->type === TransactionType::Buy->value ? $currency?->buy_rate : $currency?->sell_rate;

        $this->rate = (string) $saved;
    }

    /**
     * Empty the form, ready for a new deal.
     */
    private function resetForm(): void
    {
        $this->resetValidation();
        $this->reset('editingId', 'amount', 'customer_name', 'note', 'receipt', 'is_public', 'type');
        $this->traded_on = now()->toDateString();
        $this->currency_code = (string) $this->currencies->first()?->code;
        $this->useSavedRate();
    }

    /**
     * Refresh the list and totals after a change.
     */
    private function clearComputed(): void
    {
        unset($this->transactions, $this->totals);
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Transactions') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Keep a record of past deals. Deals marked "On website" show as recent deals on the home page, without customer names.') }}</flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">{{ __('Add transaction') }}</flux:button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text>{{ __('Deals recorded') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($this->totals['count']) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Total value in naira') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ $this->totals['volume'] }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>{{ __('Deals this month') }}</flux:text>
            <flux:heading size="xl" class="mt-1">{{ number_format($this->totals['month']) }}</flux:heading>
        </flux:card>
    </div>

    <flux:card class="mt-6 space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('Upload previous transactions') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Save your Excel or Google Sheets list as CSV, then upload it. Leave the rate empty to use today\'s saved rate.') }}</flux:text>
            </div>
            <flux:button size="sm" icon="arrow-down-tray" wire:click="downloadTemplate">{{ __('Download template') }}</flux:button>
        </div>

        <form wire:submit="import" class="flex flex-wrap items-end gap-3">
            <flux:field class="min-w-0 flex-1">
                <input type="file" wire:model="csv" accept=".csv,text/csv" class="block w-full text-sm text-zinc-700 file:me-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-4 file:py-2 file:text-white dark:text-zinc-200 dark:file:bg-white dark:file:text-zinc-800" />
                <flux:error name="csv" />
            </flux:field>
            <flux:button type="submit" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="csv, import">{{ __('Import') }}</flux:button>
        </form>

        @if ($importErrors !== [])
            <flux:callout variant="danger" icon="exclamation-triangle">
                <flux:callout.heading>{{ __('Nothing was imported. Please fix these rows and upload again:') }}</flux:callout.heading>
                <flux:callout.text>
                    <ul class="mt-2 list-disc space-y-1 ps-5">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:callout.text>
            </flux:callout>
        @endif
    </flux:card>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <flux:input type="search" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search customer, currency or note')" class="max-w-sm" autocomplete="off" name="transaction-search" />
        <flux:select wire:model.live="typeFilter" class="max-w-44" autocomplete="off" name="transaction-type-filter">
            <flux:select.option value="">{{ __('All deals') }}</flux:select.option>
            @foreach (TransactionType::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table class="mt-4" :paginate="$this->transactions">
        <flux:table.columns>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Deal') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Rate') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
            <flux:table.column>{{ __('Customer') }}</flux:table.column>
            <flux:table.column>{{ __('Receipt') }}</flux:table.column>
            <flux:table.column>{{ __('Website') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->transactions as $transaction)
                <flux:table.row :key="$transaction->id">
                    <flux:table.cell class="whitespace-nowrap">{{ $transaction->traded_on->format('j M Y') }}</flux:table.cell>
                    <flux:table.cell class="whitespace-nowrap">
                        <flux:badge size="sm" :color="$transaction->type === TransactionType::Buy ? 'sky' : 'amber'">{{ $transaction->type->label() }}</flux:badge>
                        <span class="ms-1 font-medium">{{ $transaction->formattedAmount() }} {{ $transaction->currency_code }}</span>
                    </flux:table.cell>
                    <flux:table.cell align="end" class="whitespace-nowrap">{{ Currency::naira($transaction->rate) }}</flux:table.cell>
                    <flux:table.cell align="end" class="whitespace-nowrap font-medium">{{ Currency::naira($transaction->naira_total) }}</flux:table.cell>
                    <flux:table.cell>
                        <div>{{ $transaction->customer_name ?: '—' }}</div>
                        @if ($transaction->note !== '')
                            <flux:text size="sm">{{ $transaction->note }}</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($transaction->receipt_path)
                            <x-receipt-thumb :transaction="$transaction" />
                        @else
                            <flux:text size="sm">—</flux:text>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:switch :checked="$transaction->is_public" wire:click="togglePublic({{ $transaction->id }})" :aria-label="__('Show on website')" />
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $transaction->id }})" :aria-label="__('Edit')" />
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $transaction->id }})" wire:confirm="{{ __('Delete this transaction?') }}" :aria-label="__('Delete')" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">
                        @if ($search !== '' || $typeFilter !== '')
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:text>{{ __('No transactions match your search.') }}</flux:text>
                                <flux:button size="sm" icon="x-mark" wire:click="clearFilters">{{ __('Clear search') }}</flux:button>
                            </div>
                        @else
                            <flux:text>{{ __('No transactions yet. Add one, or upload your previous deals from a spreadsheet.') }}</flux:text>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="transaction-form" class="w-full md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit transaction') : __('Add transaction') }}</flux:heading>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="traded_on" type="date" :label="__('Date')" max="{{ now()->toDateString() }}" />
                <flux:select wire:model.live="type" :label="__('Deal')">
                    @foreach (TransactionType::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model.live="currency_code" :label="__('Currency')">
                    @foreach ($this->currencies as $currency)
                        <flux:select.option value="{{ $currency->code }}">{{ $currency->code }}</flux:select.option>
                    @endforeach
                    @if ($currency_code !== '' && ! $this->currencies->contains('code', $currency_code))
                        <flux:select.option value="{{ $currency_code }}">{{ $currency_code }}</flux:select.option>
                    @endif
                </flux:select>
                <flux:input wire:model.live.debounce.300ms="amount" :label="__('Amount')" inputmode="decimal" placeholder="500" />
                <flux:input wire:model.live.debounce.300ms="rate" :label="__('Rate (₦)')" inputmode="decimal" />
            </div>

            <div class="rounded-lg bg-zinc-100 px-4 py-3 dark:bg-zinc-700/50">
                <flux:text>{{ __('Naira total') }}</flux:text>
                <flux:heading size="xl">{{ $this->formTotal }}</flux:heading>
                <flux:text size="sm">{{ __('Worked out from the amount and rate. The rate starts as today\'s saved rate; change it if the deal used another rate.') }}</flux:text>
            </div>

            <flux:input wire:model="customer_name" :label="__('Customer (private, optional)')" />
            <flux:input wire:model="note" :label="__('Note (private, optional)')" placeholder="Paid by transfer" />

            <x-image-upload
                model="receipt"
                :label="__('Receipt picture (private, optional)')"
                accept="image/jpeg,image/png,image/webp,application/pdf"
                :hint="__('A photo or screenshot of the receipt, or a PDF up to 2 MB. Only you can see it.')"
            />

            @if ($receipt?->isPreviewable() && ! $errors->has('receipt'))
                <img src="{{ $receipt->temporaryUrl() }}" alt="{{ __('New receipt') }}" class="max-h-48 rounded-lg">
            @elseif ($editingId && ($current = Transaction::query()->find($editingId))?->receipt_path)
                <div class="flex items-center gap-3">
                    <x-receipt-thumb :transaction="$current" />
                    <flux:text size="sm">{{ __('Current receipt. Choose a new file to replace it.') }}</flux:text>
                </div>
            @endif

            <flux:switch wire:model="is_public" :label="__('Show as a recent deal on the website')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="receipt, save">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
