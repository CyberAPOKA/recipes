<?php

use App\Models\Category;
use App\Services\CategoryService;

beforeEach(function () {
    $this->categoryService = new CategoryService();
});

test('can get all categories ordered by name', function () {
    Category::factory()->create(['name' => 'Sobremesas']);
    Category::factory()->create(['name' => 'Bebidas']);
    Category::factory()->create(['name' => 'Aperitivos']);

    $categories = $this->categoryService->getAllCategories();

    expect($categories)->toHaveCount(3)
        ->and($categories->first()->name)->toBe('Aperitivos')
        ->and($categories->last()->name)->toBe('Sobremesas');
});

test('returns empty collection when no categories exist', function () {
    $categories = $this->categoryService->getAllCategories();

    expect($categories)->toBeEmpty();
});

