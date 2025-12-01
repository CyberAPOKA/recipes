<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeCommentSeeder extends Seeder
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

        // 70% das receitas terão comentários
        $recipesToComment = $recipes->random((int) ceil($recipes->count() * 0.7));

        $totalComments = 0;

        foreach ($recipesToComment as $recipe) {
            // Cada receita selecionada terá entre 1 e 5 comentários
            $commentCount = rand(1, 5);

            // Garante que não haverá duplicatas de usuário por receita
            $commentedUsers = collect();
            $availableUsers = $users->where('id', '!=', $recipe->user_id);

            // Limita o número de comentários ao número de usuários disponíveis
            $maxComments = min($commentCount, $availableUsers->count());

            for ($i = 0; $i < $maxComments; $i++) {
                // Seleciona um usuário aleatório que não seja o dono da receita e que ainda não comentou
                $usersToChoose = $availableUsers->whereNotIn('id', $commentedUsers->pluck('id'));

                if ($usersToChoose->isEmpty()) {
                    break;
                }

                $commentUser = $usersToChoose->random();
                $commentedUsers->push($commentUser);

                RecipeComment::factory()->create([
                    'recipe_id' => $recipe->id,
                    'user_id' => $commentUser->id,
                ]);

                $totalComments++;
            }
        }

        $this->command->info("Criados {$totalComments} comentários em {$recipesToComment->count()} receitas.");
    }
}
