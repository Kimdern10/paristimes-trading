<?php

use App\Models\Currency;
use App\Models\Photo;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('every public page opens', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['home', 'about', 'services', 'contact', 'privacy']);

test('the home page shows the rates of shown currencies only', function () {
    Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar', 'buy_rate' => 1580, 'sell_rate' => 1600.5]);
    Currency::factory()->hidden()->create(['code' => 'CAD', 'name' => 'Canadian Dollar']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('US Dollar')
        ->assertSee('₦1,580')
        ->assertSee('₦1,600.50')
        ->assertDontSee('Canadian Dollar');
});

test('a currency without rates asks visitors to use WhatsApp', function () {
    Currency::factory()->withoutRates()->create(['code' => 'GBP']);

    $this->get(route('home'))->assertOk()->assertSee('Ask on WhatsApp');
});

test('the rates board says when rates were last updated', function () {
    Currency::factory()->create();
    $this->travelTo(now()->setTime(10, 42));
    Setting::put(['rates_updated_at' => now()->toIso8601String()]);

    $this->get(route('home'))->assertSee('Updated today, 10:42am');

    $this->travel(2)->days();

    $this->get(route('home'))->assertSee('Updated '.now()->subDays(2)->format('j M').', 10:42am');
});

test('the business details from the admin appear on the website', function () {
    Setting::put([
        'phone' => '0803 123 4567',
        'whatsapp' => '08091234567',
        'opening_hours' => 'Mon to Sat, 8am to 6pm',
        'cac_number' => 'BN 1234567',
    ]);

    $this->get(route('contact'))
        ->assertSee('0803 123 4567')
        ->assertSee('tel:+2348031234567', false)
        ->assertSee('https://wa.me/2348091234567', false)
        ->assertSee('Mon to Sat, 8am to 6pm')
        ->assertSee('Registered business: BN 1234567');
});

test('the owner\'s headline is escaped on the website', function () {
    Setting::put(['hero_title' => '<script>alert(1)</script> Foreign Currency Exchange']);

    $this->get(route('home'))
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; <span class="gold">Foreign Currency Exchange</span>', false);
});

test('shop photos appear on the about page', function () {
    Photo::factory()->create(['caption' => 'Our shop front']);

    $this->get(route('about'))->assertSee('Our shop front');
});

test('the privacy policy shows the owner\'s own text when saved', function () {
    Setting::put(['privacy_policy' => "## Our rules\n\nWe keep your data safe.\n\n<script>alert(1)</script>"]);

    $this->get(route('privacy'))
        ->assertSee('<h2>Our rules</h2>', false)
        ->assertSee('We keep your data safe.')
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('the sitemap lists the public pages', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('services'))
        ->assertSee(route('privacy'));
});

test('robots.txt points to the sitemap and hides the admin', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.route('sitemap'));
});

test('the owner sees links back to the admin on the website', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('home'))
        ->assertSee('class="owner-bar"', false)
        ->assertSee(route('dashboard'));
});

test('visitors and other users do not see the owner links', function (bool $loggedIn) {
    if ($loggedIn) {
        $this->actingAs(User::factory()->create());
    }

    $this->get(route('home'))->assertDontSee('class="owner-bar"', false);
})->with(['visitor' => false, 'user who is not the owner' => true]);

test('the home page loads business details once, not for every use', function () {
    Currency::factory()->count(3)->create();
    Setting::put(['phone' => '0803 123 4567']);

    DB::enableQueryLog();
    $this->get(route('home'))->assertOk()->assertSee('0803 123 4567');

    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(15);
});

test('unknown pages show the branded not found page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee("We couldn't find that page", false);
});
