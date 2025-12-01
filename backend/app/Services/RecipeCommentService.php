<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;

class RecipeCommentService
{
    /**
     * Create a new comment.
     */
    public function createComment(int $recipeId, User $user, string $commentText): RecipeComment
    {
        $recipe = Recipe::findOrFail($recipeId);

        $comment = RecipeComment::create([
            'recipe_id' => $recipeId,
            'user_id' => $user->id,
            'comment' => $commentText,
        ]);

        return $comment->load('user');
    }

    /**
     * Get a comment by ID.
     */
    public function getComment(int $recipeId, int $commentId): ?RecipeComment
    {
        return RecipeComment::where('recipe_id', $recipeId)
            ->where('id', $commentId)
            ->first();
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(RecipeComment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Check if user can delete the comment.
     */
    public function canDeleteComment(RecipeComment $comment, User $user): bool
    {
        return $comment->user_id === $user->id || $comment->recipe->user_id === $user->id;
    }
}

