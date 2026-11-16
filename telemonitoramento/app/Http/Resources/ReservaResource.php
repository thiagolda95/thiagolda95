<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Reserva
 */
class ReservaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paciente = $this->paciente;
        $janela = (int) config('telemonitoramento.janela_desfazer_minutos');

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'motivo_remocao' => $this->motivo_remocao
                ? config("telemonitoramento.motivos_remocao.{$this->motivo_remocao}")
                : null,
            'reservado_em' => $this->created_at?->toIso8601String(),
            'finalizada_em' => $this->finalizada_em?->toIso8601String(),
            'pode_desfazer' => $this->finalizada_em?->gt(now()->subMinutes($janela)) ?? false,
            'url_atendimento' => route('atendimentos.create', $this->resource),
            'paciente' => [
                'id' => $paciente->id,
                'nome' => $paciente->nome,
                'idade' => $paciente->idade(),
                // LGPD: o CPF completo não sai do servidor na listagem.
                'cpf' => self::mascararCpf($paciente->cpf),
                'telefone' => self::formatarTelefone($paciente->telefone),
                'telefone_link' => 'tel:+55'.preg_replace('/\D/', '', $paciente->telefone),
                'programa' => $paciente->programa->sigla,
                'prioridade' => $paciente->prioridade->value,
                'prioridade_rotulo' => $paciente->prioridade->rotulo(),
                'proximo_contato_em' => $paciente->proximo_contato_em?->toDateString(),
                'ultimo_acompanhamento_em' => $paciente->ultimo_acompanhamento_em?->toIso8601String(),
            ],
        ];
    }

    public static function mascararCpf(string $cpf): string
    {
        $d = preg_replace('/\D/', '', $cpf);

        return strlen($d) === 11
            ? '***.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-**'
            : '***';
    }

    public static function formatarTelefone(string $telefone): string
    {
        $d = preg_replace('/\D/', '', $telefone);

        return match (strlen($d)) {
            11 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7)),
            10 => sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6)),
            default => $telefone,
        };
    }
}
