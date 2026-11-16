<?php

namespace App\Services;

use App\Enums\StatusReserva;
use App\Models\Paciente;
use App\Models\Programa;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservaService
{
    /**
     * Reserva até $quantidade pacientes do programa para o operador,
     * seguindo a ordem da fila (prioridade → prazo → tempo sem contato).
     *
     * @return array{reservados: int, solicitados: int, limitado_por: string|null}
     */
    public function reservar(User $user, Programa $programa, int $quantidade): array
    {
        $limite = (int) config('telemonitoramento.limite_reservas_ativas');
        $abertas = Reserva::where('user_id', $user->id)
            ->where('status', StatusReserva::Reservado)
            ->count();
        $vagas = max(0, $limite - $abertas);

        if ($vagas === 0) {
            throw ValidationException::withMessages([
                'quantidade' => "Você já tem {$abertas} ligações a realizar, o limite por operador. Conclua algumas antes de reservar mais.",
            ]);
        }

        $aReservar = min($quantidade, $vagas);

        $reservados = DB::transaction(function () use ($user, $programa, $aReservar) {
            $consulta = Paciente::query()
                ->where('programa_id', $programa->id)
                ->disponiveis()
                ->ordemDaFila()
                ->limit($aReservar);

            // Em MySQL 8 / PostgreSQL, SKIP LOCKED impede que dois operadores
            // reservando ao mesmo tempo recebam o mesmo paciente.
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
                $consulta->lock('for update skip locked');
            }

            $ids = $consulta->pluck('id');
            $agora = now();

            Reserva::insert($ids->map(fn (int $id) => [
                'paciente_id' => $id,
                'user_id' => $user->id,
                'status' => StatusReserva::Reservado->value,
                'created_at' => $agora,
                'updated_at' => $agora,
            ])->all());

            return $ids->count();
        });

        return [
            'reservados' => $reservados,
            'solicitados' => $quantidade,
            'limitado_por' => match (true) {
                $reservados < $aReservar => 'fila',
                $aReservar < $quantidade => 'limite',
                default => null,
            },
        ];
    }

    public function registrarResultado(Reserva $reserva, StatusReserva $resultado): void
    {
        $this->garantirAberta($reserva);

        $reserva->update(['status' => $resultado, 'finalizada_em' => now()]);

        if ($resultado !== StatusReserva::NaoAtendida) {
            $reserva->paciente->update(['ultimo_acompanhamento_em' => now()]);
        }
    }

    public function remover(Reserva $reserva, string $motivo, ?string $observacao): void
    {
        $this->garantirAberta($reserva);

        $reserva->update([
            'status' => StatusReserva::Removida,
            'motivo_remocao' => $motivo,
            'observacao_remocao' => $observacao,
            'finalizada_em' => now(),
        ]);
    }

    /**
     * Desfaz "Não atendeu" ou "Remover" feitos há poucos minutos.
     */
    public function reabrir(Reserva $reserva): void
    {
        $janela = (int) config('telemonitoramento.janela_desfazer_minutos');
        $podeDesfazer = in_array($reserva->status, [StatusReserva::NaoAtendida, StatusReserva::Removida], true)
            && $reserva->finalizada_em?->gt(now()->subMinutes($janela));

        if (! $podeDesfazer) {
            throw ValidationException::withMessages([
                'reserva' => "Só é possível desfazer nos primeiros {$janela} minutos.",
            ]);
        }

        $reserva->update([
            'status' => StatusReserva::Reservado,
            'motivo_remocao' => null,
            'observacao_remocao' => null,
            'finalizada_em' => null,
        ]);
    }

    /**
     * Números do painel "Meu progresso hoje".
     *
     * @return array<string, int>
     */
    public function resumoDoDia(User $user): array
    {
        $contagem = Reserva::query()
            ->doDia($user)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $n = fn (StatusReserva $s): int => (int) ($contagem[$s->value] ?? 0);

        $resumo = [
            'a_realizar' => $n(StatusReserva::Reservado),
            'realizadas' => $n(StatusReserva::Realizada),
            'realizadas_alta' => $n(StatusReserva::RealizadaComAlta),
            'nao_atendidas' => $n(StatusReserva::NaoAtendida),
            'removidas' => $n(StatusReserva::Removida),
        ];

        $resumo['concluidas'] = $resumo['realizadas'] + $resumo['realizadas_alta'] + $resumo['nao_atendidas'];
        $resumo['total'] = $resumo['concluidas'] + $resumo['a_realizar'];
        $resumo['limite'] = (int) config('telemonitoramento.limite_reservas_ativas');

        return $resumo;
    }

    private function garantirAberta(Reserva $reserva): void
    {
        if (! $reserva->estaAberta()) {
            throw ValidationException::withMessages([
                'reserva' => 'Esta ligação já foi finalizada. Atualize o painel.',
            ]);
        }
    }
}
