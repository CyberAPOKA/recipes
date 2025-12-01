<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
     * Get all recipes for a user with filters.
     */
    public function getUserRecipesWithFilters(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Recipe::where('user_id', $user->id)
            ->with('category')
            ->orderBy('created_at', 'desc');

        // Filter by category
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by servings
        if (isset($filters['servings']) && $filters['servings']) {
            $servingsFilter = $filters['servings'];
            if (isset($servingsFilter['operator']) && isset($servingsFilter['value'])) {
                $operator = $servingsFilter['operator']; // 'exact', 'above', 'below'
                $value = (int) $servingsFilter['value'];
                
                if ($operator === 'exact') {
                    $query->where('servings', $value);
                } elseif ($operator === 'above') {
                    $query->where('servings', '>=', $value);
                } elseif ($operator === 'below') {
                    $query->where('servings', '<=', $value);
                }
            }
        }

        // Filter by prep time
        if (isset($filters['prep_time']) && $filters['prep_time']) {
            $prepTimeFilter = $filters['prep_time'];
            if (isset($prepTimeFilter['operator']) && isset($prepTimeFilter['value'])) {
                $operator = $prepTimeFilter['operator']; // 'exact', 'above', 'below'
                $value = (int) $prepTimeFilter['value'];
                
                if ($operator === 'exact') {
                    $query->where('prep_time_minutes', $value);
                } elseif ($operator === 'above') {
                    $query->where('prep_time_minutes', '>=', $value);
                } elseif ($operator === 'below') {
                    $query->where('prep_time_minutes', '<=', $value);
                }
            }
        }

        // Search
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%")
                    ->orWhere('ingredients', 'like', "%{$search}%");
            });
        }

        return $query->paginate(12);
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
        $imagePath = null;
        // Priority: uploaded file > image URL > null
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $imagePath = $this->storeImage($data['image']);
        } elseif (isset($data['image_url']) && !empty($data['image_url'])) {
            // Store image URL directly
            $imagePath = $data['image_url'];
        }

        return Recipe::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'] ?? null,
            'prep_time_minutes' => $data['prep_time_minutes'] ?? null,
            'servings' => $data['servings'] ?? null,
            'image' => $imagePath,
            'instructions' => $data['instructions'],
            'ingredients' => $data['ingredients'] ?? null,
        ]);
    }

    /**
     * Update a recipe.
     */
    public function updateRecipe(Recipe $recipe, array $data): bool
    {
        $updateData = [
            'category_id' => $data['category_id'] ?? $recipe->category_id,
            'name' => $data['name'] ?? $recipe->name,
            'prep_time_minutes' => $data['prep_time_minutes'] ?? $recipe->prep_time_minutes,
            'servings' => $data['servings'] ?? $recipe->servings,
            'instructions' => $data['instructions'] ?? $recipe->instructions,
            'ingredients' => $data['ingredients'] ?? $recipe->ingredients,
        ];

        // Handle image upload or URL
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image if exists (only if it's a local file)
            if ($recipe->image && !$this->isUrl($recipe->image)) {
                $this->deleteImage($recipe->image);
            }
            $updateData['image'] = $this->storeImage($data['image']);
        } elseif (isset($data['image_url']) && !empty($data['image_url'])) {
            // Delete old image if exists (only if it's a local file)
            if ($recipe->image && !$this->isUrl($recipe->image)) {
                $this->deleteImage($recipe->image);
            }
            $updateData['image'] = $data['image_url'];
        } elseif (isset($data['image']) && $data['image'] === null) {
            // Explicitly remove image
            if ($recipe->image && !$this->isUrl($recipe->image)) {
                $this->deleteImage($recipe->image);
            }
            $updateData['image'] = null;
        }

        return $recipe->update($updateData);
    }

    /**
     * Delete a recipe.
     */
    public function deleteRecipe(Recipe $recipe): bool
    {
        // Delete associated image if exists (only if it's a local file, not a URL)
        if ($recipe->image && !$this->isUrl($recipe->image)) {
            $this->deleteImage($recipe->image);
        }

        return $recipe->delete();
    }

    /**
     * Store uploaded image and return the path.
     */
    private function storeImage(UploadedFile $file): string
    {
        return $file->store('recipes', 'public');
    }

    /**
     * Delete image from storage.
     */
    private function deleteImage(string $path): void
    {
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get all public recipes with filters.
     */
    public function getPublicRecipes(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = Recipe::with(['category', 'user'])
            ->withCount(['comments', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->orderBy('created_at', 'desc');

        // Filter by category
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by servings
        if (isset($filters['servings']) && $filters['servings']) {
            $servingsFilter = $filters['servings'];
            if (isset($servingsFilter['operator']) && isset($servingsFilter['value'])) {
                $operator = $servingsFilter['operator']; // 'exact', 'above', 'below'
                $value = (int) $servingsFilter['value'];
                
                if ($operator === 'exact') {
                    $query->where('servings', $value);
                } elseif ($operator === 'above') {
                    $query->where('servings', '>=', $value);
                } elseif ($operator === 'below') {
                    $query->where('servings', '<=', $value);
                }
            }
        }

        // Filter by prep time
        if (isset($filters['prep_time']) && $filters['prep_time']) {
            $prepTimeFilter = $filters['prep_time'];
            if (isset($prepTimeFilter['operator']) && isset($prepTimeFilter['value'])) {
                $operator = $prepTimeFilter['operator']; // 'exact', 'above', 'below'
                $value = (int) $prepTimeFilter['value'];
                
                if ($operator === 'exact') {
                    $query->where('prep_time_minutes', $value);
                } elseif ($operator === 'above') {
                    $query->where('prep_time_minutes', '>=', $value);
                } elseif ($operator === 'below') {
                    $query->where('prep_time_minutes', '<=', $value);
                }
            }
        }

        // Filter to show only user's recipes
        if (isset($filters['my_recipes']) && $filters['my_recipes'] && $user) {
            $query->where('user_id', $user->id);
        }

        // Search
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('instructions', 'like', "%{$search}%")
                    ->orWhere('ingredients', 'like', "%{$search}%");
            });
        }

        $perPage = request()->query('per_page', 15);
        return $query->paginate($perPage);
    }

    /**
     * Get a public recipe by ID (any user can view).
     */
    public function getPublicRecipe(int $id): ?Recipe
    {
        return Recipe::with(['category', 'user', 'comments.user', 'ratings'])
            ->withCount(['comments', 'ratings'])
            ->withAvg('ratings', 'rating')
            ->find($id);
    }

    /**
     * Check if a string is a URL.
     */
    private function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }
}

