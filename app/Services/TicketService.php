<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\TicketLockedException;
use App\Models\Category;
use App\Models\KnowledgeArticle;
use App\Models\Requester;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketInteraction;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Regras do ciclo de vida do chamado: recebimento, percepção, interações e encerramento.
 */
class TicketService
{
    /**
     * Registra um chamado vindo do sistema de terceiros (webhook, simulador ou cadastro manual).
     *
     * @param  array{external_id?: ?string, title: string, description: string, requester?: array{external_id?: ?string, name: string, department?: ?string}, category?: ?string, priority?: ?string, opened_at?: mixed, is_synthetic?: bool}  $data
     */
    public function receive(array $data, string $source = 'manual'): Ticket
    {
        return DB::transaction(function () use ($data, $source) {
            if (! empty($data['external_id'])) {
                $existing = Ticket::where('external_id', $data['external_id'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            $synthetic = (bool) ($data['is_synthetic'] ?? false);

            $ticket = Ticket::create([
                'external_id' => $data['external_id'] ?? null,
                'source' => $source,
                'title' => $data['title'],
                'description' => $data['description'],
                'requester_id' => $this->resolveRequester($data['requester'] ?? null, $synthetic)?->id,
                'related_ticket_id' => $data['related_ticket_id'] ?? null,
                'category_id' => $this->resolveCategory($data['category'] ?? null)?->id,
                'priority' => TicketPriority::tryFrom((string) ($data['priority'] ?? '')) ?? TicketPriority::Media,
                'status' => TicketStatus::Novo,
                'opened_at' => isset($data['opened_at']) ? Carbon::parse($data['opened_at']) : now(),
                'received_at' => now(),
                'is_synthetic' => $synthetic,
            ]);

            $this->logEvent($ticket, 'Chamado recebido no Atendly (origem: '.Ticket::sourceLabel($source).').');

            if ($ticket->relatedTicket) {
                $this->logEvent($ticket, "Aberto como continuação do chamado {$ticket->relatedTicket->protocol()}.");
            }

            return $ticket;
        });
    }

    /** O técnico percebeu o chamado e o assume. Marca o fim do "tempo até percepção". */
    public function acknowledge(Ticket $ticket, User $user): Ticket
    {
        $this->ensureEditable($ticket);

        if ($ticket->acknowledged_at === null) {
            $ticket->acknowledged_at = now();
        }

        if ($ticket->assigned_to === null) {
            $ticket->assigned_to = $user->id;
        }

        if ($ticket->status === TicketStatus::Novo) {
            $ticket->status = TicketStatus::EmAtendimento;
        }

        if ($ticket->isDirty()) {
            $ticket->save();
            $this->logEvent($ticket, "{$user->name} assumiu o chamado.", $user);
        }

        return $ticket;
    }

    public function assign(Ticket $ticket, ?User $technician, User $by): Ticket
    {
        $this->ensureEditable($ticket);

        $ticket->assigned_to = $technician?->id;
        $ticket->acknowledged_at ??= now();
        $ticket->save();

        $this->logEvent($ticket, $technician
            ? "{$by->name} atribuiu o chamado a {$technician->name}."
            : "{$by->name} removeu o técnico responsável.", $by);

        return $ticket;
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function addInteraction(Ticket $ticket, User $user, string $body, string $type = 'comentario', array $files = []): TicketInteraction
    {
        $this->ensureEditable($ticket);

        return DB::transaction(function () use ($ticket, $user, $body, $type, $files) {
            $interaction = $ticket->interactions()->create([
                'user_id' => $user->id,
                'author_type' => 'tecnico',
                'type' => $type,
                'body' => $body,
            ]);

            foreach ($files as $file) {
                $this->storeAttachment($ticket, $file, $interaction);
            }

            // Responder o solicitante também conta como percepção.
            $ticket->acknowledged_at ??= now();
            if ($type === 'comentario') {
                $ticket->first_response_at ??= now();
            }
            $ticket->save();

            return $interaction;
        });
    }

    public function storeAttachment(Ticket $ticket, UploadedFile $file, ?TicketInteraction $interaction = null): TicketAttachment
    {
        $mime = $file->getMimeType() ?? 'application/octet-stream';
        $name = Str::uuid().'.'.($file->getClientOriginalExtension() ?: $file->extension());

        return $ticket->attachments()->create([
            'ticket_interaction_id' => $interaction?->id,
            'kind' => TicketAttachment::kindFromMime($mime),
            'path' => $file->storeAs("tickets/{$ticket->id}", $name, 'public'),
            'original_name' => $file->getClientOriginalName() ?: $name,
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ]);
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, User $user, ?string $solution = null): Ticket
    {
        $this->ensureEditable($ticket);

        if (in_array($status, [TicketStatus::Resolvido, TicketStatus::Fechado], true)
            && blank($solution ?? $ticket->solution)) {
            throw new InvalidArgumentException('Descreva a solução aplicada antes de resolver ou fechar o chamado.');
        }

        $previous = $ticket->status;
        $ticket->status = $status;

        if (filled($solution)) {
            $ticket->solution = $solution;
        }

        $ticket->acknowledged_at ??= now();
        $ticket->assigned_to ??= $user->id;

        if (! $status->isOpen()) {
            $ticket->resolved_at ??= now();
            if ($status === TicketStatus::Fechado) {
                $ticket->closed_at ??= now();
            }
        }

        $ticket->save();

        if ($previous !== $status) {
            $this->logEvent($ticket, "{$user->name} alterou o status de \"{$previous->label()}\" para \"{$status->label()}\".", $user);
        }

        return $ticket;
    }

    /** Registra que um artigo da base foi usado para resolver o chamado. */
    public function linkArticle(Ticket $ticket, KnowledgeArticle $article, User $user): void
    {
        $this->ensureEditable($ticket);

        if (blank($ticket->solution)) {
            $ticket->update(['solution' => $article->solution]);
        }

        if ($ticket->articles()->whereKey($article->id)->exists()) {
            return;
        }

        $ticket->articles()->attach($article->id, ['user_id' => $user->id]);
        $article->increment('helpful_count');
        $this->logEvent($ticket, "{$user->name} aplicou o artigo \"{$article->title}\" da base de conhecimento.", $user);
    }

    /**
     * Atualiza situação (somente estados em aberto), categoria, prioridade e técnico.
     * Resolver ou fechar passa por changeStatus(), que exige a descrição da solução.
     *
     * @param  array{status?: ?string, category_id?: ?int, priority: string, assigned_to?: ?int}  $data
     */
    public function updateDetails(Ticket $ticket, array $data, User $user): Ticket
    {
        $this->ensureEditable($ticket);

        return DB::transaction(function () use ($ticket, $data, $user) {
            if ((int) $ticket->assigned_to !== (int) ($data['assigned_to'] ?? 0)) {
                $this->assign($ticket, isset($data['assigned_to']) ? User::find($data['assigned_to']) : null, $user);
            }

            $ticket->fill(['category_id' => $data['category_id'] ?? null, 'priority' => $data['priority']]);
            if ($ticket->isDirty('category_id')) {
                $this->logEvent($ticket, "{$user->name} recategorizou o chamado.", $user);
            }
            $ticket->save();

            $status = TicketStatus::tryFrom((string) ($data['status'] ?? ''));
            if ($status && $status->isOpen() && $status !== $ticket->status) {
                $this->changeStatus($ticket, $status, $user);
            }

            return $ticket;
        });
    }

    /** @throws TicketLockedException */
    public function ensureEditable(Ticket $ticket): void
    {
        if ($ticket->isLocked()) {
            throw new TicketLockedException($ticket);
        }
    }

    public function logEvent(Ticket $ticket, string $body, ?User $user = null): TicketInteraction
    {
        return $ticket->interactions()->create([
            'user_id' => $user?->id,
            'author_type' => 'sistema',
            'author_name' => 'Atendly',
            'type' => 'evento',
            'body' => $body,
        ]);
    }

    /** @param  array{external_id?: ?string, name: string, department?: ?string}|null  $data */
    private function resolveRequester(?array $data, bool $synthetic): ?Requester
    {
        if (! $data || blank($data['name'] ?? null)) {
            return null;
        }

        $attributes = ['name' => $data['name'], 'department' => $data['department'] ?? null, 'is_synthetic' => $synthetic];

        return empty($data['external_id'])
            ? Requester::create($attributes)
            : Requester::updateOrCreate(['external_id' => $data['external_id']], $attributes);
    }

    private function resolveCategory(?string $value): ?Category
    {
        if (blank($value)) {
            return null;
        }

        return Category::where('slug', Str::slug($value))->orWhere('name', $value)->first();
    }
}
