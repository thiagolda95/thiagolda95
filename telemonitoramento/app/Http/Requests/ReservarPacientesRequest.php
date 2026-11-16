<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservarPacientesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'programa_id' => ['required', 'integer', Rule::exists('programas', 'id')->where('ativo', true)],
            'quantidade' => ['required', 'integer', 'min:1', 'max:'.config('telemonitoramento.limite_reservas_ativas')],
        ];
    }

    public function messages(): array
    {
        return [
            'programa_id.required' => 'Escolha o programa.',
            'programa_id.exists' => 'Este programa não está disponível para reserva.',
            'quantidade.required' => 'Informe quantos pacientes deseja reservar.',
            'quantidade.min' => 'Reserve pelo menos 1 paciente.',
            'quantidade.max' => 'Reserve no máximo :max pacientes por vez.',
            'quantidade.integer' => 'Use um número inteiro.',
        ];
    }
}
