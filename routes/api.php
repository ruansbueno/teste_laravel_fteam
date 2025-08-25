<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\SyncController;
use App\Http\Middleware\IntegrationMiddleware;

Route::middleware([IntegrationMiddleware::class])->group(function () {
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/products', [CatalogController::class, 'products']);
    Route::get('/stats', [CatalogController::class, 'stats']);
    Route::post('/integrations/fakestore/sync', [SyncController::class, 'sync']);
});
