<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeComment\StoreCommentRequest;
use App\Http\Resources\RecipeCommentResource;
use App\Services\RecipeCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RecipeCommentController extends Controller
{
    public function __construct(
        private RecipeCommentService $commentService
    ) {
    }

    /**
     * Store a newly created comment.
     */
    #[OA\Post(
        path: "/api/public/recipes/{recipeId}/comments",
        summary: "Create a comment on a recipe",
        tags: ["Recipe Comments"],
        parameters: [
            new OA\Parameter(name: "recipeId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["comment"],
                properties: [
                    new OA\Property(property: "comment", type: "string", maxLength: 1000, example: "Esta receita ficou deliciosa!"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Comment created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Comment created successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "comment", type: "string", example: "Esta receita ficou deliciosa!"),
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "integer", example: 1),
                                        new OA\Property(property: "name", type: "string", example: "John Doe"),
                                    ]
                                ),
                                new OA\Property(property: "created_at", type: "string", format: "date-time"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
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
    #[OA\Delete(
        path: "/api/public/recipes/{recipeId}/comments/{commentId}",
        summary: "Delete a comment",
        tags: ["Recipe Comments"],
        parameters: [
            new OA\Parameter(name: "recipeId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
            new OA\Parameter(name: "commentId", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Comment deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Comment deleted successfully"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Comment not found"),
        ]
    )]
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
