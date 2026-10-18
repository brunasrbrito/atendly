<?php

namespace Tests\Feature;

use App\Models\Interview;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeAndDiagnosisTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_articles_and_resolved_tickets(): void
    {
        $user = User::factory()->create();
        KnowledgeArticle::factory()->create(['title' => 'Fila de impressão travada', 'keywords' => 'impressora, spooler']);
        Ticket::factory()->resolved('Limpei o spooler e reiniciei.')->create(['title' => 'Impressora não imprime']);
        Ticket::factory()->create(['title' => 'Impressora sem solução ainda']); // aberto: não deve aparecer

        $this->actingAs($user)->get(route('search', ['q' => 'impressora travada']))
            ->assertOk()
            ->assertSeeText('Fila de impressão travada') // termos vêm marcados com <mark>
            ->assertSeeText('Limpei o spooler')
            ->assertDontSee('Impressora sem solução ainda');
    }

    public function test_resolved_ticket_can_become_article(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->resolved('Ajustada a VLAN de voz.')->create(['title' => 'Ramal mudo']);

        $this->actingAs($user)->get(route('articles.create', ['chamado' => $ticket->id]))
            ->assertOk()->assertSee('Ajustada a VLAN de voz.');

        $this->actingAs($user)->post(route('articles.store'), [
            'title' => 'Ramal sem áudio',
            'problem' => 'Ramal mudo',
            'solution' => 'Ajustada a VLAN de voz.',
            'keywords' => 'Ramal, VOIP, ramal',
            'source_ticket_id' => $ticket->id,
        ])->assertRedirect();

        $article = KnowledgeArticle::firstWhere('title', 'Ramal sem áudio');
        $this->assertSame('ramal, voip', $article->keywords);
        $this->assertTrue($article->tickets()->whereKey($ticket->id)->exists());
    }

    public function test_only_admins_delete_articles(): void
    {
        $article = KnowledgeArticle::factory()->create();

        $this->actingAs(User::factory()->create())->delete(route('articles.destroy', $article))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete(route('articles.destroy', $article))->assertRedirect();
        $this->assertModelMissing($article);
    }

    public function test_interview_rejects_identifying_data(): void
    {
        $this->actingAs(User::factory()->create())->post(route('interviews.store'), [
            'code' => 'E01',
            'profile' => 'Técnico N1',
            'conducted_on' => now()->toDateString(),
            'consent_recorded' => '1',
            'notes' => 'Pode me contatar em fulano@empresa.com',
        ])->assertSessionHasErrors('notes');

        $this->assertSame(0, Interview::count());
    }

    public function test_interview_requires_consent_and_saves_themes(): void
    {
        $user = User::factory()->create();
        $data = [
            'code' => 'E02',
            'profile' => 'Técnico N2',
            'conducted_on' => now()->toDateString(),
            'themes' => ['whatsapp', 'recuperacao_conhecimento'],
            'notes' => 'Procura soluções antigas no WhatsApp.',
        ];

        $this->actingAs($user)->post(route('interviews.store'), $data)->assertSessionHasErrors('consent_recorded');
        $this->actingAs($user)->post(route('interviews.store'), $data + ['consent_recorded' => '1'])->assertRedirect();

        $this->assertSame(['whatsapp', 'recuperacao_conhecimento'], Interview::first()->themes);
        $this->actingAs($user)->get(route('interviews.export'))->assertOk()->assertDownload('entrevistas-atendly.csv');
    }

    public function test_test_badge_is_shown_only_when_enabled(): void
    {
        $user = User::factory()->create();
        Ticket::factory()->create(['title' => 'Chamado de demonstração']);

        config(['atendly.show_test_marks' => false]);
        $this->actingAs($user)->get('/chamados')->assertOk()->assertDontSee('badge--synthetic', false);

        config(['atendly.show_test_marks' => true]);
        $this->actingAs($user)->get('/chamados')->assertOk()->assertSee('badge--synthetic', false)->assertSeeText('Teste');
    }

    public function test_seeded_synthetic_data_renders_all_pages(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::firstWhere('email', 'tecnico1@atendly.test');

        $this->assertSame(0, Ticket::where('is_synthetic', false)->count(), 'Todos os chamados gerados devem ser sintéticos.');

        $this->actingAs($user);
        foreach (['/chamados', '/chamados?ver=encerrados', '/painel?dias=90', '/busca?q=vpn', '/base-de-conhecimento', '/entrevistas', '/chamados/novo', '/entrevistas/novo'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('tickets.show', Ticket::whereNotNull('solution')->first()))->assertOk();
        $this->get(route('articles.show', KnowledgeArticle::first()))->assertOk();
        $this->get(route('interviews.show', Interview::first()))->assertOk();
    }
}
