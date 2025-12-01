<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Services\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

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
        // Try to authenticate user if token is present (optional auth)
        $user = null;
        $token = $request->bearerToken();
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken) {
                $user = $accessToken->tokenable;
            }
        }
        
        // Convert my_recipes from string '1'/'0' to boolean
        $myRecipesValue = $request->query('my_recipes');
        $myRecipes = false;
        if ($myRecipesValue !== null) {
            $myRecipes = in_array(strtolower($myRecipesValue), ['1', 'true', 'yes'], true);
        }
        
        \Log::info('PublicRecipeController - my_recipes filter', [
            'raw_value' => $myRecipesValue,
            'converted_value' => $myRecipes,
            'user_id' => $user?->id,
            'is_authenticated' => $user !== null,
            'has_token' => $token !== null,
        ]);
        
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
            'rating' => [
                'operator' => $request->query('rating_operator'), // 'exact', 'above', 'below'
                'value' => $request->query('rating_value'),
            ],
            'comments' => [
                'operator' => $request->query('comments_operator'), // 'exact', 'above', 'below'
                'value' => $request->query('comments_value'),
            ],
            'my_recipes' => $myRecipes,
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

        $recipes = $this->recipeService->getPublicRecipes($filters, $user);

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
