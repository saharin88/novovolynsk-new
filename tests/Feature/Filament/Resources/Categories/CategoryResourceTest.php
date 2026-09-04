<?php

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(User::factory()->admin()->create());

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

describe('List Categories Page', function () {
    it('renders category list table with required columns', function () {
        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertCanRenderTableColumn('name')
            ->toggleAllTableColumns()
            ->assertCanRenderTableColumn('created_at')
            ->assertCanRenderTableColumn('updated_at');
    });

    it('shows category name in the table and can search', function () {
        $category = Category::factory()->create(['name' => 'Technology']);
        $otherCategory = Category::factory()->create(['name' => 'Science']);

        livewire(ListCategories::class)
            ->assertCanSeeTableRecords([$category, $otherCategory])
            ->assertTableColumnStateSet('name', 'Technology', record: $category)
            ->searchTable('Tech')
            ->assertCanSeeTableRecords([$category])
            ->assertCanNotSeeTableRecords([$otherCategory]);
    });

    it('can soft delete a category via table action', function () {
        $category = Category::factory()->create();

        livewire(ListCategories::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($category))
            ->assertNotified();

        assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    });

    it('filters trashed categories and can restore them via table action', function () {
        $activeCategory = Category::factory()->create(['name' => 'Active']);
        $trashedCategory = Category::factory()->create(['name' => 'Trashed']);
        $trashedCategory->delete();

        livewire(ListCategories::class)
            ->assertCanSeeTableRecords([$activeCategory])
            ->assertCanNotSeeTableRecords([$trashedCategory])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$activeCategory, $trashedCategory])
            ->callAction(TestAction::make(RestoreAction::class)->table($trashedCategory))
            ->assertNotified();

        assertDatabaseHas('categories', [
            'id' => $trashedCategory->id,
            'deleted_at' => null,
        ]);
    });

    it('can force delete a soft deleted category via table action', function () {
        $category = Category::factory()->create();
        $category->delete();

        livewire(ListCategories::class)
            ->filterTable('trashed', true)
            ->callAction(TestAction::make(ForceDeleteAction::class)->table($category))
            ->assertNotified();

        assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    });

    it('can soft delete categories using bulk delete action', function () {
        $categories = Category::factory()->count(3)->create();

        livewire(ListCategories::class)
            ->selectTableRecords($categories->pluck('id'))
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk())
            ->assertNotified();

        foreach ($categories as $category) {
            assertSoftDeleted('categories', ['id' => $category->id]);
        }
    });
});

describe('Create Category Page', function () {
    it('can create a category from create page', function () {
        livewire(CreateCategory::class)
            ->assertOk()
            ->fillForm([
                'name' => 'Culture',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        assertDatabaseHas('categories', [
            'name' => 'Culture',
        ]);
    });

    it('cannot create a category without a name', function () {
        livewire(CreateCategory::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required'])
            ->assertNotNotified();
    });

    it('cannot create a category with a duplicate name', function () {
        Category::factory()->create(['name' => 'Existing Category']);

        livewire(CreateCategory::class)
            ->fillForm([
                'name' => 'Existing Category',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique'])
            ->assertNotNotified();
    });

    it('can create a subcategory with a parent category', function () {
        $parentCategory = Category::factory()->create();

        livewire(CreateCategory::class)
            ->fillForm([
                'name' => 'Child Category',
                'parent_id' => $parentCategory->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        assertDatabaseHas('categories', [
            'name' => 'Child Category',
            'parent_id' => $parentCategory->id,
        ]);
    });
});

describe('Edit Category Page', function () {
    it('can edit a category from edit page', function () {
        $category = Category::factory()->create([
            'name' => 'Old category',
        ]);

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertOk()
            ->fillForm([
                'name' => 'Updated category',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated category',
        ]);
    });

    it('can delete a category from edit page header action', function () {
        $category = Category::factory()->create();

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified();

        assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    });

    it('can assign a parent category from edit page', function () {
        $category = Category::factory()->create();
        $parentCategory = Category::factory()->create();

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm([
                'parent_id' => $parentCategory->id,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        assertDatabaseHas('categories', [
            'id' => $category->id,
            'parent_id' => $parentCategory->id,
        ]);
    });

    it('can remove parent category from edit page', function () {
        $parentCategory = Category::factory()->create();
        $childCategory = Category::factory()->create([
            'parent_id' => $parentCategory->id,
        ]);

        livewire(EditCategory::class, ['record' => $childCategory->getRouteKey()])
            ->fillForm([
                'parent_id' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        assertDatabaseHas('categories', [
            'id' => $childCategory->id,
            'parent_id' => null,
        ]);
    });
});
