<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    /**
     * Só quem reservou o paciente pode registrar o resultado da ligação.
     */
    public function update(User $user, Reserva $reserva): bool
    {
        return $reserva->user_id === $user->id;
    }
}
