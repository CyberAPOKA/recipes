<?php

use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
    $this->recipe = Recipe::factory()->create();
});

test('authenticated user can rate a recipe', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/public/recipes/{$this->recipe->id}/ratings", [
            'rating' => 5,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'rating', 'average_rating'],
        ]);

    $this->assertDatabaseHas('recipe_ratings', [
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
        'rating' => 5,
    ]);
});

test('user cannot rate their own recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/public/recipes/{$recipe->id}/ratings", [
            'rating' => 5,
        ]);

    $response->assertStatus(403)
        ->assertJson(['message' => 'You cannot rate your own recipe']);
});

test('user can update their rating', function () {
    RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
        'rating' => 3,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/public/recipes/{$this->recipe->id}/ratings", [
            'rating' => 5,
        ]);

    $response->assertStatus(201);

    expect(RecipeRating::where('recipe_id', $this->recipe->id)
        ->where('user_id', $this->user->id)
        ->first()
        ->rating)->toBe(5);
});

test('authenticated user can get their rating for a recipe', function () {
    RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
        'rating' => 4,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/public/recipes/{$this->recipe->id}/ratings");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'rating'],
        ])
        ->assertJson([
            'data' => ['rating' => 4],
        ]);
});

test('returns null when user has not rated recipe', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/public/recipes/{$this->recipe->id}/ratings");

    $response->assertStatus(200)
        ->assertJson(['data' => null]);
});

test('rating must be between 1 and 5', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/public/recipes/{$this->recipe->id}/ratings", [
            'rating' => 6,
        ]);

    $response->assertStatus(422);
});

