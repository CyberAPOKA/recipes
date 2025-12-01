<?php

use App\Models\Category;

test('can get all categories', function () {
    Category::factory()->count(3)->create();

    $response = $this->getJson('/api/categories');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'created_at', 'updated_at'],
            ],
        ]);

    expect($response->json('data'))->toHaveCount(3);
});

test('categories are returned ordered by name', function () {
    Category::factory()->create(['name' => 'Sobremesas']);
    Category::factory()->create(['name' => 'Bebidas']);
    Category::factory()->create(['name' => 'Aperitivos']);

    $response = $this->getJson('/api/categories');

    $response->assertStatus(200);
    $categories = $response->json('data');
    
    expect($categories[0]['name'])->toBe('Aperitivos')
        ->and($categories[1]['name'])->toBe('Bebidas')
        ->and($categories[2]['name'])->toBe('Sobremesas');
});

