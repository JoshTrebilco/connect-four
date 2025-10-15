<?php

use App\Events\Gameplay\PlacedToken;
use App\Events\Gameplay\PlayersTiedGame;
use App\Events\Gameplay\PlayerWonGame;
use App\Events\Setup\BoardCreated;
use App\Events\Setup\GameCreated;
use App\Events\Setup\GameStarted;
use App\Events\Setup\PlayerColorSelected;
use App\Events\Setup\PlayerJoinedGame;
use App\States\BoardState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Exceptions\EventNotValidForCurrentState;
use Thunk\Verbs\Facades\Verbs;
use Thunk\Verbs\Models\VerbSnapshot;

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();
});

function placeToken($game_state, int $player_id, int $column)
{
    return verb(new PlacedToken(
        game_id: $game_state->id,
        board_id: $game_state->board_id,
        player_id: $player_id,
        column: $column,
    ));
}

it('can play a game of connect 4', function () {

    // Game Setup
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    $board_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    expect($game_state->created)->toBeTrue()
        ->and($game_state->player_ids)->toHaveCount(0)
        ->and($game_state->board_id)->toBeNull()
        ->and($game_state->ended)->toBeFalse()
        ->and($game_state->winner_id)->toBeNull()
        ->and($game_state->active_player_id)->toBeNull()
        ->and($game_state->last_player_id)->toBeNull()
        ->and(fn () => GameCreated::fire(game_id: $game_state->id))->toThrow(EventNotValidForCurrentState::class);

    $board_state = verb(new BoardCreated(
        game_id: $game_state->id,
        board_id: $board_id,
    ))->state(BoardState::class);

    expect($game_state->board_id)->toBe($board_id);

    expect($board_state->columns)->toHaveCount(7)
        ->and(fn () => BoardCreated::fire(game_id: $game_state->id, board_id: $board_id))->toThrow(EventNotValidForCurrentState::class);

    verb(new PlayerColorSelected(
        game_id: $game_state->id,
        player_id: $player1_id,
        color: 'red',
    ));

    verb(new PlayerJoinedGame(
        game_id: $game_state->id,
        player_id: $player1_id,
    ));

    expect($game_state->hasPlayer($player1_id))->toBeTrue();

    $player1 = PlayerState::load($player1_id);

    expect($player1->color)->toBe('red')
        ->and($player1->setup)->toBeTrue();

    verb(new PlayerColorSelected(
        game_id: $game_state->id,
        player_id: $player2_id,
        color: 'yellow',
    ));

    verb(new PlayerJoinedGame(
        game_id: $game_state->id,
        player_id: $player2_id,
    ));

    expect($game_state->hasPlayer($player2_id))->toBeTrue();

    $player2 = PlayerState::load($player2_id);

    expect($player2->color)->toBe('yellow')
        ->and($player2->setup)->toBeTrue();

    // GameStarted event is automatically fired when second player joins
    expect($game_state->active_player_id)->toBe($player1_id);

    // We'll commit what we have so far and make sure that the state in the database
    // matches what we've got loaded into memory.

    Verbs::commit();

    $snapshot_state = VerbSnapshot::query()
        ->firstWhere('type', GameState::class)
        ->state();

    expect($snapshot_state->created)->toBeTrue()
        ->and($snapshot_state->active_player_id)->toBe($game_state->active_player_id)
        ->and(serialize($snapshot_state->players()))->toBe(serialize($game_state->players()));

    // Simulate player 1 winning

    // Player 1 will place 4 tokens in the first column
    // Player 2 will place 3 tokens in the bottom row
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 1);
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 2);
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 3);
    placeToken($game_state, $player1_id, 0);

    expect($board_state->columns[0])->toHaveCount(4);

    expect($board_state->checkWin(0, 0))->toBeTrue();

    expect($game_state->last_player_id)->toBe($player2_id);
    expect($game_state->active_player_id)->toBe($player1_id);

    expect($game_state->winner_id)->toBe($player1_id)
        ->and($game_state->winner())->toBe($player1)
        ->and($game_state->winner_id)->not->toBe($player2_id)
        ->and($game_state->ended)->toBeTrue();

    // Assert that PlayerWonGame event was fired
    Verbs::assertCommitted(PlayerWonGame::class, function ($event) use ($player1_id) {
        return $event->player_id === $player1_id;
    });
});

