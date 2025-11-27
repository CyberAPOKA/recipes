<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Bolos e tortas doces'],
            ['name' => 'Carnes'],
            ['name' => 'Aves'],
            ['name' => 'Peixes e frutos do mar'],
            ['name' => 'Saladas, molhos e acompanhamentos'],
            ['name' => 'Sopas'],
            ['name' => 'Massas'],
            ['name' => 'Bebidas'],
            ['name' => 'Doces e sobremesas'],
            ['name' => 'Lanches'],
            ['name' => 'Prato Único'],
            ['name' => 'Light'],
            ['name' => 'Alimentação Saudável'],
        ]);
    }
}
