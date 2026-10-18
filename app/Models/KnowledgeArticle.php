<?php

namespace App\Models;

use Database\Factories\KnowledgeArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'title', 'problem', 'solution', 'keywords', 'category_id', 'author_id',
    'source_ticket_id', 'views', 'helpful_count', 'is_synthetic',
])]
class KnowledgeArticle extends Model
{
    /** @use HasFactory<KnowledgeArticleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_synthetic' => 'boolean'];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Ticket, $this> */
    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    /**
     * Chamados em que o artigo foi aplicado.
     *
     * @return BelongsToMany<Ticket, $this>
     */
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class)->withPivot('user_id')->withTimestamps();
    }

    /** @return list<string> */
    public function keywordList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->keywords))));
    }
}
