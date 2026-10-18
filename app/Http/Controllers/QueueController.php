<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint consultado periodicamente pelo front-end para avisar novos chamados
 * (notificação do navegador, som e contador no título da aba).
 */
class QueueController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $since = $request->integer('desde');

        $new = $since > 0
            ? Ticket::with('category')->where('id', '>', $since)->orderBy('id')->limit(20)->get()
            : collect();

        $pending = Ticket::with('category')->unacknowledged()->get();

        return response()->json([
            'latest_id' => (int) Ticket::max('id'),
            'unacknowledged' => $pending->count(),
            'late' => $pending->filter->isPerceptionLate()->count(),
            'oldest_waiting_minutes' => $pending->map->waitingMinutes()->max(),
            'new' => $new->map(fn (Ticket $t) => [
                'id' => $t->id,
                'protocol' => $t->protocol(),
                'title' => $t->title,
                'category' => $t->category?->name,
                'priority' => $t->priority->label(),
                'url' => route('tickets.show', $t),
            ])->values(),
        ]);
    }
}
