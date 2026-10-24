<?php

use App\Models\Photo;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
});

test('choosing a photo saves it straight away as large and small WebP copies', function () {
    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->image('shop.jpg', 2400, 1800))
        ->assertHasNoErrors()
        ->assertSet('photo', null);

    $photo = Photo::query()->sole();

    expect($photo->path)->toEndWith('.webp');

    Storage::disk('public')->assertExists([$photo->path, $photo->smallPath()]);

    expect(getimagesizefromstring(Storage::disk('public')->get($photo->path))[0])->toBe(1600)
        ->and(getimagesizefromstring(Storage::disk('public')->get($photo->smallPath()))[0])->toBe(800);
});

test('the page does not use Livewire\'s reserved "upload" name for an action', function () {
    Livewire::test('pages::admin.photos')
        ->assertDontSeeHtml('wire:submit="upload"')
        ->assertDontSeeHtml('wire:click="upload"');
});

test('a caption is saved for a photo', function () {
    $photo = Photo::factory()->create(['caption' => '']);

    Livewire::test('pages::admin.photos')
        ->set("captions.{$photo->id}", ' Our shop front ')
        ->assertHasNoErrors();

    expect($photo->fresh()->caption)->toBe('Our shop front');
});

test('files that are not images are rejected', function () {
    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->create('rates.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['photo']);

    expect(Photo::query()->count())->toBe(0);
});

test('a new photo and a photo moved first are shown first', function () {
    $older = Photo::factory()->create(['sort_order' => 0]);

    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->image('new.jpg'));

    $newest = Photo::query()->latest('id')->first();

    expect(Photo::query()->orderBy('sort_order')->first()->is($newest))->toBeTrue();

    Livewire::test('pages::admin.photos')->call('makeFirst', $older->id);

    expect(Photo::query()->orderBy('sort_order')->first()->is($older))->toBeTrue()
        ->and(Photo::query()->min('sort_order'))->toBe(0);
});

test('deleting a photo removes its files', function () {
    $photo = Photo::factory()->create();
    Storage::disk('public')->put($photo->path, 'large');
    Storage::disk('public')->put($photo->smallPath(), 'small');

    Livewire::test('pages::admin.photos')->call('delete', $photo->id);

    expect($photo->fresh())->toBeNull();
    Storage::disk('public')->assertMissing([$photo->path, $photo->smallPath()]);
});
