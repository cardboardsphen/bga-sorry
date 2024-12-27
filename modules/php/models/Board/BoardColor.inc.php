<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Models\Board;

use Bga\Games\Sorry\Traits\EnumFromName;

enum BoardColor {
    case red;
    case blue;
    case yellow;
    case green;

    use EnumFromName;

    function getNextColor(): self {
        return match ($this) {
            self::red => self::blue,
            self::blue => self::yellow,
            self::yellow => self::green,
            self::green => self::red,
        };
    }

    function getPreviousColor(): self {
        return match ($this) {
            self::red => self::green,
            self::green => self::yellow,
            self::yellow => self::blue,
            self::blue => self::red,
        };
    }
}
