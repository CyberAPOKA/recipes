<?php

use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;
use App\Services\RecipeRatingService;

beforeEach(function () {
    $this->ratingService = new RecipeRatingService();
    $this->user = User::factory()->create();
    $this->recipe = Recipe::factory()->create(['user_id' => $this->user->id]);
});

test('can store a rating for a recipe', function () {
    $otherUser = User::factory()->create();
    $rating = 5;

    $storedRating = $this->ratingService->storeOrUpdateRating(
        $this->recipe->id,
        $otherUser,
        $rating
    );

    expect($storedRating)->toBeInstanceOf(RecipeRating::class)
        ->and($storedRating->rating)->toBe($rating)
        ->and($storedRating->recipe_id)->toBe($this->recipe->id)
        ->and($storedRating->user_id)->toBe($otherUser->id);
});

test('can update an existing rating', function () {
    $otherUser = User::factory()->create();
    RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser->id,
        'rating' => 3,
    ]);

    $updatedRating = $this->ratingService->storeOrUpdateRating(
        $this->recipe->id,
        $otherUser,
        5
    );

    expect($updatedRating->rating)->toBe(5)
        ->and(RecipeRating::where('recipe_id', $this->recipe->id)
            ->where('user_id', $otherUser->id)
            ->count())->toBe(1);
});

test('can get user rating for a recipe', function () {
    $otherUser = User::factory()->create();
    $rating = RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser->id,
        'rating' => 4,
    ]);

    $userRating = $this->ratingService->getUserRating($this->recipe->id, $otherUser);

    expect($userRating)->toBeInstanceOf(RecipeRating::class)
        ->and($userRating->id)->toBe($rating->id)
        ->and($userRating->rating)->toBe(4);
});

test('returns null when user has not rated recipe', function () {
    $otherUser = User::factory()->create();

    $userRating = $this->ratingService->getUserRating($this->recipe->id, $otherUser);

    expect($userRating)->toBeNull();
});

test('can get recipe with average rating', function () {
    $otherUser1 = User::factory()->create();
    $otherUser2 = User::factory()->create();

    RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser1->id,
        'rating' => 5,
    ]);

    RecipeRating::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser2->id,
        'rating' => 3,
    ]);

    $recipe = $this->ratingService->getRecipeWithAverageRating($this->recipe->id);

    expect($recipe)->not->toBeNull()
        ->and($recipe->ratings_avg_rating)->toBe(4.0);
});

test('user cannot rate their own recipe', function () {
    $canRate = $this->ratingService->canRateRecipe($this->recipe, $this->user);

    expect($canRate)->toBeFalse();
});

test('user can rate other users recipes', function () {
    $otherUser = User::factory()->create();
    $canRate = $this->ratingService->canRateRecipe($this->recipe, $otherUser);

    expect($canRate)->toBeTrue();
});

