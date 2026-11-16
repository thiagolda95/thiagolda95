<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Programa>
 */
class ProgramaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => 'Programa '.fake()->unique()->word(),
            'sigla' => strtoupper(fake()->unique()->lexify('????')),
            'ativo' => true,
        ];
    }
}
