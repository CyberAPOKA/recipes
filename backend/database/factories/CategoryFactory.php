<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Sobremesas',
            'Bebidas',
            'Aperitivos',
            'Pratos Principais',
            'Saladas',
            'Sopas',
            'Massas',
            'Carnes',
            'Peixes',
            'Vegetarianos',
            'Veganos',
            'Lanches',
            'Café da Manhã',
            'Almoço',
            'Jantar',
        ];

        return [
            'name' => fake()->unique()->randomElement($categories),
        ];
    }
}

