<?php

declare(strict_types=1);

namespace Bga\Games\Sorry\Tests;

require_once(__DIR__ . '/../../modules/php/autoload.php');

use Bga\Games\Sorry\Models\Board\{BoardLocation, BoardSection, BoardColor};
use Bga\Games\Sorry\Models\Pawn;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider};

#[CoversClass(BoardLocation::class)]
class BoardLocationTest extends TestCase {
    public function testPawnMovesOutOfStart() {
        $pawn = Pawn::create(
            382382,
            1,
            BoardColor::fromName('yellow'),
            BoardLocation::create('start', 'yellow')
        );

        $newLocation = BoardLocation::fromPawnMove($pawn, 1);

        $this->assertNotNull($newLocation);
        $this->assertEquals(BoardSection::margin, $newLocation->section);
        $this->assertEquals(BoardColor::yellow, $newLocation->color);
        $this->assertEquals(4, $newLocation->index);
    }

    public function testPawnMovesWithinMargin() {
        $pawn = Pawn::create(
            382382,
            1,
            BoardColor::fromName('yellow'),
            BoardLocation::create('margin', 'red', 5)
        );

        $newLocation = BoardLocation::fromPawnMove($pawn, 3);

        $this->assertNotNull($newLocation);
        $this->assertEquals(BoardSection::margin, $newLocation->section);
        $this->assertEquals(BoardColor::red, $newLocation->color);
        $this->assertEquals(8, $newLocation->index);
    }

    public static function pawnMovesBetweenMarginsFrowardProvider(): array {
        return [
            'red to blue forward' => [BoardColor::blue, BoardColor::red, BoardSection::margin, 5, 11, BoardColor::blue, BoardSection::margin, 1],
            'yellow to green forward' => [BoardColor::yellow, BoardColor::yellow, BoardSection::margin, 8, 11, BoardColor::green, BoardSection::margin, 4],
            'green to red forward' => [BoardColor::red, BoardColor::green, BoardSection::margin, 8, 9, BoardColor::red, BoardSection::margin, 2],
            'blue to yellow forward' => [BoardColor::green, BoardColor::blue, BoardSection::margin, 8, 11, BoardColor::yellow, BoardSection::margin, 4],
        ];
    }

    public static function pawnMovesBetweenMarginsBackwardProvider(): array {
        return [
            'blue to red backward' => [BoardColor::blue, BoardColor::blue, BoardSection::margin, 1, -11, BoardColor::red, BoardSection::margin, 5],
            'green to yellow backward' => [BoardColor::yellow, BoardColor::green, BoardSection::margin, 4, -11, BoardColor::yellow, BoardSection::margin, 8],
            'red to green backward' => [BoardColor::red, BoardColor::red, BoardSection::margin, 4, -11, BoardColor::green, BoardSection::margin, 8],
            'yellow to blue backward' => [BoardColor::green, BoardColor::yellow, BoardSection::margin, 4, -11, BoardColor::blue, BoardSection::margin, 8],
        ];
    }

    public static function pawnMovesFromMarginToSafetyProvider(): array {
        return [
            'to safety zone from own margin' => [BoardColor::blue, BoardColor::blue, BoardSection::margin, 1, 3, BoardColor::blue, BoardSection::safety, 1],
            'to safety zone from previous margin' => [BoardColor::yellow, BoardColor::blue, BoardSection::margin, 12, 10, BoardColor::yellow, BoardSection::safety, 4],
        ];
    }

    public static function pawnMovesFromSafetyToMarginProvider(): array {
        return [
            'from safety zone to own margin' => [BoardColor::blue, BoardColor::blue, BoardSection::safety, 1, -3, BoardColor::blue, BoardSection::margin, 1],
            'from safety zone to previous margin' => [BoardColor::yellow, BoardColor::yellow, BoardSection::safety, 4, -10, BoardColor::blue, BoardSection::margin, 12],
        ];
    }

    public static function pawnMovesToHomeProvider(): array {
        return [
            'to home from safety zone' => [BoardColor::green, BoardColor::green, BoardSection::safety, 2, 3, BoardColor::green, BoardSection::home, null],
            'to home from own margin' => [BoardColor::green, BoardColor::green, BoardSection::margin, 1, 7, BoardColor::green, BoardSection::home, null],
            'to home from previous margin' => [BoardColor::green, BoardColor::yellow, BoardSection::margin, 12, 11, BoardColor::green, BoardSection::home, null],
        ];
    }

    #[DataProvider('pawnMovesBetweenMarginsFrowardProvider')]
    #[DataProvider('pawnMovesBetweenMarginsBackwardProvider')]
    #[DataProvider('pawnMovesFromMarginToSafetyProvider')]
    #[DataProvider('pawnMovesFromSafetyToMarginProvider')]
    #[DataProvider('pawnMovesToHomeProvider')]
    public function testPawnMoves(BoardColor $pawnColor, BoardColor $fromColor, BoardSection $fromSection, int $fromIndex, int $steps, BoardColor $toColor, BoardSection $toSection, ?int $toIndex) {
        $pawn = Pawn::create(
            382382,
            1,
            $pawnColor,
            BoardLocation::create($fromSection->name, $fromColor->name, $fromIndex)
        );

        $newLocation = BoardLocation::fromPawnMove($pawn, $steps);

        $this->assertNotNull($newLocation);
        $this->assertEquals($toSection, $newLocation->section);
        $this->assertEquals($toColor, $newLocation->color);
        $this->assertEquals($toIndex, $newLocation->index);
    }

    public static function pawnMovesOutOfBoundsProvider(): array {
        return [
            'off of board from safety zone' => [BoardColor::red, BoardColor::red, BoardSection::safety, 2, 4],
            'off of board from own margin' => [BoardColor::red, BoardColor::red, BoardSection::margin, 1, 8],
            'off of board from previous margin' => [BoardColor::red, BoardColor::green, BoardSection::margin, 12, 12],
        ];
    }

    #[DataProvider('pawnMovesOutOfBoundsProvider')]
    public function testPawnMovesOutOfBounds(BoardColor $pawnColor, BoardColor $fromColor, BoardSection $fromSection, int $fromIndex, int $steps) {
        $pawn = Pawn::create(
            382382,
            1,
            $pawnColor,
            BoardLocation::create($fromSection->name, $fromColor->name, $fromIndex)
        );

        $newLocation = BoardLocation::fromPawnMove($pawn, $steps);

        $this->assertNull($newLocation);
    }
}