// Board Setup
test('board has exactly 7 columns', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'winner_id' => 1,
        'created' => true,
    ]);

    $board_state = verb(new BoardCreated(
        game_id: $game_state->id,
        board_id: $board_id,
    ))->state(BoardState::class);

    expect($board_state->columns)->toHaveCount(7);
});

test('board is full', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1, 1],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
    ]);

    expect($board_state->isBoardFull())->toBeTrue();
});

test('column is full', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1, 1, 1, 1, 1, 1],
        ],
    ]);

    expect($board_state->isColumnFull(0))->toBeTrue();
});

test('column is not full', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1, 1],
        ],
    ]);

    expect($board_state->isColumnFull(0))->toBeFalse();
});

// Token Placement
test('can place token when game is in progress', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players and start the game
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Verify game is in progress
    expect($game_state->isInProgress())->toBeTrue();

    // Place a token when game is in progress - should succeed
    placeToken($game_state, $player1_id, 0);

    // Verify the token was placed
    $board_state = BoardState::load($board_id);
    expect($board_state->tokenAt(0, 0))->toBe($player1_id);
});

test('cannot place token when game is not in progress', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players but don't start the game
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));

    // Verify game is not in progress
    expect($game_state->isInProgress())->toBeFalse();

    // Try to place a token when game is not in progress - should fail
    expect(fn () => placeToken($game_state, $player1_id, 0))
        ->toThrow(EventNotValidForCurrentState::class);

    // Second player joins
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Verify game is in progress
    expect($game_state->isInProgress())->toBeTrue();

    // End the game by setting it as ended
    $game_state->ended = true;

    // Verify game is no longer in progress
    expect($game_state->isInProgress())->toBeFalse();

    // Try to place a token when game is not in progress - should fail
    expect(fn () => placeToken($game_state, $player1_id, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('tokens placed correctly', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $p1 = snowflake_id();
    $p2 = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    $board_state = verb(new BoardCreated(
        game_id: $game_state->id,
        board_id: $board_id,
    ))->state(BoardState::class);

    // Add players and start the game
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $p1, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $p1));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $p2, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $p2));
    // GameStarted event is automatically fired when second player joins

    placeToken($game_state, $p1, 0);
    placeToken($game_state, $p2, 0);
    placeToken($game_state, $p1, 1);
    placeToken($game_state, $p2, 1);
    placeToken($game_state, $p1, 1);

    expect($board_state->tokenAt(0, 0))->toBe($p1);
    expect($board_state->tokenAt(0, 1))->toBe($p2);
    expect($board_state->tokenAt(1, 0))->toBe($p1);
    expect($board_state->tokenAt(1, 1))->toBe($p2);
    expect($board_state->tokenAt(1, 2))->toBe($p1);

    expect($board_state->tokenAt(0, 0))->not->toBe($p2);

    expect($board_state->height(0))->toBe(2);
    expect($board_state->height(1))->toBe(3);
});

// Win Detection
test('vertical win', function () {
    // Game Setup
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1, 1, 1, 1],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
        'winner_id' => 1,
    ]);

    expect($board_state->checkWin(0, 0))->toBeTrue();
    expect($board_state->checkWin(0, 1))->toBeTrue();
    expect($board_state->checkWin(0, 2))->toBeTrue();
    expect($board_state->checkWin(0, 3))->toBeTrue();
    expect($game_state->winner_id)->toBe(1);
});

test('horizontal win', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1],
            [1],
            [1],
            [1],
            [0],
            [0],
            [0],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
        'winner_id' => 1,
    ]);

    expect($board_state->checkWin(0, 0))->toBeTrue();
    expect($board_state->checkWin(1, 0))->toBeTrue();
    expect($board_state->checkWin(2, 0))->toBeTrue();
    expect($board_state->checkWin(3, 0))->toBeTrue();
    expect($game_state->winner_id)->toBe(1);
});

