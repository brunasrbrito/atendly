<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'ticket_interaction_id', 'kind', 'path', 'original_name', 'mime_type', 'size'])]
class TicketAttachment extends Model
{
    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public static function kindFromMime(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'image/') => 'imagem',
            str_starts_with($mime, 'audio/'), $mime === 'video/webm' => 'audio',
            default => 'arquivo',
        };
    }
}
