<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Business details')] class extends Component {
    /**
     * The values being edited, keyed by setting name.
     *
     * @var array<string, string>
     */
    public array $values = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->values = collect(Setting::BUSINESS_KEYS)
            ->mapWithKeys(fn (string $key): array => [$key => Setting::get($key)])
            ->all();
    }

    /**
     * Save the business details shown across the website.
     */
    public function save(): void
    {
        $phoneRule = ['required', 'string', 'regex:/^\+?[0-9 ]{10,16}$/'];

        $validated = $this->validate([
            'values.business_name' => ['required', 'string', 'max:120'],
            'values.hero_title' => ['required', 'string', 'max:120'],
            'values.hero_text' => ['required', 'string', 'max:300'],
            'values.phone' => $phoneRule,
            'values.whatsapp' => $phoneRule,
            'values.email' => ['nullable', 'email', 'max:255'],
            'values.address_street' => ['required', 'string', 'max:160'],
            'values.address_area' => ['required', 'string', 'max:160'],
            'values.opening_hours' => ['nullable', 'string', 'max:160'],
            'values.maps_url' => ['nullable', 'url:https', 'max:500'],
            'values.cac_number' => ['nullable', 'string', 'max:60'],
            'values.facebook_url' => ['nullable', 'url:https', 'max:255'],
            'values.instagram_url' => ['nullable', 'url:https', 'max:255'],
            'values.rates_note' => ['nullable', 'string', 'max:300'],
            'values.privacy_policy' => ['nullable', 'string', 'max:20000'],
        ], [
            'values.phone.regex' => __('Enter a phone number like 08146552236.'),
            'values.whatsapp.regex' => __('Enter a WhatsApp number like 08146552236.'),
        ], [
            'values.business_name' => 'business name',
            'values.hero_title' => 'headline',
            'values.hero_text' => 'sub-headline',
            'values.phone' => 'phone number',
            'values.whatsapp' => 'WhatsApp number',
            'values.email' => 'email',
            'values.address_street' => 'street address',
            'values.address_area' => 'area',
            'values.maps_url' => 'Google Maps link',
            'values.facebook_url' => 'Facebook link',
            'values.instagram_url' => 'Instagram link',
        ]);

        Setting::put(array_map(fn (?string $value): string => trim((string) $value), $validated['values']));

        Flux::toast(variant: 'success', text: __('Business details saved.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <flux:heading size="xl" level="1">{{ __('Business details') }}</flux:heading>
    <flux:text class="mt-2">{{ __('These details appear on every page of the website.') }}</flux:text>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card class="space-y-5">
            <flux:heading size="lg">{{ __('Home page') }}</flux:heading>
            <flux:input wire:model="values.business_name" :label="__('Business name')" />
            <flux:input wire:model="values.hero_title" :label="__('Headline')" />
            <flux:textarea wire:model="values.hero_text" :label="__('Sub-headline')" rows="2" />
            <flux:textarea wire:model="values.rates_note" :label="__('Note under the rates')" rows="2" />
        </flux:card>

        <flux:card class="space-y-5">
            <flux:heading size="lg">{{ __('Contact') }}</flux:heading>
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="values.phone" :label="__('Phone number')" type="tel" />
                <flux:input wire:model="values.whatsapp" :label="__('WhatsApp number')" type="tel" />
            </div>
            <flux:input wire:model="values.email" :label="__('Email (optional)')" type="email" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="values.address_street" :label="__('Street address')" />
                <flux:input wire:model="values.address_area" :label="__('Area')" />
            </div>
            <flux:input wire:model="values.opening_hours" :label="__('Opening hours (optional)')" placeholder="Monday to Saturday, 8am to 6pm" />
            <flux:input wire:model="values.maps_url" :label="__('Google Maps link (optional)')" :description="__('Leave empty to search Google Maps for the address.')" placeholder="https://maps.app.goo.gl/..." />
        </flux:card>

        <flux:card class="space-y-5">
            <flux:heading size="lg">{{ __('Trust and social') }}</flux:heading>
            <flux:input wire:model="values.cac_number" :label="__('CAC registration number (optional)')" :description="__('Shown at the bottom of every page, e.g. BN 1234567.')" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input wire:model="values.facebook_url" :label="__('Facebook link (optional)')" placeholder="https://facebook.com/..." />
                <flux:input wire:model="values.instagram_url" :label="__('Instagram link (optional)')" placeholder="https://instagram.com/..." />
            </div>
        </flux:card>

        <flux:card class="space-y-5">
            <flux:heading size="lg">{{ __('Privacy policy') }}</flux:heading>
            <flux:textarea wire:model="values.privacy_policy" :label="__('Your own privacy policy (optional)')" :description="__('Leave empty to use the standard one. Use ## for headings.')" rows="8" />
        </flux:card>

        <flux:button variant="primary" type="submit" icon="check">{{ __('Save details') }}</flux:button>
    </form>
</section>
