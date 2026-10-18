<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketInteractionController extends Controller
{
    public function store(Request $request, Ticket $ticket, TicketService $tickets): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:20000'],
            'type' => ['required', Rule::in(['comentario', 'nota_interna'])],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,mp3,ogg,oga,wav,m4a,webm,pdf,txt,log'],
        ]);

        $files = $request->file('attachments', []);
        $body = $data['body'] ?? '';
        if ($body === '' && $files !== []) {
            $body = count($files) === 1 ? 'Anexo enviado.' : count($files).' anexos enviados.';
        }

        $tickets->addInteraction($ticket, $request->user(), $body, $data['type'], $files);

        return redirect()->to(route('tickets.show', $ticket).'#historico')->with('success', 'Interação registrada.');
    }
}
