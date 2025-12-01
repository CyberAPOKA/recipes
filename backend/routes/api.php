<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PublicRecipeController;
use App\Http\Controllers\RecipeCommentController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\RecipeRatingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/categories', [CategoryController::class, 'index']);

// Public recipe routes (accessible without auth, but auth optional for filters)
// Note: Sanctum middleware will authenticate user if token is present, but requires token
// For optional auth, we'll handle it in the controller
Route::get('/public/recipes', [PublicRecipeController::class, 'index']);
Route::get('/public/recipes/{id}', [PublicRecipeController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Recipe routes
    Route::apiResource('recipes', RecipeController::class);
    Route::post('/recipes/scrape', [RecipeController::class, 'scrape']);

    // Recipe comments
    Route::post('/public/recipes/{recipeId}/comments', [RecipeCommentController::class, 'store']);
    Route::delete('/public/recipes/{recipeId}/comments/{commentId}', [RecipeCommentController::class, 'destroy']);

    // Recipe ratings
    Route::post('/public/recipes/{recipeId}/ratings', [RecipeRatingController::class, 'store']);
    Route::get('/public/recipes/{recipeId}/ratings', [RecipeRatingController::class, 'show']);
});
