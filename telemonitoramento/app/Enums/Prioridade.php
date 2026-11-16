<?php

namespace App\Enums;

enum Prioridade: string
{
    case Alta = 'alta';
    case Media = 'media';
    case Baixa = 'baixa';

    public function rotulo(): string
    {
        return match ($this) {
            self::Alta => 'Alta',
            self::Media => 'Média',
            self::Baixa => 'Baixa',
        };
    }

    /**
     * Expressão SQL que ordena Alta → Média → Baixa.
     */
    public static function ordemSql(string $coluna = 'prioridade'): string
    {
        return "CASE {$coluna} WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END";
    }
}