test('diagonal (top-right to bottom-left) win', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [1, 0, 0, 0],
            [0, 1, 0, 0],
            [0, 0, 1, 0],
            [0, 0, 0, 1],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
        'winner_id' => 1,
    ]);

    expect($board_state->checkWin(0, 0))->toBeTrue();
    expect($board_state->checkWin(1, 1))->toBeTrue();
    expect($board_state->checkWin(2, 2))->toBeTrue();
    expect($board_state->checkWin(3, 3))->toBeTrue();

    expect($game_state->winner_id)->toBe(1);
});

test('diagonal (top-left to bottom-right) win', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [0, 0, 0, 1],
            [0, 0, 1, 0],
            [0, 1, 0, 0],
            [1, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
        'winner_id' => 1,
    ]);

    expect($board_state->checkWin(0, 3))->toBeTrue();
    expect($board_state->checkWin(1, 2))->toBeTrue();
    expect($board_state->checkWin(2, 1))->toBeTrue();
    expect($board_state->checkWin(3, 0))->toBeTrue();

    expect($game_state->winner_id)->toBe(1);
});

test('no win', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    $board_state = BoardState::factory()->create([
        'id' => $board_id,
        'columns' => [
            [0, 1, 0, 1],
            [0, 1, 0, 1],
            [1, 0, 1, 0],
            [1, 0, 1, 0],
            [0, 1, 0, 1],
            [0, 1, 0, 1],
            [1, 0, 1, 0],
        ],
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => $board_id,
        'winner_id' => null,
    ]);

    expect($board_state->checkWin(0, 0))->toBeFalse();
    expect($board_state->checkWin(1, 0))->toBeFalse();
    expect($board_state->checkWin(2, 0))->toBeFalse();
    expect($board_state->checkWin(3, 0))->toBeFalse();
    expect($game_state->winner_id)->toBeNull();
});

// GameState Assertions
test('game state initial values', function () {
    $game_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    expect($game_state->created)->toBeTrue()
        ->and($game_state->board_id)->toBeNull()
        ->and($game_state->last_player_id)->toBeNull()
        ->and($game_state->player_ids)->toHaveCount(0)
        ->and($game_state->active_player_id)->toBeNull()
        ->and($game_state->winner_id)->toBeNull()
        ->and($game_state->available_colors)->toBe(['blue', 'green', 'red', 'yellow'])
        ->and($game_state->created_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});

test('game state has player', function () {
    $game_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1_id, $player2_id],
    ]);

    expect($game_state->hasPlayer($player1_id))->toBeTrue()
        ->and($game_state->hasPlayer($player2_id))->toBeTrue()
        ->and($game_state->hasPlayer(999))->toBeFalse()
        ->and($game_state->hasPlayer(null))->toBeFalse();
});

test('game state has enough players', function () {
    $game_id = snowflake_id();

    // No players
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [],
    ]);
    expect($game_state->hasAllPlayersJoined())->toBeFalse();

    // One player
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [snowflake_id()],
    ]);
    expect($game_state->hasAllPlayersJoined())->toBeFalse();

    // Two players
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [snowflake_id(), snowflake_id()],
    ]);
    expect($game_state->hasAllPlayersJoined())->toBeTrue();

    // Three players (not allowed in our game)
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [snowflake_id(), snowflake_id(), snowflake_id()],
    ]);
    expect($game_state->hasAllPlayersJoined())->toBeFalse();
});

test('game state players collection', function () {
    $game_id = snowflake_id();

    // Create player states
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
        'setup' => true,
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id],
    ]);

    $players = $game_state->players();

    expect($players)->toHaveCount(2)
        ->and($players->first()->id)->toBe($player1->id)
        ->and($players->first()->color)->toBe('red')
        ->and($players->last()->id)->toBe($player2->id)
        ->and($players->last()->color)->toBe('yellow');
});

test('game state active player', function () {
    $game_id = snowflake_id();

    // Create player states
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
        'setup' => true,
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id],
        'active_player_id' => $player1->id,
    ]);

    $active_player = $game_state->activePlayer();

    expect($active_player)->not->toBeNull()
        ->and($active_player->id)->toBe($player1->id)
        ->and($active_player->color)->toBe('red');

    // Test with no active player
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'active_player_id' => null,
    ]);

    expect($game_state->activePlayer())->toBeNull();
});

