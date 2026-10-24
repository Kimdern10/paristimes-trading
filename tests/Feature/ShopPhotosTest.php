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

test('an uploaded photo is saved as large and small WebP copies', function () {
    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->image('shop.jpg', 2400, 1800))
        ->set('caption', 'Our shop front')
        ->call('addPhoto')
        ->assertHasNoErrors();

    $photo = Photo::query()->sole();

    expect($photo->caption)->toBe('Our shop front')
        ->and($photo->path)->toEndWith('.webp');

    Storage::disk('public')->assertExists([$photo->path, $photo->smallPath()]);

    expect(getimagesizefromstring(Storage::disk('public')->get($photo->path))[0])->toBe(1600)
        ->and(getimagesizefromstring(Storage::disk('public')->get($photo->smallPath()))[0])->toBe(800);
});

test('the form saves through addPhoto, not Livewire\'s own upload command', function () {
    Livewire::test('pages::admin.photos')
        ->assertSeeHtml('wire:submit="addPhoto"')
        ->assertDontSeeHtml('wire:submit="upload"');
});

test('files that are not images are rejected', function () {
    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->create('rates.pdf', 100, 'application/pdf'))
        ->call('addPhoto')
        ->assertHasErrors(['photo']);

    expect(Photo::query()->count())->toBe(0);
});

test('a new photo and a photo moved first are shown first', function () {
    $older = Photo::factory()->create(['sort_order' => 0]);

    Livewire::test('pages::admin.photos')
        ->set('photo', UploadedFile::fake()->image('new.jpg'))
        ->call('addPhoto');

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
