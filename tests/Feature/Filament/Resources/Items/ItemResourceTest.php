<?php

use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\Items\Pages\ViewItem;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows items in table and supports search', function () {
    $matchingItem = Item::factory()->create(['title' => 'Laptop for sale']);
    $otherItem = Item::factory()->create(['title' => 'Mountain bike']);

    livewire(ListItems::class)
        ->assertCanSeeTableRecords([$matchingItem, $otherItem])
        ->searchTable('Laptop')
        ->assertCanSeeTableRecords([$matchingItem])
        ->assertCanNotSeeTableRecords([$otherItem]);
});

it('can load the item view page with infolist data', function () {
    $item = Item::factory()->create([
        'title' => 'Laptop for sale',
        'body' => 'Good condition laptop.',
        'views' => 15,
        'phone_views' => 5,
        'email_views' => 3,
    ]);

    livewire(ViewItem::class, ['record' => $item->getRouteKey()])
        ->assertOk()
        ->assertSchemaStateSet([
            'title' => 'Laptop for sale',
            'body' => 'Good condition laptop.',
            'views' => 15,
            'phone_views' => 5,
            'email_views' => 3,
        ]);
});

it('can create an item from create page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    livewire(CreateItem::class)
        ->fillForm([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laptop for sale',
            'body' => 'Good condition laptop.',
            'contacts' => [
                ['name' => 'John', 'phone' => '+380971112233'],
            ],
            'price' => 250.50,
            'currency' => 'UAH',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('items', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Laptop for sale',
        'currency' => 'UAH',
        'price' => 25050,
    ]);
});

it('can update an item from edit page', function () {
    $item = Item::factory()->create(['title' => 'Old title']);
    $newCategory = Category::factory()->create();

    livewire(EditItem::class, ['record' => $item->getRouteKey()])
        ->fillForm([
            'category_id' => $newCategory->id,
            'title' => 'Updated title',
            'body' => 'Updated description',
            'contacts' => [
                ['name' => 'Alex', 'phone' => '+380501234567'],
            ],
            'price' => 123.45,
            'currency' => 'USD',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('items', [
        'id' => $item->id,
        'category_id' => $newCategory->id,
        'title' => 'Updated title',
        'currency' => 'USD',
        'price' => 12345,
    ]);
});

it('can soft delete an item from edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->getRouteKey()])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    assertSoftDeleted('items', [
        'id' => $item->id,
    ]);
});