test('game state last player', function () {
    $game_id = snowflake_id();

    // Create player states
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
        'setup' => true,
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id],
        'last_player_id' => $player2->id,
    ]);

    $last_player = $game_state->lastPlayer();

    expect($last_player)->not->toBeNull()
        ->and($last_player->id)->toBe($player2->id)
        ->and($last_player->color)->toBe('yellow');

    // Test with no last player
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'last_player_id' => null,
    ]);

    expect($game_state->lastPlayer())->toBeNull();
});

test('game state winner', function () {
    $game_id = snowflake_id();

    // Create player states
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
        'setup' => true,
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id],
        'winner_id' => $player1->id,
    ]);

    $winner = $game_state->winner();

    expect($winner)->not->toBeNull()
        ->and($winner->id)->toBe($player1->id)
        ->and($winner->color)->toBe('red');

    // Test with no winner
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'winner_id' => null,
    ]);

    expect($game_state->winner())->toBeNull();
});

test('game state is in progress', function () {
    $game_id = snowflake_id();

    // Create player state
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    // Game in progress
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id],
        'active_player_id' => $player1->id,
    ]);

    expect($game_state->isInProgress())->toBeTrue();

    // Game not in progress (no active player)
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id],
        'active_player_id' => null,
    ]);

    expect($game_state->isInProgress())->toBeFalse();
});

test('game state move to next player', function () {
    $game_id = snowflake_id();

    $player1 = PlayerState::factory()->create(['name' => 'Player One', 'color' => 'red', 'setup' => true]);
    $player2 = PlayerState::factory()->create(['name' => 'Player Two', 'color' => 'yellow', 'setup' => true]);
    $player3 = PlayerState::factory()->create(['name' => 'Player Three', 'color' => 'blue', 'setup' => true]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id, $player3->id],
        'active_player_id' => $player1->id,
    ]);

    // Move from player 1 to player 2
    $game_state->moveToNextPlayer();
    expect($game_state->active_player_id)->toBe($player2->id);

    // Move from player 2 to player 3
    $game_state->moveToNextPlayer();
    expect($game_state->active_player_id)->toBe($player3->id);

    // Move from player 3 back to player 1 (wraps around)
    $game_state->moveToNextPlayer();
    expect($game_state->active_player_id)->toBe($player1->id);
});

test('game state board relationship', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();

    // Game with board
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'created' => true,
    ]);

    // Create board state
    verb(new BoardCreated(
        game_id: $game_state->id,
        board_id: $board_id,
    ))->state(BoardState::class);

    $board = $game_state->board();
    expect($board)->not->toBeNull()
        ->and($board->id)->toBe($board_id)
        ->and($board->columns)->toHaveCount(7);

    // Game without board
    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'board_id' => null,
    ]);

    expect($game_state->board())->toBeNull();
});

test('game state has player with PlayerState object', function () {
    $game_id = snowflake_id();

    // Create player states
    $player1 = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
        'setup' => true,
    ]);

    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
        'setup' => true,
    ]);

    $game_state = GameState::factory()->create([
        'id' => $game_id,
        'player_ids' => [$player1->id, $player2->id],
    ]);

    expect($game_state->hasPlayer($player1))->toBeTrue()
        ->and($game_state->hasPlayer($player2))->toBeTrue();
    expect($game_state->players())->toHaveCount(2)
        ->and($game_state->players()->first()->id)->toBe($player1->id)
        ->and($game_state->players()->first()->color)->toBe('red')
        ->and($game_state->players()->last()->id)->toBe($player2->id)
        ->and($game_state->players()->last()->color)->toBe('yellow');
});

