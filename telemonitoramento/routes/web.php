<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\AtendimentoController;
use App\Http\Controllers\PainelController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::redirect('/', '/painel');
    Route::get('/painel', [PainelController::class, 'index'])->name('painel');

    // Integração com o formulário de atendimento existente.
    Route::get('/atendimentos/{reserva}', [AtendimentoController::class, 'create'])->name('atendimentos.create');
    Route::post('/atendimentos/{reserva}', [AtendimentoController::class, 'store'])->name('atendimentos.store');

    // Endpoints JSON do painel (mesma sessão e proteção CSRF da aplicação web).
    Route::prefix('api/painel')->name('api.')->group(function () {
        Route::get('resumo', [Api\PainelController::class, 'resumo'])->name('resumo');
        Route::get('programas', [Api\PainelController::class, 'programas'])->name('programas');
        Route::get('reservas', [Api\ReservaController::class, 'index'])->name('reservas.index');
        Route::post('reservas', [Api\ReservaController::class, 'store'])->name('reservas.store');
        Route::post('reservas/{reserva}/nao-atendeu', [Api\ReservaController::class, 'naoAtendeu'])->name('reservas.nao-atendeu');
        Route::post('reservas/{reserva}/reabrir', [Api\ReservaController::class, 'reabrir'])->name('reservas.reabrir');
        Route::delete('reservas/{reserva}', [Api\ReservaController::class, 'destroy'])->name('reservas.destroy');
    });

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

/*
| Login de demonstração: só existe em APP_ENV=local.
| No sistema real, use a autenticação já existente (rota nomeada "login").
*/
if (app()->environment('local', 'testing')) {
    Route::get('/login', function () {
        Auth::login(User::firstOrFail());

        return redirect()->route('painel');
    })->name('login');
}
