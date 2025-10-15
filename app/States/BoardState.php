<?php

namespace App\States;

use Thunk\Verbs\State;

class BoardState extends State
{
    public const MAX_HEIGHT = 6;

    public const MAX_WIDTH = 7;

    public const WIN_LENGTH = 4;

    public array $columns = [];

    public function setup()
    {
        $this->columns = array_fill(0, self::MAX_WIDTH, []);
    }

    public function height(int $column): int
    {
        return count($this->columns[$column]);
    }

    public function tokenAt(int $column, int $row): ?int
    {
        return $this->columns[$column][$row] ?? null;
    }

    public function tokenColorAt(int $column, int $row): ?string
    {
        return $this->tokenAt($column, $row) ? PlayerState::load($this->tokenAt($column, $row))->color : null;
    }

    public function isColumnFull(int $column): bool
    {
        return count($this->columns[$column]) === self::MAX_HEIGHT;
    }

    public function isBoardFull(): bool
    {
        foreach (array_keys($this->columns) as $i) {
            if (! $this->isColumnFull($i)) {
                return false;
            }
        }

        return true;
    }

    public function checkWin(int $column, int $row): bool
    {
        $playerId = $this->tokenAt($column, $row);
        if ($playerId === null) {
            return false;
        }

        // Check horizontal
        if ($this->checkDirection($column, $row, 1, 0, $playerId) +
            $this->checkDirection($column, $row, -1, 0, $playerId) >= 3) {
            return true;
        }

        // Check vertical
        if ($this->checkDirection($column, $row, 0, 1, $playerId) +
            $this->checkDirection($column, $row, 0, -1, $playerId) >= 3) {
            return true;
        }

        // Check diagonal (top-left to bottom-right)
        if ($this->checkDirection($column, $row, 1, 1, $playerId) +
            $this->checkDirection($column, $row, -1, -1, $playerId) >= 3) {
            return true;
        }

        // Check diagonal (top-right to bottom-left)
        if ($this->checkDirection($column, $row, 1, -1, $playerId) +
            $this->checkDirection($column, $row, -1, 1, $playerId) >= 3) {
            return true;
        }

        return false;
    }

    private function checkDirection(int $column, int $row, int $deltaCol, int $deltaRow, int $playerId): int
    {
        $count = 0;
        $currentCol = $column + $deltaCol;
        $currentRow = $row + $deltaRow;

        while ($currentCol >= 0 && $currentCol < self::MAX_WIDTH &&
               $currentRow >= 0 && $currentRow < $this->height($currentCol) &&
               $this->tokenAt($currentCol, $currentRow) === $playerId) {
            $count++;
            $currentCol += $deltaCol;
            $currentRow += $deltaRow;
        }

        return $count;
    }
}