test('game started event fires when second player joins', function () {
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game
    $game_state = verb(new GameCreated)->state(GameState::class);

    // First player joins
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));

    expect($game_state->player_ids)->toHaveCount(1)
        ->and($game_state->hasPlayer($player1_id))->toBeTrue()
        ->and($game_state->hasAllPlayersJoined())->toBeFalse();

    // Second player joins - this should trigger GameStarted event
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));

    expect($game_state->player_ids)->toHaveCount(2)
        ->and($game_state->hasPlayer($player2_id))->toBeTrue()
        ->and($game_state->hasAllPlayersJoined())->toBeTrue();

    // Assert that GameStarted event was fired when second player joined
    Verbs::assertCommitted(GameStarted::class, function ($event) use ($game_state, $player1_id) {
        return $event->game_id === $game_state->id && $event->player_id === $player1_id;
    });

    // Verify game is now started
    expect($game_state->started)->toBeTrue()
        ->and($game_state->active_player_id)->toBe($player1_id);
});

// PlayerState Assertions
test('player state initial values', function () {
    $player = PlayerState::factory()->create([
        'name' => 'Test Player',
    ]);

    expect($player->setup)->toBeFalse()
        ->and($player->color)->toBeNull()
        ->and($player->name)->toBe('Test Player');
});

test('player state setup', function () {
    $player = PlayerState::factory()->create([
        'name' => 'Test Player',
        'setup' => true,
    ]);

    expect($player->setup)->toBeTrue();
});

test('player state color assignment', function () {
    $player = PlayerState::factory()->create([
        'name' => 'Player One',
        'color' => 'red',
    ]);

    expect($player->color)->toBe('red');

    // Test different colors
    $player2 = PlayerState::factory()->create([
        'name' => 'Player Two',
        'color' => 'yellow',
    ]);

    expect($player2->color)->toBe('yellow');
});

test('player state name assignment', function () {
    $player = PlayerState::factory()->create([
        'name' => 'Player One',
    ]);

    expect($player->name)->toBe('Player One');
});

test('player state complete setup', function () {
    $player = PlayerState::factory()->create([
        'name' => 'Alice',
        'color' => 'blue',
        'setup' => true,
    ]);

    expect($player->name)->toBe('Alice')
        ->and($player->color)->toBe('blue')
        ->and($player->setup)->toBeTrue();
});

test('player state available colors', function () {
    $game_id = snowflake_id();

    $game_state = verb(new GameCreated)->state(GameState::class);

    expect($game_state->available_colors)->toBe(['blue', 'green', 'red', 'yellow'])
        ->and($game_state->available_colors)->toHaveCount(4)
        ->and($game_state->available_colors)->toContain('red')
        ->and($game_state->available_colors)->toContain('blue')
        ->and($game_state->available_colors)->toContain('green')
        ->and($game_state->available_colors)->toContain('yellow');
});

// Edge Cases and Error Conditions
test('cannot place token in full column', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Fill column 0 completely
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $player1_id : $player2_id, 0);
    }

    // Try to place another token in the full column
    expect(fn () => placeToken($game_state, $player1_id, 0))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot place token when not players turn', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Player 1 places a token
    placeToken($game_state, $player1_id, 0);

    // Player 1 tries to place another token (should fail)
    expect(fn () => placeToken($game_state, $player1_id, 1))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot place token in invalid column', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Try to place token in column 7 (invalid - only 0-6 are valid)
    expect(fn () => placeToken($game_state, $player1_id, 7))
        ->toThrow(EventNotValidForCurrentState::class);

    // Try to place token in column -1 (invalid)
    expect(fn () => placeToken($game_state, $player1_id, -1))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('game ends when board is full', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $p1 = snowflake_id();
    $p2 = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    $board_state = verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id))->state(BoardState::class);

    // Add players
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $p1, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $p1));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $p2, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $p2));
    // GameStarted event is automatically fired when second player joins

    // Fill the board

    // column 0 (fill all but the last row to switch to p2)
    for ($i = 0; $i < 5; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p1 : $p2, 0);
    }

    // column 2
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p2 : $p1, 2);  // p2 goes first
    }

    // column 3
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p2 : $p1, 3);
    }

    // column 6
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p2 : $p1, 6);
    }

    placeToken($game_state, $p2, 0); // fill column 0 in last row with p2, this will make it p1's turn again

    // column 1
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p1 : $p2, 1);
    }

    // column 4
    for ($i = 0; $i < 6; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p1 : $p2, 4);
    }

    // column 5
    for ($i = 0; $i < 5; $i++) {
        placeToken($game_state, $i % 2 === 0 ? $p1 : $p2, 5);
    }

    expect($board_state->isBoardFull())->toBeFalse();

    // this is how the board should look at this point
    // [
    //     [1, 2, 1, 2, 1, 2],
    //     [1, 2, 1, 2, 1, 2],
    //     [2, 1, 2, 1, 2, 1],
    //     [2, 1, 2, 1, 2, 1],
    //     [1, 2, 1, 2, 1, 2],
    //     [1, 2, 1, 2, 1],
    //     [2, 1, 2, 1, 2, 1],
    // ]

    placeToken($game_state, $p2, 5); // fill column 5 in last row with p2, this will fill the board

    expect($board_state->isBoardFull())->toBeTrue();

    expect($game_state->ended)->toBeTrue();
    expect($game_state->winner_id)->toBeNull();

    // Assert that PlayersTiedGame event was fired
    Verbs::assertCommitted(PlayersTiedGame::class, function ($event) use ($game_state) {
        return $event->game_id === $game_state->id;
    });
});

