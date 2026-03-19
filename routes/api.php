<?php

use App\Http\Controllers\ProjectsController;
use Illuminate\Support\Facades\Route;

/**
 * Tenant-Aware Rate Limited API Routes
 * 
 * Her endpoint tenant.identify middleware'ı üzerinden geçer.
 * Rate limiting policy'leri tier bazlı uygulanır:
 * - tenant.read: GET endpoint'leri için (free: 60/min, pro: 300/min)
 * - tenant.write: POST/PUT/DELETE endpoint'leri için (free: 20/min, pro: 100/min)
 */
Route::middleware(['tenant.identify'])->group(function () {
    
    // Okuma endpoint'i -> 'tenant.read' politikasına tabi
    Route::get('/projects', [ProjectsController::class, 'index'])
        ->name('projects.index')
        ->middleware('throttle:tenant.read');

    // Yazma endpoint'i -> 'tenant.write' politikasına tabi
    Route::post('/projects', [ProjectsController::class, 'store'])
        ->name('projects.store')
        ->middleware('throttle:tenant.write');
});