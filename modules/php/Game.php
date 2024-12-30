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

use Bga\Games\Sorry\Helpers\DatabaseHelpers;
use Bga\Games\Sorry\Models\{Pawn, Move};
use Bga\Games\Sorry\Models\Board\{BoardLocation, BoardSection, BoardColor};
use BgaUserException;
use stdClass;

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
    public function argDrawCard(): array {
        $playerId = intval($this->getActivePlayerId());
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;

        $pawns = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns");

        $cards = [];
        $cards['nextCard'] = self::getFirstRowFromDb("SELECT min(id) as id from cards where pile = 'draw'")->id;

        $topDiscards = self::getRowsFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 2");
        $cards['lastCard'] = count($topDiscards) > 0 ? $topDiscards[0]->rank : null;

        return [
            'color' => $playerColor,
            'pawns' => $pawns,
            'cards' => $cards,
        ];
    }

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
            'selectedPawn' => $this->getSelectedPawn(),
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
        $canSkip = self::getFirstRowFromDb("SELECT count(optional) as numberRequired from possible_moves where pawn_id = '{$possibleMoves[0]->pawnId}' and optional = false")->numberRequired == 0;
        return [
            'player' => $playerId,
            'color' => $playerColor,
            'cardRank' => $rank,
            'cardDescription' => CARD_DESCRIPTIONS[$rank],
            'selectPawn' => $this->getSelectedPawn(),
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
            $this->notifyAllPlayers('simplePause', '', ['time' => 3500]);

            $topCards = self::getRowsFromDb("SELECT id, rank from cards where pile = 'draw' order by position limit 2");
        }

        $thisCard = $topCards[0];
        $this->DbQuery("UPDATE cards set pile = 'discard' where id = $thisCard->id");

        $rank = $thisCard->rank;
        $this->notifyAllPlayers('drawCard', '', ['rank' => $rank, 'hasMoreToDraw' => count($topCards) > 1]);
        $this->notifyAllPlayers('simplePause', '', ['time' => 2500]);

        $this->notifyAllPlayers(
            'drawCardMessage',
            clienttranslate('${player_name} draws a ${rank} card.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->notifyAllPlayers('simplePause', '', ['time' => 1500]);

        $this->determineAllPossibleMoves();
        $possibleMoveCount = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves")->count;

        if ($possibleMoveCount > 0) {
            $this->gamestate->nextState("selectPawn");
            return;
        }

        $this->notifyAllPlayers(
            'noValidMoves',
            clienttranslate('${player_name} has no valid moves for ${rank} card.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->triggerClientAnimationOfDiscard();
        $this->gamestate->nextState("nextPlayer");
    }

    public function actSelectPawn(int $pawnId) {
        $player = $this->getActivePlayerName();
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $possibleMoves = self::getRowsFromDb("SELECT pawn_id, number_of_steps, selected_as from possible_moves where pawn_id = '$pawnId' and selected_as = 'none'");

        if (count($possibleMoves) == 0)
            throw new BgaUserException(sprintf($this->_('%s can not move that pawn right now.'), $player));

        if ($pawnId < 0) {
            $canSkip = self::getFirstRowFromDb("SELECT count(optional) as numberRequired from possible_moves where pawn_id = '$pawnId' and optional = false")->numberRequired == 0;
            if (!$canSkip)
                throw new BgaUserException(sprintf($this->_('%s has valid moves and can not skip right now.'), $player));

            $this->triggerClientAnimationOfDiscard();
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        if (count($possibleMoves) == 1) {
            $hasPreviousSelection = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves where pawn_id != '$pawnId' and selected_as = 'first_move'")->count  == 1;

            if ($rank != 7 || $hasPreviousSelection) {
                $this->DbQuery("UPDATE possible_moves set selected_as = 'final_move' where pawn_id = '$pawnId'");
                $this->gamestate->nextState('movePawn');
                return;
            }

            // we're making the first selection for a 7 card now
            $this->DbQuery("UPDATE possible_moves set selected_as = 'first_move' where pawn_id = '$pawnId'");
            $this->gamestate->nextState('selectPawn');
            return;
        }

        $this->DbQuery("UPDATE possible_moves set selected_as = 'pawn' where pawn_id = '$pawnId'");
        $this->gamestate->nextState('selectSquare');
    }

    public function actSelectSquare(string $squareId): void {
        $player = $this->getActivePlayerName();
        [$section, $color, $index] = explode('-', $squareId);
        $hasPreviousSelection = self::getFirstRowFromDb("SELECT count(id) as count from possible_moves where selected_as = 'first_move'")->count == 1;
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;

        $moveStage = ($rank != 7 || $hasPreviousSelection) ? 'final_move' : 'first_move';
        $this->DbQuery("UPDATE possible_moves set selected_as = '$moveStage' where selected_as = 'pawn' and destination_section = '$section' and destination_section_color = '$color' and destination_section_index = nullif('$index','')");
        $this->DbQuery("UPDATE possible_moves set selected_as = 'none' where selected_as = 'pawn'");

        if ($rank != 7 || $hasPreviousSelection)
            $this->gamestate->nextState('movePawn');
        else
            $this->gamestate->nextState('selectPawn');
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

        if ($rank == '2') {
            $this->notifyAllPlayers('drawAgain', clienttranslate('${player_name} gets to draw again.'), ['player_name' => $this->getActivePlayerName()]);
            $this->gamestate->nextState('drawAgain');
            return;
        }

        $this->triggerClientAnimationOfDiscard();
        $this->gamestate->nextState('nextPlayer');
    }

    public function stNextPlayer(): void {
        $winner = $this->getWinner();
        if ($winner >= 0) {
            $this->DbQuery("UPDATE player set player_score = player_score+1 where player_id='$winner'");
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

        // Dummy content.
        // $this->initStat("table", "table_teststat1", 0);
        // $this->initStat("player", "player_teststat1", 0);

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

        $this->notifyAllPlayers('removeSevens', 'removing all the sevens from the deck for testing.', []);
        $this->DbQuery("DELETE from cards where rank = '7'");

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

        if ($card == 7) {
            $this->notifyAllPlayers('sevenIssue', 'seven should add cases even if a pawn is there because that pawn might move.', []);
            $pawns = $this->getRowsFromDb("SELECT distinct pawn_id from possible_moves");
            foreach ($pawns as $pawn) {
                $possibleMoves = $this->getRowsFromDb("SELECT id, number_of_steps from possible_moves where pawn_id = '$pawn->pawnId' and number_of_steps < 7");
                $otherPawnsPossibleMoves = $this->getRowsFromDb("SELECT number_of_steps from possible_moves where pawn_id != '$pawn->pawnId' and number_of_steps < 7");
                foreach ($possibleMoves as $move) {
                    $found_7sAddendPair = false;
                    foreach ($otherPawnsPossibleMoves as $otherMove) {
                        if ($move->numberOfSteps + $otherMove->numberOfSteps == 7)
                            $found_7sAddendPair = true;
                    }

                    if (!$found_7sAddendPair)
                        $this->DbQuery("DELETE from possible_moves where id = '$move->id'");
                }
            }
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
            for ($i = 1; $i <= 7; $i++)
                $this->addMoveIfPossible($possibleMoves, $pawn, $i);
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
        $this->triggerClientAnimationOfMoves($move);
        $this->bumpPawnIfPresent($move);

        $this->slidePawnIfOnArrow($move);
    }

    private function slidePawnIfOnArrow(Move $move): void {
        if ($move->destination->section === BoardSection::margin && $move->destination->color !== $move->pawn->color && in_array($move->destination->index, [1, 9])) {
            $distanceOfSlide = $move->destination->index == 1 ? 3 : 4;
            $newIndex = $move->destination->index + $distanceOfSlide;
            $this->DbQuery("UPDATE pawns set board_section_index = '$newIndex' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
            $this->triggerClientAnimationOfSlide($move, $distanceOfSlide);
            for ($i = 0; $i < $distanceOfSlide; $i++) {
                $move->destination->index++;
                $this->bumpPawnIfPresent($move);
            }
        }
    }

    private function swapPawns(Move $move): void {
        $bumppedPawn = self::getFirstRowFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where board_section = '{$move->destination->section->name}' and board_section_color = '{$move->destination->color->name}' and board_section_index = nullif('{$move->destination->index}','')");
        if ($move->pawn->location->section === BoardSection::start) {
            $this->DbQuery("UPDATE pawns set board_section = 'start', board_section_color = '$bumppedPawn->color', board_section_index = null where player = '$bumppedPawn->player' and id = '$bumppedPawn->id'");
            $this->DbQuery("UPDATE pawns set board_section = '$bumppedPawn->boardSection', board_section_color = '$bumppedPawn->boardSectionColor', board_section_index = '$bumppedPawn->boardSectionIndex' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
            $this->notifyAllPlayers(
                'sorry',
                clienttranslate('SORRY! ${player_name} moves out a pawn from Start and bumps ${player_name2}\'s pawn back to Start.'),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'player_name2' => $this->getPlayerNameById($bumppedPawn->player),

                    'playerId' => $move->pawn->playerId,
                    'pawnId' => $move->pawn->id,
                    'destinationSection' => $move->destination->section->name,
                    'destinationColor' => $move->destination->color->name,
                    'destinationIndex' => $move->destination->index,
                    'bumppedPlayerId' => $bumppedPawn->player,
                    'bumppedPawnId' => $bumppedPawn->id,
                ]
            );
            $this->notifyAllPlayers('simplePause', '', ['time' => 5000]);
        } else {
            $this->DbQuery("UPDATE pawns set board_section = '{$move->pawn->location->section->name}', board_section_color = '{$move->pawn->location->color->name}', board_section_index = {$move->pawn->location->index} where player = '$bumppedPawn->player' and id = '$bumppedPawn->id'");
            $this->DbQuery("UPDATE pawns set board_section = '$bumppedPawn->boardSection', board_section_color = '$bumppedPawn->boardSectionColor', board_section_index = '$bumppedPawn->boardSectionIndex' where player = '{$move->pawn->playerId}' and id = '{$move->pawn->id}'");
            $this->notifyAllPlayers(
                'swapPawns',
                clienttranslate('${player_name} swaps pawn places with ${player_name2}.'),
                [
                    'player_name' => $this->getActivePlayerName(),
                    'player_name2' => $this->getPlayerNameById($bumppedPawn->player),

                    'playerId' => $move->pawn->playerId,
                    'pawnId' => $move->pawn->id,
                    'destinationSection' => $move->destination->section->name,
                    'destinationColor' => $move->destination->color->name,
                    'destinationIndex' => $move->destination->index,
                    'bumppedPlayerId' => $bumppedPawn->player,
                    'bumppedPawnId' => $bumppedPawn->id,
                ]
            );
            $this->notifyAllPlayers('simplePause', '', ['time' => 5000]);
        }
    }

    private function triggerClientAnimationOfDiscard(): void {
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $this->notifyAllPlayers('discardCard', '', ['rank' => $rank]);
        $this->notifyAllPlayers('simplePause', '', ['time' => 3000]);
    }

    public function triggerClientAnimationOfMoves(Move $move): void {
        $message = $move->numberOfSteps > 0
            ? '${player_name} moves a pawn ${numberOfSteps} places.'
            : '${player_name} moves a pawn ${numberOfSteps} places backward.';
        if ($move->pawn->location->section === BoardSection::start)
            $message = '${player_name} moves a pawn out of Start.';
        if ($move->destination->section === BoardSection::home)
            $message = '${player_name} moves a pawn into his Home.'; // BGA replaces 'his' with player's pronouns magically

        $this->notifyAllPlayers(
            'movePawnMessage',
            clienttranslate($message),
            [
                'player_name' => $this->getActivePlayerName(),
                'numberOfSteps' => abs($move->numberOfSteps),
            ]
        );

        $step = $move->numberOfSteps < 0 ? -1 : 1;
        for ($i = 0; $i != $move->numberOfSteps; $i += $step) {
            $incrementalLocation = BoardLocation::fromPawnMove($move->pawn, 1 + $i);
            $this->notifyAllPlayers(
                'stepPawn',
                '',
                [
                    'playerId' => $move->pawn->playerId,
                    'pawnId' => $move->pawn->id,
                    'destinationSection' => $incrementalLocation->section->name,
                    'destinationColor' => $incrementalLocation->color->name,
                    'destinationIndex' => $incrementalLocation->index,
                ]
            );
            $this->notifyAllPlayers('simplePause', '', ['time' => 1000]);
        }
    }

    private function triggerClientAnimationOfSlide(Move $move, int $distanceOfSlide): void {
        $this->notifyAllPlayers(
            'slidePawn',
            clienttranslate('${player_name} slides his pawn on <span style="font-weight:bold; color:${color};">${colorForDisplay}</span>.'), // BGA replaces 'his' with player's pronouns magically
            [
                'i18n' => ['colorForDisplay'],
                'player_name' => $this->getActivePlayerName(),
                'colorForDisplay' => $move->destination->color->name,

                'playerId' => $move->pawn->playerId,
                'pawnId' => $move->pawn->id,
                'section' => $move->destination->section->name,
                'color' => $move->destination->color->name,
                'index' => $move->destination->index + $distanceOfSlide,
            ]
        );
        $this->notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    private function bumpPawnIfPresent(Move $move): void {
        $bumppedPawn = self::getFirstRowFromDb("SELECT player, id, color from pawns where (player != '{$move->pawn->playerId}' or id != '{$move->pawn->id}') and board_section = '{$move->destination->section->name}' and board_section_color = '{$move->destination->color->name}' and board_section_index = nullif('{$move->destination->index}','')");
        if (!isset($bumppedPawn->player))
            return;

        $this->DbQuery("UPDATE pawns set board_section = 'start', board_section_color = '$bumppedPawn->color', board_section_index = null where player = '$bumppedPawn->player' and id = '$bumppedPawn->id'");

        $this->notifyAllPlayers(
            'bumpPawn',
            clienttranslate('${player_name2}\'s pawn was bumpped back to Start by ${player_name}.'),
            [
                'player_name' => $this->getActivePlayerName(),
                'player_name2' => $this->getPlayerNameById($bumppedPawn->player),

                'bumppedPlayerId' => $bumppedPawn->player,
                'bumppedPawnId' => $bumppedPawn->id,
                'section' => $move->destination->section->name,
                'color' => $move->destination->color->name,
                'index' => $move->destination->index,
            ]
        );
        $this->notifyAllPlayers('simplePause', '', ['time' => 2500]);
    }

    private function getSelectedPawn(): ?array {
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
