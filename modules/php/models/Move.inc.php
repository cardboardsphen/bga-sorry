<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models;

use Bga\Games\Sorry\Models\Board\BoardColor;
use Bga\Games\Sorry\Models\Board\BoardLocation;
use stdClass;

class Move {
    /**
     * @param Pawn $pawn The pawn to move.
     * @param BoardLocation $destination The destination location on the board.
     * @param bool $isOptional Whether the move can be skipped by the player.
     * @param int $numberOfSteps The number of steps the pawn has to take to complete this move. If negative, the move is a swap.
     */
    private function __construct(public Pawn $pawn, public BoardLocation $destination, public bool $isOptional, public int $numberOfSteps) {
    }

    /**
     * @param Pawn $pawn The pawn to move.
     * @param BoardLocation $destination The destination location on the board.
     */
    public static function create(Pawn $pawn, BoardLocation $destination, int $numberOfSteps = 0, bool $isOptional = false): Move {
        return new self($pawn, $destination, $isOptional, $numberOfSteps);
    }

    /**
     * Creates a new Move from a database object.
     *
     * @param stdClass $move The database object consiting of the move data joined with the pawn data.
     *
     * @return Move|null The move object, or null if the object is missing required properties.
     */
    public static function fromDb(stdClass $move): ?Move {
        if (!isset($move->player, $move->pawnId, $move->color, $move->boardSection, $move->boardSectionColor, $move->destinationSection, $move->destinationSectionColor, $move->optional, $move->numberOfSteps))
            return null;
        if (!property_exists($move, 'boardSectionIndex') || !property_exists($move, 'destinationSectionIndex'))
            return null;

        $pawn = Pawn::create(intval($move->player), intval($move->pawnId), BoardColor::fromName($move->color), BoardLocation::create($move->boardSection, $move->boardSectionColor, is_null($move->boardSectionIndex) ? null : intval($move->boardSectionIndex)));
        $destination = BoardLocation::create($move->destinationSection, $move->destinationSectionColor, is_null($move->destinationSectionIndex) ? null : intval($move->destinationSectionIndex));

        return new self(
            $pawn,
            $destination,
            $move->optional == 1,
            intval($move->numberOfSteps),
        );
    }

    /**
     * Whether the move is a swap between this move's pawn and one of an opponent's pawns.
     *
     * @return bool true if the move is a swap, false otherwise.
     */
    public function isSwap(): bool {
        return $this->numberOfSteps == 0;
    }
}
