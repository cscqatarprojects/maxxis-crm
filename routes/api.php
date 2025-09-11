<?php

use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes for webhook integration
Route::prefix('v1')->group(function () {
    // Lead creation endpoint for chatbot webhook
    Route::post('leads', [LeadController::class, 'store'])->name('api.leads.store');

    // Health check endpoint
    Route::get('health', function () {
        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toISOString(),
            'service'   => 'Maxxiss CRM API',
        ]);
    })->name('api.health');
});

// Webhook routes (no authentication required for external services)
Route::prefix('webhook')->group(function () {
    Route::post('chatbot/lead', [WebhookController::class, 'handleChatbotLead'])->name('webhook.chatbot.lead');
    Route::get('health', [WebhookController::class, 'health'])->name('webhook.health');
});
