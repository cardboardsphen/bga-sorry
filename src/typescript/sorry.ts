/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Sorry implementation : © cardboardsphen, bga-dev@sphen.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * sorry.js
 *
 * Sorry user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

/// <amd-module name="bgagame/sorry"/>
/// <reference path="./types/all-bga-types.d.ts"/>

import 'dojo';
import 'dojo/_base/declare';
import 'ebg/counter';
import * as GameGui from 'ebg/core/gamegui';
import {undef} from 'require';

/**
 * Client implementation of Sorry.
 */
export default class Sorry extends GameGui {
    // enable dynamic method calls
    [key: string]: any;

    constructor() {
        super();
        console.log('sorry constructor');
    }

    override setup(gamedatas: BGA.Gamedatas | any): void {
        console.log('Starting game setup');

        this.buildBoard(gamedatas);

        this.setupNotifications = () => {
            console.log('notifications subscriptions setup');

            this.bgaSetupPromiseNotifications({prefix: 'notification_'});
        };
        this.setupNotifications();

        console.log('Ending game setup');
    }

    override onEnteringState(stateName: string, args: any): void {
        console.log('Entering state: ' + stateName, args);

        var methodName = 'enteringState_' + stateName;
        if (typeof this[methodName] === 'function') {
            console.log('Calling ' + methodName);
            this[methodName](args);
        }
    }

    override onLeavingState(stateName: string): void {
        console.log('Leaving state: ' + stateName);
        var methodName = 'leavingState_' + stateName;
        if (typeof this[methodName] === 'function') {
            console.log('Calling ' + methodName);
            this[methodName]();
        }
    }

    override onUpdateActionButtons(stateName: string, args: any): void {
        console.log('onUpdateActionButtons: ' + stateName, args);
        var methodName = 'updateActionButtons_' + stateName;
        if (typeof this[methodName] === 'function') {
            console.log('Calling ' + methodName);
            this[methodName](args);
        }
    }

    ///////////////////////////////////////////////////
    //// State Handling
    enteringState_drawCard(args: any): void {
        this.clearPossibleMoves();
        if (this.isCurrentPlayerActive()) document.getElementById('draw-pile')!.classList.add('possible-move');
    }

    leavingState_drawCard(): void {
        this.clearPossibleMoves();
    }

    updateActionButtons_drawCard(args: any): void {
        if (this.isCurrentPlayerActive())
            this.addActionButton('draw-card-btn', 'Draw a Card', () => this.bgaPerformAction('actDrawCard'), undefined, undefined, args.color);
    }

    enteringState_selectPawn(args: any): void {
        this.clearPossibleMoves();

        if (this.isCurrentPlayerActive()) {
            for (let pawnId in args.args.possibleMoves) document.getElementById(`pawn-${args.args.player}-${pawnId}`)!.classList.add('possible-move');
        }
    }

    leavingState_selectPawn(): void {
        this.clearPossibleMoves();
    }

    updateActionButtons_selectPawn(args: any) {
        if (!this.isCurrentPlayerActive()) return;

        if (args.canSkip)
            this.addActionButton(
                'skip-turn-btn',
                'Skip Turn',
                () =>
                    this.bgaPerformAction('actSelectPawn', {
                        pawnId: -1,
                    }),
                undefined,
                undefined,
                args.color
            );
    }

    enteringState_selectSquare(args: any): void {}

    leavingState_selectSquare(): void {}

    updateActionButtons_selectSquare(args: any): void {}

    ///////////////////////////////////////////////////
    //// Notification Handling
    async notification_drawCard(args: any): Promise<void> {
        this.clearPossibleMoves();

        let card = document.getElementById('reveal-card')!;
        let cardFront = card.querySelector('.front') as HTMLElement;
        cardFront.dataset['rank'] = args.rank;

        card.removeAttribute('class');
        card.classList.add('revealing');

        if (args.hasMoreToDraw) document.getElementById('draw-card')!.classList.remove('hidden');
        else document.getElementById('draw-card')!.classList.add('hidden');
    }

    async notification_discardCard(args: any): Promise<void> {
        let card = document.getElementById('reveal-card')!;
        let cardFront = card.querySelector('.front') as HTMLElement;
        cardFront.dataset['rank'] = args.rank;

        if (card.classList.contains('revealed')) {
            card.removeAttribute('class');
            card.classList.add('discarding');
        } else {
            document.getElementById('discard-card')!.dataset['rank'] = args.rank;
            document.getElementById('discard-card')!.classList.remove('hidden');
        }
    }

