<?php

use App\States\GameState;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('game.{game_id}', function ($user, $game_id) {
    // Anyone in the game can listen to game events
    $game = GameState::load($game_id);

    return $game && $game->hasPlayer($user->current_player_id);
});
