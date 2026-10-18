<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Esqueci a senha": envia um link com token (válido por 60 min, ver config/auth.php)
 * e permite definir uma nova senha a partir dele.
 */
class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => 'Informe o seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        // Só contas ativas recebem o link.
        Password::sendResetLink(['email' => $request->input('email'), 'is_active' => true]);

        // Mesma resposta exista ou não a conta: não revela quais e-mails estão cadastrados.
        return back()->with('status', 'Se o e-mail estiver cadastrado, você receberá em instantes um link para criar uma nova senha. O link vale por 60 minutos.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'email.required' => 'Informe o seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação não confere com a nova senha.',
            'password.min' => 'A senha precisa ter pelo menos 8 caracteres.',
            'password.letters' => 'A senha precisa ter pelo menos uma letra.',
            'password.numbers' => 'A senha precisa ter pelo menos um número.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                // Encerra sessões abertas em outros navegadores.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Este link é inválido ou expirou. Solicite um novo em "Esqueceu a senha?".',
            ]);
        }

        return redirect()->route('login')->with('status', 'Senha alterada. Entre com a nova senha.');
    }
}