    async notification_shuffleDeck(args: any): Promise<void> {
        this.shuffleCards();
    }

    ///////////////////////////////////////////////////
    //// Utility functions
    buildBoard(gamedatas: BGA.Gamedatas | any): void {
        document.getElementById('game_play_area')!.insertAdjacentHTML(
            'beforeend',
            `
                <div id="board">
                    <div id="pawns"></div>
                </div>
            `
        );

        // TODO: Set up your game interface here, according to "gamedatas"
        const board = document.getElementById('board')!;

        // start spaces
        board.insertAdjacentHTML(
            'beforeend',
            `
                <div id="start-red" class="circle"></div>
                <div id="start-blue" class="circle"></div>
                <div id="start-yellow" class="circle"></div>
                <div id="start-green" class="circle"></div>
            `
        );

        // outer rows
        for (let y = 0; y < 15; y++) {
            const offset = y * 50;
            board.insertAdjacentHTML(
                'beforeend',
                `
                    <div id="margin-red-${y}" class="square" style="left: ${51 + offset}px; top: 51px;"></div>
                    <div id="margin-blue-${y}" class="square" style="left: 801px; top: ${51 + offset}px;"></div>
                    <div id="margin-yellow-${y}" class="square" style="left: ${801 - offset}px; top: 801px;"></div>
                    <div id="margin-green-${y}" class="square" style="left: 51px; top: ${801 - offset}px;"></div>
                `
            );
        }

        // safety zones
        for (let y = 0; y < 5; y++) {
            const offset = y * 50;
            board.insertAdjacentHTML(
                'beforeend',
                `
                    <div id="safety-red-${y}" class="square" style="left: 151px; top: ${101 + offset}px;"></div>
                    <div id="safety-blue-${y}" class="square" style="left: ${751 - offset}px; top: 151px;"></div>
                    <div id="safety-yellow-${y}" class="square" style="left: 701px; top: ${751 - offset}px;"></div>
                    <div id="safety-green-${y}" class="square" style="left: ${101 + offset}px; top: 701px;"></div>
                `
            );
        }

        // homes
        board.insertAdjacentHTML(
            'beforeend',
            `
                <div id="home-red" class="circle"></div>
                <div id="home-blue" class="circle"></div>
                <div id="home-yellow" class="circle"></div>
                <div id="home-green" class="circle"></div>
            `
        );

        // discard pile
        board.insertAdjacentHTML(
            'beforeend',
            `<div id="discard-pile" class="card-pile">
                <div class="card hidden" id="discard-card"></div>
            </div>`
        );

        // draw pile
        board.insertAdjacentHTML(
            'beforeend',
            `<div id="draw-pile" class="card-pile">
                <div class="card hidden" data-rank="back" id="draw-card"></div>
            </div>`
        );
        document.getElementById('draw-pile')!.addEventListener('click', (e) => {
            if ((e.currentTarget as HTMLElement).classList.contains('possible-move') && this.checkAction('actDrawCard', true))
                this.bgaPerformAction('actDrawCard');
        });

        // reveal card
        board.insertAdjacentHTML(
            'beforeend',
            `<div class="hidden" id="reveal-card">
                <div class="card back" data-rank="back"></div>
                <div class="card front"></div>
            </div>`
        );
        document.getElementById('reveal-card')!.addEventListener('animationend', this.revealCardAnmiationStopped.bind(this));
        document.getElementById('reveal-card')!.addEventListener('animationcancel', this.revealCardAnmiationStopped.bind(this));

        document.getElementById('discard-pile')!.insertAdjacentHTML('beforeend', `<div id="shuffle-cards" class="hidden"></div>`);
        for (let rank in [1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 'sorry'])
            document.getElementById('shuffle-cards')!.insertAdjacentHTML('beforeend', `<div class="card" data-rank="${rank}"></div>`);

        // create pawns
        for (let i in gamedatas.pawns) {
            let pawn = gamedatas.pawns[i];
            let pawnElementId = `pawn-${pawn.player}-${pawn.id}`;

            document
                .getElementById('pawns')!
                .insertAdjacentHTML('beforeend', `<div class="pawn" data-color="${pawn.color}" id="${pawnElementId}"></div>`);
            this.jumpPawnToLocation(pawnElementId, pawn.id, pawn.boardSection, pawn.boardSectionColor, pawn.boardSectionIndex);
        }
        document.querySelectorAll('.pawn').forEach((pawn) =>
            pawn.addEventListener('click', (e) => {
                const clickedPawn = e.currentTarget as HTMLElement;
                if (clickedPawn.classList.contains('possible-move') && this.checkAction('actSelectPawn', true))
                    this.bgaPerformAction('actSelectPawn', {
                        pawnId: clickedPawn.id.match(/\d+$/),
                    });
            })
        );

        // show the visible cards
        if (gamedatas.nextCard) {
            document.getElementById('draw-card')!.classList.remove('hidden');
        }
        if (gamedatas.revealCard) {
            document.getElementById('reveal-card')!.removeAttribute('class');
            document.getElementById('reveal-card')!.classList.add('revealed');
            (document.querySelector('#reveal-card .front') as HTMLElement).dataset['rank'] = gamedatas.revealCard;
        }
        if (gamedatas.lastCard) {
            document.getElementById('discard-card')!.classList.remove('hidden');
            document.getElementById('discard-card')!.dataset['rank'] = gamedatas.lastCard;
        }
    }

