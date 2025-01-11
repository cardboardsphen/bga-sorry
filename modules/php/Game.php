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
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\Sorry;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once("autoload.php");

use \stdClass;

use \Bga\GameFramework\Actions\CheckAction;
use \BgaUserException;

use \Bga\Games\Sorry\Helpers\DatabaseHelpers;
use \Bga\Games\Sorry\Models\{Pawn, Move};
use \Bga\Games\Sorry\Models\Board\{BoardLocation, BoardSection, BoardColor};

class Game extends \Table {
    use DatabaseHelpers;

    private static array $COLOR_NAMES;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct() {
        parent::__construct();

        $this->initGameStateLabels([]);

        self::$COLOR_NAMES = [
            "ff0000" => "red",
            "32cd32" => "green",
            "4169e1" => "blue",
            "ffbf00" => "yellow",
        ];
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */
    public function argSelectPawn(): array {
        $playerId = intval($this->getActivePlayerId());
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $canSkip = self::getFirstRowFromDb("SELECT count(optional) as numberRequired from possible_moves where optional = false")->numberRequired == 0;
        $canUndo = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves where selected_as = 'first_move'")->count == 1;
        $possibleMoves = self::getRowsFromDb("SELECT pawn_id, destination_section, destination_section_color, destination_section_index from possible_moves where selected_as = 'none'");

        return [
            'player' => $playerId,
            'color' => $playerColor,
            'cardRank' => $rank,
            'cardDescription' => CARD_DESCRIPTIONS[$rank],
            'selectedMove' => $this->getSelectedMove(),
            'possibleMoves' => $this->groupPossibleMoves($possibleMoves),
            'canSkip' => $canSkip,
            'canUndo' => $canUndo,
        ];
    }

    public function argSelectSquare(): array {
        $playerId = intval($this->getActivePlayerId());
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $possibleMoves = self::getRowsFromDb("SELECT pawn_id, destination_section, destination_section_color, destination_section_index from possible_moves where selected_as = 'pawn'");
        $canSkip = self::getFirstRowFromDb("SELECT count(optional) as numberRequired from possible_moves where optional = false")->numberRequired == 0;
        return [
            'player' => $playerId,
            'color' => $playerColor,
            'cardRank' => $rank,
            'cardDescription' => CARD_DESCRIPTIONS[$rank],
            'selectedMove' => $this->getSelectedMove(),
            'possibleMoves' => $this->groupPossibleMoves($possibleMoves),
            'canSkip' => $canSkip,
        ];
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression(): int {
        $pawnsAtHome = self::getFirstRowFromDb("SELECT max(count) as count from (SELECT count(id) as count from pawns where board_section = 'home' group by player) as pawns_at_home;")->count;
        return $pawnsAtHome * 25;
    }

    /**
     * Player actions
     */
    public function actDrawCard(): void {
        $topCards = self::getRowsFromDb("SELECT id, rank from cards where pile = 'draw' order by position limit 2");
        if (count($topCards) == 0) {
            $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
            $this->shuffleDeck();
            $this->notifyAllPlayers('shuffleDeck', clienttranslate('${player_name} shuffles the deck.'), ['player_name' => $this->getActivePlayerName(), 'rank' => $rank]);

            $topCards = self::getRowsFromDb("SELECT id, rank from cards where pile = 'draw' order by position limit 2");
        }

        $thisCard = $topCards[0];
        $this->DbQuery("UPDATE cards set pile = 'discard' where id = $thisCard->id");

        $rank = $thisCard->rank;
        $this->triggerClientAnimationOfDraw($rank, count($topCards) > 1);
        $this->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} draws a ${rank} card.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->notifyAllPlayers('simplePause', '', ['time' => 1500]); // give a second to ensure draw animation has finished

        if ($rank != 7)
            $this->determineAllPossibleMoves();
        else
            $this->determineAllPossibleMovesForSeven();

        $possibleMoveCount = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves")->count;

        if ($possibleMoveCount > 0) {
            $this->gamestate->nextState("selectPawn");
            return;
        }

        $this->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} has no valid moves for ${rank} card.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->triggerClientAnimationOfDiscard();

        if ($this->getWinner() < 0 && $rank == '2') {
            $this->notifyAllPlayers('drawAgain', clienttranslate('${player_name} gets to draw again.'), ['player_name' => $this->getActivePlayerName()]);
            $this->gamestate->nextState('drawAgain');
            return;
        }

        $this->gamestate->nextState('nextPlayer');
    }

