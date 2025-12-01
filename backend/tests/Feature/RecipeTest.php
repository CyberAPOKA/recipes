<?php

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
    $this->category = Category::factory()->create();
});

test('authenticated user can create a recipe', function () {
    $recipeData = [
        'name' => 'Bolo de Chocolate',
        'category_id' => $this->category->id,
        'prep_time_minutes' => 30,
        'servings' => 8,
        'instructions' => 'Misture todos os ingredientes e asse por 30 minutos.',
        'ingredients' => '2 xícaras de farinha, 3 ovos, 1 xícara de açúcar',
    ];

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/recipes', $recipeData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'name', 'instructions', 'ingredients'],
        ]);

    $this->assertDatabaseHas('recipes', [
        'name' => 'Bolo de Chocolate',
        'user_id' => $this->user->id,
    ]);
});

test('unauthenticated user cannot create a recipe', function () {
    $response = $this->postJson('/api/recipes', [
        'name' => 'Bolo de Chocolate',
        'instructions' => 'Test',
    ]);

    $response->assertStatus(401);
});

test('authenticated user can get their recipes', function () {
    Recipe::factory()->count(3)->create(['user_id' => $this->user->id]);
    Recipe::factory()->count(2)->create(); // Other user's recipes

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/recipes?my_recipes=true');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'user_id'],
            ],
            'meta',
        ]);

    expect($response->json('data'))->toHaveCount(3);
    
    // Verify all returned recipes belong to the authenticated user
    foreach ($response->json('data') as $recipe) {
        expect($recipe['user_id'])->toBe($this->user->id);
    }
});

test('authenticated user can get a specific recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/recipes/{$recipe->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'instructions'],
        ])
        ->assertJson([
            'data' => [
                'id' => $recipe->id,
            ],
        ]);
});

test('authenticated user cannot get other users recipe', function () {
    $otherUser = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/recipes/{$recipe->id}");

    $response->assertStatus(404);
});

test('authenticated user can update their recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->putJson("/api/recipes/{$recipe->id}", [
            'name' => 'Updated Recipe Name',
            'instructions' => $recipe->instructions,
        ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Recipe updated successfully']);

    $this->assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name' => 'Updated Recipe Name',
    ]);
});

test('authenticated user can delete their recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/recipes/{$recipe->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Recipe deleted successfully']);

    $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
});

test('public can view public recipes', function () {
    Recipe::factory()->count(3)->create();

    $response = $this->getJson('/api/public/recipes');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name'],
            ],
            'meta',
        ]);
});

test('public can view a specific public recipe', function () {
    $recipe = Recipe::factory()->create();

    $response = $this->getJson("/api/public/recipes/{$recipe->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'instructions'],
        ]);
});

test('public recipes can be filtered by category', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    
    Recipe::factory()->create(['category_id' => $category1->id]);
    Recipe::factory()->create(['category_id' => $category1->id]);
    Recipe::factory()->create(['category_id' => $category2->id]);

    $response = $this->getJson("/api/public/recipes?category_id={$category1->id}");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});

