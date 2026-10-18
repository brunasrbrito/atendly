<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::resourceVerbs(['create' => 'novo', 'edit' => 'editar']);
        Paginator::defaultView('partials.pagination');

        // E-mail de "esqueci a senha" em português.
        ResetPassword::toMailUsing(fn ($user, string $token) => (new MailMessage)
            ->subject('Redefinição de senha · Atendly')
            ->greeting("Olá, {$user->name}!")
            ->line('Recebemos um pedido para redefinir a senha da sua conta no Atendly.')
            ->action('Criar nova senha', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('O link vale por '.config('auth.passwords.users.expire').' minutos.')
            ->line('Se você não fez esse pedido, ignore este e-mail: sua senha continua a mesma.')
            ->salutation('Equipe de suporte de TI'));

        View::composer('components.layouts.app', function ($view) {
            $view->with([
                'navUnacknowledged' => Ticket::unacknowledged()->count(),
                'latestTicketId' => (int) Ticket::max('id'),
            ]);
        });
    }
}
