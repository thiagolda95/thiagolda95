<?php

namespace App\Http\Controllers;

use App\Enums\StatusReserva;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Ponto de integração com o formulário de atendimento (SOAP) já existente.
 * Aqui há só o mínimo para fechar o ciclo da ligação no painel.
 */
class AtendimentoController extends Controller
{
    public function __construct(private readonly ReservaService $reservas) {}

    public function create(Reserva $reserva): View
    {
        Gate::authorize('update', $reserva);

        return view('atendimento', ['reserva' => $reserva->load('paciente.programa')]);
    }

    public function store(Request $request, Reserva $reserva): RedirectResponse
    {
        Gate::authorize('update', $reserva);

        $dados = $request->validate([
            'resultado' => ['required', Rule::in([StatusReserva::Realizada->value, StatusReserva::RealizadaComAlta->value])],
        ]);

        $this->reservas->registrarResultado($reserva, StatusReserva::from($dados['resultado']));

        return redirect()->route('painel')->with('aviso', 'Atendimento registrado.');
    }
}