test('cannot start game with only one player', function () {
    $game_id = snowflake_id();
    $player1_id = snowflake_id();

    // Create game
    $game_state = verb(new GameCreated)->state(GameState::class);

    // Add only one player
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));

    // Try to start game with only one player
    expect(fn () => verb(new GameStarted(game_id: $game_state->id, player_id: $player1_id)))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot select same color twice', function () {
    $game_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game
    $game_state = verb(new GameCreated)->state(GameState::class);

    // Player 1 selects red
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));

    // Player 2 tries to select red (should fail)
    expect(fn () => verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'red')))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('cannot select unavailable color', function () {
    $game_id = snowflake_id();
    $player_id = snowflake_id();

    // Create game
    $game_state = verb(new GameCreated)->state(GameState::class);

    // Player tries to select purple (should fail because it's not available)
    expect(fn () => verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player_id, color: 'purple')))
        ->toThrow(EventNotValidForCurrentState::class);
});

test('game state after winner is determined', function () {
    $game_id = snowflake_id();
    $board_id = snowflake_id();
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();

    // Create game and board
    $game_state = verb(new GameCreated)->state(GameState::class);
    verb(new BoardCreated(game_id: $game_state->id, board_id: $board_id));

    // Add players
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));
    // GameStarted event is automatically fired when second player joins

    // Player 1 wins with 4 in a column
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 1);
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 2);
    placeToken($game_state, $player1_id, 0);
    placeToken($game_state, $player2_id, 3);
    placeToken($game_state, $player1_id, 0);

    // Assert that PlayerWonGame event was fired
    Verbs::assertCommitted(PlayerWonGame::class, function ($event) use ($player1_id) {
        return $event->player_id === $player1_id;
    });

    // Verify game state after win
    expect($game_state->ended)->toBeTrue()
        ->and($game_state->winner_id)->toBe($player1_id)
        ->and($game_state->winner()->id)->toBe($player1_id)
        ->and($game_state->isInProgress())->toBeFalse(); // Game is still in progress until explicitly ended
});

test('only 2 players may join the game', function () {
    $player1_id = snowflake_id();
    $player2_id = snowflake_id();
    $player3_id = snowflake_id();

    // Create game
    $game_state = verb(new GameCreated)->state(GameState::class);

    // First player joins
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player1_id, color: 'red'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player1_id));

    expect($game_state->player_ids)->toHaveCount(1)
        ->and($game_state->hasPlayer($player1_id))->toBeTrue();

    // Second player joins
    verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player2_id, color: 'yellow'));
    verb(new PlayerJoinedGame(game_id: $game_state->id, player_id: $player2_id));

    expect($game_state->player_ids)->toHaveCount(2)
        ->and($game_state->hasPlayer($player2_id))->toBeTrue();

    // Third player tries to join - this should fail at color selection stage
    expect(fn () => verb(new PlayerColorSelected(game_id: $game_state->id, player_id: $player3_id, color: 'blue')))
        ->toThrow(EventNotValidForCurrentState::class);

    // Verify third player was not added
    expect($game_state->player_ids)->toHaveCount(2)
        ->and($game_state->hasPlayer($player3_id))->toBeFalse();
});
