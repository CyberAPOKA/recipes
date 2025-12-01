<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeComment>
 */
class RecipeCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $comments = [
            'Excelente receita! Ficou deliciosa.',
            'Muito fácil de fazer e o resultado foi incrível.',
            'Adorei! Vou fazer novamente com certeza.',
            'Receita perfeita, segui exatamente e ficou ótimo.',
            'Gostei muito, mas aumentei um pouco o sal.',
            'Ficou uma delícia! Recomendo para todos.',
            'Receita simples e saborosa. Meus filhos adoraram!',
            'Perfeito! Exatamente como esperava.',
            'Muito boa receita, vou guardar para fazer sempre.',
            'Ficou delicioso! Obrigada pela receita.',
            'Adorei o resultado final. Super recomendo!',
            'Receita fácil e rápida. Perfeita para o dia a dia.',
            'Ficou incrível! Todos elogiaram.',
            'Muito saborosa! Vou fazer mais vezes.',
            'Excelente! Segui a receita e ficou perfeito.',
        ];

        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'comment' => fake()->randomElement($comments),
        ];
    }
}
