<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReceivingRecordController;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

Route::middleware('auth:api')->get('/departments', [DepartmentController::class, 'index']);

// Receiving Records Routes - Protected by auth and department middleware
Route::middleware(['auth:api', 'department:receiving'])->group(function () {
    Route::post('/receiving-records', [ReceivingRecordController::class, 'store']);
    Route::get('/receiving-records', [ReceivingRecordController::class, 'index']);
    Route::get('/receiving-records/{id}', [ReceivingRecordController::class, 'show']);
});
