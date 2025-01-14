
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Sorry implementation : © cardboardsphen, bga-dev@sphen.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
-- 
-- Full source available at https://github.com/cardboardsphen/bga-sorry
-- 
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

create table if not exists cards (
    id int unsigned not null primary key,
    position smallint unsigned not null,
    rank enum('1', '2','3', '4', '5', '7', '8', '10', '11', '12', 'sorry') not null,
    pile enum('draw', 'discard') not null
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------
-- The check constraint doesn't work because as of 2024-12-28, BGA uses MySql 5.7.44, which doesn't support check constraints.
-- Since it doesn't cause an errors, I'm leaving it in for a future upgrade.
-- It's not critical to anything, but it would be nice to have.
-- ------
create table if not exists pawns (
    player int unsigned not null,
    id smallint unsigned not null,
    color enum('red', 'blue', 'yellow', 'green') not null,
    board_section enum('start', 'margin', 'safety', 'home') not null,
    board_section_color enum('red', 'blue', 'yellow', 'green') not null,
    board_section_index smallint unsigned,
    primary key (player, id),
    constraint index_valid_check check (
        (board_section = 'start' and board_section_index is null) or
        (board_section = 'margin' and board_section_index < 15) or
        (board_section = 'safety' and board_section_index < 5) or
        (board_section = 'home' and board_section_index is null)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

create table if not exists possible_moves (
    id int unsigned not null auto_increment primary key,
    player int unsigned not null,
    pawn_id smallint unsigned not null,
    destination_section enum('start', 'margin', 'safety', 'home') not null,
    destination_section_color enum('red', 'blue', 'yellow', 'green') not null,
    destination_section_index smallint unsigned,
    optional boolean not null,
    number_of_steps smallint not null,
    selected_as enum('none', 'pawn', 'first_move', 'final_move', 'blocked') not null default 'none',
    foreign key (player, pawn_id)
        references pawns (player, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;