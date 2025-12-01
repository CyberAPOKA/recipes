<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\ScrapeRecipeRequest;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\CacheService;
use App\Services\RecipeService;
use App\Services\RecipeScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function __construct(
        private RecipeService $recipeService,
        private RecipeScraperService $scraperService,
        private CacheService $cacheService
    ) {
    }

    /**
     * Display a listing of all recipes.
     */
    public function index(Request $request): JsonResponse
    {
        // Always build filters array to support all filter types
        $filters = [
            'category_id' => $request->query('category_id'),
            'servings' => [
                'operator' => $request->query('servings_operator'),
                'value' => $request->query('servings_value'),
            ],
            'prep_time' => [
                'operator' => $request->query('prep_time_operator'),
                'value' => $request->query('prep_time_value'),
            ],
            'rating' => [
                'operator' => $request->query('rating_operator'),
                'value' => $request->query('rating_value'),
            ],
            'comments' => [
                'operator' => $request->query('comments_operator'),
                'value' => $request->query('comments_value'),
            ],
            'my_recipes' => $request->boolean('my_recipes'),
            'search' => $request->query('search'),
        ];

        // Remove empty filter values (but keep my_recipes even if false)
        $filters = array_filter($filters, function ($key, $value) {
            // Keep my_recipes filter even if false (it's a boolean filter)
            if ($key === 'my_recipes') {
                return true;
            }
            if (is_array($value)) {
                return !empty(array_filter($value));
            }
            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        // Use getPublicRecipes to return recipes (filtered by user if my_recipes is true)
        $recipes = $this->recipeService->getPublicRecipes($filters, $request->user());

        return response()->json([
            'data' => RecipeResource::collection($recipes->items()),
            'meta' => [
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'per_page' => $recipes->perPage(),
                'total' => $recipes->total(),
            ],
        ]);
    }

    /**
     * Store a newly created recipe.
     */
    public function store(StoreRecipeRequest $request): JsonResponse
    {
        $recipe = $this->recipeService->createRecipe(
            $request->user(),
            $request->validated()
        );

        // Invalidate cache when a new recipe is created
        $this->cacheService->invalidateRecipeCache($recipe->id);

        return response()->json([
            'message' => 'Recipe created successfully',
            'data' => new RecipeResource($recipe->load('category')),
        ], 201);
    }

    /**
     * Display the specified recipe.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $recipe = $this->recipeService->getRecipe($id, $request->user());

        if (!$recipe) {
            return response()->json([
                'message' => 'Recipe not found',
            ], 404);
        }

        return response()->json([
            'data' => new RecipeResource($recipe),
        ]);
    }

    /**
     * Update the specified recipe.
     */
    public function update(UpdateRecipeRequest $request, int $id): JsonResponse
    {
        $recipe = $this->recipeService->getRecipe($id, $request->user());

        if (!$recipe) {
            return response()->json([
                'message' => 'Recipe not found',
            ], 404);
        }

        $this->recipeService->updateRecipe($recipe, $request->validated());

        // Invalidate cache when a recipe is updated
        $this->cacheService->invalidateRecipeCache($recipe->id);

        return response()->json([
            'message' => 'Recipe updated successfully',
            'data' => new RecipeResource($recipe->fresh()->load('category')),
        ]);
    }

    /**
     * Remove the specified recipe.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $recipe = $this->recipeService->getRecipe($id, $request->user());

        if (!$recipe) {
            return response()->json([
                'message' => 'Recipe not found',
            ], 404);
        }

        $recipeId = $recipe->id;
        $this->recipeService->deleteRecipe($recipe);

        // Invalidate cache when a recipe is deleted
        $this->cacheService->invalidateRecipeCache($recipeId);

        return response()->json([
            'message' => 'Recipe deleted successfully',
        ]);
    }

    /**
     * Scrape recipe from external URL (TudoGostoso)
     */
    public function scrape(ScrapeRecipeRequest $request): JsonResponse
    {
        $result = $this->scraperService->scrapeTudoGostoso($request->validated()['url']);

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to scrape recipe',
                'error' => $result['error'] ?? 'Unknown error',
            ], 400);
        }

        return response()->json([
            'message' => 'Recipe scraped successfully',
            'data' => $result['data'],
        ]);
    }
}

