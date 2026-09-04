<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads parent and children relationships', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->contains($child))->toBeTrue();
});

it('loads ancestors recursively', function () {
    $root = Category::factory()->create(['name' => 'Root']);
    $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);
    $grandchild = Category::factory()->create(['name' => 'Grandchild', 'parent_id' => $child->id]);

    $grandchild->load('ancestors');

    expect($grandchild->ancestors)->not->toBeNull()
        ->and($grandchild->ancestors->is($child))->toBeTrue()
        ->and($grandchild->ancestors->ancestors->is($root))->toBeTrue();
});

it('loads descendants recursively', function () {
    $root = Category::factory()->create(['name' => 'Root']);
    $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);
    $grandchild = Category::factory()->create(['name' => 'Grandchild', 'parent_id' => $child->id]);

    $root->load('descendants');

    expect($root->descendants->contains($child))->toBeTrue()
        ->and($root->descendants->first()->descendants->contains($grandchild))->toBeTrue();
});
