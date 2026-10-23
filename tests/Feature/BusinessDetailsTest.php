<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

test('the owner can update the business details', function () {
    Livewire::test('pages::admin.business')
        ->set('values.phone', '0803 123 4567')
        ->set('values.opening_hours', ' Mon to Sat, 8am to 6pm ')
        ->set('values.maps_url', 'https://maps.app.goo.gl/abc')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('phone'))->toBe('0803 123 4567')
        ->and(Setting::get('opening_hours'))->toBe('Mon to Sat, 8am to 6pm')
        ->and(Setting::mapsUrl())->toBe('https://maps.app.goo.gl/abc');
});

test('invalid business details are rejected', function (string $field, string $value) {
    Livewire::test('pages::admin.business')
        ->set("values.{$field}", $value)
        ->call('save')
        ->assertHasErrors(["values.{$field}"]);

    expect(Setting::query()->count())->toBe(0);
})->with([
    'phone with letters' => ['phone', '0814-CALL-ME'],
    'empty WhatsApp' => ['whatsapp', ''],
    'email' => ['email', 'not-an-email'],
    'maps link that is not https' => ['maps_url', 'javascript:alert(1)'],
    'empty business name' => ['business_name', ''],
]);

test('WhatsApp numbers are turned into international wa.me links', function (string $saved) {
    Setting::put(['whatsapp' => $saved]);

    expect(Setting::whatsappUrl('Hi there'))->toBe('https://wa.me/2348146552236?text=Hi%20there');
})->with([
    'local number' => '0814 655 2236',
    'already international' => '+234 814 655 2236',
]);
