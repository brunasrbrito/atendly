<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Novo = 'novo';
    case EmAtendimento = 'em_atendimento';
    case Aguardando = 'aguardando';
    case Resolvido = 'resolvido';
    case Fechado = 'fechado';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::EmAtendimento => 'Em atendimento',
            self::Aguardando => 'Aguardando solicitante',
            self::Resolvido => 'Resolvido',
            self::Fechado => 'Fechado',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Resolvido, self::Fechado], true);
    }

    /** @return list<self> */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => $s->isOpen()));
    }
}
