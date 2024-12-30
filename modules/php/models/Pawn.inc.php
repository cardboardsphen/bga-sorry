<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models;

use Bga\Games\Sorry\Models\Board\{BoardColor, BoardLocation};
use stdClass;

class Pawn {
    /**
     * @param int $playerId The ID of the player that owns this pawn.
     * @param int $id The ID of this pawn. Each player has four.
     * @param BoardColor $color The color of this pawn.
     * @param BoardLocation $location The location of this pawn on the board.
     */
    private function __construct(public int $playerId, public int $id, public BoardColor $color, public BoardLocation $location) {
    }

    /**
     * Creates a new pawn.
     *
     * @param int $playerId The ID of the player that owns this pawn.
     * @param int $id The ID of this pawn.
     * @param BoardColor $color The color of this pawn.
     * @param BoardLocation $location The location of this pawn on the board.
     *
     * @return Pawn Returns the Pawn.
     */
    public static function create(int $playerId, int $id, BoardColor $color, BoardLocation $location): Pawn {
        return new Pawn($playerId, $id, $color, $location);
    }

    /**
     * Creates a new pawn from a database object.
     *
     * @param stdClass $pawn The database object.
     *
     * @return Pawn|null Returns the pawn if the object is contains the required fields; null otherwise.
     */
    public static function fromDb(stdClass $pawn): ?Pawn {
        if (!isset($pawn->player, $pawn->id, $pawn->color))
            return null;

        return new Pawn(
            intval($pawn->player),
            intval($pawn->id),
            BoardColor::fromName($pawn->color),
            BoardLocation::fromDb($pawn),
        );
    }
}
