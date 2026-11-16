<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Programa;
use App\Services\ReservaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainelController extends Controller
{
    public function __construct(private readonly ReservaService $reservas) {}

    public function resumo(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reservas->resumoDoDia($request->user()),
            'atualizado_em' => now()->toIso8601String(),
        ]);
    }

    public function programas(): JsonResponse
    {
        $programas = Programa::ativos()
            ->withCount(['pacientes as disponiveis' => fn (Builder $q) => $q->disponiveis()])
            ->orderBy('nome')
            ->get(['id', 'nome', 'sigla']);

        return response()->json(['data' => $programas]);
    }
}
