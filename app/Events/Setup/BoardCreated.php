<?php

namespace App\Events\Setup;

use App\Events\BroadcastEvent;
use App\States\BoardState;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\AppliesToState;
use Thunk\Verbs\Event;

#[AppliesToState(GameState::class)]
#[AppliesToState(BoardState::class)]
class BoardCreated extends Event
{
    public function __construct(
        public int $game_id,
        public ?int $board_id = null,
    ) {}

    public function validateGame(GameState $game)
    {
        $this->assert($game->created, 'Game must be created before a board can be created.');
        $this->assert(! $game->board_id, 'Board has already been created.');
    }

    public function applyToGame(GameState $game)
    {
        $game->board_id = $this->board_id ?? snowflake_id();
    }

    public function applyToBoard(BoardState $board)
    {
        $board->setup();
    }

    public function handle(GameState $game, BoardState $board)
    {
        $broadcastEvent = new BroadcastEvent;
        $broadcastEvent->setGameState($game);
        $broadcastEvent->setBoardState($board);
        $broadcastEvent->setEvent(self::class);
        event($broadcastEvent);
    }
}
