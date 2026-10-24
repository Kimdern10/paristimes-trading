<?php

use App\Models\Currency;
use Livewire\Livewire;

test('the hero shows the first currency\'s sell rate as the big flip-board rate', function () {
    Currency::factory()->create(['code' => 'USD', 'symbol' => '$', 'buy_rate' => 1455, 'sell_rate' => 1470, 'sort_order' => 1]);
    Currency::factory()->create(['code' => 'GBP', 'symbol' => '£', 'buy_rate' => 1950, 'sell_rate' => 1975, 'sort_order' => 2]);

    Livewire::test('live-rates')
        ->assertSeeHtml('data-flip="₦1,470"')
        ->assertSeeHtml('data-code="USD" aria-selected="true"')
        ->assertSeeHtml('data-code="GBP" aria-selected="false"')
        ->assertSee('We buy at');
});

test('the hero checks for new rates every minute while it is on screen', function () {
    Currency::factory()->create();

    Livewire::test('live-rates')->assertSeeHtml('wire:poll.60s.visible');
});

test('new rates from the admin appear on the next check without reloading', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'buy_rate' => 1455, 'sell_rate' => 1470]);

    $component = Livewire::test('live-rates')->assertSeeHtml('data-flip="₦1,470"');

    $usd->update(['sell_rate' => 1490]);

    $component->call('$refresh')
        ->assertSeeHtml('data-flip="₦1,490"')
        ->assertDontSeeHtml('data-flip="₦1,470"');
});

test('the calculator gets the latest rates from the page', function () {
    Currency::factory()->create(['code' => 'EUR', 'buy_rate' => 1690, 'sell_rate' => 1712.5]);

    Livewire::test('live-rates')
        ->assertSeeHtml('id="live-rates-data"')
        ->assertSeeHtml('"code":"EUR"')
        ->assertSeeHtml('"sell":"1712.50"');
});

test('without any rates the hero shows the sub-headline instead of a big rate', function () {
    Currency::factory()->withoutRates()->create();

    Livewire::test('live-rates')
        ->assertDontSeeHtml('data-hero-rate')
        ->assertSee('Fast, secure, and trusted currency exchange services');
});

test('the home page includes the live rates', function () {
    Currency::factory()->create(['code' => 'USD', 'sell_rate' => 1470]);

    $this->get(route('home'))->assertSeeLivewire('live-rates');
});
