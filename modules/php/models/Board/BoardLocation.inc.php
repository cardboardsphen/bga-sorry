<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models\Board;

use Bga\Games\Sorry\Models\Pawn;

use BgaVisibleSystemException;
use OutOfRangeException;

/**
 * Represents a location on the board.
 */
class BoardLocation {
    /**
     * The section of the board.
     *
     * @var BoardSection
     */
    public BoardSection $section;

    /**
     * The color of the section of the board.
     *
     * @var BoardColor
     */
    public BoardColor $color;

    /**
     * The index of the location within the section.
     *
     * @var integer|null
     */
    public ?int $index;

    private function __construct(string $section, string $color, ?int $index) {
        $this->section = BoardSection::fromName($section);
        $this->color = BoardColor::fromName($color);
        $this->index = $index;
    }

    /**
     * Creates a new location on the board.
     *
     * @param string $section The section of the board.
     * @param string $color The color of the section of the board.
     * @param integer|null $index The index of the location within the section.
     * 
     * @return BoardLocation Returns the location.
     */
    public static function create(string $section, string $color, ?int $index = null): BoardLocation {
        return new self($section, $color, $index);
    }

    /**
     * Returns the location of a pawn after moving a number of steps.
     * 
     * @param Pawn $pawn The pawn to move.
     * @param int $steps The number of steps to move the pawn.
     * 
     * @return BoardLocation|null Returns the new location or null if the location is outside of the board.
     */
    public static function fromPawnMove(Pawn $pawn, int $steps): ?BoardLocation {
        $destination = clone $pawn->location;
        try {
            $destination->moveSteps($pawn->color, $steps);
            return $destination;
        } catch (OutOfRangeException $e) {
            return null;
        }
    }

    private function moveSteps(BoardColor $pawnColor, int $steps): void {
        if ($this->section === BoardSection::home)
            throw new BgaVisibleSystemException("can not start at home");

        if ($this->section === BoardSection::start) {
            if ($steps <= 0)
                return;

            $this->section = BoardSection::margin;
            $this->index = 4;
            return;
        }

        if (is_null($this->index))
            throw new BgaVisibleSystemException("starting location must have an index");

        if ($this->section === BoardSection::safety) {
            $newIndex = $this->index + $steps;
            if ($newIndex > 5)
                throw new OutOfRangeException("past the safety space");

            if ($newIndex == 5) {
                $this->section = BoardSection::home;
                $this->index = null;
                return;
            }

            if ($newIndex >= 0) {
                $this->index = $newIndex;
                return;
            }

            // moving out backwards
            $this->section = BoardSection::margin;
            $newIndex += 3;
            if ($newIndex >= 0) {
                $this->index = $newIndex;
            } else {
                $this->index = 0;
                $this->moveSteps($pawnColor, $newIndex);
            }

            return;
        }

        if ($this->section === BoardSection::margin) {
            $newIndex = $this->index + $steps;

            if ($newIndex < 0) {
                $newIndex += 15;
                if ($newIndex < 0)
                    throw new BgaVisibleSystemException("number of steps was too big");

                $this->color = $this->color->getPreviousColor();
                $this->index = $newIndex;
                return;
            }

            if ($this->index < 3 && $newIndex >= 3 && $this->color === $pawnColor) {
                $this->section = BoardSection::safety;
                $this->index = 0;
                $this->moveSteps($pawnColor, $newIndex - 3);
                return;
            }

            if ($newIndex < 15) {
                $this->index = $newIndex;
                return;
            }

            $newIndex -= 15;
            if ($newIndex > 14)
                throw new BgaVisibleSystemException("number of steps was too big");

            $this->color = $this->color->getNextColor();
            $this->index = 0;
            $this->moveSteps($pawnColor, $newIndex);
            return;
        }

        throw new BgaVisibleSystemException("starting location '$this->section' is not valid");
    }
}
