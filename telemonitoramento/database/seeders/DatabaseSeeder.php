<?php

namespace Database\Seeders;

use App\Enums\StatusReserva;
use App\Models\Paciente;
use App\Models\Programa;
use App\Models\Reserva;
use App\Models\User;
use App\Services\ReservaService;
use Illuminate\Database\Seeder;

/**
 * Cenário de demonstração com dados fictícios: um operador no meio do turno.
 */
class DatabaseSeeder extends Seeder
{
    public function run(ReservaService $reservas): void
    {
        $operador = User::factory()->create([
            'name' => 'ANA PAULA RIBEIRO',
            'papel' => 'Técnica de enfermagem',
            'email' => 'operador@exemplo.test',
        ]);

        $hiperdia = Programa::create(['nome' => 'Hipertensão e Diabetes', 'sigla' => 'HIPERDIA']);
        $gestantes = Programa::create(['nome' => 'Gestantes de alto risco', 'sigla' => 'GESTANTE AR']);
        $tb = Programa::create(['nome' => 'Tuberculose (novos casos)', 'sigla' => 'TB']);

        Paciente::factory()->count(420)->for($hiperdia)->create();
        Paciente::factory()->count(90)->for($gestantes)->create();
        Paciente::factory()->count(40)->for($tb)->create();

        // Reserva 24 pacientes e registra parte do turno.
        $reservas->reservar($operador, $hiperdia, 24);
        $abertas = Reserva::where('user_id', $operador->id)->orderBy('id')->get();

        $roteiro = [
            ...array_fill(0, 7, StatusReserva::Realizada),
            ...array_fill(0, 2, StatusReserva::RealizadaComAlta),
            ...array_fill(0, 3, StatusReserva::NaoAtendida),
        ];

        foreach ($roteiro as $i => $status) {
            $abertas[$i]->update([
                'status' => $status,
                'finalizada_em' => today()->setTime(8, 5)->addMinutes($i * 14),
            ]);
        }

        $abertas[count($roteiro)]->update([
            'status' => StatusReserva::Removida,
            'motivo_remocao' => 'numero_invalido',
            'finalizada_em' => today()->setTime(10, 52),
        ]);
    }
}
