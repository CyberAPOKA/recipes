<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\ScrapeRecipeRequest;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\RecipeService;
use App\Services\RecipeScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function __construct(
        private RecipeService $recipeService,
        private RecipeScraperService $scraperService
    ) {
    }

    /**
     * Display a listing of all recipes.
     */
    public function index(Request $request): JsonResponse
    {
        // Check if filters are provided (new format) or just search (old format for backward compatibility)
        $hasFilters = $request->has(['category_id', 'servings_operator', 'servings_value', 'prep_time_operator', 'prep_time_value']) 
            || $request->has('category_id') 
            || $request->has('servings_operator') 
            || $request->has('prep_time_operator');

        if ($hasFilters) {
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
                'search' => $request->query('search'),
            ];

            // Remove empty filter values
            $filters = array_filter($filters, function ($value) {
                if (is_array($value)) {
                    return !empty(array_filter($value));
                }
                return $value !== null && $value !== '';
            });

            // Use getPublicRecipes to return all recipes (not filtered by user)
            $recipes = $this->recipeService->getPublicRecipes($filters, $request->user());
        } else {
            // Backward compatibility: use public recipes method with just search
            $search = $request->query('search');
            $filters = $search ? ['search' => $search] : [];
            $recipes = $this->recipeService->getPublicRecipes($filters, $request->user());
        }

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

        $this->recipeService->deleteRecipe($recipe);

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

