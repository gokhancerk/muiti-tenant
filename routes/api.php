<?php

use App\Http\Controllers\ProjectsController;
use App\Http\Middleware\TenantIdentificationMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::middleware([TenantIdentificationMiddleware::class])->group(function () {
    // Controller'a ulaştığında TenantManager hazırdır.
    // Global Scope (TenantScope) otomatik olarak devreye girer.
    Route::get('/projects', [ProjectsController::class, 'index']);
    Route::post('/projects', [ProjectsController::class, 'store']);
});