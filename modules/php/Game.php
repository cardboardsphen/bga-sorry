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

use Bga\Games\Sorry\{Pawn, Move};
use Bga\Games\Sorry\Board\{BoardLocation, BoardSection, BoardColor};
use BgaUserException;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once('databasehelpers.inc.php');

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
        $playerId = (int)$this->getActivePlayerId();
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
        return [
            'color' => $playerColor,
        ];
    }

    public function argSelectPawn(): array {
        $playerId = (int)$this->getActivePlayerId();
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $possibleMoves = $this->getAllPossibleMoves();
        $canSkip = true;
        return [
            'player' => $playerId,
            'color' => $playerColor,
            'cardRank' => $rank,
            'cardDescription' => CARD_DESCRIPTIONS[$rank],
            'possibleMoves' => $possibleMoves,
            'canSkip' => $canSkip,
        ];
    }

    public function argSelectSquare(): array {
        return [];
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
        // TODO: compute and return the game progression

        return bga_rand(0, 100);
    }

    /**
     * Player actions
     */
    public function actDrawCard(): void {
        $topCards = self::getRowsFromDb("SELECT id, rank from cards where pile = 'draw' order by position limit 2");
        if (count($topCards) == 0) {
            $this->shuffleDeck();
            $this->notifyAllPlayers('shuffleDeck', clienttranslate('${player_name} shuffles the deck.'), ['player_name' => self::getActivePlayerName()]);
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
            clienttranslate('${player_name} draws a ${rank} card'),
            [
                'player_name' => self::getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->notifyAllPlayers('simplePause', '', ['time' => 1500]);

        $possibleMoves = $this->determineAllPossibleMoves();

        if ($possibleMoves) {
            $this->gamestate->nextState("selectPawn");
            return;
        }

        $this->notifyAllPlayers(
            'noValidMoves',
            clienttranslate('${player_name} has no valid moves for ${rank} card'),
            [
                'player_name' => self::getActivePlayerName(),
                'rank' => $rank,
            ]
        );
        $this->discardCard();
        $this->gamestate->nextState("nextPlayer");
    }

    public function actSelectPawn(int $pawnId) {
        $player = $this->getActivePlayerName();
        $rank = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $possibleMoves = $this->getAllPossibleMoves();

        if ($pawnId < 0) {
            $noRequiredMoves = $rank == '11';
            foreach ($possibleMoves as $moves) {
                foreach ($moves as $move) {
                    $noRequiredMoves &= false;
                }
            }
            if (!$noRequiredMoves)
                throw new BgaUserException("$player has valid moves and can not skip right now.");
        }

        if (!array_key_exists($pawnId, $possibleMoves) || count($possibleMoves[$pawnId]) == 0)
            throw new BgaUserException("$player can not move that pawn right now.");

        if (count($possibleMoves[$pawnId]) == 1) {
            $this->movePawn($pawnId, $possibleMoves[$pawnId][0]);
            $this->discardCard();
            $this->gamestate->nextState('movePawn');
            return;
        }

        if ($rank == '7') {
            $this->notifyAllPlayers('sevenProblem', 'SEVEN PROBLEM', []);
            $this->gamestate->nextState('nextPlayer');
        }

        $this->discardCard();
        if ($rank == '2') {
            $this->gamestate->nextState('drawAgain');
            return;
        }

        $this->notifyAllPlayers('stuck here', 'STUCK HERE', []);
        //$this->gamestate->nextState('nextPlayer');
    }

    public function actSelectSquare(string $move): void {
        $this->gamestate->nextState('nextPlayer');
    }

    /**
     * Game state actions
     */
    public function stNextPlayer(): void {
        $winner = $this->getWinner();
        if ($winner >= 0) {
            $this->DbQuery("UPDATE player set player_score = player_score+1 where player_id='$winner'");
            $this->gamestate->nextState('endGame');
            return;
        }

        $playerId = (int)$this->getActivePlayerId();
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
        $currentPlayerId = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result['players'] = $this->getCollectionFromDb(
            "SELECT player_id as id, player_score as score, player_color as color, player_color_name as color_name FROM player"
        );

        // Get pawn locations
        $result['pawns'] = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns");

        $result['nextCard'] = self::getFirstRowFromDb("SELECT min(id) as id from cards where pile = 'draw'")->id;

        $topDiscards = self::getRowsFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 2");
        if (in_array($this->gamestate->state()['name'], ['selectPawn', 'selectSquare']))
            $result['revealCard'] = count($topDiscards) > 0 ? $topDiscards[0]->rank : null;

        $result['lastCard'] = count($topDiscards) > 1
            ? (property_exists($topDiscards[1], "rank") ? $topDiscards[1]->rank : null)
            : null;

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
        $sql .= ") o on c.id = o.id set c.position = o.position";
        $this->DbQuery($sql);
    }

    private function discardCard(): void {
        $rank = self::getFirstRowFromDb("SELECT id, rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $this->notifyAllPlayers('discardCard', '', ['rank' => $rank]);
        $this->notifyAllPlayers('simplePause', '', ['time' => 3000]);
    }

    private function determineAllPossibleMoves(): void {
        $this->DbQuery("DELETE from possible_moves");

        $playerId = (int)$this->getActivePlayerId();
        $card = self::getFirstRowFromDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $pawns = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where player = '$playerId'");

        $sql = "INSERT into possible_moves (player, pawn_id, destination_section, destination_section_color, destination_section_index, optional, number_of_steps) values ";
        $sqlRows = [];
        foreach ($pawns as $pawn) {
            $moves = $this->getPossibleMoves(Pawn::fromDb($pawn), $card);
            foreach ($moves as $move) {
                $sqlRows[] = "('$playerId', '$pawn->id', '{$move->destination->section}', '{$move->destination->color}', '{$move->destination->index}', '$move->isOptional', '$move->numberOfSteps')";
            }
        }
        $this->DbQuery($sql . implode(',', $sqlRows));

        if ($card == 7) {
            $pawns = $this->getRowsFromDb();
            $possibleMoves = $this->getRowsFromDb("SELECT player, pawn_id, number_of_steps from possible_moves where player = '$playerId'");
            $this->dump('$possibleMoves', $possibleMoves);
            foreach ($possibleMoves as $pawnId => $moves) {
                foreach (array_keys($moves) as $numSteps) {
                    $found_7sAddendPair = false;
                    foreach ($possibleMoves as $otherPawnId => $otherMoves) {
                        if ($otherPawnId == $pawnId)
                            continue;

                        foreach (array_keys($otherMoves) as $numOtherSteps) {
                            if ($numSteps + $numOtherSteps == 7)
                                $found_7sAddendPair = true;
                        }
                    }

                    if (!$found_7sAddendPair)
                        unset($moves[$numSteps]);
                }
            }
        }
    }

    /**
     * @return Move[]
     */
    private function getPossibleMoves(Pawn $pawn, string $card): array {
        $cardVal = (int)$card;

        if ($pawn->location->section === BoardSection::home)
            return [];

        if ($pawn->location->section === BoardSection::start) {
            $destination = BoardLocation::fromPawnMove($pawn, 1);
            if (in_array($cardVal, [1, 2]) && $this->pawnCanMoveToLocation($pawn, $destination))
                return Move::create($pawn, $destination, 1);

            if ($card == 'sorry') {
                $otherPlayerPawnsOnMargin = self::getRowsFromDb("SELECT board_section, board_section_color, board_section_index from pawns where player != {$pawn->playerId} and board_section = 'margin'");
                return array_map(fn($otherPawn) => Move::create($pawn, $otherPawn->location), $otherPlayerPawnsOnMargin);
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
                $swapMoves = array_map(fn($otherPawn) => Move::create($pawn, $otherPawn->location, isOptional: true), $otherPlayerPawnsOnMargin);
                $possibleMoves = array_merge($possibleMoves, $swapMoves);
            }

            $this->addMoveIfPossible($possibleMoves, $pawn, 11);
            return $possibleMoves;
        }

        return [];
    }

    private function addMoveIfPossible(array &$possibleMoves, Pawn $pawn, int $numSteps): void {
        $destination = BoardLocation::fromPawnMove($pawn, $numSteps);
        if ($this->pawnCanMoveToLocation($pawn, $destination, $numSteps))
            $possibleMoves[] = $destination;
    }

    private function pawnCanMoveToLocation(Pawn $pawn, BoardLocation $location): bool {
        if (is_null($location))
            return false;

        if ($location->section === BoardSection::start)
            return false;

        if ($location->section === BoardSection::home)
            return true;

        $sameColorPawnsAtLocation = self::getRowsFromDb("SELECT id from pawns where color = '$pawn->color' and board_section = '$location->section' and board_section_color = '$location->color' and board_section_index = '$location->index'");
        return count($sameColorPawnsAtLocation) == 0;
    }

    private function movePawn(int $pawnId, array $location): void {
        [$section, $sectionColor, $sectionIndex] = $location;
    }

    private function getWinner(): int {
        $players = self::getRowsFromDb("SELECT player_id as id from player");
        foreach ($players as $player) {
            $pawns = self::getRowsFromDb("SELECT board_section, board_section_color from pawns where player = '{$player->id}' and board_section = 'home'");
            if (count($pawns) == 4)
                return (int)$player->id;
        }
        return -1;
    }
}
