<?php

use App\Models\Currency;
use App\Models\RateSnapshot;
use App\Models\Setting;
use App\Support\RatesMessage;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Exchange rates')] class extends Component {
    /**
     * The rates being edited, keyed by currency id.
     *
     * @var array<int, array{buy: string, sell: string, active: bool}>
     */
    public array $rates = [];

    public string $code = '';

    public string $name = '';

    public string $symbol = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRates();
    }

    /**
     * Every currency, in the order shown on the website.
     *
     * @return Collection<int, Currency>
     */
    #[Computed]
    public function currencies(): Collection
    {
        return Currency::query()->orderBy('sort_order')->orderBy('code')->get();
    }

    /**
     * Today's rates as a message to paste into WhatsApp or a Status.
     */
    #[Computed]
    public function whatsappText(): string
    {
        return RatesMessage::text();
    }

    /**
     * Save today's buy and sell rates and mark them as updated now.
     */
    public function save(): void
    {
        $this->validate([
            'rates' => ['array'],
            'rates.*.buy' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rates.*.sell' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'rates.*.active' => ['boolean'],
        ], attributes: [
            'rates.*.buy' => 'buy rate',
            'rates.*.sell' => 'sell rate',
        ]);

        foreach ($this->rates as $id => $rate) {
            if (filled($rate['buy']) && filled($rate['sell']) && (float) $rate['sell'] < (float) $rate['buy']) {
                $this->addError("rates.{$id}.sell", __('The sell rate should not be lower than the buy rate.'));
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        foreach ($this->currencies as $currency) {
            $rate = $this->rates[$currency->id] ?? null;

            if ($rate === null) {
                continue;
            }

            $currency->update([
                'buy_rate' => filled($rate['buy']) ? $rate['buy'] : null,
                'sell_rate' => filled($rate['sell']) ? $rate['sell'] : null,
                'is_active' => (bool) $rate['active'],
            ]);
        }

        Setting::put(['rates_updated_at' => now()->toIso8601String()]);
        RateSnapshot::recordToday();

        unset($this->currencies, $this->whatsappText);
        $this->loadRates();

        Flux::toast(variant: 'success', text: __('Rates saved. The website now shows them.'));
    }

    /**
     * Add another currency to the rates board.
     */
    public function addCurrency(): void
    {
        $this->code = strtoupper(trim($this->code));

        $validated = $this->validate([
            'code' => ['required', 'alpha', 'size:3', Rule::unique('currencies', 'code')],
            'name' => ['required', 'string', 'max:60'],
            'symbol' => ['nullable', 'string', 'max:8'],
        ]);

        Currency::query()->create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'symbol' => $validated['symbol'] ?? '',
            'sort_order' => (int) Currency::query()->max('sort_order') + 1,
        ]);

        $this->reset('code', 'name', 'symbol');
        unset($this->currencies);
        $this->loadRates();

        Flux::modal('add-currency')->close();
        Flux::toast(variant: 'success', text: __('Currency added. Enter its rates and save.'));
    }

    /**
     * Remove a currency from the website.
     */
    public function deleteCurrency(int $id): void
    {
        Currency::query()->whereKey($id)->delete();

        unset($this->currencies);
        $this->loadRates();

        Flux::toast(text: __('Currency removed.'));
    }

    /**
     * Fill the form from the database.
     */
    private function loadRates(): void
    {
        $this->rates = $this->currencies->mapWithKeys(fn (Currency $currency): array => [
            $currency->id => [
                'buy' => (string) $currency->buy_rate,
                'sell' => (string) $currency->sell_rate,
                'active' => $currency->is_active,
            ],
        ])->all();
    }
}; ?>

