<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Requester;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'source' => 'manual',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requester_id' => Requester::factory(),
            'category_id' => Category::factory(),
            'status' => TicketStatus::Novo,
            'priority' => TicketPriority::Media,
            'opened_at' => now()->subMinutes(5),
            'received_at' => now(),
            'is_synthetic' => true,
        ];
    }

    public function resolved(string $solution = 'Solução aplicada.'): static
    {
        return $this->state(fn () => [
            'status' => TicketStatus::Fechado,
            'solution' => $solution,
            'opened_at' => now()->subHours(5),
            'acknowledged_at' => now()->subHours(4),
            'first_response_at' => now()->subHours(4),
            'resolved_at' => now()->subHours(2),
            'closed_at' => now()->subHour(),
        ]);
    }
}
