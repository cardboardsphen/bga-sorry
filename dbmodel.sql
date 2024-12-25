
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Sorry implementation : © cardboardsphen, bga-dev@sphen.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

alter table player add player_color_name enum('red', 'blue', 'yellow', 'green');

create table if not exists pawns (
    player int unsigned not null,
    id smallint unsigned not null,
    color enum('red', 'blue', 'yellow', 'green') not null,
    board_section enum('start', 'margin', 'safety', 'home') not null,
    board_section_color enum('red', 'blue', 'yellow', 'green') not null,
    board_section_index smallint unsigned,
    primary key (player, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

create table if not exists cards (
    id int unsigned not null primary key,
    position smallint unsigned not null,
    rank enum('1', '2','3', '4', '5', '7', '8', '10', '11', '12', 'sorry') not null,
    pile enum('draw', 'discard') not null
) ENGINE=InnoDB DEFAULT CHARSET=utf8;