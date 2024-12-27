<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models\Board;

use Bga\Games\Sorry\Traits\EnumFromName;

/**
 * Represents a section of the board.
 */
enum BoardSection {
    /**
     * The home circle.
     */
    case home;

    /**
     * The start circle.
     */
    case start;

    /**
     * The safety zone.
     */
    case safety;

    /**
     * The margin squares.
     */
    case margin;

    use EnumFromName;
}
