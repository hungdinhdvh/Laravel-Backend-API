<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\TelegramNotificationController;
use App\Http\Controllers\Api\V1\BinanceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return "Api V1";
});


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'

], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('verify_user_email', [AuthController::class, "verifyUserEmail"]);
    Route::post('resend_email_verification_link', [AuthController::class, "resendEmailVerificationLink"]);
});

Route::middleware(['auth'])->group(function () {
    Route::post('/auth/change_password', [AuthController::class, "changeUserPassword"]);

    // Binance routes
    Route::prefix('binance')->group(function () {
        Route::post('/api-keys', [BinanceApiController::class, 'store']);
        Route::put('/api-keys', [BinanceApiController::class, 'update']);
        Route::post('/api-keys/status', [BinanceApiController::class, 'status']);
        Route::post('/api-keys/delete', [BinanceApiController::class, 'destroy']);
        Route::get('/api-keys/{apiKey}/test', [BinanceApiController::class, 'testConnection']);
    });

    // Telegram routes
    Route::post('/telegram/send', [TelegramNotificationController::class, 'send']);
});
