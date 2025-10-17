<?php

namespace App\Events\Gameplay;

use App\Events\BroadcastEvent;
use App\States\BoardState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(GameState::class)]
#[AppliesToState(PlayerState::class)]
#[AppliesToState(BoardState::class)]
class PlacedToken extends Event
{
    // use PlayerAction;

    public function __construct(
        public int $game_id,
        public int $player_id,
        public int $column,
        public int $board_id,
    ) {}

    public function validateGame(GameState $game)
    {
        $this->assert($game->isInProgress(), 'The game is not in progress.');
        $this->assert($game->last_player_id !== $this->player_id, 'It is not your turn.');
    }

    public function validateBoard(BoardState $board)
    {
        $this->assert($this->column >= 0 && $this->column < count($board->columns), 'Column is invalid.');
        $this->assert(! $board->isColumnFull($this->column), 'Column is full.');
    }

    public function applyToBoard(BoardState $board)
    {
        $board->columns[$this->column][] = $this->player_id;
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->last_placed_column = $this->column;
    }

    public function fired()
    {
        $board = BoardState::load($this->board_id);
        $game = GameState::load($this->game_id);

        if ($board->checkWin($this->column, $board->height($this->column) - 1)) {
            PlayerWonGame::commit(
                game_id: $this->game_id,
                player_id: $this->player_id,
            );
        }

        if ($board->isBoardFull()) {
            PlayersTiedGame::commit(
                game_id: $this->game_id,
            );
        }

        if ($game->isInProgress()) {
            EndedTurn::commit(
                game_id: $this->game_id,
                player_id: $this->player_id,
            );
        }
    }

    public function handle(GameState $game, BoardState $board, PlayerState $player)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setBoardState($board);
        $broadcastEvent->setPlayerState($player);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
