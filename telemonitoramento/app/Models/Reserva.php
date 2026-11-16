<?php

namespace App\Models;

use App\Enums\StatusReserva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    use HasFactory;

    protected $fillable = [
        'paciente_id', 'user_id', 'status', 'motivo_remocao',
        'observacao_remocao', 'finalizada_em',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusReserva::class,
            'finalizada_em' => 'datetime',
        ];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reservas que aparecem no painel de hoje do operador:
     * as ainda abertas (de qualquer dia) e as finalizadas hoje.
     */
    public function scopeDoDia(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id)
            ->where(function (Builder $q) {
                $q->where('status', StatusReserva::Reservado)
                    ->orWhere('finalizada_em', '>=', today());
            });
    }

    public function estaAberta(): bool
    {
        return $this->status === StatusReserva::Reservado;
    }
}
