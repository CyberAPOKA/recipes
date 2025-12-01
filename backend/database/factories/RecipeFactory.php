<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recipeNames = [
            'Bolo de Chocolate',
            'Frango Grelhado',
            'Salada Caesar',
            'Sopa de Legumes',
            'Lasanha à Bolonhesa',
            'Suco de Laranja',
            'Pudim de Leite',
            'Hambúrguer Artesanal',
            'Risotto de Cogumelos',
            'Torta de Limão',
            'Peixe Assado',
            'Salada de Frutas',
            'Macarronada',
            'Brigadeiro',
            'Coxinha',
            'Pizza Margherita',
            'Brownie',
            'Churrasco',
            'Feijoada',
            'Moqueca de Peixe',
            'Pão de Açúcar',
            'Quiche de Espinafre',
            'Tacos Mexicanos',
            'Creme Brulee',
            'Yakisoba',
        ];

        $ingredientsTemplates = [
            '<ul><li>2 xícaras de farinha de trigo</li><li>1 xícara de açúcar</li><li>3 ovos</li><li>1/2 xícara de óleo</li><li>1 colher de sopa de fermento</li></ul>',
            '<ul><li>500g de carne</li><li>2 cebolas</li><li>3 dentes de alho</li><li>Sal e pimenta a gosto</li><li>2 colheres de azeite</li></ul>',
            '<ul><li>1 alface</li><li>2 tomates</li><li>1 pepino</li><li>Azeite e vinagre</li><li>Sal a gosto</li></ul>',
            '<ul><li>1 litro de água</li><li>2 batatas</li><li>2 cenouras</li><li>1 cebola</li><li>Sal e temperos</li></ul>',
            '<ul><li>500g de massa de lasanha</li><li>500g de carne moída</li><li>500g de queijo mussarela</li><li>Molho de tomate</li><li>Queijo parmesão</li></ul>',
        ];

        $instructionsTemplates = [
            '<ol><li>Pré-aqueça o forno a 180°C</li><li>Misture todos os ingredientes secos</li><li>Adicione os ingredientes líquidos</li><li>Misture até obter uma massa homogênea</li><li>Asse por 40 minutos</li></ol>',
            '<ol><li>Aqueça uma panela com azeite</li><li>Refogue a cebola e o alho</li><li>Adicione a carne e tempere</li><li>Cozinhe por 20 minutos</li><li>Sirva quente</li></ol>',
            '<ol><li>Lave bem todos os vegetais</li><li>Corte em pedaços pequenos</li><li>Misture em uma tigela</li><li>Tempere com azeite e vinagre</li><li>Sirva imediatamente</li></ol>',
            '<ol><li>Coloque a água para ferver</li><li>Adicione os legumes cortados</li><li>Cozinhe por 30 minutos</li><li>Tempere a gosto</li><li>Sirva quente</li></ol>',
            '<ol><li>Prepare o molho de carne</li><li>Monte camadas alternadas</li><li>Cubra com queijo</li><li>Asse por 45 minutos</li><li>Deixe descansar antes de servir</li></ol>',
        ];

        // Gera uma URL de imagem genérica aleatória usando picsum.photos
        // Usa um número aleatório para garantir imagens diferentes
        $randomId = fake()->numberBetween(1, 1000);
        $imageUrl = "https://picsum.photos/640/480?random={$randomId}";

        return [
            'user_id' => User::factory(),
            'category_id' => Category::inRandomOrder()->value('id'),
            'name' => fake()->randomElement($recipeNames) . ' ' . fake()->word(),
            'prep_time_minutes' => fake()->numberBetween(15, 180),
            'servings' => fake()->numberBetween(2, 12),
            'image' => $imageUrl,
            'instructions' => fake()->randomElement($instructionsTemplates),
            'ingredients' => fake()->randomElement($ingredientsTemplates),
        ];
    }
}
