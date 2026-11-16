<?php

namespace Tests\Feature;

use App\Enums\StatusReserva;
use App\Models\Paciente;
use App\Models\Programa;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    private User $operador;

    private Programa $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operador = User::factory()->create();
        $this->programa = Programa::factory()->create();
    }

    public function test_painel_abre_para_usuario_autenticado(): void
    {
        $this->withoutVite();
        $this->actingAs($this->operador)->get('/painel')
            ->assertOk()
            ->assertSee('painel-app', false);
    }

    public function test_reserva_segue_prioridade_e_prazo(): void
    {
        $baixa = Paciente::factory()->for($this->programa)->create(['prioridade' => 'baixa', 'proximo_contato_em' => today()->subDays(10)]);
        $altaDepois = Paciente::factory()->for($this->programa)->create(['prioridade' => 'alta', 'proximo_contato_em' => today()->addDays(5)]);
        $altaVencida = Paciente::factory()->for($this->programa)->create(['prioridade' => 'alta', 'proximo_contato_em' => today()->subDay()]);

        $this->actingAs($this->operador)
            ->postJson('/api/painel/reservas', ['programa_id' => $this->programa->id, 'quantidade' => 2])
            ->assertCreated()
            ->assertJsonPath('data.reservados', 2);

        $reservados = Reserva::pluck('paciente_id')->all();
        $this->assertEqualsCanonicalizing([$altaVencida->id, $altaDepois->id], $reservados);
        $this->assertNotContains($baixa->id, $reservados);
    }

    public function test_paciente_reservado_nao_vai_para_outro_operador(): void
    {
        Paciente::factory()->for($this->programa)->count(3)->create();
        $outro = User::factory()->create();

        $this->actingAs($this->operador)->postJson('/api/painel/reservas', ['programa_id' => $this->programa->id, 'quantidade' => 2]);
        $this->actingAs($outro)
            ->postJson('/api/painel/reservas', ['programa_id' => $this->programa->id, 'quantidade' => 5])
            ->assertJsonPath('data.reservados', 1)
            ->assertJsonPath('data.limitado_por', 'fila');

        $this->assertSame(3, Reserva::distinct()->count('paciente_id'));
    }

    public function test_respeita_limite_de_ligacoes_abertas(): void
    {
        config(['telemonitoramento.limite_reservas_ativas' => 3]);
        Paciente::factory()->for($this->programa)->count(10)->create();

        $this->actingAs($this->operador)
            ->postJson('/api/painel/reservas', ['programa_id' => $this->programa->id, 'quantidade' => 3])
            ->assertJsonPath('data.reservados', 3);

        $this->actingAs($this->operador)
            ->postJson('/api/painel/reservas', ['programa_id' => $this->programa->id, 'quantidade' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantidade');
    }

    public function test_programa_obrigatorio_com_mensagem_em_portugues(): void
    {
        $this->actingAs($this->operador)
            ->postJson('/api/painel/reservas', ['quantidade' => 5])
            ->assertUnprocessable()
            ->assertJsonPath('errors.programa_id.0', 'Escolha o programa.');
    }

    public function test_nao_atendeu_e_desfazer(): void
    {
        $reserva = $this->reservaAberta();

        $this->actingAs($this->operador)
            ->postJson("/api/painel/reservas/{$reserva->id}/nao-atendeu")
            ->assertOk()
            ->assertJsonPath('data.status', 'nao_atendida');

        $this->actingAs($this->operador)
            ->postJson("/api/painel/reservas/{$reserva->id}/reabrir")
            ->assertOk()
            ->assertJsonPath('data.status', 'reservado');
    }

    public function test_remover_exige_motivo_e_descricao_para_outro(): void
    {
        $reserva = $this->reservaAberta();

        $this->actingAs($this->operador)
            ->deleteJson("/api/painel/reservas/{$reserva->id}", ['motivo' => 'outro'])
            ->assertJsonValidationErrors('observacao');

        $this->actingAs($this->operador)
            ->deleteJson("/api/painel/reservas/{$reserva->id}", ['motivo' => 'obito'])
            ->assertOk()
            ->assertJsonPath('data.status', 'removida')
            ->assertJsonPath('data.motivo_remocao', 'Óbito informado');
    }

    public function test_operador_nao_altera_reserva_de_outra_pessoa(): void
    {
        $reserva = $this->reservaAberta();

        $this->actingAs(User::factory()->create())
            ->postJson("/api/painel/reservas/{$reserva->id}/nao-atendeu")
            ->assertForbidden();
    }

    public function test_listagem_mascara_cpf_e_resume_o_dia(): void
    {
        $paciente = Paciente::factory()->for($this->programa)->create(['cpf' => '12345678901']);
        Reserva::create(['paciente_id' => $paciente->id, 'user_id' => $this->operador->id, 'status' => StatusReserva::Reservado]);

        $this->actingAs($this->operador)
            ->getJson('/api/painel/reservas?aba=a_realizar')
            ->assertOk()
            ->assertJsonPath('data.0.paciente.cpf', '***.456.789-**')
            ->assertJsonMissing(['cpf' => '12345678901']);

        $this->actingAs($this->operador)
            ->getJson('/api/painel/resumo')
            ->assertJsonPath('data.a_realizar', 1)
            ->assertJsonPath('data.total', 1);
    }

    private function reservaAberta(): Reserva
    {
        $paciente = Paciente::factory()->for($this->programa)->create();

        return Reserva::create([
            'paciente_id' => $paciente->id,
            'user_id' => $this->operador->id,
            'status' => StatusReserva::Reservado,
        ]);
    }
}
