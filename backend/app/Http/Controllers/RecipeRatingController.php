<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeRating\StoreRatingRequest;
use App\Http\Resources\RecipeRatingResource;
use App\Models\Recipe;
use App\Services\RecipeRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeRatingController extends Controller
{
    public function __construct(
        private RecipeRatingService $ratingService
    ) {
    }

    /**
     * Store or update a rating.
     */
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