    public function actSelectPawn(int $pawnId) {
        if (intval($pawnId) < 0) {
            $this->skipOrThrow();
            return;
        }

        $player = $this->getActivePlayerName();
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;

        $possibleMoves = self::getRowsFromDb("SELECT pawn_id, number_of_steps, selected_as from possible_moves where pawn_id = '$pawnId' and selected_as = 'none'");
        if (count($possibleMoves) == 0)
            throw new BgaUserException(sprintf($this->_('%s can not move that pawn right now.'), $player));

        if (count($possibleMoves) == 1) {
            $hasPreviousSelection = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves where pawn_id != '$pawnId' and selected_as = 'first_move'")->count  == 1;

            if ($rank != 7 || $hasPreviousSelection || $possibleMoves[0]->numberOfSteps == 7) {
                $this->DbQuery("UPDATE possible_moves set selected_as = 'final_move' where pawn_id = '$pawnId' and selected_as = 'none'");
                $this->gamestate->nextState('movePawn');
                return;
            }

            // we're making the first selection for a 7 card now
            $this->DbQuery("UPDATE possible_moves set selected_as = 'first_move' where pawn_id = '$pawnId' and selected_as = 'none'");
            $this->blockInvalidSecondMovesForSeven();
            $this->gamestate->nextState('selectPawn');
            return;
        }

        $this->DbQuery("UPDATE possible_moves set selected_as = 'pawn' where pawn_id = '$pawnId'");
        $this->gamestate->nextState('selectSquare');
    }

    public function actSelectSquare(string $squareId): void {
        if (intval($squareId) < 0) {
            $this->skipOrThrow();
            return;
        }

        $player = $this->getActivePlayerName();
        $square = explode('-', $squareId);
        $section = $square[0];
        $color = $square[1];
        $index = count($square) > 2 ? $square[2] : null;
        $hasPreviousSelection = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves where selected_as = 'first_move'")->count == 1;
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;

        $moveStage = ($rank != 7 || $hasPreviousSelection) ? 'final_move' : 'first_move';
        $indexEquality = is_null($index) ? 'is null' : "= '{$index}'";
        $this->DbQuery("UPDATE possible_moves set selected_as = '$moveStage' where selected_as = 'pawn' and destination_section = '$section' and destination_section_color = '$color' and destination_section_index $indexEquality");
        $this->DbQuery("UPDATE possible_moves set selected_as = 'none' where selected_as = 'pawn'");

        $numberOfSteps = self::getFirstRowFromDb("SELECT number_of_steps from possible_moves where selected_as = '$moveStage'")->numberOfSteps;
        if ($rank != 7 || $hasPreviousSelection || $numberOfSteps == 7) {
            $this->gamestate->nextState('movePawn');
        } else {
            $this->blockInvalidSecondMovesForSeven();
            $this->gamestate->nextState('selectPawn');
        }
    }

    public function actUndoSelection(): void {
        $this->DbQuery("UPDATE possible_moves set selected_as = 'none'");
        $this->gamestate->nextState('selectPawn');
    }

    /**
     * Game state actions
     */
    public function stMovePawn(): void {
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $firstMove = Move::fromDb(self::getFirstRowFromDb(
            "SELECT possible_moves.player, pawn_id, color, board_section, board_section_color, board_section_index, destination_section, destination_section_color, destination_section_index, optional, number_of_steps
             from possible_moves
             join pawns
                on possible_moves.player = pawns.player
                and possible_moves.pawn_id = pawns.id
             where selected_as = 'first_move'"
        ));
        $this->movePawn($firstMove);

        $finalMove = Move::fromDb(self::getFirstRowFromDb(
            "SELECT possible_moves.player, pawn_id, color, board_section, board_section_color, board_section_index, destination_section, destination_section_color, destination_section_index, optional, number_of_steps
             from possible_moves
             join pawns
                on possible_moves.player = pawns.player
                and possible_moves.pawn_id = pawns.id
             where selected_as = 'final_move'"
        ));
        $this->movePawn($finalMove);
        $this->triggerClientAnimationOfDiscard();

        $this->calculateMoveStats($rank, $firstMove, $finalMove);

        if ($this->getWinner() < 0 && $rank == '2') {
            $this->notifyAllPlayers('drawAgain', clienttranslate('${player_name} gets to draw again.'), ['player_name' => $this->getActivePlayerName()]);
            $this->gamestate->nextState('drawAgain');
            return;
        }

        $this->gamestate->nextState('nextPlayer');
    }

