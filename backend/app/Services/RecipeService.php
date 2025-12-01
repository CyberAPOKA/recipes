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
    public function __construct(
        private CacheService $cacheService
    ) {
    }
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
        $page = (int) request()->query('page', 1);
        $perPage = (int) request()->query('per_page', 15);
        $sortBy = request()->query('sort_by', 'recent');
        
        // Log per_page for debugging
        \Log::info('RecipeService getPublicRecipes', [
            'per_page_requested' => request()->query('per_page'),
            'per_page_used' => $perPage,
            'page' => $page,
            'sort_by' => $sortBy,
        ]);
        
        // Include user_id in filters for cache key when filtering by my_recipes
        $cacheFilters = $filters;
        if (isset($filters['my_recipes']) && $filters['my_recipes'] && $user) {
            $cacheFilters['user_id'] = $user->id;
        }
        
        // Try to get from cache
        $cached = $this->cacheService->getCachedRecipes($cacheFilters, $page, $perPage, $sortBy);
        
        if ($cached !== null) {
            // Reconstruct paginator from cached data
            \Log::info('Recipes served from CACHE', [
                'total' => $cached['total'],
                'current_page' => $cached['current_page'],
                'per_page' => $cached['per_page'],
                'filters_applied' => !empty($filters),
            ]);
            
            return new \Illuminate\Pagination\LengthAwarePaginator(
                collect($cached['data']),
                $cached['total'],
                $cached['per_page'],
                $cached['current_page'],
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $query = Recipe::with(['category', 'user'])
            ->withCount(['comments', 'ratings'])
            ->withAvg('ratings', 'rating');

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
        // Check if my_recipes is explicitly set and true, and user is authenticated
        \Log::info('RecipeService - Checking my_recipes filter', [
            'my_recipes_set' => isset($filters['my_recipes']),
            'my_recipes_value' => $filters['my_recipes'] ?? 'not set',
            'my_recipes_type' => gettype($filters['my_recipes'] ?? null),
            'my_recipes_strict_true' => ($filters['my_recipes'] ?? false) === true,
            'user_exists' => $user !== null,
            'user_id' => $user?->id,
        ]);
        
        if (isset($filters['my_recipes']) && $filters['my_recipes'] === true && $user) {
            \Log::info('Filtering by user recipes - APPLYING FILTER', [
                'user_id' => $user->id,
                'my_recipes' => $filters['my_recipes'],
            ]);
            $query->where('user_id', $user->id);
        } else {
            \Log::info('Filtering by user recipes - NOT APPLYING FILTER', [
                'reason' => !isset($filters['my_recipes']) ? 'not set' : ($filters['my_recipes'] !== true ? 'not true' : 'no user'),
                'my_recipes_value' => $filters['my_recipes'] ?? 'not set',
                'user_exists' => $user !== null,
            ]);
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

        // Filter by rating
        if (isset($filters['rating']) && $filters['rating']) {
            $ratingFilter = $filters['rating'];
            if (isset($ratingFilter['operator']) && isset($ratingFilter['value'])) {
                $operator = $ratingFilter['operator'];
                $value = (float) $ratingFilter['value'];
                
                // Use havingRaw for aggregated columns (ratings_avg_rating is created by withAvg)
                if ($operator === 'exact') {
                    $query->havingRaw('COALESCE(ratings_avg_rating, 0) = ?', [$value]);
                } elseif ($operator === 'above') {
                    $query->havingRaw('COALESCE(ratings_avg_rating, 0) >= ?', [$value]);
                } elseif ($operator === 'below') {
                    $query->havingRaw('COALESCE(ratings_avg_rating, 0) <= ?', [$value]);
                }
            }
        }

        // Filter by comments count
        if (isset($filters['comments']) && $filters['comments']) {
            $commentsFilter = $filters['comments'];
            if (isset($commentsFilter['operator']) && isset($commentsFilter['value'])) {
                $operator = $commentsFilter['operator'];
                $value = (int) $commentsFilter['value'];
                
                if ($operator === 'exact') {
                    $query->having('comments_count', '=', $value);
                } elseif ($operator === 'above') {
                    $query->having('comments_count', '>=', $value);
                } elseif ($operator === 'below') {
                    $query->having('comments_count', '<=', $value);
                }
            }
        }

        // Apply sorting
        $this->applySorting($query, $sortBy);

        $paginator = $query->paginate($perPage);
        
        // Cache the results
        $this->cacheService->cacheRecipes(
            $cacheFilters,
            [
                'data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
            $page,
            $perPage,
            $sortBy
        );
        
        \Log::info('Recipes fetched from database', [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'filters_applied' => !empty($filters),
        ]);
        
        return $paginator;
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
     * Apply sorting to the query.
     */
    private function applySorting($query, string $sortBy): void
    {
        switch ($sortBy) {
            case 'rating_desc':
                // Best rated (highest average rating)
                // Use the aggregated column from withAvg
                $query->orderByRaw('COALESCE((SELECT AVG(rating) FROM recipe_ratings WHERE recipe_id = recipes.id), 0) DESC')
                    ->orderBy('created_at', 'desc');
                break;
                
            case 'rating_asc':
                // Worst rated (lowest average rating)
                $query->orderByRaw('COALESCE((SELECT AVG(rating) FROM recipe_ratings WHERE recipe_id = recipes.id), 0) ASC')
                    ->orderBy('created_at', 'desc');
                break;
                
            case 'comments_desc':
                // Most commented
                $query->orderBy('comments_count', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
                
            case 'comments_asc':
                // Least commented
                $query->orderBy('comments_count', 'asc')
                    ->orderBy('created_at', 'desc');
                break;
                
            case 'recent':
                // Most recent (default)
                $query->orderBy('created_at', 'desc');
                break;
                
            case 'oldest':
                // Oldest first
                $query->orderBy('created_at', 'asc');
                break;
                
            case 'name_asc':
                // Name A-Z
                $query->orderBy('name', 'asc');
                break;
                
            case 'name_desc':
                // Name Z-A
                $query->orderBy('name', 'desc');
                break;
                
            default:
                // Default: most recent
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    /**
     * Check if a string is a URL.
     */
    private function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }
}

