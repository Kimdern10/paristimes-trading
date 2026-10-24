<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\TransactionReceiptController;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('about', 'about')->name('about');
    Route::get('services', 'services')->name('services');
    Route::get('contact', 'contact')->name('contact');
    Route::get('privacy-policy', 'privacy')->name('privacy');
    Route::get('sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('robots.txt', 'robots')->name('robots');
});

Route::get('deals/{transaction}/receipt', [TransactionReceiptController::class, 'public'])->name('deals.receipt');

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.rates')->name('dashboard');
    Route::livewire('photos', 'pages::admin.photos')->name('admin.photos');
    Route::livewire('business', 'pages::admin.business')->name('admin.business');
    Route::livewire('transactions', 'pages::admin.transactions')->name('admin.transactions');
    Route::livewire('reviews', 'pages::admin.reviews')->name('admin.reviews');
    Route::livewire('faqs', 'pages::admin.faqs')->name('admin.faqs');
    Route::get('transactions/{transaction}/receipt', TransactionReceiptController::class)->name('admin.transactions.receipt');
});

require __DIR__.'/settings.php';
