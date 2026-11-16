<?php

namespace App\Models;

use App\Enums\Prioridade;
use App\Enums\StatusReserva;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'programa_id', 'nome', 'cpf', 'data_nascimento', 'telefone',
        'prioridade', 'proximo_contato_em', 'ultimo_acompanhamento_em',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'proximo_contato_em' => 'date',
            'ultimo_acompanhamento_em' => 'datetime',
            'prioridade' => Prioridade::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /**
     * Pacientes que podem ser reservados agora: sem reserva ativa de ninguém
     * e sem tentativa de contato concluída hoje (evita ligar duas vezes no dia).
     */
    public function scopeDisponiveis(Builder $query): void
    {
        $query->whereDoesntHave('reservas', function (Builder $q) {
            $q->where(function (Builder $q) {
                $q->where('status', StatusReserva::Reservado)
                    ->orWhere(function (Builder $q) {
                        $q->whereIn('status', StatusReserva::concluidos())
                            ->where('finalizada_em', '>=', today());
                    });
            });
        });
    }

    /**
     * Ordem da fila: prioridade, depois prazo de contato (vencidos primeiro),
     * depois quem está há mais tempo sem acompanhamento.
     */
    public function scopeOrdemDaFila(Builder $query): void
    {
        $query->orderByRaw(Prioridade::ordemSql())
            ->orderByRaw('proximo_contato_em IS NULL')
            ->orderBy('proximo_contato_em')
            ->orderByRaw('ultimo_acompanhamento_em IS NOT NULL')
            ->orderBy('ultimo_acompanhamento_em');
    }

    public function idade(): int
    {
        return $this->data_nascimento->age;
    }
}
