<?php

declare(strict_types=1);

namespace Bga\Games\Sorry;

use Bga\Games\Sorry\Board\BoardLocation;

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
    public static function create(Pawn $pawn, BoardLocation $destination, int $numberOfSteps = -1, bool $isOptional = false): Move {
        return new self($pawn, $destination, $isOptional, $numberOfSteps);
    }

    /**
     * Whether the move is a swap between this move's pawn and one of an opponent's pawns.
     *
     * @return bool true if the move is a swap, false otherwise.
     */
    public function isSwap(): bool {
        return $this->numberOfSteps < 0;
    }
}
