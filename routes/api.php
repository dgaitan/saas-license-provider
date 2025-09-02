<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API v1 routes - Force JSON responses for all API endpoints
Route::prefix('v1')->middleware(['force.json'])->group(function () {
    // Brand-facing APIs for license provisioning (US1 & US2)
    Route::controller(App\Http\Controllers\Api\V1\Brand\LicenseKeyController::class)->group(function () {
        Route::post('/license-keys', 'store');
        Route::get('/license-keys/{licenseKey}', 'show');
    });

    Route::controller(App\Http\Controllers\Api\V1\Brand\LicenseController::class)->group(function () {
        Route::post('/licenses', 'store');
        Route::get('/licenses/{license}', 'show');
        Route::patch('/licenses/{license}/renew', 'renew');
        Route::patch('/licenses/{license}/suspend', 'suspend');
        Route::patch('/licenses/{license}/resume', 'resume');
        Route::patch('/licenses/{license}/cancel', 'cancel');
    });

    // Product-facing APIs for license activation (US3 & US5)
    Route::controller(App\Http\Controllers\Api\V1\Product\ActivationController::class)->group(function () {
        Route::post('/licenses/{license}/activate', 'activate');
        Route::post('/licenses/{license}/deactivate', 'deactivate');
        Route::get('/licenses/{license}/activation-status', 'status');
    });

    // Product-facing APIs for license status checking (US4)
    Route::controller(App\Http\Controllers\Api\V1\Product\LicenseStatusController::class)->group(function () {
        Route::get('/license-keys/{licenseKey}/status', 'status');
        Route::get('/license-keys/{licenseKey}/is-valid', 'isValid');
        Route::get('/license-keys/{licenseKey}/entitlements', 'entitlements');
        Route::get('/license-keys/{licenseKey}/seat-usage', 'seatUsage');
    });
});
