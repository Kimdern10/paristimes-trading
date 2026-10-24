<?php

use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake(Transaction::RECEIPT_DISK);
    $this->actingAs(User::factory()->admin()->create());
});

test('the form starts with the saved rate for the chosen currency and deal', function () {
    Currency::factory()->create(['code' => 'USD', 'buy_rate' => 1455, 'sell_rate' => 1470, 'sort_order' => 1]);
    Currency::factory()->create(['code' => 'GBP', 'buy_rate' => 1950, 'sell_rate' => 1975, 'sort_order' => 2]);

    Livewire::test('pages::admin.transactions')
        ->assertSet('currency_code', 'USD')
        ->assertSet('rate', '1470.00')
        ->set('type', 'buy')
        ->assertSet('rate', '1455.00')
        ->set('currency_code', 'GBP')
        ->assertSet('rate', '1950.00');
});

test('the owner can record a deal and the naira total is worked out', function () {
    Livewire::test('pages::admin.transactions')
        ->set('traded_on', '2026-10-20')
        ->set('type', 'sell')
        ->set('currency_code', 'usd')
        ->set('amount', '500')
        ->set('rate', '1470.50')
        ->assertSee('₦735,250')
        ->set('customer_name', 'Mr Ade')
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::query()->sole())
        ->type->toBe(TransactionType::Sell)
        ->currency_code->toBe('USD')
        ->naira_total->toBe('735250.00')
        ->customer_name->toBe('Mr Ade')
        ->is_public->toBeTrue();
});

test('invalid deals are rejected', function (string $field, string $value) {
    Livewire::test('pages::admin.transactions')
        ->set('currency_code', 'USD')
        ->set('amount', '100')
        ->set('rate', '1500')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field]);

    expect(Transaction::query()->count())->toBe(0);
})->with([
    'future date' => ['traded_on', fn () => now()->addDay()->toDateString()],
    'zero amount' => ['amount', '0'],
    'rate with letters' => ['rate', 'abc'],
    'unknown deal type' => ['type', 'swap'],
]);

test('a receipt photo is kept privately with the deal', function () {
    Livewire::test('pages::admin.transactions')
        ->set('currency_code', 'USD')
        ->set('amount', '100')
        ->set('rate', '1500')
        ->set('receipt', UploadedFile::fake()->image('receipt.jpg', 1200, 1600))
        ->call('save')
        ->assertHasNoErrors();

    $transaction = Transaction::query()->sole();

    expect($transaction->receipt_path)->toStartWith('receipts/')->toEndWith('.webp');
    Storage::disk(Transaction::RECEIPT_DISK)->assertExists($transaction->receipt_path);

    $this->get(route('admin.transactions.receipt', $transaction))->assertOk();
});

test('a PDF receipt is stored as it is', function () {
    Livewire::test('pages::admin.transactions')
        ->set('currency_code', 'USD')
        ->set('amount', '100')
        ->set('rate', '1500')
        ->set('receipt', UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Transaction::query()->sole()->receipt_path)->toEndWith('.pdf');
});

test('receipts can only be opened by the owner', function () {
    $transaction = Transaction::factory()->create(['receipt_path' => 'receipts/r.webp']);
    Storage::disk(Transaction::RECEIPT_DISK)->put('receipts/r.webp', 'image');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.transactions.receipt', $transaction))
        ->assertForbidden();

    auth()->logout();

    $this->get(route('admin.transactions.receipt', $transaction))->assertRedirect(route('login'));
});

test('the owner can edit a deal', function () {
    $transaction = Transaction::factory()->create(['amount' => 100, 'rate' => 1500, 'naira_total' => 150000]);

    Livewire::test('pages::admin.transactions')
        ->call('edit', $transaction->id)
        ->assertSet('amount', '100.00')
        ->set('amount', '200')
        ->call('save')
        ->assertHasNoErrors();

    expect($transaction->fresh()->naira_total)->toBe('300000.00')
        ->and(Transaction::query()->count())->toBe(1);
});

test('deleting a deal also deletes its receipt', function () {
    $transaction = Transaction::factory()->create(['receipt_path' => 'receipts/r.webp']);
    Storage::disk(Transaction::RECEIPT_DISK)->put('receipts/r.webp', 'image');

    Livewire::test('pages::admin.transactions')->call('delete', $transaction->id);

    expect($transaction->fresh())->toBeNull();
    Storage::disk(Transaction::RECEIPT_DISK)->assertMissing('receipts/r.webp');
});

