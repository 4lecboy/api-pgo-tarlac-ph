<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentRecordsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReceivingRecordController;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

// Receiving Records Routes - Only for Receiving department
Route::middleware(['auth:api', 'department:receiving'])->group(function () {
    Route::post('/receiving-records', [ReceivingRecordController::class, 'store']);
    Route::get('/receiving-records', [ReceivingRecordController::class, 'index']);
    Route::get('/receiving-records/{id}', [ReceivingRecordController::class, 'show']);
    Route::delete('/receiving-records/{id}', [ReceivingRecordController::class, 'destroy']);
});

// Department Dashboard Routes - For ALL departments to view their assigned records
Route::middleware('auth:api')->prefix('my-department')->group(function () {
    Route::get('/records', [DepartmentRecordsController::class, 'index']);
    Route::get('/records/{id}', [DepartmentRecordsController::class, 'show']);
    Route::put('/records/{id}', [DepartmentRecordsController::class, 'update']);
    Route::get('/statistics', [DepartmentRecordsController::class, 'statistics']);
});
