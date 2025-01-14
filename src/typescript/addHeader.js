import {readFileSync, writeFileSync} from 'fs';
import {join} from 'path';

const header = `/*!
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sorry implementation : © cardboardsphen, bga-dev@sphen.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * This is a generated file. Generated on: ${new Date().toISOString()}
 * Full source available at https://github.com/cardboardsphen/bga-sorry
 *
 * -----
 *
 * sorry.js
 *
 * Sorry user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 */\n`;

const outputFile = 'sorry.js';
const originalContent = readFileSync(outputFile, 'utf8');

writeFileSync(outputFile, header + originalContent, 'utf8');
