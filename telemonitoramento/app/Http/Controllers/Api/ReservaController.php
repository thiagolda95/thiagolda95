<?php

namespace App\Http\Controllers\Api;

use App\Enums\Prioridade;
use App\Enums\StatusReserva;
use App\Http\Controllers\Controller;
use App\Http\Requests\RemoverReservaRequest;
use App\Http\Requests\ReservarPacientesRequest;
use App\Http\Resources\ReservaResource;
use App\Models\Paciente;
use App\Models\Programa;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    public function __construct(private readonly ReservaService $reservas) {}

    /**
     * Lista as ligações do operador.
     * aba=a_realizar → abertas, na ordem da fila.
     * aba=realizadas → finalizadas hoje, mais recentes primeiro.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'aba' => ['required', Rule::in(['a_realizar', 'realizadas'])],
            'busca' => ['nullable', 'string', 'max:100'],
            'resultado' => ['nullable', Rule::in(array_column(StatusReserva::finalizados(), 'value'))],
            'por_pagina' => ['nullable', 'integer', Rule::in([10, 25, 50])],
        ]);

        $consulta = Reserva::query()
            ->doDia($request->user())
            ->with('paciente.programa')
            ->when($dados['busca'] ?? null, function (Builder $q, string $busca) {
                $digitos = preg_replace('/\D/', '', $busca);
                $q->whereHas('paciente', fn (Builder $p) => $p
                    ->where('nome', 'like', '%'.$busca.'%')
                    ->when(strlen($digitos) >= 3, fn (Builder $p) => $p->orWhere('cpf', 'like', '%'.$digitos.'%')));
            });

        if ($dados['aba'] === 'a_realizar') {
            $consulta->where('status', StatusReserva::Reservado)
                ->orderBy(
                    Paciente::selectRaw(Prioridade::ordemSql())->whereColumn('pacientes.id', 'reservas.paciente_id')
                )
                ->orderBy(
                    Paciente::select('proximo_contato_em')->whereColumn('pacientes.id', 'reservas.paciente_id')
                )
                ->orderBy('reservas.id');
        } else {
            $consulta->where('status', '!=', StatusReserva::Reservado)
                ->when($dados['resultado'] ?? null, fn (Builder $q, string $r) => $q->where('status', $r))
                ->latest('finalizada_em');
        }

        return ReservaResource::collection($consulta->paginate($dados['por_pagina'] ?? 10));
    }

    public function store(ReservarPacientesRequest $request): JsonResponse
    {
        $programa = Programa::findOrFail($request->integer('programa_id'));
        $resultado = $this->reservas->reservar($request->user(), $programa, $request->integer('quantidade'));

        return response()->json(['data' => $resultado], 201);
    }

    public function naoAtendeu(Reserva $reserva): ReservaResource
    {
        Gate::authorize('update', $reserva);
        $this->reservas->registrarResultado($reserva, StatusReserva::NaoAtendida);

        return new ReservaResource($reserva->refresh()->load('paciente.programa'));
    }

    public function destroy(RemoverReservaRequest $request, Reserva $reserva): ReservaResource
    {
        Gate::authorize('update', $reserva);
        $this->reservas->remover($reserva, $request->string('motivo')->toString(), $request->input('observacao'));

        return new ReservaResource($reserva->refresh()->load('paciente.programa'));
    }

    public function reabrir(Reserva $reserva): ReservaResource
    {
        Gate::authorize('update', $reserva);
        $this->reservas->reabrir($reserva);

        return new ReservaResource($reserva->refresh()->load('paciente.programa'));
    }
}