    revealCardAnmiationStopped(e: AnimationEvent): void {
        let card = e.currentTarget as HTMLElement;
        if (card.classList.contains('revealing')) {
            card.removeAttribute('class');
            card.classList.add('revealed');
        }
        if (card.classList.contains('discarding')) {
            let rank = (card.querySelector('.front') as HTMLElement).dataset['rank'];
            document.getElementById('discard-card')!.dataset['rank'] = rank;
            document.getElementById('discard-card')!.classList.remove('hidden');
            card.removeAttribute('class');
            card.classList.add('hidden');
        }
    }

    jumpPawnToLocation(pawnElementId: string, pawnId: number, section: string, sectionColor: string, sectionIndex: string) {
        let pawnElement = document.getElementById(pawnElementId)!;
        let locationId = `${section}-${sectionColor}`;
        if (sectionIndex) locationId += `-${sectionIndex}`;

        // fan out the pawns if on start or home
        if (section == 'start' || section == 'home') {
            let boardLocationStyle = window.getComputedStyle(document.getElementById(locationId)!);
            let left = this.parseDimensionToPx(boardLocationStyle.left);
            let top = this.parseDimensionToPx(boardLocationStyle.top);
            let leftOffset = Math.floor(pawnId / 2) * 40;
            let topOffset = (pawnId % 2) * 40;
            pawnElement.style.left = `${left + 9 + leftOffset}px`;
            pawnElement.style.top = `${top + 9 + topOffset}px`;
            return;
        }

        this.placeOnObject(pawnElementId, locationId);
    }

    parseDimensionToPx(dimension: string): number {
        let [, value, unit] = dimension.match(/([\d\.]+)([a-z%]+)/)!;
        if (!value || !unit) {
            this['sendMessage']('error', `Could not parse dimension to pixels: ${dimension}`);
            return 0;
        }

        let numericValue = Number.parseFloat(value);
        if (unit === 'px') {
            return numericValue;
        }

        let tempDiv = document.createElement('div');
        tempDiv.style.position = 'absolute';
        tempDiv.style.left = dimension;
        document.body.appendChild(tempDiv);
        let pixels = tempDiv.offsetLeft;
        document.body.removeChild(tempDiv);

        return pixels;
    }

    clearPossibleMoves(): void {
        document.querySelectorAll('.possible-move').forEach((div) => div.classList.remove('possible-move'));
    }

    shuffleCards(): void {
        return;
        document.getElementById('reveal-card')!.classList.add('hidden');

        // have 12 shuffle cards, one of each rank
        // animate them to spin and flip and move to draw pile when set to class='shuffle'
        document.querySelectorAll('.shuffle-card').forEach((card) => {
            card.classList.add('shuffle');
            card.classList.remove('hidden');
        });
        document.getElementById('discard-card')!.classList.add('hidden');

        setTimeout(() => {
            document.getElementById('draw-card')!.dataset['rank'] = 'back';
            document.getElementById('draw-card')!.classList.remove('hidden');
            document.querySelectorAll('.shuffle-card').forEach((card) => {
                card.classList.add('hidden');
                card.classList.remove('shuffle');
            }, 1000);
        });
    }
}

define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui', 'ebg/counter'], dojo.declare('bgagame.sorry', ebg.core.gamegui, new Sorry()));
