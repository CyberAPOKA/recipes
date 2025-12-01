<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();

        if ($users->isEmpty()) {
            $this->command->warn('Nenhum usuário encontrado. Execute UserSeeder primeiro.');
            return;
        }

        if ($categories->isEmpty()) {
            $this->command->warn('Nenhuma categoria encontrada. Execute CategorySeeder primeiro.');
            return;
        }

        foreach ($users as $user) {
            // Cada usuário terá entre 5 e 10 receitas
            $recipeCount = rand(5, 10);

            Recipe::factory()
                ->count($recipeCount)
                ->create([
                    'user_id' => $user->id,
                    'category_id' => $categories->random()->id,
                ]);
        }

        $this->command->info("Criadas receitas para {$users->count()} usuários.");
    }
}
