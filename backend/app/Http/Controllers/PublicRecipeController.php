<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Services\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicRecipeController extends Controller
{
    public function __construct(
        private RecipeService $recipeService
    ) {
    }

    /**
     * Display a listing of all public recipes with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category_id' => $request->query('category_id'),
            'servings' => [
                'operator' => $request->query('servings_operator'), // 'exact', 'above', 'below'
                'value' => $request->query('servings_value'),
            ],
            'prep_time' => [
                'operator' => $request->query('prep_time_operator'), // 'exact', 'above', 'below'
                'value' => $request->query('prep_time_value'),
            ],
            'my_recipes' => $request->boolean('my_recipes'),
            'search' => $request->query('search'),
        ];

        // Remove empty filter values
        $filters = array_filter($filters, function ($value) {
            if (is_array($value)) {
                return !empty(array_filter($value));
            }
            return $value !== null && $value !== '';
        });

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
     * Display the specified public recipe.
     */
    public function show(int $id): JsonResponse
    {
        $recipe = $this->recipeService->getPublicRecipe($id);

        if (!$recipe) {
            return response()->json([
                'message' => 'Recipe not found',
            ], 404);
        }

        return response()->json([
            'data' => new RecipeResource($recipe),
        ]);
    }
}
