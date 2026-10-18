<?php

namespace App\Models;

use Database\Factories\RequesterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['external_id', 'name', 'department', 'is_synthetic'])]
class Requester extends Model
{
    /** @use HasFactory<RequesterFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_synthetic' => 'boolean'];
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
