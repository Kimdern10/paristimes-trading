<?php

use App\Enums\TransactionType;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

/**
 * Make a CSV upload from lines of text.
 *
 * @param  list<string>  $lines
 */
function csvUpload(array $lines): UploadedFile
{
    return UploadedFile::fake()->createWithContent('deals.csv', implode("\n", $lines)."\n");
}

test('previous deals are imported from a spreadsheet', function () {
    Currency::factory()->create(['code' => 'GBP', 'buy_rate' => 1950, 'sell_rate' => 1975]);

    Livewire::test('pages::admin.transactions')
        ->set('csv', csvUpload([
            "\u{FEFF}Date,Type,Currency,Amount,Rate,Customer,Note,Show",
            '20/10/2026,sell,usd,"1,500",1470,Mr Ade,Transfer,yes',
            '2026-10-21,Bought,GBP,200,,Mrs Bisi,,no',
        ]))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('importErrors', []);

    $deals = Transaction::query()->orderBy('traded_on')->get();

    expect($deals)->toHaveCount(2)
        ->and($deals[0])
        ->traded_on->toDateString()->toBe('2026-10-20')
        ->type->toBe(TransactionType::Sell)
        ->currency_code->toBe('USD')
        ->naira_total->toBe('2205000.00')
        ->is_public->toBeTrue()
        ->and($deals[1])
        ->type->toBe(TransactionType::Buy)
        ->rate->toBe('1950.00')
        ->is_public->toBeFalse();
});

test('nothing is imported when any row is wrong', function () {
    Livewire::test('pages::admin.transactions')
        ->set('csv', csvUpload([
            'date,type,currency,amount,rate',
            '20/10/2026,sell,USD,100,1470',
            '31/02/2026,swap,DOLLARS,-5,abc',
            '20/10/2026,sell,CAD,100,',
        ]))
        ->call('import')
        ->assertSet('importErrors', [
            'Row 3: the date should look like 24/10/2026, the type should be "buy" or "sell", the currency should be a 3-letter code like USD, the amount must be a number above 0, the rate must be a number above 0.',
            'Row 4: there is no rate, and no saved rate for this currency.',
        ]);

    expect(Transaction::query()->count())->toBe(0);
});

test('a file without the needed columns is rejected', function () {
    $component = Livewire::test('pages::admin.transactions')
        ->set('csv', csvUpload(['name,phone', 'Ade,0803']))
        ->call('import');

    expect($component->get('importErrors')[0])->toContain('must have a "date" column')
        ->and(Transaction::query()->count())->toBe(0);
});

test('semicolon separated files from Excel are read', function () {
    Livewire::test('pages::admin.transactions')
        ->set('csv', csvUpload(['date;type;currency;amount;rate', '20/10/2026;sell;EUR;100;1712.50']))
        ->call('import')
        ->assertSet('importErrors', []);

    expect(Transaction::query()->sole()->naira_total)->toBe('171250.00');
});

test('the template can be downloaded', function () {
    Livewire::test('pages::admin.transactions')
        ->call('downloadTemplate')
        ->assertFileDownloaded('transactions-template.csv');
});
