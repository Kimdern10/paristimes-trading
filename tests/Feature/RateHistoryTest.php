<?php

use App\Models\Currency;
use App\Models\RateSnapshot;
use App\Models\User;
use App\Support\RatesMessage;
use Livewire\Livewire;

test('saving rates records one history entry per currency per day', function () {
    $this->actingAs(User::factory()->admin()->create());
    $usd = Currency::factory()->create(['code' => 'USD', 'buy_rate' => 1450, 'sell_rate' => 1470]);
    Currency::factory()->withoutRates()->create(['code' => 'GBP']);

    Livewire::test('pages::admin.rates')->call('save');
    Livewire::test('pages::admin.rates')
        ->set("rates.{$usd->id}.sell", '1480')
        ->call('save');

    expect(RateSnapshot::query()->sole())
        ->currency_code->toBe('USD')
        ->recorded_on->toDateString()->toBe(now()->toDateString())
        ->sell_rate->toBe('1480.00');
});

test('the home page shows the rate history once there are two days of rates', function () {
    Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar']);
    RateSnapshot::factory()->create(['recorded_on' => now()->subDays(7)->toDateString(), 'buy_rate' => 1400, 'sell_rate' => 1400]);

    $this->get(route('home'))->assertDontSee('How the rates moved');

    RateSnapshot::factory()->create(['recorded_on' => now()->toDateString(), 'buy_rate' => 1450, 'sell_rate' => 1470]);

    $this->get(route('home'))
        ->assertSee('How the rates moved')
        ->assertSee('US Dollar sell rate')
        ->assertSee('₦1,470')
        ->assertSee('5% since', false);
});

test('history older than the chart period is left out', function () {
    Currency::factory()->create(['code' => 'USD']);
    RateSnapshot::factory()->create(['recorded_on' => now()->subDays(60)->toDateString()]);
    RateSnapshot::factory()->create(['recorded_on' => now()->toDateString()]);

    $this->get(route('home'))->assertDontSee('How the rates moved');
});

test('the WhatsApp message lists today\'s rates and contact details', function () {
    Currency::factory()->create(['code' => 'USD', 'buy_rate' => 1455, 'sell_rate' => 1470, 'sort_order' => 1]);
    Currency::factory()->withoutRates()->create(['code' => 'GBP']);
    Currency::factory()->hidden()->create(['code' => 'CAD']);

    $message = RatesMessage::text();

    expect($message)
        ->toContain('USD: Buy ₦1,455 | Sell ₦1,470')
        ->toContain('41 Ashogbon Street')
        ->toContain('https://wa.me/2348146552236')
        ->not->toContain('GBP')
        ->not->toContain('CAD');
});

test('the rates page shows the WhatsApp message to copy', function () {
    $this->actingAs(User::factory()->admin()->create());
    Currency::factory()->create(['code' => 'USD', 'buy_rate' => 1455, 'sell_rate' => 1470]);

    Livewire::test('pages::admin.rates')
        ->assertSee('Share today&#039;s rates on WhatsApp', false)
        ->assertSee('USD: Buy ₦1,455 | Sell ₦1,470');
});
