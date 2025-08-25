<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\CatalogController;

Route::post('/integrations/fakestore/sync', [SyncController::class, 'sync']);

Route::middleware(['catalog.etag'])->group(function() {    
    Route::get('/categories', [CatalogController::class, 'categories']);

    Route::get('/products', [CatalogController::class, 'products']);

    Route::get('/stats', [CatalogController::class, 'stats']);
});
