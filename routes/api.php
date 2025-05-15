<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Weather\WeatherController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('auth/logout', [AuthController::class, 'logout']);

//Register users

Route::middleware('auth:sanctum')->post('users', [UserController::class, 'create']);
Route::middleware('auth:sanctum', 'role:admin')->put('users/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum', 'role:admin')->delete('users/{id}', [UserController::class, 'destroy']);


//Weather
Route::get('weather', [WeatherController::class, 'index']);
Route::middleware('auth:sanctum')->get('/search-history', [WeatherController::class, 'getSearchHistory']);
Route::middleware('auth:sanctum')->post('/mark-favorite', [WeatherController::class, 'markAsFavorite']);
Route::middleware('auth:sanctum')->get('/favorite-cities', [WeatherController::class, 'getFavoriteCities']);
