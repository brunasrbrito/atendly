<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['atendly.integration_token' => 'token-de-teste']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'EXT-1001',
            'title' => 'Impressora offline',
            'description' => 'A impressora do RH não aparece.',
            'category' => 'impressoras',
            'priority' => 'alta',
            'opened_at' => now()->subMinutes(10)->toIso8601String(),
            'requester' => ['external_id' => 'SOL-9', 'name' => 'Solicitante 9', 'department' => 'RH'],
        ], $overrides);
    }

    public function test_rejects_requests_without_valid_token(): void
    {
        $this->postJson('/api/integracao/chamados', $this->payload())->assertUnauthorized();
        $this->postJson('/api/integracao/chamados', $this->payload(), ['Authorization' => 'Bearer errado'])->assertUnauthorized();
    }

    public function test_creates_ticket_from_third_party_system(): void
    {
        Category::factory()->create(['slug' => 'impressoras']);

        $this->postJson('/api/integracao/chamados', $this->payload(), ['Authorization' => 'Bearer token-de-teste'])
            ->assertCreated()
            ->assertJsonPath('external_id', 'EXT-1001')
            ->assertJsonPath('status', 'novo');

        $ticket = Ticket::firstWhere('external_id', 'EXT-1001');
        $this->assertSame('webhook', $ticket->source);
        $this->assertSame('impressoras', $ticket->category->slug);
        $this->assertSame('RH', $ticket->requester->department);
        $this->assertNull($ticket->acknowledged_at);
    }

    public function test_repeated_delivery_is_idempotent(): void
    {
        $headers = ['Authorization' => 'Bearer token-de-teste'];

        $this->postJson('/api/integracao/chamados', $this->payload(), $headers)->assertCreated();
        $this->postJson('/api/integracao/chamados', $this->payload(), $headers)->assertOk();

        $this->assertSame(1, Ticket::count());
    }

    public function test_validates_payload(): void
    {
        $this->postJson('/api/integracao/chamados', ['title' => ''], ['Authorization' => 'Bearer token-de-teste'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['external_id', 'title', 'description']);
    }
}
