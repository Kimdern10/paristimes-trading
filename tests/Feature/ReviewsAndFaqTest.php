<?php

use App\Models\Faq;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

test('the owner can add a review', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.reviews')
        ->set('name', 'Mrs Bisi A.')
        ->set('detail', 'Trader, Idumota')
        ->set('rating', 5)
        ->set('body', 'Fast and honest service.')
        ->call('save')
        ->assertHasNoErrors();

    expect(Review::query()->sole())
        ->name->toBe('Mrs Bisi A.')
        ->rating->toBe(5)
        ->is_published->toBeTrue();
});

test('a review needs a name, text and 1 to 5 stars', function (string $field, mixed $value) {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.reviews')
        ->set('name', 'Ade')
        ->set('body', 'Good service.')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field]);

    expect(Review::query()->count())->toBe(0);
})->with([
    'no name' => ['name', ''],
    'no text' => ['body', ''],
    'six stars' => ['rating', 6],
]);

test('the owner can edit, hide and delete reviews', function () {
    $this->actingAs(User::factory()->admin()->create());
    $review = Review::factory()->create(['body' => 'Old text']);

    Livewire::test('pages::admin.reviews')
        ->call('edit', $review->id)
        ->set('body', 'New text')
        ->call('save')
        ->call('togglePublished', $review->id);

    expect($review->fresh())->body->toBe('New text')->is_published->toBeFalse();

    Livewire::test('pages::admin.reviews')->call('delete', $review->id);

    expect($review->fresh())->toBeNull();
});

test('only published reviews show on the home page', function () {
    Review::factory()->create(['name' => 'Mrs Bisi', 'body' => 'Fast and <b>honest</b>.']);
    Review::factory()->hidden()->create(['name' => 'Mr Hidden']);

    $this->get(route('home'))
        ->assertSee('What our customers say')
        ->assertSee('Mrs Bisi')
        ->assertSee('Fast and &lt;b&gt;honest&lt;/b&gt;.', false)
        ->assertDontSee('Mr Hidden');
});

test('the reviews section is hidden until there is a published review', function () {
    $this->get(route('home'))->assertDontSee('What our customers say');
});

test('the owner can add a question at the end of the list', function () {
    $this->actingAs(User::factory()->admin()->create());
    Faq::factory()->create(['sort_order' => 4]);

    Livewire::test('pages::admin.faqs')
        ->set('question', 'Do you open on Sundays?')
        ->set('answer', 'No, we open Monday to Saturday.')
        ->call('save')
        ->assertHasNoErrors();

    expect(Faq::query()->latest('id')->first())
        ->question->toBe('Do you open on Sundays?')
        ->sort_order->toBe(5);
});

test('the owner can move a question up', function () {
    $this->actingAs(User::factory()->admin()->create());
    $first = Faq::factory()->create(['sort_order' => 1]);
    $second = Faq::factory()->create(['sort_order' => 2]);

    Livewire::test('pages::admin.faqs')->call('move', $second->id, -1);

    expect(Faq::query()->published()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('published questions show on the home and contact pages', function (string $route) {
    Faq::factory()->create(['question' => 'Can I pay online?']);
    Faq::factory()->hidden()->create(['question' => 'Secret question?']);

    $this->get(route($route))
        ->assertSee('Can I pay online?')
        ->assertDontSee('Secret question?');
})->with(['home', 'contact']);

test('the contact page loads the map only when asked', function () {
    $this->get(route('contact'))
        ->assertSee('Show map')
        ->assertSee('data-map="https://www.google.com/maps?q=41%20Ashogbon%20Street', false)
        ->assertDontSee('<iframe', false);
});

test('site pages link to each other with Livewire navigation', function () {
    $this->get(route('home'))
        ->assertSee('href="'.route('about').'" wire:navigate.hover', false);
});
