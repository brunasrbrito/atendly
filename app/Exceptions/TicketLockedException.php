<?php

namespace App\Exceptions;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Chamado resolvido ou fechado não pode mais ser alterado.
 * Se o problema voltar ou surgir outro, abre-se um novo chamado relacionado.
 */
class TicketLockedException extends RuntimeException
{
    public function __construct(public readonly Ticket $ticket)
    {
        parent::__construct("O chamado {$ticket->protocol()} está encerrado e não pode ser alterado. Se o problema voltou ou é outro, abra um novo chamado relacionado.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return redirect()->route('tickets.show', $this->ticket)->with('error', $this->getMessage());
    }
}
