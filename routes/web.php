<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Routes
Route::get('/docs/api', [DocumentationController::class, 'index'])->name('scramble.docs');
Route::get('/docs/api.json', [DocumentationController::class, 'specification'])->name('scramble.spec');
