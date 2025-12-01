<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeComment\StoreCommentRequest;
use App\Http\Resources\RecipeCommentResource;
use App\Services\RecipeCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeCommentController extends Controller
{
    public function __construct(
        private RecipeCommentService $commentService
    ) {
    }

    /**
     * Store a newly created comment.
     */
    public function store(StoreCommentRequest $request, int $recipeId): JsonResponse
    {
        $comment = $this->commentService->createComment(
            $recipeId,
            $request->user(),
            $request->validated()['comment']
        );

        return response()->json([
            'message' => 'Comment created successfully',
            'data' => new RecipeCommentResource($comment),
        ], 201);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, int $recipeId, int $commentId): JsonResponse
    {
        $comment = $this->commentService->getComment($recipeId, $commentId);

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
            ], 404);
        }

        if (!$this->commentService->canDeleteComment($comment, $request->user())) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->commentService->deleteComment($comment);

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }
}
