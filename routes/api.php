<?php

use App\Http\Controllers\AirportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\UserController;
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

Route::post('/register', [UserController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/airport', [AirportController::class, 'index']);
Route::get('/flight', [FlightController::class, 'index']);
Route::post('/booking', [BookingController::class, 'store']);
Route::get('/booking/{booking:code}', [BookingController::class, 'show']);
Route::get('/booking/{booking:code}/seat', [BookingController::class, 'occupied_seats']);
Route::patch('/booking/{booking:code}/seat', [BookingController::class, 'select_place']);
Route::get('/user/booking', [BookingController::class, 'index'])->middleware('auth');
Route::get('/user', [UserController::class, 'show'])->middleware('auth');