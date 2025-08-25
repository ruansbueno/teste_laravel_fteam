<?php

use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['integration'])->group(function () {
    // Catálogo
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    
    // Estatísticas
    Route::get('/statistics', [StatisticsController::class, 'index']);
});

// Sincronização (sem middleware para facilitar execução via comando/API)
Route::post('/integrations/fakestore/sync', [IntegrationController::class, 'sync']);