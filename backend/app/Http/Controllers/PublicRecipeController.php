<?php

namespace App\Http\Controllers;

use App\Http\Resources\RecipeResource;
use App\Services\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class PublicRecipeController extends Controller
{
    public function __construct(
        private RecipeService $recipeService
    ) {
    }

    /**
     * Display a listing of all public recipes with filters.
     */
    #[OA\Get(
        path: "/api/public/recipes",
        summary: "Get all public recipes with filters",
        tags: ["Public Recipes"],
        parameters: [
            new OA\Parameter(name: "category_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
            new OA\Parameter(name: "servings_operator", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["exact", "above", "below"]), example: "exact"),
            new OA\Parameter(name: "servings_value", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 4),
            new OA\Parameter(name: "prep_time_operator", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["exact", "above", "below"]), example: "below"),
            new OA\Parameter(name: "prep_time_value", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 30),
            new OA\Parameter(name: "rating_operator", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["exact", "above", "below"]), example: "above"),
            new OA\Parameter(name: "rating_value", in: "query", required: false, schema: new OA\Schema(type: "number", format: "float"), example: 4.0),
            new OA\Parameter(name: "comments_operator", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["exact", "above", "below"]), example: "above"),
            new OA\Parameter(name: "comments_value", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 5),
            new OA\Parameter(name: "my_recipes", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: false),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "bolo"),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Recipes retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Recipe")
                        ),
                        new OA\Property(
                            property: "meta",
                            type: "object",
                            properties: [
                                new OA\Property(property: "current_page", type: "integer", example: 1),
                                new OA\Property(property: "last_page", type: "integer", example: 10),
                                new OA\Property(property: "per_page", type: "integer", example: 15),
                                new OA\Property(property: "total", type: "integer", example: 150),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
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
    #[OA\Get(
        path: "/api/public/recipes/{id}",
        summary: "Get a specific public recipe",
        tags: ["Public Recipes"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Recipe retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/Recipe"),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Recipe not found"),
        ]
    )]
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
