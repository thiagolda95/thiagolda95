<?php

namespace Database\Factories;

use App\Models\Programa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Dados fictícios para desenvolvimento e testes.
 *
 * @extends Factory<\App\Models\Paciente>
 */
class PacienteFactory extends Factory
{
    public function definition(): array
    {
        $ultimo = fake()->optional(0.85)->dateTimeBetween('-150 days', '-5 days');

        return [
            'programa_id' => Programa::factory(),
            'nome' => fake()->firstName().' '.fake()->lastName().' '.fake()->lastName(),
            'cpf' => fake()->unique()->numerify('###########'),
            'data_nascimento' => fake()->dateTimeBetween('-88 years', '-35 years'),
            'telefone' => '929'.fake()->numerify('########'),
            'prioridade' => fake()->randomElement(['alta', 'media', 'media', 'baixa', 'baixa', 'baixa']),
            'proximo_contato_em' => fake()->optional(0.9)->dateTimeBetween('-12 days', '+20 days'),
            'ultimo_acompanhamento_em' => $ultimo,
        ];
    }
}
