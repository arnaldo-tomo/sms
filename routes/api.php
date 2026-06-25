<?php

use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NumberController;
use App\Http\Controllers\Api\WebhookController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API / Webhooks
|--------------------------------------------------------------------------
| Endpoint público chamado pelo httpSMS quando há eventos (entregue, falhou,
| recebido, heartbeat...). É protegido por um segredo partilhado opcional
| (ver Configurações > Webhooks). Sem sessão nem CSRF.
*/

Route::post('/webhooks/httpsms', [WebhookController::class, 'httpsms'])
    ->name('webhooks.httpsms');

/*
|--------------------------------------------------------------------------
| API pública v1 (multi-empresa) — estilo Twilio
|--------------------------------------------------------------------------
| Autenticada por token Bearer (sk_live_…) que identifica a empresa.
| Rate limit por empresa (messages_per_minute).
*/
Route::prefix('v1')
    ->middleware(['company.api', 'throttle:sms-api'])
    ->group(function () {
        Route::post('/sms', [MessageController::class, 'store']);
        Route::get('/sms', [MessageController::class, 'index']);
        Route::get('/sms/{id}', [MessageController::class, 'show']);

        Route::get('/numbers', [NumberController::class, 'index']);

        // Identidade da empresa autenticada (sanity check da API key).
        Route::get('/me', function (Request $request) {
            /** @var Company $company */
            $company = $request->attributes->get('company');

            return response()->json([
                'id' => $company->slug,
                'name' => $company->name,
                'numbers' => $company->devices()->count(),
                'rate_limit_per_minute' => $company->messages_per_minute,
            ]);
        });
    });
