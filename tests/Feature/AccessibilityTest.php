<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garantias estruturais de acessibilidade (complementam a auditoria com axe-core
 * descrita no README, que cobre contraste e regras WCAG 2.1 AA no navegador).
 */
class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_declare_language_and_skip_link(): void
    {
        $this->get('/login')
            ->assertSee('<html lang="pt-BR">', false)
            ->assertSee('<a href="#conteudo" class="skip-link">Pular para o conteúdo</a>', false)
            ->assertSee('id="conteudo"', false);

        $this->actingAs(User::factory()->create())->get('/chamados')
            ->assertSee('<html lang="pt-BR">', false)
            ->assertSee('class="skip-link"', false)
            ->assertSee('<main class="content" id="conteudo" tabindex="-1">', false);
    }

    public function test_current_page_is_announced_in_navigation(): void
    {
        $this->actingAs(User::factory()->create())->get('/busca')
            ->assertSee('aria-current="page"', false)
            ->assertSee('aria-label="Menu principal"', false);
    }

    public function test_icon_only_buttons_have_accessible_names(): void
    {
        $this->actingAs(User::factory()->create())->get('/chamados')
            ->assertSee('<span class="sr-only">Alternar tema</span>', false)
            ->assertSee('data-sound-label>Som ligado</span>', false)
            ->assertSee('aria-label="Menu" aria-controls="menu-principal" aria-expanded="false"', false);
    }

    public function test_form_errors_have_ids_to_be_linked_to_fields(): void
    {
        $this->post('/login', ['email' => 'nao-e-email', 'password' => 'x']);

        $this->get('/login')->assertSee('id="email-error"', false);
    }
}
