<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('games.index');
Route::get('/login', [AuthController::class, 'index'])->name('login.index');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout.destroy');

Route::post('/games', [GameController::class, 'store'])->name('games.store');
Route::get('/games/{game_id}', [GameController::class, 'show'])->name('games.show');

// // Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/games/{game_id}/join', [PlayerController::class, 'join'])->name('players.join');
    Route::post('/games/{game_id}/players/{player_id}/place-token/{column}', [PlayerController::class, 'placeToken'])->name('players.placeToken');
    Route::post('/games/{game_id}/start-game', [PlayerController::class, 'startGame'])->name('players.startGame');
});

Route::get('/websocket', function () {
    return view('websocket');
})->name('websocket');
