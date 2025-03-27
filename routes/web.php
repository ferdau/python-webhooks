<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebhookController::class, 'index']);
Route::post('/webhooks', [WebhookController::class, 'store']);
Route::put('/webhooks/{webhook}', [WebhookController::class, 'update']);
Route::delete('/webhooks/{webhook}', [WebhookController::class, 'destroy']);
Route::post('/webhooks/{webhook}/toggle', [WebhookController::class, 'toggle']);
Route::post('/webhooks/upload-script', [WebhookController::class, 'uploadScript']);
Route::get('/webhooks/list-scripts', [WebhookController::class, 'listScripts']);
Route::get('/webhooks/{webhook:url}', [WebhookController::class, 'execute'])->name('webhooks.execute');