<section class="mx-auto w-full max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Exchange rates') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Enter today\'s rates in naira for one unit of each currency, then press Save. The home page updates straight away.') }}</flux:text>
        </div>

        <flux:modal.trigger name="add-currency">
            <flux:button icon="plus">{{ __('Add currency') }}</flux:button>
        </flux:modal.trigger>
    </div>

    @php($updatedAt = App\Models\Setting::ratesUpdatedAt())
    <flux:callout class="mt-6" :variant="$updatedAt?->isToday() ? 'success' : 'warning'" :icon="$updatedAt?->isToday() ? 'check-circle' : 'clock'">
        <flux:callout.heading>
            @if ($updatedAt === null)
                {{ __('Rates have not been saved yet.') }}
            @elseif ($updatedAt->isToday())
                {{ __('Rates updated today at :time.', ['time' => $updatedAt->format('g:ia')]) }}
            @else
                {{ __('Rates last updated :when. Please check them for today.', ['when' => $updatedAt->format('j M Y, g:ia')]) }}
            @endif
        </flux:callout.heading>
    </flux:callout>

    <form wire:submit="save" class="mt-6 space-y-4">
        @forelse ($this->currencies as $currency)
            <flux:card wire:key="currency-{{ $currency->id }}" class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-lg bg-amber-100 font-semibold text-amber-800 dark:bg-amber-400/15 dark:text-amber-300">{{ $currency->symbol ?: mb_substr($currency->code, 0, 1) }}</span>
                        <div>
                            <flux:heading>{{ $currency->code }}</flux:heading>
                            <flux:text>{{ $currency->name }}</flux:text>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:switch wire:model="rates.{{ $currency->id }}.active" :label="__('Show')" align="left" />
                        <flux:button
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            wire:click="deleteCurrency({{ $currency->id }})"
                            wire:confirm="{{ __('Remove :code from the website?', ['code' => $currency->code]) }}"
                            :aria-label="__('Remove :code', ['code' => $currency->code])"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input
                        wire:model="rates.{{ $currency->id }}.buy"
                        :label="__('We buy at (₦)')"
                        :description="__('What you pay a customer for 1 :code', ['code' => $currency->code])"
                        inputmode="decimal"
                        placeholder="e.g. 1580"
                    />
                    <flux:input
                        wire:model="rates.{{ $currency->id }}.sell"
                        :label="__('We sell at (₦)')"
                        :description="__('What a customer pays you for 1 :code', ['code' => $currency->code])"
                        inputmode="decimal"
                        placeholder="e.g. 1600"
                    />
                </div>
            </flux:card>
        @empty
            <flux:card>
                <flux:text>{{ __('No currencies yet. Use "Add currency" to start.') }}</flux:text>
            </flux:card>
        @endforelse

        @if ($this->currencies->isNotEmpty())
            <div class="flex items-center gap-3">
                <flux:button variant="primary" type="submit" icon="check">{{ __('Save rates') }}</flux:button>
                <flux:text>{{ __('Leave a box empty to show "Ask on WhatsApp".') }}</flux:text>
            </div>
        @endif
    </form>

    <flux:card class="mt-8 space-y-4" x-data="{ copied: false }">
        <div>
            <flux:heading size="lg">{{ __('Share today\'s rates on WhatsApp') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Copy this message and paste it into your WhatsApp Status, groups or chats. It updates when you save new rates.') }}</flux:text>
        </div>

        <flux:textarea readonly rows="10" x-ref="message" class="font-mono text-sm">{{ $this->whatsappText }}</flux:textarea>

        <div class="flex flex-wrap gap-2">
            <flux:button
                icon="clipboard-document"
                x-on:click="navigator.clipboard.writeText($refs.message.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            >
                <span x-show="! copied">{{ __('Copy message') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
            </flux:button>
            <flux:button icon="paper-airplane" :href="'https://wa.me/?text='.rawurlencode($this->whatsappText)" target="_blank">
                {{ __('Open in WhatsApp') }}
            </flux:button>
        </div>
    </flux:card>

    <flux:modal name="add-currency" class="md:w-96">
        <form wire:submit="addCurrency" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Add a currency') }}</flux:heading>
                <flux:text class="mt-2">{{ __('For example CAD, Canadian Dollar, $.') }}</flux:text>
            </div>

            <flux:input wire:model="code" :label="__('Code (3 letters)')" maxlength="3" placeholder="CAD" />
            <flux:input wire:model="name" :label="__('Name')" placeholder="Canadian Dollar" />
            <flux:input wire:model="symbol" :label="__('Symbol (optional)')" placeholder="$" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Add currency') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
