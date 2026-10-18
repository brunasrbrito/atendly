<?php

use App\Http\Controllers\Api\IntegrationController;
use Illuminate\Support\Facades\Route;

// Webhook chamado pelo sistema de terceiros a cada novo chamado aberto.
Route::middleware(['integration', 'throttle:120,1'])->group(function () {
    Route::post('/integracao/chamados', [IntegrationController::class, 'store'])->name('api.integration.tickets');
});
