<?php

use App\Http\Controllers\PageController;
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

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.rates')->name('dashboard');
    Route::livewire('photos', 'pages::admin.photos')->name('admin.photos');
    Route::livewire('business', 'pages::admin.business')->name('admin.business');
});

require __DIR__.'/settings.php';
