<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    public function store(Request $request, TicketService $tickets): JsonResponse
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'opened_at' => ['nullable', 'date'],
            'requester' => ['nullable', 'array'],
            'requester.external_id' => ['nullable', 'string', 'max:100'],
            'requester.name' => ['required_with:requester', 'string', 'max:255'],
            'requester.department' => ['nullable', 'string', 'max:255'],
            'is_synthetic' => ['sometimes', 'boolean'],
        ]);

        $ticket = $tickets->receive($data, 'webhook');

        return response()->json([
            'id' => $ticket->id,
            'external_id' => $ticket->external_id,
            'status' => $ticket->status->value,
            'url' => route('tickets.show', $ticket),
        ], $ticket->wasRecentlyCreated ? 201 : 200);
    }
}
