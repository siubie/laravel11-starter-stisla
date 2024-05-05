<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::group(
    ['middleware' => 'auth:sanctum'],
    function () {
        // Route::apiResource('categories', CategoryController::class);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::apiResource('categories', CategoryController::class);
    }
);
