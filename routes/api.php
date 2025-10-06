<?php

use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle']);
Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify']);
