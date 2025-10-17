<?php

namespace App\States;

use Thunk\Verbs\State;

class PlayerState extends State
{
    public bool $setup = false;

    public string $name;

    public ?string $color = null;

    public ?int $last_placed_column = null;
}
