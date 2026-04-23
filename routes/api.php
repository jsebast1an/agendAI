<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify']);
