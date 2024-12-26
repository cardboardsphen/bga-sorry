<?php

declare(strict_types=1);

namespace Bga\Games\Sorry;

use Bga\Games\Sorry\Board\BoardLocation;
use stdClass;

class Pawn {
    private function __construct(public int $playerId, public int $id, public string $color, public BoardLocation $location) {
    }

    public static function create(int $playerId, int $id, string $color, BoardLocation $location): Pawn {
        return new self($playerId, $id, $color, $location);
    }

    public static function fromDb(stdClass $pawn): Pawn {
        return new self(
            $pawn->player,
            $pawn->id,
            $pawn->color,
            BoardLocation::create(
                $pawn->boardSection,
                $pawn->boardSectionColor,
                $pawn->boardSectionIndex,
            ),
        );
    }
}
