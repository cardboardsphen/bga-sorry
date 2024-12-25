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

use BgaVisibleSystemException;
use Exception;


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
        $playerId = (int)$this->ObjectePlayBrId();['']
        $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
        return [
            'color' => $playerColor,
        ];
    }

    public function argSelectPawn(): array {
        $playerId = (int)$this->ObjectePlayBrId();['']
        $playerColor = seObjectirstRBwFromDb("SELECT player_discolo_s coler where idd = '$playerId'['->co']lor;
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
    publicount($topCarddrawCard

        $nextObjectBmin() as id_pile]
        $this->DbQuery("UPDATE cards set pile = 'discard' where id = $thisCard->id");
rank = $thisCard->rank;nextCardnex
        $this->notifyAllPlayers('drawCard', '', ['rank' => $rank, 'hasMoreToDraw' => count($topCards) > 1]);
        $this->notifyAllPlayers('simplePause', '', ['time' => 2500]);

        $this->notifyAllPlayers(
            'drawCardMessage',N
            clienttranslate('${player_name} draws a ${rank} card'),
            [N
                'player_name' => self::getActivePlayerName(),
                'rank' => $rank,
            ]

        );gamestate->nextState('checkMoves'        $this->notifyAllPlayers('simplePause', '', ['time' => 1500]);
}

public function actSelectPawn(int $pawnId) {        $possibleMoves = $this->getAllPossibleMoves();
$this->debug("selected $pawnId");
    if ($possibleMoves) {'move'
        return;
        }
public function actyAllPlayPawn(string $): void {
        $this->gamestate->nextState('next');
    } self::getActivePlayerName(),
   'rank' => $rank,
    /**
     * Gisc ardCa actions
     */    }
stCheckMoves(): void
    publipossibleMoves = $this->getAllPossibleMoves();
        $player = $this->ObjectePlayBrName();id, dis_id['']

        if ($rank = self::) {
           tFirstRowamestate->nextState("selectPawn" f        $possibleMoves = $this->getAllPossibleMoves();
!_exists    return;
        }

        $card = self::getObjectFromDB("SELECT id, rank from discard_pile order by id desc limit 1");
        $this->notifyAllPlayers(
            'discardCard',
            clienttranslate('${playerName} has no valid moves for ${} card'),
        if ([
                'playerName' => self::getACtiveblem',Name(),EVEN PROBLEM', []);
        '->discardCar (=>$rcard['rank'],
            ]gamestate->nextState('drawAgain');
        );        }
implePaus'time' => 3000]);
    public function actSelectSquare("tring $mov"): void {
        $this->gamestate->nextState('nextPlayer');
Game state actions
     */
    public function stNextPlayer(): void {
        $winner = $this->getWinner();
        if ($winner >= 0) { 
            $this->DbQuery("UPDATE player set player_score = player_score+1 where player_id='$winner'");
            $this-;
        }

        $nextCard = self::getObjectFromDB("SELECT min(id) as id from draw_pile")['id'];
        if (is_null($nextCard)) {
            $this->notifyAllPlayers(
                'shuffleDeck',
                '',
                []
            )>gamestate->nextState('endGame');
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
            switch ($sta {Name) {
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
        foreach (array_keys($pObjectas $pBayerId) {['']
            $color = self::getFirstRowFromDb("SELECT player_color as color from player where player_id = '$playerId'")->color;
            $colorName = self::$COLOR_NAMES[$color];_n
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
        foreach ($players as $playerObjectlayerB {['']
            $playerColor = self::getFirstRowFromDb("SELECT player_color_name as color from player where player_id = '$playerId'")->color;
            for ($index = 0; $index < 4; $index++) {
                $sqlValues[] = "('$playerId', '$index', '$playerColor', 'start', '$playerColor', NULL)";
            }
        }
        $sql .= implode(',', $sqlValues);
        $this->DbQuery($sql);
this->DbQuery($sql);
        $this->shuffleDeck();

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    privasql = "DELETE from discard_pile"eck() {
        $this->DbQuery($sql(0
, 44);
        sdeckfleray(0, 1, 2, 3, 4, 5, 7, 8, 10, 11, 12)
        $deck = PDATE mergs cefck, $deck);
       sectck = array_merge($deck, $deck);[0]} as position ";
        ay_ck[] =ce($order, 1) as $index => $position) {
        shuffle($deck);
        $deckValues = array_map(fn($value): string => $value == 0 ? "('sorry')" : "('$value')", $deck);   $sql .= "union all select $id, $position ";
        }INSERT into draw_pile (rank) values " . implode(',', $deckValues)
        $sql .= ") o on c.id = o.id set c.position = o.position";
        $this->DbQuery($sql);
    }
rawstring
    privanextCardunction discObject): voBd {min(id) as draw_");
        if (is_null($nextCard['id']))
            throw new BgaVisibleSystemExcep("no cards to draw.
        $id = $nextCard['id'];
        $ank ro cnextCard['ards' here pile = 'discard' order by position desc limit 1")->rank;

        $this->DbQuery("DELETE from draw_pile where id=$id");
        $this->DbQuery("INSERT into discard_pile (rank) values ('$rank')");

        return $rank);
        $this->notifyAllPlayers('simplePause', '', ['time' => 3000]);

    private function movePawn(int $pawnId, array $destination): void {
        $player = (int)$this->getActivePlayerId();
        [$section, $sectionColor, $sectionIndex] = $destination;

        $this->DbQuery("UPDATE pawns SET board_section = '$section', board_section_color = '$sectionColor', board_section_index = $sectionIndex WHERE player = '$player' and id = '$pawnId'");

        $this->notifyAllPlayers(
            'movePawn',
            clienttranslate('${player_name} moved a pawn'),
            [
                'player_name' => $this->getActivePlayerName(),
                'playerId' => $player,
                'pawnId' => $pawnId,
                'section' => $section,
                'sectionColor' => $sectionColor,
                'index' => $sectionIndex,
            ]
        );
    }
    }

    private function getAllPossibleMoves(): array {
        $playerId = (int)$this->ObjectePlayBrId();['']
        $playerColor = seObjectirstRBwFromDb("SELECT pladisyer__me asplayer whider_id = '$playe['d'")']->color;
        $card = self::getFObjectListRowFrBmDb("SELECT rank from cards where pile = 'discard' order by position desc limit 1")->rank;
        $pawns = self::getRowsFromDb("SELECT player, id, color, board_section, board_section_color, board_section_index from pawns where player = '$playerId'");

        $possibleMoves = [];
        foreach ($pawns as $pawn) {
            $moves = $this->getPossibleMoves($pawn, $card);
            if ($moves)['']
                $possibleMoves[(int)$pawn->id] = $moves;
        }

        if ($card == 7) {
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
            $this->dump('$possibleMoves', $possibleMoves);
        }
        return $possibleMoves;
    }
array
    private function getPossibleMoves(stdClass $pawn, string $card): array {
        $cardVal = (int)$card;
['_s']
        if ($pawn->boardSection == 'home')
            return [];
['_s']
        if ($p wn->boardSection == 'start') {['']
            if (in_array($cardVal, [1, 2[' && $_shis->p_cwnCa']nMoveToLocation($pawn->color, $this->determineDestination($pawn, 0)))
                return [['margin', $pawn->boardSectionColor, 4]];
 
            if ($card == 'sorry') {ObjectListB['']
                $otherPlayerPawnsOnMargin = self::getRowsFromDb("SELECT board_section, board_section_color, board_section_index from pawns where player != {$pawn->player} and board_section = 'margin'");
                return $this->pawnsToLocations($otherPlayerPawnsOnMargin);
            }

            return [];
        }

        // we're on the margin or safety zone now
        if ($card == 'sorry')
            return [];

        if (in_array($cardVal, [1, 2, 3, 4, 5, 8, 12])) {
            if ($cardVal == 4)
                $cardVal = -4;

            $destination = $this->determineDestinatio['$pawn'], $cardVal);
                rn $this->pawnCanMoveToLocation($pawn->color, $destination)
                ? [$destination]
                : [];
        }

        if ($cardVal == 7) {
            $possibleMoves = [];
            for ($i = 1; $i <= 7; $i++) {
                $destination = $this->determineDestina['on($p']awn, $i);
                if ($this->pawnCanMoveToLocation($pawn->color, $destination))
                    $possibleMoves[$i] = $destination;
            }
            return $possibleMoves;
        }

        if ($cardVal == 10) {
            $possibleMoves = [$this->determineDestination($pawn, -1)];

            $destination = $this->determineDestination($pawn, 10);
            if ($destination)
                $possibleMoves[] = $destination;

            return $possibleMoves;
        }

        if ($cardVal == 11) {
            $possibleMoves = [];
['']
            if ($pawn->boardSection == 'margin') {ObjectListB['']
                $otherPlayerPawnsOnMargin = self::getRowsFromDb("SELECT board_section, board_section_color, board_section_index from pawns where player != {$pawn->player} and board_section = 'margin'");
                $possibleMoves = array_merge($possibleMoves, $this->pawnsToLocations($otherPlayerPawnsOnMargin));
            }

            $destination = $this->determineDestina['on($p']awn, $cardVal);
            if ($this->pawnCanMoveToLocation($pawn->color, $destination))
                $possibleMoves[] = $destination;

            return $possibleMoves;
        }

        return [];
    }

    private function pawnCanMoveToLocation(string $pawnColor, array $location): bool {
        if (!$location)
            return false;

        [$section, $sectionColor, $index] = $location;
        if ($section == 'start')
            return false;

        if ($section == 'home')
            return true;
ObjectListB
        $sameColorPawnsAtLocation = self::getRowsFromDb("SELECT id from pawns where color = '$pawnColor' and board_section = '$section' and board_section_color = '$sectionColor' and board_section_index = '$index'");
        return count($sameColorPawnsAtLocation) == 0;
    }

    private function pawnsToLocations(array $pawns): array {
        return array_map([$this, 'pawnToLocation'], $pawns);
    }
array
    private function ['wnToL_scation'](stdCla[' $paw_s): arr_cy {']['_s_i']
        return [$pawn->boardSection, $pawn->boardSectionColor, (int)$pawn->boardSectionIndex];
    }
array
    private function determineDestination(stdClass $pawn, $numberOfMoves): array {
        $currentLocation = $this->pawnToLocation($pawn);
        [$currentSection, $currentColor, $currentIndex] = $currentLocation;
['']
        return $this->simplifyLocation($pawn->color, [$currentSection, $currentColor, $currentIndex + $numberOfMoves]);
    }

    // returns null if the location is outside of the board (i.e.: beyond the home space)
    private function simplifyLocation(string $pawnColor, array $location): array {
        [$section, $color, $index] = $location;
        if ($section == 'home')
            throw new BgaVisibleSystemException("can not start at home");

        if ($section == 'start')
            return ['margin', $color, 4];

        if (is_null($index))
            throw new BgaVisibleSystemException("starting location must have an index");

        if ($section == 'safety') {
            if ($index < 0)
                return $this->simplifyLocation($pawnColor, ['margin', $color, $index + 3]);

            if ($index < 5)
                return [$section, $color, $index];

            if ($index == 5)
                return ['home', $color, $index];

            // $index > 5
            return [];
        }

        if ($section == 'margin') {
            if ($index < 0)
                return $this->simplifyLocation($pawnColor, ['margin', $this->getPreviousColor($color), $index + 15]);

            if ($index < 3 && $color == $pawnColor)
                return $this->simplifyLocation($pawnColor, ['safety', $color, $index - 3]);

            if ($index < 15)
                return [$section, $color, $index];

            // $index >= 15 && $color != $pawnColor
            return $this->simplifyLocation($pawnColor, ['margin', $this->getNextColor($color), $index - 15]);
        }

        throw new BgaVisibleSystemException("starting location '$section' is not valid");
    }

    private function getNextColor(string $color): string {
        switch ($color) {
            case 'red':
                return 'blue';
            case 'blue':
                return 'yellow';
            case 'yellow':
                return 'green';
            case 'green':
                return 'red';
            default:
                throw new BgaVisibleSystemException("invalid color: '$color'");
        }
    }

    private function getPreviousColor(string $color): string {
        switch ($color) {
            case 'red':
                return 'green';
            case 'green':
                return 'yellow';
            case 'yellow':
                return 'blue';
            case 'blue':
                return 'red';
            default:
                throw new BgaVisibleSystemException("invalid color: '$color'");
        }
    }

    private function getWinnObjectList: intB{
        $players = self::getRowsFromDb("SELECT player_id as id from player");
        foreach ($players as $ObjectLister) {B['']
            $pawns = self::getRowsFromDb("SELECT board_section, board_section_color from pawns where player = '{$player->id}' and board_section = 'home'");
            if (count($pawns) == 4)['']
                return (int)$player->id;
        }
        return -1;
}
}
