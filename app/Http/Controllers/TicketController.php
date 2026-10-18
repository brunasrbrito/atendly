<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\User;
use App\Services\KnowledgeSearch;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class TicketController extends Controller
{
    public const VIEWS = [
        'pendentes' => 'Aguardando percepção',
        'abertos' => 'Em aberto',
        'meus' => 'Meus chamados',
        'encerrados' => 'Encerrados',
        'todos' => 'Todos',
    ];

    public function __construct(private TicketService $tickets) {}

    public function index(Request $request): View
    {
        $view = array_key_exists($request->query('ver'), self::VIEWS) ? $request->query('ver') : 'abertos';
        $q = trim((string) $request->query('q'));

        $tickets = Ticket::query()
            ->with(['category', 'requester', 'technician'])
            ->when($view === 'pendentes', fn ($query) => $query->unacknowledged())
            ->when($view === 'abertos', fn ($query) => $query->open())
            ->when($view === 'meus', fn ($query) => $query->open()->where('assigned_to', $request->user()->id))
            ->when($view === 'encerrados', fn ($query) => $query->whereIn('status', [TicketStatus::Resolvido, TicketStatus::Fechado]))
            ->when($request->filled('categoria'), fn ($query) => $query->where('category_id', $request->integer('categoria')))
            ->when($request->filled('prioridade'), fn ($query) => $query->where('priority', $request->query('prioridade')))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('external_id', 'like', "%{$q}%")
                ->when(ctype_digit(ltrim($q, '#')), fn ($w) => $w->orWhere('id', (int) ltrim($q, '#')))))
            ->when(
                in_array($view, ['encerrados', 'todos'], true),
                fn ($query) => $query->orderByDesc('opened_at'),
                // Na fila: não percebidos primeiro, depois quem espera há mais tempo.
                fn ($query) => $query->orderByRaw('CASE WHEN acknowledged_at IS NULL THEN 0 ELSE 1 END')->orderBy('opened_at'),
            )
            ->paginate(25)
            ->withQueryString();

        $pending = Ticket::with('category')->unacknowledged()->get();

        return view('tickets.index', [
            'tickets' => $tickets,
            'pending' => [
                'count' => $pending->count(),
                'late' => $pending->filter->isPerceptionLate()->count(),
                'oldest' => $pending->map->waitingMinutes()->max(),
            ],
            'view' => $view,
            'views' => self::VIEWS,
            'counts' => [
                'pendentes' => Ticket::unacknowledged()->count(),
                'abertos' => Ticket::open()->count(),
                'meus' => Ticket::open()->where('assigned_to', $request->user()->id)->count(),
            ],
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        // "Abrir novo chamado relacionado" a partir de um chamado encerrado.
        $related = $request->filled('relacionado')
            ? Ticket::with(['requester', 'category'])->find($request->integer('relacionado'))
            : null;

        return view('tickets.create', [
            'related' => $related,
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'external_id' => ['nullable', 'string', 'max:100', 'unique:tickets,external_id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', 'exists:categories,slug'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'opened_at' => ['nullable', 'date', 'before_or_equal:now'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_department' => ['nullable', 'string', 'max:255'],
            'related_ticket_id' => ['nullable', 'exists:tickets,id'],
            'is_synthetic' => ['sometimes', 'boolean'],
            'attachments' => ['array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,mp3,ogg,oga,wav,m4a,webm,pdf,txt,log'],
        ]);

        $ticket = $this->tickets->receive([
            ...$data,
            'requester' => ['name' => $data['requester_name'], 'department' => $data['requester_department'] ?? null],
            'is_synthetic' => $request->boolean('is_synthetic'),
        ], 'manual');

        foreach ($request->file('attachments', []) as $file) {
            $this->tickets->storeAttachment($ticket, $file);
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Chamado registrado.');
    }

    public function show(Ticket $ticket, KnowledgeSearch $search): View
    {
        $ticket->load([
            'category', 'requester', 'technician', 'interactions.user', 'interactions.attachments',
            'attachments', 'articles', 'relatedTicket', 'followUps',
        ]);

        return view('tickets.show', [
            'ticket' => $ticket,
            // Encerrado não precisa de sugestões: a solução já está registrada.
            'similar' => $ticket->isLocked() ? collect() : $search->similarTo($ticket, 4),
            'terms' => $search->terms($ticket->title),
            'categories' => Category::orderBy('name')->get(),
            'priorities' => TicketPriority::cases(),
            'openStatuses' => TicketStatus::open(),
            'technicians' => User::where('is_active', true)->whereIn('role', [UserRole::Tecnico, UserRole::Admin])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_map(fn (TicketStatus $s) => $s->value, TicketStatus::open()))],
            'category_id' => ['nullable', 'exists:categories,id'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $this->tickets->updateDetails($ticket, $data, $request->user());

        return back()->with('success', 'Chamado atualizado.');
    }

    public function acknowledge(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->acknowledge($ticket, $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Você assumiu o chamado.');
    }

    /** Assume o chamado que está há mais tempo esperando alguém perceber. */
    public function acknowledgeNext(Request $request): RedirectResponse
    {
        $ticket = Ticket::unacknowledged()->orderBy('opened_at')->first();

        if (! $ticket) {
            return redirect()->route('tickets.index')->with('success', 'Nenhum chamado aguardando. Fila em dia!');
        }

        $this->tickets->acknowledge($ticket, $request->user());

        return redirect()->route('tickets.show', $ticket)->with('success', 'Você assumiu o chamado que esperava há mais tempo.');
    }

    public function status(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->tickets->ensureEditable($ticket);

        $data = $request->validate([
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'solution' => ['nullable', 'string', 'max:20000'],
        ]);

        try {
            $this->tickets->changeStatus($ticket, TicketStatus::from($data['status']), $request->user(), $data['solution'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['solution' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Status atualizado.');
    }

    public function linkArticle(Request $request, Ticket $ticket, KnowledgeArticle $article): RedirectResponse
    {
        $this->tickets->linkArticle($ticket, $article, $request->user());

        return redirect()->to(route('tickets.show', $ticket).'#resolver')
            ->with('success', 'Artigo aplicado. A solução foi pré-preenchida: revise e encerre o chamado.');
    }
}
