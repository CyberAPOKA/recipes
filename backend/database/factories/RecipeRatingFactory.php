<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeRating>
 */
class RecipeRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
