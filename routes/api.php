<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReceivingRecordController;

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');

Route::middleware('auth:api')->get('/departments', [DepartmentController::class, 'index']);

// Receiving Records Routes - Only for Receiving department
Route::middleware(['auth:api', 'department:receiving'])->group(function () {
    Route::post('/receiving-records', [ReceivingRecordController::class, 'store']);
    Route::get('/receiving-records', [ReceivingRecordController::class, 'index']);
    Route::get('/receiving-records/{id}', [ReceivingRecordController::class, 'show']);
});

// Department Dashboard Routes - For ALL departments to view their assigned records
Route::middleware('auth:api')->prefix('my-department')->group(function () {
    Route::get('/records', [\App\Http\Controllers\Api\DepartmentRecordsController::class, 'index']);
    Route::get('/records/{id}', [\App\Http\Controllers\Api\DepartmentRecordsController::class, 'show']);
    Route::put('/records/{id}', [\App\Http\Controllers\Api\DepartmentRecordsController::class, 'update']);
    Route::get('/statistics', [\App\Http\Controllers\Api\DepartmentRecordsController::class, 'statistics']);
});
