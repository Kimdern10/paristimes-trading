<?php

use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the owner can save today\'s rates', function () {
    $this->freezeSecond();
    $usd = Currency::factory()->withoutRates()->create(['code' => 'USD']);
    $gbp = Currency::factory()->create(['code' => 'GBP']);

    Livewire::test('pages::admin.rates')
        ->set("rates.{$usd->id}.buy", '1580')
        ->set("rates.{$usd->id}.sell", '1600.50')
        ->set("rates.{$gbp->id}.buy", '')
        ->set("rates.{$gbp->id}.sell", '')
        ->set("rates.{$gbp->id}.active", false)
        ->call('save')
        ->assertHasNoErrors();

    expect($usd->fresh())
        ->buy_rate->toBe('1580.00')
        ->sell_rate->toBe('1600.50')
        ->and($gbp->fresh())
        ->buy_rate->toBeNull()
        ->sell_rate->toBeNull()
        ->is_active->toBeFalse()
        ->and(Setting::ratesUpdatedAt()->equalTo(now()))->toBeTrue();
});

test('a sell rate lower than the buy rate is rejected', function () {
    $usd = Currency::factory()->create(['buy_rate' => 1500, 'sell_rate' => 1520]);

    Livewire::test('pages::admin.rates')
        ->set("rates.{$usd->id}.buy", '1600')
        ->set("rates.{$usd->id}.sell", '1590')
        ->call('save')
        ->assertHasErrors(["rates.{$usd->id}.sell"]);

    expect($usd->fresh()->buy_rate)->toBe('1500.00')
        ->and(Setting::ratesUpdatedAt())->toBeNull();
});

test('rates must be positive numbers', function (string $value) {
    $usd = Currency::factory()->create();

    Livewire::test('pages::admin.rates')
        ->set("rates.{$usd->id}.buy", $value)
        ->call('save')
        ->assertHasErrors(["rates.{$usd->id}.buy"]);
})->with(['-5', 'abc']);

test('the owner can add a currency', function () {
    Livewire::test('pages::admin.rates')
        ->set('code', 'cad')
        ->set('name', 'Canadian Dollar')
        ->set('symbol', '$')
        ->call('addCurrency')
        ->assertHasNoErrors();

    expect(Currency::query()->where('code', 'CAD')->first())
        ->name->toBe('Canadian Dollar')
        ->buy_rate->toBeNull()
        ->is_active->toBeTrue();
});

test('a currency code can only be added once', function () {
    Currency::factory()->create(['code' => 'USD']);

    Livewire::test('pages::admin.rates')
        ->set('code', 'usd')
        ->set('name', 'US Dollar again')
        ->call('addCurrency')
        ->assertHasErrors(['code' => 'unique']);

    expect(Currency::query()->count())->toBe(1);
});

test('the owner can remove a currency', function () {
    $usd = Currency::factory()->create();

    Livewire::test('pages::admin.rates')->call('deleteCurrency', $usd->id);

    expect($usd->fresh())->toBeNull();
});
