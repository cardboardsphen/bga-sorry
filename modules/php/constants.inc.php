<?php

class States {
    const START = 1;
    const END = 99;

    const PLAYER_DRAW_CARD = 10;
    const PLAYER_SELECT_PAWN = 20;
    const PLAYER_SELECT_SQUARE = 30;
    const MOVE_PAWN = 40;
    const NEXT_PLAYER = 50;
}

const CARD_DESCRIPTIONS = [
    '1' => 'must either move a pawn from Start or move a pawn one space forward.',
    '2' => 'must either move a pawn from Start or move a pawn two spaces forward. Then draw again.',
    '3' => 'must move a pawn three spaces forward.',
    '4' => 'must move a pawn four spaces backward.',
    '5' => 'must move a pawn five spaces forward.',
    '7' => 'must either move one pawn seven spaces forward, or split the seven spaces between two pawns.',
    '8' => 'must move a pawn eight spaces forward.',
    '10' => 'must move a pawn either ten spaces forward or one space backward.',
    '11' => 'must either move a pawn eleven spaces forward or switch the places of one of the your pawns and an opponent\'s pawn. NOTE: If you cannot move 11 spaces, you may instead can end your turn.',
    '12' => 'must move a pawn twelve spaces forward.',
    'sorry' => 'must take any one pawn from Start and move it directly to a square occupied by any opponent\'s pawn, sending that pawn back to its own Start.',
];