test('the owner can hide a deal from the website', function () {
    $transaction = Transaction::factory()->create();

    Livewire::test('pages::admin.transactions')->call('togglePublic', $transaction->id);

    expect($transaction->fresh()->is_public)->toBeFalse();
});

test('the list can be searched by customer', function () {
    Transaction::factory()->create(['customer_name' => 'Mrs Bisi']);
    Transaction::factory()->create(['customer_name' => 'Mr Chidi']);

    Livewire::test('pages::admin.transactions')
        ->set('search', 'Bisi')
        ->assertSee('Mrs Bisi')
        ->assertDontSee('Mr Chidi');
});

test('a new deal shows in the list even if a search was typed', function () {
    Livewire::test('pages::admin.transactions')
        ->set('search', 'Aut error porro')
        ->set('typeFilter', 'sell')
        ->set('type', 'buy')
        ->set('currency_code', 'USD')
        ->set('amount', '9800')
        ->set('rate', '1450')
        ->set('customer_name', 'Shay Walton')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('search', '')
        ->assertSet('typeFilter', '')
        ->assertSee('Shay Walton');
});

test('an empty search result offers to clear the search', function () {
    Transaction::factory()->create(['customer_name' => 'Mr Ade']);

    Livewire::test('pages::admin.transactions')
        ->set('search', 'nobody')
        ->assertSee('No transactions match your search')
        ->call('clearFilters')
        ->assertSee('Mr Ade');
});

test('receipt pictures show as thumbnails in the list', function () {
    $transaction = Transaction::factory()->create(['receipt_path' => 'receipts/r.webp']);
    Storage::disk(Transaction::RECEIPT_DISK)->put('receipts/r.webp', 'large');
    Storage::disk(Transaction::RECEIPT_DISK)->put('receipts/r-sm.webp', 'small');

    Livewire::test('pages::admin.transactions')
        ->assertSee(route('admin.transactions.receipt', ['transaction' => $transaction, 'size' => 'small']), false);

    expect($this->get(route('admin.transactions.receipt', ['transaction' => $transaction, 'size' => 'small']))->streamedContent())->toBe('small')
        ->and($this->get(route('admin.transactions.receipt', $transaction))->streamedContent())->toBe('large');
});

test('visitors can see a receipt only when the owner shows it', function (bool $dealPublic, bool $receiptPublic, bool $visible) {
    auth()->logout();
    $transaction = Transaction::factory()->create([
        'is_public' => $dealPublic,
        'receipt_is_public' => $receiptPublic,
        'receipt_path' => 'receipts/r.webp',
    ]);
    Storage::disk(Transaction::RECEIPT_DISK)->put('receipts/r.webp', 'large');

    $response = $this->get(route('deals.receipt', $transaction));
    $visible ? $response->assertOk() : $response->assertNotFound();

    $home = $this->get(route('home'));
    $visible
        ? $home->assertSee(route('deals.receipt', $transaction))->assertSee('View receipt')
        : $home->assertDontSee(route('deals.receipt', $transaction));
})->with([
    'deal and receipt shown' => [true, true, true],
    'receipt kept private' => [true, false, false],
    'deal hidden from website' => [false, true, false],
]);

test('the owner can switch on a receipt for visitors', function () {
    $transaction = Transaction::factory()->create(['receipt_path' => 'receipts/r.webp']);

    Livewire::test('pages::admin.transactions')
        ->call('edit', $transaction->id)
        ->assertSet('receipt_is_public', false)
        ->set('receipt_is_public', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($transaction->fresh()->receipt_is_public)->toBeTrue();
});

test('a missing receipt file gives not found', function () {
    $transaction = Transaction::factory()->create(['receipt_path' => 'receipts/gone.webp']);

    $this->get(route('admin.transactions.receipt', $transaction))->assertNotFound();
});

test('the home page shows public deals and totals without customer names', function () {
    Transaction::factory()->create(['type' => 'sell', 'currency_code' => 'USD', 'amount' => 2500, 'rate' => 1470, 'naira_total' => 3675000, 'customer_name' => 'Mrs Bisi']);
    Transaction::factory()->private()->create(['currency_code' => 'GBP', 'amount' => 999, 'naira_total' => 1000000]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Recent deals')
        ->assertSee('2,500')
        ->assertSee('at ₦1,470')
        ->assertSee('₦3,675,000')
        ->assertDontSee('Mrs Bisi')
        ->assertDontSee('999');
});

test('the recent deals section is hidden until there are public deals', function () {
    Transaction::factory()->private()->create();

    $this->get(route('home'))->assertDontSee('Recent deals');
});
