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
use OpenApi\Attributes as OA;

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
    #[OA\Get(
        path: "/api/recipes",
        summary: "Get all recipes with filters",
        tags: ["Recipes"],
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
        security: [["sanctum" => []]],
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
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
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
    #[OA\Post(
        path: "/api/recipes",
        summary: "Create a new recipe",
        tags: ["Recipes"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["instructions"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "name", type: "string", maxLength: 45, nullable: true, example: "Bolo de Chocolate"),
                    new OA\Property(property: "prep_time_minutes", type: "integer", minimum: 0, nullable: true, example: 30),
                    new OA\Property(property: "servings", type: "integer", minimum: 1, nullable: true, example: 8),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (max 5MB, formats: jpeg, jpg, png, gif, webp)", nullable: true),
                    new OA\Property(property: "image_url", type: "string", format: "url", maxLength: 500, nullable: true, example: "https://example.com/image.jpg"),
                    new OA\Property(property: "instructions", type: "string", example: "Misture todos os ingredientes..."),
                    new OA\Property(property: "ingredients", type: "string", nullable: true, example: "2 xícaras de farinha, 3 ovos..."),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Recipe created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Recipe created successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Recipe"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
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
    #[OA\Get(
        path: "/api/recipes/{id}",
        summary: "Get a specific recipe",
        tags: ["Recipes"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
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
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Recipe not found"),
        ]
    )]
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
    #[OA\Put(
        path: "/api/recipes/{id}",
        summary: "Update a recipe",
        tags: ["Recipes"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["instructions"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", nullable: true, example: 1),
                    new OA\Property(property: "name", type: "string", maxLength: 45, nullable: true, example: "Bolo de Chocolate"),
                    new OA\Property(property: "prep_time_minutes", type: "integer", minimum: 0, nullable: true, example: 30),
                    new OA\Property(property: "servings", type: "integer", minimum: 1, nullable: true, example: 8),
                    new OA\Property(property: "image", type: "string", format: "binary", description: "Image file (max 5MB, formats: jpeg, jpg, png, gif, webp)", nullable: true),
                    new OA\Property(property: "image_url", type: "string", format: "url", maxLength: 500, nullable: true, example: "https://example.com/image.jpg"),
                    new OA\Property(property: "instructions", type: "string", example: "Misture todos os ingredientes..."),
                    new OA\Property(property: "ingredients", type: "string", nullable: true, example: "2 xícaras de farinha, 3 ovos..."),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Recipe updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Recipe updated successfully"),
                        new OA\Property(property: "data", ref: "#/components/schemas/Recipe"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Recipe not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
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
    #[OA\Delete(
        path: "/api/recipes/{id}",
        summary: "Delete a recipe",
        tags: ["Recipes"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Recipe deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Recipe deleted successfully"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Recipe not found"),
        ]
    )]
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
    #[OA\Post(
        path: "/api/recipes/scrape",
        summary: "Scrape recipe from TudoGostoso website",
        tags: ["Recipes"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["url"],
                properties: [
                    new OA\Property(property: "url", type: "string", format: "url", example: "https://www.tudogostoso.com.br/receita/123-bolo-de-chocolate"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Recipe scraped successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Recipe scraped successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "name", type: "string", example: "Bolo de Chocolate"),
                                new OA\Property(property: "ingredients", type: "string", example: "2 xícaras de farinha..."),
                                new OA\Property(property: "instructions", type: "string", example: "Misture todos os ingredientes..."),
                                new OA\Property(property: "image_url", type: "string", format: "url", nullable: true),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Failed to scrape recipe"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
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

