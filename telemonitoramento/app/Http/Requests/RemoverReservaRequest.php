<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RemoverReservaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'motivo' => ['required', Rule::in(array_keys(config('telemonitoramento.motivos_remocao')))],
            'observacao' => ['nullable', 'string', 'max:500', 'required_if:motivo,outro'],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Escolha o motivo da remoção.',
            'motivo.in' => 'Escolha um motivo da lista.',
            'observacao.required_if' => 'Descreva o motivo.',
            'observacao.max' => 'Use no máximo 500 caracteres.',
        ];
    }
}
