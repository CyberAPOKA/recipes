<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class RecipeService
{
    /**
     * Get all recipes for a user with optional search.
     */
    public function getUserRecipes(User $user, ?string $search = null): LengthAwarePaginator
    {
        $query = Recipe::where('user_id', $user->id)
            ->with('category')
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%")
                    ->orWhere('ingredients', 'like', "%{$search}%");
            });
        }

        return $query->paginate(15);
    }

    /**
     * Get a single recipe by ID.
     */
    public function getRecipe(int $id, User $user): ?Recipe
    {
        return Recipe::where('id', $id)
            ->where('user_id', $user->id)
            ->with('category')
            ->first();
    }

    /**
     * Create a new recipe.
     */
    public function createRecipe(User $user, array $data): Recipe
    {
        return Recipe::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'] ?? null,
            'prep_time_minutes' => $data['prep_time_minutes'] ?? null,
            'servings' => $data['servings'] ?? null,
            'instructions' => $data['instructions'],
            'ingredients' => $data['ingredients'] ?? null,
        ]);
    }

    /**
     * Update a recipe.
     */
    public function updateRecipe(Recipe $recipe, array $data): bool
    {
        return $recipe->update([
            'category_id' => $data['category_id'] ?? $recipe->category_id,
            'name' => $data['name'] ?? $recipe->name,
            'prep_time_minutes' => $data['prep_time_minutes'] ?? $recipe->prep_time_minutes,
            'servings' => $data['servings'] ?? $recipe->servings,
            'instructions' => $data['instructions'] ?? $recipe->instructions,
            'ingredients' => $data['ingredients'] ?? $recipe->ingredients,
        ]);
    }

    /**
     * Delete a recipe.
     */
    public function deleteRecipe(Recipe $recipe): bool
    {
        return $recipe->delete();
    }
}

