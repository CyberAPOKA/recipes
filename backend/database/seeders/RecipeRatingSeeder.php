<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipes = Recipe::all();
        $users = User::all();

        if ($recipes->isEmpty()) {
            $this->command->warn('Nenhuma receita encontrada. Execute RecipeSeeder primeiro.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('Nenhum usuário encontrado. Execute UserSeeder primeiro.');
            return;
        }

        // 70% das receitas terão avaliações
        $recipesToRate = $recipes->random((int) ceil($recipes->count() * 0.7));

        $totalRatings = 0;

        foreach ($recipesToRate as $recipe) {
            // Cada receita selecionada terá entre 1 e 8 avaliações
            $ratingCount = rand(1, 8);

            // Garante que não haverá duplicatas de usuário por receita
            $ratedUsers = collect();

            for ($i = 0; $i < $ratingCount && $ratedUsers->count() < $users->count() - 1; $i++) {
                // Seleciona um usuário aleatório que não seja o dono da receita e que ainda não avaliou
                $availableUsers = $users->where('id', '!=', $recipe->user_id)
                    ->whereNotIn('id', $ratedUsers->pluck('id'));

                if ($availableUsers->isEmpty()) {
                    break;
                }

                $ratingUser = $availableUsers->random();
                $ratedUsers->push($ratingUser);

                RecipeRating::factory()->create([
                    'recipe_id' => $recipe->id,
                    'user_id' => $ratingUser->id,
                    'rating' => rand(1, 5), // Avaliação entre 1 e 5 estrelas
                ]);

                $totalRatings++;
            }
        }

        $this->command->info("Criadas {$totalRatings} avaliações em {$recipesToRate->count()} receitas.");
    }
}
