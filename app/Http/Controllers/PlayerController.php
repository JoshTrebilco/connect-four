<?php

namespace App\Http\Controllers;

use App\Events\Gameplay\PlacedToken;
use App\Events\Setup\PlayerColorSelected;
use App\Events\Setup\PlayerJoinedGame;
use App\States\GameState;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    public function join(Request $request, int $game_id)
    {
        $player_id = snowflake_id();
        $user = Auth::user();

        $user->update(['current_player_id' => $player_id]);

        event(new PlayerColorSelected(
            game_id: $game_id,
            player_id: $player_id,
            color: $request->color,
        ));

        event(new PlayerJoinedGame(
            game_id: $game_id,
            player_id: $player_id,
        ));

        return redirect()->route('games.show', $game_id);
    }

    public function placeToken(int $game_id, int $player_id, int $column)
    {
        if (Auth::user()->current_player_id != $player_id) {
            return redirect()->route('games.show', $game_id);
        }

        $game_state = GameState::load($game_id);

        PlacedToken::commit(
            game_id: $game_id,
            board_id: $game_state->board_id,
            player_id: $player_id,
            column: $column,
        );
    }
}
