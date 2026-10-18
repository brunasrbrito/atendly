<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\BusinessCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessCalendarTest extends TestCase
{
    use RefreshDatabase;

    /** Resposta no formato real da BrasilAPI (/api/feriados/v1/2026). */
    protected function holidaysResponse(): array
    {
        return [
            ['date' => '2026-11-02', 'name' => 'Finados', 'type' => 'national'],
            ['date' => '2026-11-20', 'name' => 'Dia da consciência negra', 'type' => 'national'],
        ];
    }

    public function test_reads_holidays_from_brasilapi_and_caches_them(): void
    {
        $calendar = app(BusinessCalendar::class);

        $this->assertSame('Feriado: Finados', $calendar->closedReason(Carbon::parse('2026-11-02')));
        $this->assertSame('Sábado', $calendar->closedReason(Carbon::parse('2026-10-17')));
        $this->assertSame('Domingo', $calendar->closedReason(Carbon::parse('2026-10-18')));
        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2026-10-19')));

        // Sábado 31/10 → pula domingo e o feriado de 02/11 → terça 03/11.
        $this->assertSame('2026-11-03', $calendar->nextBusinessDay(Carbon::parse('2026-10-31'))->toDateString());

        // Várias consultas, uma única chamada à API (cache).
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://brasilapi.com.br/api/feriados/v1/2026');
    }

    public function test_falls_back_to_weekend_rule_when_api_is_down(): void
    {
        // API fora do ar (outro endereço, respondendo 503).
        config(['atendly.holidays_api' => 'https://feriados.indisponivel.test/v1']);
        Http::fake(['feriados.indisponivel.test/*' => Http::response('erro', 503)]);

        $calendar = app(BusinessCalendar::class);

        $this->assertTrue($calendar->isBusinessDay(Carbon::parse('2026-11-02')));
        $this->assertFalse($calendar->isBusinessDay(Carbon::parse('2026-11-01')));
    }

    public function test_manual_registration_is_blocked_on_holidays(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 10:00'));
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tickets.index'))
            ->assertOk()->assertSee('Feriado: Finados')->assertSee('registro-bloqueado', false);
        $this->get(route('tickets.create'))
            ->assertOk()->assertSee('O registro manual de chamados só é permitido em dias úteis');

        $this->post(route('tickets.store'), [
            'title' => 'Teste no feriado',
            'description' => 'Não deve ser registrado.',
            'priority' => 'media',
            'requester_name' => 'Solicitante 1',
        ])->assertRedirect(route('tickets.create'))->assertSessionHas('error');

        $this->assertSame(0, Ticket::count());
    }

    public function test_manual_registration_is_blocked_on_weekends(): void
    {
        $this->travelTo(Carbon::parse('2026-10-17 10:00')); // sábado

        $this->actingAs(User::factory()->create())->post(route('tickets.store'), [
            'title' => 'Teste no sábado',
            'description' => 'Não deve ser registrado.',
            'priority' => 'media',
            'requester_name' => 'Solicitante 1',
        ])->assertSessionHas('error');

        $this->assertSame(0, Ticket::count());
    }

    public function test_integration_still_receives_tickets_on_holidays(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 10:00'));
        config(['atendly.integration_token' => 'token']);

        $this->postJson('/api/integracao/chamados', [
            'external_id' => 'EXT-FERIADO',
            'title' => 'Sem acesso ao sistema',
            'description' => 'Aberto no sistema de origem durante o feriado.',
        ], ['Authorization' => 'Bearer token'])->assertCreated();

        $this->assertSame(1, Ticket::count());
    }

    public function test_rule_can_be_disabled(): void
    {
        $this->travelTo(Carbon::parse('2026-10-18 10:00')); // domingo
        config(['atendly.block_non_business_days' => false]);

        $this->assertNull(app(BusinessCalendar::class)->manualRegistrationBlock());
    }
}
