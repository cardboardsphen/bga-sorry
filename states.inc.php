<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sorry implementation : © cardboardsphen, bga-dev@sphen.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * Full source available at https://github.com/cardboardsphen/bga-sorry
 * 
 * -----
 *
 * states.inc.php
 *
 * Sorry game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

require_once("modules/php/Constants.inc.php");

$machinestates = [
    // The initial state. Please do not modify.
    States::START => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => States::PLAYER_DRAW_CARD]
    ),

    States::PLAYER_DRAW_CARD => [
        "name" => "drawCard",
        "description" => clienttranslate('${actplayer} must draw a card'),
        "descriptionmyturn" => clienttranslate('${you} must draw a card'),
        "type" => "activeplayer",
        "possibleactions" => ["actDrawCard"],
        "transitions" => ["nextPlayer" => States::NEXT_PLAYER, "selectPawn" => States::PLAYER_SELECT_PAWN, "drawAgain" => States::PLAYER_DRAW_CARD, "zombiePass" => States::END]
    ],

    States::PLAYER_SELECT_PAWN => [
        "name" => "selectPawn",
        "description" => clienttranslate('${actplayer} must must make a move'),
        "descriptionmyturn" => clienttranslate('${you} ${cardDescription}'),
        "type" => "activeplayer",
        "args" => "argSelectPawn",
        "possibleactions" => ["actSelectPawn", "actUndoSelection"],
        "transitions" => ["skipTurn" => States::NEXT_PLAYER, "selectPawn" => States::PLAYER_SELECT_PAWN, "selectSquare" => States::PLAYER_SELECT_SQUARE, "movePawn" => States::MOVE_PAWN]
    ],

    States::PLAYER_SELECT_SQUARE => [
        "name" => "selectSquare",
        "description" => clienttranslate('${actplayer} must must make a move'),
        "descriptionmyturn" => clienttranslate('${you} ${cardDescription}'),
        "type" => "activeplayer",
        "args" => "argSelectSquare",
        "possibleactions" => ["actSelectSquare", "actUndoSelection"],
        "transitions" => ["skipTurn" => States::NEXT_PLAYER, "movePawn" => States::MOVE_PAWN, "selectPawn" => States::PLAYER_SELECT_PAWN]
    ],

    States::MOVE_PAWN => [
        "name" => "movePawn",
        "description" => "moving pawns",
        "type" => "game",
        "action" => "stMovePawn",
        "transitions" => ["nextPlayer" => States::NEXT_PLAYER, "secondMove" => States::PLAYER_SELECT_PAWN, "drawAgain" => States::PLAYER_DRAW_CARD]
    ],

    States::NEXT_PLAYER => [
        "name" => "nextPlayer",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array("nextTurn" => States::PLAYER_DRAW_CARD, "endGame" => States::END)
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    States::END => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];
