<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ProductMediaController;

Route::get('/products', [ProductController::class, 'apiIndex']);
Route::post('/products', [ProductController::class, 'apiStore']);
Route::get('/products/{id}', [ProductController::class, 'apiShow']);
Route::post('/products/{id}', [ProductController::class, 'apiUpdate']);
Route::delete('/products/{id}', [ProductController::class, 'apiDelete']);

Route::post('/products/{productId}/media/upload', [ProductMediaController::class, 'uploadMultiple']);
Route::post('/products/{productId}/media/base64', [ProductMediaController::class, 'uploadBase64']);
Route::get('/products/{productId}/media', [ProductMediaController::class, 'index']);
Route::get('/products/{productId}/media/{id}', [ProductMediaController::class, 'show']);
Route::put('/products/{productId}/media/{id}', [ProductMediaController::class, 'update']);
Route::delete('/products/{productId}/media/{id}', [ProductMediaController::class, 'destroy']);
Route::post('/products/{productId}/media/{id}/set-primary', [ProductMediaController::class, 'setPrimary']);
