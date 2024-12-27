<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models;

use Bga\Games\Sorry\Models\Board\{BoardColor, BoardLocation};
use stdClass;

class Pawn {
    private function __construct(public int $playerId, public int $id, public BoardColor $color, public BoardLocation $location) {
    }

    public static function create(int $playerId, int $id, BoardColor $color, BoardLocation $location): Pawn {
        return new Pawn($playerId, $id, $color, $location);
    }

    public static function fromDb(stdClass $pawn): Pawn {
        return new Pawn(
            intval($pawn->player),
            intval($pawn->id),
            BoardColor::fromName($pawn->color),
            BoardLocation::create(
                $pawn->boardSection,
                $pawn->boardSectionColor,
                intval($pawn->boardSectionIndex),
            ),
        );
    }
}
