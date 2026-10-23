<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Add the currencies the business trades. Rates are left empty so the
     * website never shows a number the owner has not entered.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'sort_order' => 1],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'sort_order' => 2],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'sort_order' => 3],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->firstOrCreate(['code' => $currency['code']], $currency);
        }
    }
}
