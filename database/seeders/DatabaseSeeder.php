<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The owner's login is created with `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call(CurrencySeeder::class);
    }
}
