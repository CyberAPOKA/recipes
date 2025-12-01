<?php

namespace App\Http\Controllers;

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
     * Display a listing of the user's recipes.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $recipes = $this->recipeService->getUserRecipes($request->user(), $search);

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
    public function scrape(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        $result = $this->scraperService->scrapeTudoGostoso($request->input('url'));

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

