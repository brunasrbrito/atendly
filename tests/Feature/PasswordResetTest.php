<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('Esqueceu a senha?');
    }

    public function test_reset_link_is_sent_to_active_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $mail = $n->toMail($user);

            $html = (string) $mail->render();

            return $mail->subject === 'Redefinição de senha · Atendly'
                && str_contains($mail->actionUrl, '/redefinir-senha/')
                && str_contains($html, 'copie e cole')
                && ! str_contains($html, 'having trouble');
        });
    }

    public function test_same_message_for_unknown_or_inactive_email(): void
    {
        Notification::fake();
        $inactive = User::factory()->create(['is_active' => false]);

        $unknown = $this->post(route('password.email'), ['email' => 'ninguem@atendly.test'])->assertSessionHas('status');
        $blocked = $this->post(route('password.email'), ['email' => $inactive->email])->assertSessionHas('status');

        $this->assertSame($unknown->getSession()->get('status'), $blocked->getSession()->get('status'));
        Notification::assertNothingSent();
    }

    public function test_user_can_set_new_password_with_valid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()->assertSee($user->email);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('novaSenha123', $user->fresh()->password));

        // Token é de uso único.
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'outraSenha456',
            'password_confirmation' => 'outraSenha456',
        ])->assertSessionHasErrors('email');
    }

    public function test_invalid_token_and_weak_password_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertSessionHasErrors('email');

        $this->post(route('password.update'), [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
