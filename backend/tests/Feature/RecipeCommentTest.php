<?php

use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test-token')->plainTextToken;
    $this->recipe = Recipe::factory()->create();
});

test('authenticated user can create a comment', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/public/recipes/{$this->recipe->id}/comments", [
            'comment' => 'This recipe is amazing!',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'data' => ['id', 'comment', 'user'],
        ]);

    $this->assertDatabaseHas('recipe_comments', [
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
        'comment' => 'This recipe is amazing!',
    ]);
});

test('unauthenticated user cannot create a comment', function () {
    $response = $this->postJson("/api/public/recipes/{$this->recipe->id}/comments", [
        'comment' => 'This recipe is amazing!',
    ]);

    $response->assertStatus(401);
});

test('comment owner can delete their comment', function () {
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/public/recipes/{$this->recipe->id}/comments/{$comment->id}");

    $response->assertStatus(200)
        ->assertJson(['message' => 'Comment deleted successfully']);

    $this->assertDatabaseMissing('recipe_comments', ['id' => $comment->id]);
});

test('recipe owner can delete any comment on their recipe', function () {
    $recipeOwner = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $recipeOwner->id]);
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $recipe->id,
        'user_id' => $this->user->id,
    ]);

    $ownerToken = $recipeOwner->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$ownerToken}")
        ->deleteJson("/api/public/recipes/{$recipe->id}/comments/{$comment->id}");

    $response->assertStatus(200);
});

test('user cannot delete other users comments', function () {
    $otherUser = User::factory()->create();
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/public/recipes/{$this->recipe->id}/comments/{$comment->id}");

    $response->assertStatus(403);
});

