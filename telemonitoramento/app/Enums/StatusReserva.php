<?php

namespace App\Enums;

enum StatusReserva: string
{
    case Reservado = 'reservado';
    case Realizada = 'realizada';
    case RealizadaComAlta = 'realizada_alta';
    case NaoAtendida = 'nao_atendida';
    case Removida = 'removida';

    public function rotulo(): string
    {
        return match ($this) {
            self::Reservado => 'A realizar',
            self::Realizada => 'Ligação realizada',
            self::RealizadaComAlta => 'Realizada com alta',
            self::NaoAtendida => 'Não atendeu',
            self::Removida => 'Removido da reserva',
        };
    }

    /**
     * Status que contam como tentativa de contato concluída no dia.
     *
     * @return list<self>
     */
    public static function concluidos(): array
    {
        return [self::Realizada, self::RealizadaComAlta, self::NaoAtendida];
    }

    /**
     * @return list<self>
     */
    public static function finalizados(): array
    {
        return [...self::concluidos(), self::Removida];
    }
}
