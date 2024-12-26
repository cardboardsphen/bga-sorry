<?php

declare(strict_types=1);

namespace Bga\Games\Sorry;

use BgaVisibleSystemException;

/**
 * Adds the ability to create an enum from a string name.
 */
trait EnumFromName {
    /**
     * Tries to create an instance of this enum from a string representation of its name.
     *
     * @param string $name The name of the enum value to create.
     * @param self|null $output The output parameter that will contain the enum value if the name is valid.
     * 
     * @return boolean True if the name is a valid enum value, false otherwise.
     */
    public static function tryFromName(string $name, ?self &$output): bool {
        foreach (self::cases() as $case) {
            if ($name === $case->name) {
                $output = $case;
                return true;
            }
        }

        $output = null;
        return false;
    }

    /**
     * Creates an instance of this enum from a string representation of its name.
     *
     * @param string $name The name of the enum value to create.
     *
     * @return self The enum value correspononding to the name provided.
     * 
     * @throws BgaVisibleSystemException If the name is not a valid enum value.
     */
    public static function fromName(string $name): self {
        if (!self::tryFromName($name, $output))
            throw new BgaVisibleSystemException("$name is not a valid  " . self::class);

        return $output;
    }
}