    public function stNextPlayer(): void {
        $this->calculateTurnStats();

        if ($this->getWinner() >= 0) {
            $this->gamestate->nextState('endGame');
            return;
        }

        $playerId = intval($this->getActivePlayerId());
        $this->giveExtraTime($playerId);
        $this->activeNextPlayer();
        $this->gamestate->nextState('nextTurn');
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $activePlayer): void {
        $stateName = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($stateName) {
                default: {
                        $this->gamestate->nextState("zombiePass");
                        break;
                    }
            }

            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$stateName}\".");
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version) {
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas() {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $currentPlayerId = intval($this->getCurrentPlayerId());

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result['players'] = $this->getCollectionFromDb(
            "SELECT player_id as id, player_score as score, player_color as color, player_color_name as color_name FROM player"
        );

        // Get pawn locations
        $result['pawns'] = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns");

        // Get card information
        $result['cards'] = [];
        $result['cards']['nextCard'] = self::getFirstRowFromDb("SELECT min(id) as id from cards where pile = 'draw'")->id;

        $topDiscards = self::getRowsFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 2");
        if (in_array($this->gamestate->state()['name'], ['selectPawn', 'selectSquare'])) {
            $result['cards']['revealCard'] = count($topDiscards) > 0 ? $topDiscards[0]->rank : null;
            $result['cards']['lastCard'] = count($topDiscards) > 1 ? $topDiscards[1]->rank : null;
        } else {
            $result['cards']['lastCard'] = count($topDiscards) > 0 ? $topDiscards[0]->rank : null;
        }

        $result['version'] = $this->getGameStateValue('game_db_version');

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName() {
        return "sorry";
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = []) {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $defaultColors = array("ff0000", "32cd32", "4169e1", "ffbf00");
        shuffle($defaultColors);

        foreach ($players as $playerId => $player) {
            $color = array_shift($defaultColors);
            // Now you can access both $playerId and $player array
            $queryValues[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $playerId,
                $color,
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        $this->DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $queryValues)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        foreach (array_keys($players) as $playerId) {
            $color = self::getFirstRowFromDb("SELECT player_color as color from player where player_id = '$playerId'")->color;
            $colorName = self::$COLOR_NAMES[$color];
            $this->DbQuery("UPDATE player set player_color_name = '$colorName' where player_id = '$playerId'");
        }

        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.

        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.
        $this->initStat('table', 'numberOfTurns', 0);
        $this->initStat('table', 'percentOfPawnsAtHome', 0);
        $this->initStat('table', 'percentOfPawnsAtStart', 0);
        $this->initStat('table', 'numberOfSorrysUsed', 0);
        $this->initStat('table', 'totalSquaresMoved', 0);
        $this->initStat('player', 'numberOfTurns', 0);
        $this->initStat('player', 'numberOfMoves', 0);
        $this->initStat('player', 'totalSquaresMoved', 0);
        $this->initStat('player', 'numberOfSorrysUsed', 0);
        $this->initStat('player', 'timesBumpedOtherPawns', 0);
        $this->initStat('player', 'timesBumped', 0);
        $this->initStat('player', 'pawnsAtHome', 0);
        $this->initStat('player', 'pawnsAtStart', 0);
        $this->initStat('player', 'pawnsBackedOutOfSafetyZone', 0);
        $this->initStat('player', 'timesBumpedOwnPawns', 0);

        // Store four pawns in each player's home circles
        $sql = "INSERT into pawns (player, id, color, board_section, board_section_color, board_section_index) values ";
        $sqlValues = array();
        foreach ($players as $playerId => $player) {
            $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
            for ($index = 0; $index < 4; $index++) {
                $sqlValues[] = "('$playerId', '$index', '$playerColor', 'start', '$playerColor', NULL)";
            }
        }
        $sql .= implode(',', $sqlValues);
        $this->DbQuery($sql);

        $deck = array(0, 1, 2, 3, 4, 5, 7, 8, 10, 11, 12);
        $deck = array_merge($deck, $deck);
        $deck = array_merge($deck, $deck);
        $deck[] = 1;
        $sql = "INSERT into cards (id, position, rank, pile) values ";
        foreach ($deck as $position => $value) {
            $rank = $value == 0 ? "('sorry')" : "('$value')";
            $sql .= "($position, $position, $rank, 'draw'),";
        }
        $sql = substr($sql, 0, -1);
        $this->DbQuery($sql);
        $this->shuffleDeck();

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    private function shuffleDeck() {
        $order = range(0, 44);
        shuffle($order);
        $sql = "UPDATE cards c left join (select 0 as id, {$order[0]} as position ";
        foreach (array_slice($order, 1) as $index => $position) {
            $id = $index + 1;
            $sql .= "union all select $id, $position ";
        }
        $sql .= ") o on c.id = o.id set c.position = o.position, pile = 'draw'";
        $this->DbQuery($sql);
    }

    private function determineAllPossibleMoves(): void {
        $this->DbQuery("DELETE from possible_moves");

        $playerId = intval($this->getActivePlayerId());
        $card = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $pawns = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where player = '$playerId'");

        $sql = "INSERT into possible_moves (player, pawn_id, destination_section, destination_section_color, destination_section_index, optional, number_of_steps) values ";
        $sqlRows = [];
        foreach ($pawns as $pawn) {
            $moves = $this->determinePossibleMoves(Pawn::fromDb($pawn), $card);
            foreach ($moves as $move)
                $sqlRows[] = "('$playerId', '$pawn->id', '{$move->destination->section->name}', '{$move->destination->color->name}', nullif('{$move->destination->index}',''), '" . ($move->isOptional ? 1 : 0) . "', '$move->numberOfSteps')";
        }
        if (count($sqlRows) > 0)
            $this->DbQuery($sql . implode(',', $sqlRows));
    }

    private function determineAllPossibleMovesForSeven(): void {
        $this->DbQuery("DELETE from possible_moves");

        $playerId = intval($this->getActivePlayerId());
        $card = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $pawns = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where player = '$playerId' and board_section != 'start' and board_section != 'home'");

        if (count($pawns) == 0)
            return;

        if (count($pawns) == 1) {
            $pawn = Pawn::fromDb($pawns[0]);
            $destination = BoardLocation::fromPawnMove($pawn, 7);
            if ($this->pawnCanMoveToLocation($pawn, $destination)) {
                $sql = "INSERT into possible_moves (player, pawn_id, destination_section, destination_section_color, destination_section_index, optional, number_of_steps)
                               values ('$playerId', '{$pawns[0]->id}', '{$destination->section->name}', '{$destination->color->name}', nullif('{$destination->index}',''), 0, 7)";
                $this->DbQuery($sql);
            }
            return;
        }

        $moves = [];
        foreach ($pawns as $pawn)
            $moves = array_merge($moves, $this->determinePossibleMoves(Pawn::fromDb($pawn), $card));

        $movesToCheck = $moves;
        foreach ($movesToCheck as $move) {
            if (!in_array($move, $moves))
                continue;

            $sameColorPawnsAtLocation = self::getRowsFromDb("SELECT id from pawns where color = '{$move->pawn->color->name}' and board_section = '{$move->destination->section->name}' and board_section_color = '{$move->destination->color->name}' and board_section_index = '{$move->destination->index}'");
            if (count($sameColorPawnsAtLocation) == 0 && $move->numberOfSteps == 7)
                continue;

            if (count($sameColorPawnsAtLocation) > 0) {
                if (count(array_filter($moves, fn($otherMove) => $otherMove->pawn->id == $sameColorPawnsAtLocation[0]->id && $move->numberOfSteps + $otherMove->numberOfSteps == 7)) == 0)
                    $moves = array_filter($moves, fn($removedMove) => $removedMove->pawn->id != $move->pawn->id || $removedMove->numberOfSteps != $move->numberOfSteps);
                continue;
            }

            $found7sAddendPair = false;
            $otherMoves = array_filter($moves, fn($otherMove) => $otherMove->pawn->id != $move->pawn->id);
            foreach ($otherMoves as $otherMove) {
                if ($move->numberOfSteps + $otherMove->numberOfSteps == 7 && ($move->destination->section === BoardSection::home || $move->destination->section !== $otherMove->destination->section || $move->destination->color !== $otherMove->destination->color || $move->destination->index != $otherMove->destination->index))
                    $found7sAddendPair = true;
            }
            if (!$found7sAddendPair)
                $moves = array_filter($moves, fn($removedMove) => $removedMove->pawn->id != $move->pawn->id || $removedMove->numberOfSteps != $move->numberOfSteps);
        }

        $sql = "INSERT into possible_moves (player, pawn_id, destination_section, destination_section_color, destination_section_index, optional, number_of_steps) values ";
        $sqlRows = [];
        foreach ($moves as $move)
            $sqlRows[] = "('$playerId', '{$move->pawn->id}', '{$move->destination->section->name}', '{$move->destination->color->name}', nullif('{$move->destination->index}',''), '0', '$move->numberOfSteps')";
        if (count($sqlRows) > 0)
            $this->DbQuery($sql . implode(',', $sqlRows));
    }

    private function blockInvalidSecondMovesForSeven(): void {
        $player = $this->getActivePlayerId();
        $firstMove = self::getFirstRowFromDb("SELECT pawn_id, number_of_steps, destination_section, destination_section_color, destination_section_index from possible_moves where selected_as = 'first_move'");

        $numberOfStepsToMakeSeven = 7 - $firstMove->numberOfSteps;
        $this->DbQuery("UPDATE possible_moves set selected_as = 'blocked' where selected_as != 'first_move' and (pawn_id = '$firstMove->pawnId' or number_of_steps != '$numberOfStepsToMakeSeven')");

        if ($firstMove->destinationSection == 'home')
            return;

        $firstLandingOn = self::getFirstRowFromDb("SELECT id from pawns where player = '$player' and board_section = '{$firstMove->destinationSection}' and board_section_color = '{$firstMove->destinationSectionColor}' and board_section_index = '{$firstMove->destinationSectionIndex}'");
        if (isset($firstLandingOn->id)) {
            $this->DbQuery("UPDATE possible_moves set selected_as = 'blocked' where selected_as != 'first_move' and pawn_id != '{$firstLandingOn->id}'");
        }

        $possibleMoves = self::getRowsFromDb("SELECT pawn_id, destination_section, destination_section_color, destination_section_index from possible_moves where selected_as = 'none'");
        foreach ($possibleMoves as $possibleMove) {
            $landingOnFirst = $possibleMove->destinationSection == $firstMove->destinationSection && $possibleMove->destinationSectionColor == $firstMove->destinationSectionColor && $possibleMove->destinationSectionIndex == $firstMove->destinationSectionIndex;
            $pawnAtDestination = self::getFirstRowFromDb("SELECT id from pawns where player = '$player' and id != '$firstMove->pawnId' and board_section = '$possibleMove->destinationSection' and board_section_color = '$possibleMove->destinationSectionColor' and board_section_index = '$possibleMove->destinationSectionIndex'");
            if ($landingOnFirst || isset($pawnAtDestination->id))
                $this->DbQuery("UPDATE possible_moves set selected_as = 'blocked' where selected_as != 'first_move' and pawn_id = '$possibleMove->pawnId'");
        }
    }

    /**
     * @return Move[]
     */
    private function determinePossibleMoves(Pawn $pawn, string $card): array {
        $cardVal = intval($card);

        if ($pawn->location->section === BoardSection::home)
            return [];

        if ($pawn->location->section === BoardSection::start) {
            $destination = BoardLocation::fromPawnMove($pawn, 1);
            if (in_array($cardVal, [1, 2]) && $this->pawnCanMoveToLocation($pawn, $destination))
                return [Move::create($pawn, $destination, 1)];

            if ($card == 'sorry') {
                $otherPlayerPawnsOnMargin = self::getRowsFromDb("SELECT board_section, board_section_color, board_section_index from pawns where player != {$pawn->playerId} and board_section = 'margin'");
                return array_map(fn($otherPawn) => Move::create($pawn, BoardLocation::fromDb($otherPawn)), $otherPlayerPawnsOnMargin);
            }

            return [];
        }

        // we're on the margin or safety zone now
        if ($card == 'sorry')
            return [];

        if (in_array($cardVal, [1, 2, 3, 4, 5, 8, 10, 12])) {
            if ($cardVal == 4)
                $cardVal = -4;

            $possibleMoves = [];
            $this->addMoveIfPossible($possibleMoves, $pawn, $cardVal);

            if ($cardVal == 10)
                $this->addMoveIfPossible($possibleMoves, $pawn, -1);

            return $possibleMoves;
        }

        if ($cardVal == 7) {
            $possibleMoves = [];
            for ($i = 1; $i <= 7; $i++) {
                // add all the moves for 7; will prune impossible moves later since other pawns can move too
                $possibleDestination = BoardLocation::fromPawnMove($pawn, $i);
                if (!is_null($possibleDestination))
                    $possibleMoves[] = Move::create($pawn, $possibleDestination, $i);
            }
            return $possibleMoves;
        }

        if ($cardVal == 11) {
            $possibleMoves = [];

            if ($pawn->location->section === BoardSection::margin) {
                $otherPlayerPawnsOnMargin = self::getRowsFromDb("SELECT board_section, board_section_color, board_section_index from pawns where player != {$pawn->playerId} and board_section = 'margin'");
                $swapMoves = array_map(
                    fn($otherPawn) => Move::create($pawn, BoardLocation::fromDb($otherPawn), isOptional: true),
                    $otherPlayerPawnsOnMargin
                );
                $possibleMoves = array_merge($possibleMoves, $swapMoves);
            }

            $this->addMoveIfPossible($possibleMoves, $pawn, 11);
            return $possibleMoves;
        }

        return [];
    }

    /**
     * @param Move[] $possibleMoves
     */
    private function addMoveIfPossible(array &$possibleMoves, Pawn $pawn, int $numSteps): void {
        $destination = BoardLocation::fromPawnMove($pawn, $numSteps);
        if ($this->pawnCanMoveToLocation($pawn, $destination, $numSteps))
            $possibleMoves[] = Move::create($pawn, $destination, $numSteps);
    }

    private function pawnCanMoveToLocation(Pawn $pawn, ?BoardLocation $location): bool {
        if (is_null($location))
            return false;

        if ($location->section === BoardSection::start)
            return false;

        if ($location->section === BoardSection::home)
            return true;

        $sameColorPawnsAtLocation = self::getRowsFromDb("SELECT id from pawns where color = '{$pawn->color->name}' and board_section = '{$location->section->name}' and board_section_color = '{$location->color->name}' and board_section_index = '$location->index'");
        return count($sameColorPawnsAtLocation) == 0;
    }

    private function movePawn(?Move $move): void {
        if (is_null($move))
            return;

        if ($move->isSwap()) {
            $this->swapPawns($move);
            $this->slidePawnIfOnArrow($move);
            return;
        }

        $this->DbQuery("UPDATE pawns set board_section = '{$move->destination->section->name}', board_section_color = '{$move->destination->color->name}', board_section_index = nullif('{$move->destination->index}','') where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
        $this->triggerClientAnimationForMove($move);


        if ($move->destination->section === BoardSection::home) {
            $playerId = intval($this->getActivePlayerId());
            $score = self::getFirstRowFromDb("SELECT count(id) as count from pawns where player = '{$playerId}' and board_section = 'home'")->count;
            $this->DbQuery("UPDATE player set player_score = $score where player_id='$playerId'");
            $this->notifyAllPlayers(
                'score',
                '',
                [
                    'playerId' => $playerId,
                    'score' => $score,
                ]
            );
        }

        $this->slidePawnIfOnArrow($move);
    }

    private function slidePawnIfOnArrow(Move $move): void {
        if ($move->destination->section !== BoardSection::margin || $move->destination->color === $move->pawn->color || !in_array($move->destination->index, [1, 9]))
            return;

        $this->incStat(1, 'slidesTraveled');
        $this->incStat(1, 'slidesTraveled', $move->pawn->playerId);

        $distanceOfSlide = $move->destination->index == 1 ? 3 : 4;
        $startingIndex = intval($move->destination->index);
        $slidingPawn = Pawn::create($move->pawn->playerId, $move->pawn->id, $move->pawn->color, BoardLocation::create($move->destination->section->name, $move->destination->color->name, $move->destination->index));
        $bumpedPawns = [];
        for ($i = 0; $i <= $distanceOfSlide; $i++) {
            $bumpedPawn = $this->bumpPawnIfPresent(Move::create($slidingPawn, BoardLocation::fromPawnMove($slidingPawn, $i)), $startingIndex + $distanceOfSlide);
            if (!is_null($bumpedPawn))
                $bumpedPawns[] = [
                    'moveType' => 'jump',
                    'durationSecondsPerSquare' => 0.4,
                    'startMoveAtPercentage' => ($bumpedPawn->location->index - $startingIndex) * 100 / $distanceOfSlide - 35,
                    'move' => Move::create($bumpedPawn, BoardLocation::create('start', $bumpedPawn->color->name, null))
                ];
        }
        $move->destination->index += $distanceOfSlide;
        $this->DbQuery("UPDATE pawns set board_section_index = '{$move->destination->index}' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");

        $this->notifyAllPlayers(
            'message',
            clienttranslate('${player_name} slides his pawn on <span style="font-weight:bold; color:#${colorRgbForDisplay};">${colorNameForDisplay}</span>.'), // BGA replaces 'his' with player's pronouns magically
            [
                'i18n' => ['colorForDisplay'],
                'player_name' => $this->getActivePlayerName(),
                'colorNameForDisplay' => $move->destination->color->name,
                'colorRgbForDisplay' => array_search($move->destination->color->name, self::$COLOR_NAMES),
            ]
        );

        $this->triggerClientAnimationOfPawnMoves(
            '',
            [],
            [
                'moveType' => 'slide',
                'durationSecondsPerSquare' => 0.8,
                'move' => $move
            ],
            ...$bumpedPawns
        );

        foreach ($bumpedPawns as $bumpedPawn)
            $this->notifyAllPlayers(
                'message',
                clienttranslate('${player_name2}\'s pawn was bumped back to Start by ${player_name}.'),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'player_name2' => $this->getPlayerNameById($bumpedPawn['move']->pawn->playerId),
                ],
            );
    }

    private function swapPawns(Move $move): void {
        $indexEquality = is_null($move->destination->index) ? 'is null' : "= '{$move->destination->index}'";
        $otherPawn = self::getFirstRowFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where board_section = '{$move->destination->section->name}' and board_section_color = '{$move->destination->color->name}' and board_section_index $indexEquality");
        if ($move->pawn->location->section === BoardSection::start) {
            $this->DbQuery("UPDATE pawns set board_section = 'start', board_section_color = '$otherPawn->color', board_section_index = null where player = '$otherPawn->player' and id = '$otherPawn->id'");
            $this->DbQuery("UPDATE pawns set board_section = '$otherPawn->boardSection', board_section_color = '$otherPawn->boardSectionColor', board_section_index = '$otherPawn->boardSectionIndex' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
            $this->incStat(1, 'timesBumpedOtherPawns', $move->pawn->playerId);
            $this->incStat(1, 'timesBumped', $otherPawn->player);
            $this->triggerClientAnimationOfSorry($move, Pawn::fromDb($otherPawn));
        } else {
            $this->DbQuery("UPDATE pawns set board_section = '{$move->pawn->location->section->name}', board_section_color = '{$move->pawn->location->color->name}', board_section_index = {$move->pawn->location->index} where player = '$otherPawn->player' and id = '$otherPawn->id'");
            $this->DbQuery("UPDATE pawns set board_section = '$otherPawn->boardSection', board_section_color = '$otherPawn->boardSectionColor', board_section_index = '$otherPawn->boardSectionIndex' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
            $this->triggerClientAnimationOfSwap($move->pawn, Pawn::fromDb($otherPawn));
        }
    }

    private function triggerClientAnimationOfDraw(string $rank, bool $hasMoreToDraw): void {
        $this->notifyAllPlayers('drawCard', '', ['rank' => $rank, 'hasMoreToDraw' => $hasMoreToDraw]);
    }

    private function triggerClientAnimationOfDiscard(): void {
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $this->notifyAllPlayers('discardCard', '', ['rank' => $rank]);
    }

    public function triggerClientAnimationForMove(Move $move): void {
        $message = $move->numberOfSteps > 0
            ? '${player_name} moves a pawn ${numberOfSteps} places.'
            : '${player_name} moves a pawn ${numberOfSteps} places backward.';
        if ($move->pawn->location->section === BoardSection::start)
            $message = '${player_name} moves his pawn out of Start.';
        if ($move->destination->section === BoardSection::home)
            $message = '${player_name} moves a pawn into his Home.'; // BGA replaces 'his' with player's pronouns magically

        $this->notifyAllPlayers(
            'message',
            clienttranslate($message),
            [
                'player_name' => $this->getActivePlayerName(),
                'numberOfSteps' => abs($move->numberOfSteps),
            ]
        );

        $step = $move->numberOfSteps < 0 ? -1 : 1;
        for ($i = 0; $i != $move->numberOfSteps - $step; $i += $step) {
            $incrementalLocation = BoardLocation::fromPawnMove($move->pawn, $step + $i);
            $this->triggerClientAnimationOfPawnMoves('', [], [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.6,
                'move' => Move::create($move->pawn, $incrementalLocation)
            ]);
        }

        $bumpedPawn = $this->bumpPawnIfPresent($move);
        if (is_null($bumpedPawn)) {
            $this->triggerClientAnimationOfPawnMoves('', [], [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.7,
                'move' => $move,
            ]);
            return;
        }

        $this->triggerClientAnimationOfPawnMoves(
            clienttranslate('${player_name2}\'s pawn was bumped back to Start by ${player_name}.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'player_name2' => $this->getPlayerNameById($bumpedPawn->playerId),
            ],
            [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.7,
                'move' => $move
            ],
            [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.4,
                'move' => Move::create($bumpedPawn, BoardLocation::create('start', $bumpedPawn->color->name, null))
            ]
        );
    }

    private function triggerClientAnimationOfPawnMoves(string $message, array $messageArgs, array $move, array ...$otherMoves): void {
        $this->notifyAllPlayers(
            'movePawns',
            $message,
            array_merge(
                $messageArgs,
                [
                    'move' => [
                        'moveType' => $move['moveType'],
                        'durationSeconds' => $move['durationSeconds'] ?? null,
                        'durationSecondsPerSquare' => $move['durationSecondsPerSquare'] ?? 1,
                        'playerId' => $move['move']->pawn->playerId,
                        'pawnId' => $move['move']->pawn->id,
                        'section' => $move['move']->destination->section->name,
                        'color' => $move['move']->destination->color->name,
                        'index' => $move['move']->destination->index,
                    ],
                    'otherMoves' => array_map(fn($otherMove) => [
                        'moveType' => $otherMove['moveType'],
                        'durationSeconds' => $otherMove['durationSeconds'],
                        'durationSecondsPerSquare' => $otherMove['durationSecondsPerSquare'] ?? 1,
                        'startMoveAtPercentage' => $otherMove['startMoveAtPercentage'] ?? 0,
                        'playerId' => $otherMove['move']->pawn->playerId,
                        'pawnId' => $otherMove['move']->pawn->id,
                        'section' => $otherMove['move']->destination->section->name,
                        'color' => $otherMove['move']->destination->color->name,
                        'index' => $otherMove['move']->destination->index,
                    ], $otherMoves)
                ]
            )
        );
    }

    private function triggerClientAnimationOfSorry(Move $move, Pawn $bumpedPawn) {
        $this->triggerClientAnimationOfPawnMoves(
            clienttranslate('SORRY! ${player_name} moves out a pawn from Start and bumps ${player_name2}\'s pawn back to Start.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'player_name2' => $this->getPlayerNameById($bumpedPawn->playerId),
            ],
            [
                'moveType' => 'jump',
                'durationSeconds' => 2.5,
                'move' => $move
            ],
            [
                'moveType' => 'jump',
                'durationSeconds' => 2,
                'startMoveAtPercentage' => 70,
                'move' => Move::create($bumpedPawn, BoardLocation::create('start', $bumpedPawn->color->name, null))
            ]
        );
    }

    private function triggerClientAnimationOfSwap(Pawn $pawn, Pawn $otherPawn) {
        $this->triggerClientAnimationOfPawnMoves(
            clienttranslate('${player_name} swaps pawn places with ${player_name2}.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'player_name2' => $this->getPlayerNameById($otherPawn->playerId),
            ],
            [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.5,
                'move' => Move::create($pawn, BoardLocation::create($otherPawn->location->section->name, $otherPawn->location->color->name, $otherPawn->location->index))
            ],
            [
                'moveType' => 'jump',
                'durationSecondsPerSquare' => 0.6,
                'move' => Move::create($otherPawn, BoardLocation::create($pawn->location->section->name, $pawn->location->color->name, $pawn->location->index))
            ]
        );
    }

    private function bumpPawnIfPresent(Move $move, int $slidingTo = null): ?Pawn {
        $indexEquality = is_null($move->destination->index) ? 'is null' : "= '{$move->destination->index}'";
        $bumpedPawn = self::getFirstRowFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where (player != '{$move->pawn->playerId}' or id != '{$move->pawn->id}') and board_section != 'home' and board_section = '{$move->destination->section->name}' and board_section_color = '{$move->destination->color->name}' and board_section_index $indexEquality");
        if (!isset($bumpedPawn->player))
            return null;

        if ($move->pawn->playerId == $bumpedPawn->player)
            $this->incStat(1, 'timesBumpedOwnPawns', $move->pawn->playerId);
        else
            $this->incStat(1, 'timesBumpedOtherPawns', $move->pawn->playerId);
        $this->incStat(1, 'timesBumped', $bumpedPawn->player);

        if ($bumpedPawn->player == $move->pawn->playerId) {
            $finalMove = self::getFirstRowFromDb("SELECT id, destination_section, destination_section_color, destination_section_index from possible_moves where pawn_id = '{$bumpedPawn->id}' and selected_as = 'final_move'");
            if (isset($finalMove->id)) {
                // the bumped pawn is going to move
                if (is_null($slidingTo))
                    return null; // it'll move out of the way

                // first pawn is sliding into second pawn
                if ($finalMove->destinationSection != $move->destination->section->name || $finalMove->destinationSectionColor != $move->destination->color->name || $finalMove->destinationSectionIndex > $slidingTo)
                    return null;

                // final pawn is getting bumped; don't move it later
                $this->DbQuery("UPDATE possible_moves set selected_as = 'blocked' where selected_as = 'final_move'");
            }
        }

        $this->DbQuery("UPDATE pawns set board_section = 'start', board_section_color = '$bumpedPawn->color', board_section_index = null where player = '$bumpedPawn->player' and id = '$bumpedPawn->id'");
        return Pawn::fromDb($bumpedPawn);
    }

    private function getSelectedMove(): ?array {
        $selectedPawn = self::getFirstRowFromDb("SELECT pawn_id, destination_section, destination_section_color, destination_section_index from possible_moves where selected_as = 'first_move'");
        if (!isset($selectedPawn->pawnId))
            return null;

        return [
            'id' => $selectedPawn->pawnId,
            'section' => $selectedPawn->destinationSection,
            'color' => $selectedPawn->destinationSectionColor,
            'index' => $selectedPawn->destinationSectionIndex,
        ];
    }

    /**
     * @param stdClass[] $possibleMoves
     */
    private function groupPossibleMoves(array $possibleMoves): array {
        $groupedPossibleMoves = [];
        foreach ($possibleMoves as $move) {
            if (!array_key_exists($move->pawnId, $groupedPossibleMoves))
                $groupedPossibleMoves[$move->pawnId] = [];

            $groupedPossibleMoves[$move->pawnId][] = [
                'section' => $move->destinationSection,
                'color' => $move->destinationSectionColor,
                'index' => $move->destinationSectionIndex,
            ];
        }
        return $groupedPossibleMoves;
    }

    private function skipOrThrow() {
        $player = $this->getActivePlayerName();
        $canSkip = self::getFirstRowFromDb("SELECT count(optional) as numberRequired from possible_moves where optional = false")->numberRequired == 0;
        if (!$canSkip)
            throw new BgaUserException(sprintf($this->_('%s has valid moves and can not skip right now.'), $player));

        $this->notifyAllPlayers('message', clienttranslate('${player_name} skips their turn.'), ['player_name' => $player]);
        $this->triggerClientAnimationOfDiscard();
        $this->gamestate->nextState('skipTurn');
    }

    private function calculateMoveStats(string $rank, ?Move $firstMove, ?Move $finalMove) {
        $playerId = intval($this->getActivePlayerId());

        $move = $finalMove ?? $firstMove;
        if (is_null($move))
            throw new BgaUserException('no move');

        $this->incStat(1, 'numberOfMoves', $playerId);

        if ($rank == 'sorry') {
            $this->incStat(1, 'numberOfSorrysUsed');
            $this->incStat(1, 'numberOfSorrysUsed', $playerId);
            return;
        }

        $numberOfSquaresMoved = (is_null($finalMove) ? 0 : abs($finalMove?->numberOfSteps)) + (is_null($firstMove) ? 0 : abs($firstMove?->numberOfSteps));
        $this->incStat($numberOfSquaresMoved, 'totalSquaresMoved');
        $this->incStat($numberOfSquaresMoved, 'totalSquaresMoved', $playerId);

        if ($move->numberOfSteps < 0 && $move->pawn->location->section === BoardSection::safety && $move->destination->section !== BoardSection::safety)
            $this->incStat(1, 'pawnsBackedOutOfSafetyZone', $playerId);
    }

    private function calculateTurnStats(): void {
        $playerId = intval($this->getActivePlayerId());
        $this->incStat(1, 'numberOfTurns');
        $this->incStat(1, 'numberOfTurns', $playerId);

        $pawnsAtHome = self::getFirstRowFromDb("SELECT sum(board_section = 'home')/count(id)*100 as percentage from pawns")->percentage;
        $pawnsAtStart = self::getFirstRowFromDb("SELECT sum(board_section = 'start')/count(id)*100 as percentage from pawns")->percentage;
        $this->setStat($pawnsAtHome, 'percentOfPawnsAtHome');
        $this->setStat($pawnsAtStart, 'percentOfPawnsAtStart');

        $playerPawnsAtHome = self::getFirstRowFromDb("SELECT sum(board_section = 'home') as count from pawns where player = '$playerId'")->count;
        $playerPawnsAtStart = self::getFirstRowFromDb("SELECT sum(board_section = 'start') as count from pawns where player = '$playerId'")->count;
        $this->setStat($playerPawnsAtHome, 'pawnsAtHome', $playerId);
        $this->setStat($playerPawnsAtStart, 'pawnsAtStart', $playerId);
    }

    private function getWinner(): int {
        $players = self::getRowsFromDb("SELECT player_id as id from player");
        foreach ($players as $player) {
            $pawns = self::getRowsFromDb("SELECT board_section, board_section_color from pawns where player = '{$player->id}' and board_section = 'home'");
            if (count($pawns) == 4)
                return intval($player->id);
        }
        return -1;
    }
}
