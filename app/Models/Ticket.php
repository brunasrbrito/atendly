<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'external_id', 'source', 'title', 'description', 'requester_id', 'related_ticket_id', 'category_id',
    'assigned_to', 'status', 'priority', 'solution', 'opened_at', 'received_at',
    'acknowledged_at', 'first_response_at', 'resolved_at', 'closed_at', 'is_synthetic',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'opened_at' => 'datetime',
            'received_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'is_synthetic' => 'boolean',
        ];
    }

    /** @return BelongsTo<Requester, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Requester::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Chamado anterior que originou este (o problema voltou ou é desdobramento).
     *
     * @return BelongsTo<Ticket, $this>
     */
    public function relatedTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'related_ticket_id');
    }

    /** @return HasMany<Ticket, $this> */
    public function followUps(): HasMany
    {
        return $this->hasMany(Ticket::class, 'related_ticket_id');
    }

    /** @return HasMany<TicketInteraction, $this> */
    public function interactions(): HasMany
    {
        return $this->hasMany(TicketInteraction::class)->oldest();
    }

    /** @return HasMany<TicketAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /** @return BelongsToMany<KnowledgeArticle, $this> */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class)->withPivot('user_id')->withTimestamps();
    }

    /** @param Builder<Ticket> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', TicketStatus::open());
    }

    /** Chamados que ainda ninguém assumiu. */
    public function scopeUnacknowledged(Builder $query): void
    {
        $query->whereNull('acknowledged_at')->whereIn('status', TicketStatus::open());
    }

    /** Minutos entre a abertura e a percepção (alguém assumir). Null enquanto pendente. */
    public function perceptionMinutes(): ?int
    {
        return $this->acknowledged_at ? (int) $this->opened_at->diffInMinutes($this->acknowledged_at) : null;
    }

    public function resolutionMinutes(): ?int
    {
        $end = $this->resolved_at ?? $this->closed_at;

        return $end ? (int) $this->opened_at->diffInMinutes($end) : null;
    }

    /** Tempo de espera atual (para chamados ainda não assumidos). */
    public function waitingMinutes(): int
    {
        return (int) $this->opened_at->diffInMinutes($this->acknowledged_at ?? now());
    }

    public function isPerceptionLate(): bool
    {
        $limit = $this->category?->sla_response_minutes ?? config('atendly.ack_alert_minutes');

        return $this->waitingMinutes() > $limit;
    }

    /** Resolvido ou fechado: somente leitura. Novo problema = novo chamado. */
    public function isLocked(): bool
    {
        return ! $this->status->isOpen();
    }

    public static function sourceLabel(string $source): string
    {
        return match ($source) {
            'manual' => 'cadastro manual',
            'webhook', 'integracao' => 'integração',
            default => $source,
        };
    }

    public function protocol(): string
    {
        return $this->external_id ?? '#'.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
