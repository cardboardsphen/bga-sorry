<?php

declare(scrict_types=1);

namespace Bga\Games\Sorry\Board;

use Bga\Games\Sorry\Pawn;

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
        $destination->index += $steps;
        try {
            $destination->simplifyLocation(BoardColor::fromName($pawn->color));
        } catch (OutOfRangeException $e) {
            return null;
        }
        return $destination;
    }

    private function simplifyLocation(BoardColor $pawnColor): void {
        if ($this->section === BoardSection::home)
            throw new BgaVisibleSystemException("can not start at home");

        if ($this->section === BoardSection::start) {
            $this->section = BoardSection::margin;
            $this->index = 4;
            return;
        }

        if (is_null($this->index))
            throw new BgaVisibleSystemException("starting location must have an index");

        if ($this->section === BoardSection::safety) {
            if ($this->index < 0) {
                $this->section = BoardSection::margin;
                $this->index += 3;
                $this->simplifyLocation($pawnColor);
                return;
            }

            if ($this->index < 5)
                return;

            if ($this->index == 5) {
                $this->section = BoardSection::home;
                return;
            }

            // $index > 5
            throw new OutOfRangeException("past the home space");
        }

        if ($this->section === 'margin') {
            if ($this->index < 0) {
                $this->color = $this->color->getPreviousColor();
                $this->index += 15;
                $this->simplifyLocation($pawnColor);
                return;
            }

            if ($this->index >= 3 && $this->color === $pawnColor) {
                $this->section = BoardSection::safety;
                $this->index -= 3;
                $this->simplifyLocation($pawnColor);
                return;
            }

            if ($this->index < 15)
                return;

            // $index >= 15 && $color != $pawnColor
            $this->color = $this->color->getNextColor();
            $this->index -= 15;
            return;
        }

        throw new BgaVisibleSystemException("starting location '$this->section' is not valid");
    }
}
