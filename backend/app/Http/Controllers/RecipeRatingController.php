<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeRating\StoreRatingRequest;
use App\Http\Resources\RecipeRatingResource;
use App\Models\Recipe;
use App\Services\RecipeRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RecipeRatingController extends Controller
{
    public function __construct(
        private RecipeRatingService $ratingService
    ) {
    }

    /**
     * Store or update a rating.
     */
    #[OA\Post(
        path: "/api/public/recipes/{recipeId}/ratings",
        summary: "Create or update a rating for a recipe",
        tags: ["Recipe Ratings"],
        parameters: [
            new OA\Parameter(name: "recipeId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["rating"],
                properties: [
                    new OA\Property(property: "rating", type: "integer", minimum: 1, maximum: 5, example: 5),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Rating saved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Rating saved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "rating", type: "integer", example: 5),
                                new OA\Property(property: "average_rating", type: "number", format: "float", example: 4.5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Cannot rate your own recipe"),
            new OA\Response(response: 404, description: "Recipe not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StoreRatingRequest $request, int $recipeId): JsonResponse
    {
        $recipe = Recipe::find($recipeId);

        if (!$recipe) {
            return response()->json([
                'message' => 'Recipe not found',
            ], 404);
        }

        if (!$this->ratingService->canRateRecipe($recipe, $request->user())) {
            return response()->json([
                'message' => 'You cannot rate your own recipe',
            ], 403);
        }

        $rating = $this->ratingService->storeOrUpdateRating(
            $recipeId,
            $request->user(),
            $request->validated()['rating']
        );

        $recipe = $this->ratingService->getRecipeWithAverageRating($recipeId);

        return response()->json([
            'message' => 'Rating saved successfully',
            'data' => [
                'id' => $rating->id,
                'rating' => $rating->rating,
                'average_rating' => round($recipe->ratings_avg_rating ?? 0, 2),
            ],
        ], 201);
    }

    /**
     * Get user's rating for a recipe.
     */
    #[OA\Get(
        path: "/api/public/recipes/{recipeId}/ratings",
        summary: "Get user's rating for a recipe",
        tags: ["Recipe Ratings"],
        parameters: [
            new OA\Parameter(name: "recipeId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rating retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "data",
                            oneOf: [
                                new OA\Schema(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "rating", type: "integer", example: 5),
                                    ]
                                ),
                                new OA\Schema(type: "null"),
                            ],
                            nullable: true
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function show(Request $request, int $recipeId): JsonResponse
    {
        $rating = $this->ratingService->getUserRating($recipeId, $request->user());

        if (!$rating) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => new RecipeRatingResource($rating),
        ]);
    }
}
