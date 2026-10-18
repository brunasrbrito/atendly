<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $tech;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tech = User::factory()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/chamados')->assertRedirect('/login');
    }

    public function test_user_can_log_in(): void
    {
        $this->post('/login', ['email' => $this->tech->email, 'password' => 'password'])
            ->assertRedirect(route('tickets.index'));
        $this->assertAuthenticatedAs($this->tech);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->tech->update(['is_active' => false]);

        $this->post('/login', ['email' => $this->tech->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_queue_lists_unacknowledged_tickets_first(): void
    {
        $acknowledged = Ticket::factory()->create(['title' => 'Já assumido', 'acknowledged_at' => now(), 'status' => TicketStatus::EmAtendimento, 'opened_at' => now()->subHours(3)]);
        $pending = Ticket::factory()->create(['title' => 'Ninguém viu', 'opened_at' => now()->subMinutes(2)]);

        $this->actingAs($this->tech)->get('/chamados')
            ->assertOk()
            ->assertSeeInOrder([$pending->title, $acknowledged->title]);
    }

    public function test_acknowledging_marks_perception_time_and_assigns_technician(): void
    {
        $ticket = Ticket::factory()->create(['opened_at' => now()->subMinutes(40)]);

        $this->actingAs($this->tech)->post(route('tickets.acknowledge', $ticket))->assertRedirect();

        $ticket->refresh();
        $this->assertNotNull($ticket->acknowledged_at);
        $this->assertSame($this->tech->id, $ticket->assigned_to);
        $this->assertSame(TicketStatus::EmAtendimento, $ticket->status);
        $this->assertSame(40, $ticket->perceptionMinutes());
    }

    public function test_acknowledge_next_takes_the_ticket_waiting_longest(): void
    {
        $newer = Ticket::factory()->create(['opened_at' => now()->subMinutes(5)]);
        $older = Ticket::factory()->create(['opened_at' => now()->subHour()]);

        $this->actingAs($this->tech)->post(route('tickets.acknowledge-next'))
            ->assertRedirect(route('tickets.show', $older));

        $this->assertNotNull($older->fresh()->acknowledged_at);
        $this->assertNull($newer->fresh()->acknowledged_at);
    }

    public function test_acknowledge_next_with_empty_queue_returns_to_list(): void
    {
        $this->actingAs($this->tech)->post(route('tickets.acknowledge-next'))
            ->assertRedirect(route('tickets.index'));
    }

    public function test_poll_endpoint_reports_new_tickets_since_last_id(): void
    {
        $old = Ticket::factory()->create();
        $new = Ticket::factory()->create(['title' => 'Chamado recém-chegado']);

        $this->actingAs($this->tech)->getJson(route('queue.poll', ['desde' => $old->id]))
            ->assertOk()
            ->assertJsonPath('latest_id', $new->id)
            ->assertJsonPath('unacknowledged', 2)
            ->assertJsonCount(1, 'new')
            ->assertJsonPath('new.0.title', 'Chamado recém-chegado');
    }

    public function test_cannot_resolve_without_solution(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->tech)->post(route('tickets.status', $ticket), ['status' => 'resolvido'])
            ->assertSessionHasErrors('solution');
        $this->assertSame(TicketStatus::Novo, $ticket->fresh()->status);
    }

    public function test_closing_records_solution_and_timestamps(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->tech)->post(route('tickets.status', $ticket), ['status' => 'fechado', 'solution' => 'Reiniciado o spooler.']);
        $ticket->refresh();
        $this->assertNotNull($ticket->resolved_at);
        $this->assertNotNull($ticket->closed_at);
        $this->assertSame('Reiniciado o spooler.', $ticket->solution);
    }

    public function test_closed_ticket_cannot_be_changed(): void
    {
        $ticket = Ticket::factory()->resolved('Solução original.')->create();
        $article = KnowledgeArticle::factory()->create();
        $before = $ticket->only(['status', 'priority', 'category_id', 'assigned_to', 'solution']);

        $attempts = [
            fn () => $this->post(route('tickets.status', $ticket), ['status' => 'em_atendimento']),
            fn () => $this->post(route('tickets.status', $ticket), ['status' => 'fechado', 'solution' => 'Outra solução.']),
            fn () => $this->patch(route('tickets.update', $ticket), ['priority' => 'critica', 'assigned_to' => $this->tech->id]),
            fn () => $this->post(route('tickets.interactions.store', $ticket), ['type' => 'comentario', 'body' => 'Voltou a dar erro.']),
            fn () => $this->post(route('tickets.acknowledge', $ticket)),
            fn () => $this->post(route('tickets.link-article', [$ticket, $article])),
        ];

        $this->actingAs($this->tech);
        foreach ($attempts as $attempt) {
            $attempt()->assertRedirect(route('tickets.show', $ticket))->assertSessionHas('error');
        }

        $ticket->refresh();
        $this->assertSame($before, $ticket->only(['status', 'priority', 'category_id', 'assigned_to', 'solution']));
        $this->assertSame(0, $ticket->interactions()->count());
        $this->assertSame(0, $ticket->articles()->count());
    }

    public function test_closed_ticket_page_offers_related_ticket_instead_of_editing(): void
    {
        $ticket = Ticket::factory()->resolved()->create();

        $this->actingAs($this->tech)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Abrir novo chamado relacionado')
            ->assertDontSee('name="body"', false)
            ->assertDontSee('id="resolver"', false);
    }

    public function test_related_ticket_is_prefilled_and_linked(): void
    {
        $old = Ticket::factory()->resolved()->create();

        $this->actingAs($this->tech)->get(route('tickets.create', ['relacionado' => $old->id]))
            ->assertOk()
            ->assertSee($old->requester->name)
            ->assertSee('name="related_ticket_id" value="'.$old->id.'"', false);

        $this->post(route('tickets.store'), [
            'title' => 'Problema voltou',
            'description' => 'Depois de uma semana o erro voltou.',
            'priority' => 'media',
            'requester_name' => $old->requester->name,
            'related_ticket_id' => $old->id,
        ])->assertRedirect();

        $new = Ticket::firstWhere('title', 'Problema voltou');
        $this->assertSame($old->id, $new->related_ticket_id);
        $this->assertTrue($old->followUps()->whereKey($new->id)->exists());
        $this->assertTrue($new->interactions()->where('body', 'like', '%continuação do chamado%')->exists());
    }

    public function test_details_form_updates_open_status_but_cannot_close(): void
    {
        $ticket = Ticket::factory()->create(['acknowledged_at' => now(), 'status' => TicketStatus::EmAtendimento]);

        $this->actingAs($this->tech)->patch(route('tickets.update', $ticket), ['status' => 'aguardando', 'priority' => 'alta'])->assertRedirect();
        $this->assertSame(TicketStatus::Aguardando, $ticket->fresh()->status);

        $this->patch(route('tickets.update', $ticket), ['status' => 'fechado', 'priority' => 'alta'])->assertSessionHasErrors('status');
        $this->assertSame(TicketStatus::Aguardando, $ticket->fresh()->status);
    }

    public function test_reply_with_audio_attachment_sets_first_response(): void
    {
        Storage::fake('public');
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->tech)->post(route('tickets.interactions.store', $ticket), [
            'type' => 'comentario',
            'body' => 'Segue o áudio explicando.',
            'attachments' => [UploadedFile::fake()->create('explicacao.ogg', 50, 'audio/ogg')],
        ])->assertRedirect();

        $ticket->refresh();
        $this->assertNotNull($ticket->first_response_at);
        $attachment = $ticket->attachments()->first();
        $this->assertSame('audio', $attachment->kind);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_internal_note_does_not_count_as_first_response(): void
    {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->tech)->post(route('tickets.interactions.store', $ticket), ['type' => 'nota_interna', 'body' => 'Verificar switch.']);

        $this->assertNull($ticket->fresh()->first_response_at);
    }

    public function test_linking_article_records_reuse_and_prefills_solution(): void
    {
        $ticket = Ticket::factory()->create();
        $article = KnowledgeArticle::factory()->create(['solution' => 'Limpar a fila de impressão.']);

        $this->actingAs($this->tech)->post(route('tickets.link-article', [$ticket, $article]))->assertRedirect();

        $this->assertTrue($ticket->articles()->whereKey($article->id)->exists());
        $this->assertSame('Limpar a fila de impressão.', $ticket->fresh()->solution);
        $this->assertSame(1, $article->fresh()->helpful_count);
    }

    public function test_manual_registration_creates_ticket_with_requester(): void
    {
        $category = Category::factory()->create();

        $this->actingAs($this->tech)->post(route('tickets.store'), [
            'title' => 'Mouse sem fio parou',
            'description' => 'Troquei a pilha e nada.',
            'category' => $category->slug,
            'priority' => 'baixa',
            'requester_name' => 'Solicitante teste',
            'is_synthetic' => '1',
        ])->assertRedirect();

        $ticket = Ticket::firstWhere('title', 'Mouse sem fio parou');
        $this->assertSame($category->id, $ticket->category_id);
        $this->assertSame('Solicitante teste', $ticket->requester->name);
        $this->assertTrue($ticket->is_synthetic);
        $this->assertNotNull($ticket->received_at);
    }

    public function test_ticket_page_suggests_similar_solutions(): void
    {
        Ticket::factory()->resolved('Reinstalar o FortiClient na versão homologada.')->create(['title' => 'VPN não conecta em casa']);
        $ticket = Ticket::factory()->create(['title' => 'Erro na VPN', 'description' => 'A vpn não conecta desde ontem.']);

        $this->actingAs($this->tech)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Reinstalar o FortiClient', false);
    }
}
