<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;

class RecipeRatingService
{
    /**
     * Store or update a rating.
     */
    public function storeOrUpdateRating(int $recipeId, User $user, int $rating): RecipeRating
    {
        $recipe = Recipe::findOrFail($recipeId);

        return RecipeRating::updateOrCreate(
            [
                'recipe_id' => $recipeId,
                'user_id' => $user->id,
            ],
            [
                'rating' => $rating,
            ]
        );
    }

    /**
     * Get user's rating for a recipe.
     */
    public function getUserRating(int $recipeId, User $user): ?RecipeRating
    {
        return RecipeRating::where('recipe_id', $recipeId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Get recipe with average rating.
     */
    public function getRecipeWithAverageRating(int $recipeId): ?Recipe
    {
        return Recipe::withAvg('ratings', 'rating')->find($recipeId);
    }

    /**
     * Check if user can rate the recipe.
     */
    public function canRateRecipe(Recipe $recipe, User $user): bool
    {
        return $recipe->user_id !== $user->id;
    }
}

