<?php

use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use App\Services\RecipeCommentService;

beforeEach(function () {
    $this->commentService = new RecipeCommentService();
    $this->user = User::factory()->create();
    $this->recipe = Recipe::factory()->create(['user_id' => $this->user->id]);
});

test('can create a comment on a recipe', function () {
    $commentText = 'This recipe is amazing!';

    $comment = $this->commentService->createComment(
        $this->recipe->id,
        $this->user,
        $commentText
    );

    expect($comment)->toBeInstanceOf(RecipeComment::class)
        ->and($comment->comment)->toBe($commentText)
        ->and($comment->recipe_id)->toBe($this->recipe->id)
        ->and($comment->user_id)->toBe($this->user->id)
        ->and($comment->user)->not->toBeNull();
});

test('can get a comment by id', function () {
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
    ]);

    $foundComment = $this->commentService->getComment($this->recipe->id, $comment->id);

    expect($foundComment)->toBeInstanceOf(RecipeComment::class)
        ->and($foundComment->id)->toBe($comment->id);
});

test('returns null when comment does not exist', function () {
    $foundComment = $this->commentService->getComment($this->recipe->id, 999);

    expect($foundComment)->toBeNull();
});

test('can delete a comment', function () {
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
    ]);

    $result = $this->commentService->deleteComment($comment);

    expect($result)->toBeTrue()
        ->and(RecipeComment::find($comment->id))->toBeNull();
});

test('user can delete their own comment', function () {
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $this->user->id,
    ]);

    $canDelete = $this->commentService->canDeleteComment($comment, $this->user);

    expect($canDelete)->toBeTrue();
});

test('recipe owner can delete any comment on their recipe', function () {
    $otherUser = User::factory()->create();
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser->id,
    ]);

    $canDelete = $this->commentService->canDeleteComment($comment, $this->recipe->user);

    expect($canDelete)->toBeTrue();
});

test('user cannot delete other users comments', function () {
    $otherUser = User::factory()->create();
    $comment = RecipeComment::factory()->create([
        'recipe_id' => $this->recipe->id,
        'user_id' => $otherUser->id,
    ]);

    $anotherUser = User::factory()->create();
    $canDelete = $this->commentService->canDeleteComment($comment, $anotherUser);

    expect($canDelete)->toBeFalse();
});

