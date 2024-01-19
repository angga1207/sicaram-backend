<?php

use App\Http\Controllers\API\TestingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/testing', [TestingController::class, 'index']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
