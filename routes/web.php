<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\KnowledgeArticleController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketInteractionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/esqueci-a-senha', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/esqueci-a-senha', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/chamados');
    Route::get('/painel', DashboardController::class)->name('dashboard');

    // Fila e acompanhamento de novos chamados
    Route::get('/fila/novidades', QueueController::class)->name('queue.poll');

    Route::get('/chamados', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/chamados/novo', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/chamados', [TicketController::class, 'store'])->name('tickets.store');
    Route::post('/chamados/assumir-proximo', [TicketController::class, 'acknowledgeNext'])->name('tickets.acknowledge-next');
    Route::get('/chamados/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::patch('/chamados/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::post('/chamados/{ticket}/assumir', [TicketController::class, 'acknowledge'])->name('tickets.acknowledge');
    Route::post('/chamados/{ticket}/status', [TicketController::class, 'status'])->name('tickets.status');
    Route::post('/chamados/{ticket}/artigos/{article}', [TicketController::class, 'linkArticle'])->name('tickets.link-article');
    Route::post('/chamados/{ticket}/interacoes', [TicketInteractionController::class, 'store'])->name('tickets.interactions.store');

    // Recuperação de conhecimento
    Route::get('/busca', SearchController::class)->name('search');
    Route::resource('base-de-conhecimento', KnowledgeArticleController::class)
        ->parameters(['base-de-conhecimento' => 'article'])
        ->names('articles');

    // Diagnóstico: entrevistas sem identificação
    Route::get('/entrevistas/exportar', [InterviewController::class, 'export'])->name('interviews.export');
    Route::resource('entrevistas', InterviewController::class)
        ->parameters(['entrevistas' => 'interview'])
        ->names('interviews');
});
