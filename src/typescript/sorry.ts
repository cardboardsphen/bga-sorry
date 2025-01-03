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

        this.addBoardPieces(gamedatas);
        this.placeBoardPieces(gamedatas.pawns, gamedatas.cards);

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
        if (this.isCurrentPlayerActive()) this.addActionButton('draw-card-btn', 'Draw a Card', () => this.bgaPerformAction('actDrawCard'));
    }

    enteringState_selectPawn(args: any): void {
        this.clearPossibleMoves();

        if (this.isCurrentPlayerActive()) {
            for (let pawnId in args.args.possibleMoves) {
                document.getElementById(`pawn-${args.args.player}-${pawnId}`)!.classList.add('possible-move');

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    let locationElement = document.getElementById(locationId)!;
                    locationElement.classList.add('possible-move-destination');
                    locationElement.classList.add(`for-pawn-${pawnId}`);
                }
            }
        }
    }

    leavingState_selectPawn(): void {
        this.clearPossibleMoves();
    }

    updateActionButtons_selectPawn(args: any) {
        if (!this.isCurrentPlayerActive()) return;

        if (args.canSkip)
            this.addActionButton('skip-turn-btn', 'Skip Turn', () =>
                this.bgaPerformAction('actSelectPawn', {
                    pawnId: -1,
                })
            );

        if (args.canUndo) {
            this.addActionButton('undo-btn', 'Undo', () => this.bgaPerformAction('actUndoSelection'), undefined, undefined, 'red');
        }
    }

    enteringState_selectSquare(args: any): void {
        this.clearPossibleMoves();

        if (this.isCurrentPlayerActive()) {
            for (let pawnId in args.args.possibleMoves) {
                document.getElementById(`pawn-${args.args.player}-${pawnId}`)!.classList.add('active-pawn', `for-pawn-${pawnId}`);

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    document.getElementById(locationId)!.classList.add('possible-move', `for-pawn-${pawnId}`);
                }
            }
        }
    }

    leavingState_selectSquare(): void {}

    updateActionButtons_selectSquare(args: any): void {
        if (!this.isCurrentPlayerActive()) return;

        if (args.canSkip)
            this.addActionButton('skip-turn-btn', 'Skip Turn', () =>
                this.bgaPerformAction('actSelectPawn', {
                    pawnId: -1,
                })
            );

        this.addActionButton('undo-btn', 'Undo', () => this.bgaPerformAction('actUndoSelection'), undefined, undefined, 'red');
    }

    ///////////////////////////////////////////////////
    //// Notification Handling
    async notification_drawCard(args: any): Promise<void> {
        console.log('Received notification: drawCard');
        this.clearPossibleMoves();

        let card = document.getElementById('reveal-card')!;
        let cardFront = card.querySelector('.front') as HTMLElement;
        cardFront.dataset['rank'] = args.rank;

        card.classList.remove('hidden', 'revealing', 'revealed', 'discarding');
        card.classList.add('revealing');

        if (args.hasMoreToDraw) document.getElementById('draw-card')!.classList.remove('hidden');
        else document.getElementById('draw-card')!.classList.add('hidden');
    }

    async notification_discardCard(args: any): Promise<void> {
        console.log('Received notification: discardCard');
        let card = document.getElementById('reveal-card')!;
        let cardFront = card.querySelector('.front') as HTMLElement;
        cardFront.dataset['rank'] = args.rank;

        if (card.classList.contains('revealing') || card.classList.contains('revealed')) {
            card.classList.remove('hidden', 'revealing', 'revealed', 'discarding');
            card.classList.add('discarding');
        } else {
            document.getElementById('discard-card')!.dataset['rank'] = args.rank;
            document.getElementById('discard-card')!.classList.remove('hidden');
        }
    }

    async notification_shuffleDeck(args: any): Promise<void> {
        console.log('Received notification: shuffleDeck');
        this.shuffleCards(args.rank);
    }

    async notification_movePawns(args: any): Promise<void> {
        console.log('Received notification: jumpPawn');
        this.clearPossibleMoves();
        const pawnElementId = `pawn-${args.playerId}-${args.pawnId}`;
        const pawn = document.getElementById(pawnElementId)!;
        const startingLeft = this.parseDimensionToPx(window.getComputedStyle(pawn).left);
        const startingTop = this.parseDimensionToPx(window.getComputedStyle(pawn).top);

        const [offsetLeft, offsetTop] = this.getPawnOffsetInPixelsToLocation(pawn, args.pawnId, args.section, args.color, args.index);
        if (!this.bgaAnimationsActive()) {
            pawn.style.left = `${startingLeft + offsetLeft}px`;
            pawn.style.top = `${startingTop + offsetTop}px`;
            return;
        }

        const distance = Math.sqrt(offsetLeft ** 2 + offsetTop ** 2) / 50;
        const duration = 1000 * distance;
        requestAnimationFrame((timestamp) =>
            this.jumpPawnStep(pawn, document.timeline.currentTime as number, duration, startingLeft, startingTop, offsetLeft, offsetTop, timestamp)
        );
        await this.wait(duration);
    }

    async notification_slidePawn(args: any): Promise<void> {
        console.log('Received notification: slidePawn');
        const pawnElementId = `pawn-${args.playerId}-${args.pawnId}`;
        const pawn = document.getElementById(pawnElementId)!;
        const startingLeft = this.parseDimensionToPx(window.getComputedStyle(pawn).left);
        const startingTop = this.parseDimensionToPx(window.getComputedStyle(pawn).top);

        const [offsetLeft, offsetTop] = this.getPawnOffsetInPixelsToLocation(pawn, args.pawnId, args.section, args.color, args.index);
        if (!this.bgaAnimationsActive()) {
            pawn.style.left = `${startingLeft + offsetLeft}px`;
            pawn.style.top = `${startingTop + offsetTop}px`;
            return;
        }

        const duration = 2000;
        requestAnimationFrame((timestamp) =>
            this.slidePawnStep(pawn, document.timeline.currentTime as number, duration, startingLeft, startingTop, offsetLeft, offsetTop, timestamp)
        );
    }

    async notification_swapPawns(args: any): Promise<void> {
        console.log('Received notification: swapPawns');
    }

    ///////////////////////////////////////////////////
    //// Utility functions

    /**
     * Add the board pieces to the game play area.
     * This should be called once during the setup phase.
     *
     * @param gamedatas Data from the server.
     */
    addBoardPieces(gamedatas: BGA.Gamedatas | any): void {
        document.getElementById('game_play_area')!.insertAdjacentHTML(
            'beforeend',
            `
                <div id="board">
                    <div id="pawns"></div>
                </div>
            `
        );

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
        document.querySelectorAll('.square, #home-red, #home-blue, #home-yellow, #home-green').forEach((square) =>
            square.addEventListener('click', (e) => {
                const clickedSquare = e.currentTarget as HTMLElement;
                if (clickedSquare.classList.contains('possible-move') && this.checkAction('actSelectSquare', true))
                    this.bgaPerformAction('actSelectSquare', {
                        squareId: clickedSquare.id,
                    });
            })
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

        board.insertAdjacentHTML('beforeend', `<div id="shuffle-cards" class="card-pile hidden"></div>`);
        for (let rank of [1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 'sorry']) {
            document.getElementById('shuffle-cards')!.insertAdjacentHTML(
                'beforeend',
                `<div class="shuffle-card">
                    <div class="card back" data-rank="back"></div>
                    <div class="card front" data-rank="${rank}"></div>
                </div>`
            );
        }
        {
            let degrees = [0, 35, 70, 105, 140, 175, 210, 245, 280, 315, 350];
            this.shuffleArray(degrees);

            let i = 0;
            document.querySelectorAll('#shuffle-cards .shuffle-card').forEach((card) => {
                const xTranslation = Math.sin((degrees[i]! * Math.PI) / 180) * 100;
                const yTranslation = Math.cos((degrees[i]! * Math.PI) / 180) * 100;
                const cardElement = card as HTMLElement;

                cardElement.style.setProperty('--x-translation', `${xTranslation}px`);
                cardElement.style.setProperty('--y-translation', `${yTranslation}px`);

                i++;
            });
        }
        document.getElementById('shuffle-cards')!.addEventListener('animationend', this.shuffleCardsAnimationStopped.bind(this));
        document.getElementById('shuffle-cards')!.addEventListener('animationcancel', this.shuffleCardsAnimationStopped.bind(this));

        // create pawns
        for (let pawn of gamedatas.pawns) {
            let pawnElementId = `pawn-${pawn.player}-${pawn.id}`;

            document
                .getElementById('pawns')!
                .insertAdjacentHTML('beforeend', `<div class="pawn" data-color="${pawn.color}" id="${pawnElementId}"></div>`);
        }
        document.querySelectorAll('.pawn').forEach((pawn) =>
            pawn.addEventListener('click', (e) => {
                const clickedPawn = e.currentTarget as HTMLElement;
                if (clickedPawn.classList.contains('possible-move') && this.checkAction('actSelectPawn', true))
                    this.bgaPerformAction('actSelectPawn', {pawnId: clickedPawn.id.match(/\d+$/)});
            })
        );
    }

    /**
     * Places all the board pieces on the game board.
     *
     * @param gamedatas Data from the server.
     */
    placeBoardPieces(pawns: any, cards: any): void {
        for (let pawn of pawns) {
            let pawnElementId = `pawn-${pawn.player}-${pawn.id}`;
            let pawnElement = document.getElementById(pawnElementId)!;
            let [pawnDestinationLeft, pawnDestinationTop] = this.getPawnCoorinatesInPixelsAtLocation(
                pawn.boardSection,
                pawn.boardSectionColor,
                pawn.boardSectionIndex,
                pawn.id
            );

            pawnElement.style.left = `${pawnDestinationLeft}px`;
            pawnElement.style.top = `${pawnDestinationTop}px`;
        }

        if (cards.nextCard) {
            document.getElementById('draw-card')!.classList.remove('hidden');
        } else {
            document.getElementById('draw-card')!.classList.add('hidden');
        }

        if (cards.revealCard) {
            document.getElementById('reveal-card')!.classList.remove('hidden', 'revealing', 'discarding');
            document.getElementById('reveal-card')!.classList.add('revealed');
            (document.querySelector('#reveal-card .front') as HTMLElement).dataset['rank'] = cards.revealCard;
        } else {
            document.getElementById('reveal-card')!.classList.remove('revealing', 'revealed', 'discarding');
            document.getElementById('reveal-card')!.classList.add('hidden');
        }

        if (cards.lastCard) {
            document.getElementById('discard-card')!.classList.remove('hidden');
            document.getElementById('discard-card')!.dataset['rank'] = cards.lastCard;
        } else {
            document.getElementById('discard-card')!.classList.add('hidden');
        }
    }

    getLocationId(section: string, color: string, index: number | null): string {
        let destinationId = `${section}-${color}`;
        if (index !== null) destinationId += `-${index}`;
        return destinationId;
    }

    revealCardAnmiationStopped(e: AnimationEvent): void {
        let card = e.currentTarget as HTMLElement;
        if (card.classList.contains('revealing')) {
            document.getElementById('reveal-card')!.classList.remove('hidden', 'revealing', 'discarding');
            card.classList.add('revealed');
        }
        if (card.classList.contains('discarding')) {
            let rank = (card.querySelector('.front') as HTMLElement).dataset['rank'];
            document.getElementById('discard-card')!.dataset['rank'] = rank;
            document.getElementById('discard-card')!.classList.remove('hidden');
            document.getElementById('reveal-card')!.classList.remove('revealing', 'revealed', 'discarding');
            card.classList.add('hidden');
        }
    }

    shuffleCardsAnimationStopped(e: AnimationEvent): void {
        let shuffleCards = e.currentTarget as HTMLElement;
        if (shuffleCards.classList.contains('shuffling')) {
            shuffleCards.classList.remove('shuffling');
            shuffleCards.classList.add('hidden');
            document.getElementById('draw-card')!.classList.remove('hidden');
        }
    }

    /**
     * Gets the position of a pawn at a given space on the board.
     *
     * @param pawnId The id of the pawn to be placed at the position. This can be omitted if the pawn is not at start or home.
     * @returns An array of the form [left, top]. The values are strings which can be used as CSS values.
     */
    getPawnCoorinatesInPixelsAtLocation(section: string, sectionColor: string, sectionIndex: string, pawnId: number = 0): [number, number] {
        let locationId = this.getLocationId(section, sectionColor, sectionIndex === null ? null : parseInt(sectionIndex));
        let boardLocationStyle = window.getComputedStyle(document.getElementById(locationId)!);
        let locationLeft = this.parseDimensionToPx(boardLocationStyle.left);
        let locationTop = this.parseDimensionToPx(boardLocationStyle.top);
        let leftOffset = 4;
        let topOffset = 4;

        // fan out the pawns if on start or home
        if (section == 'start' || section == 'home') {
            leftOffset = 9 + Math.floor(pawnId / 2) * 40;
            topOffset = 9 + (pawnId % 2) * 40;
        }

        return [locationLeft + leftOffset, locationTop + topOffset];
    }

    getPawnOffsetInPixelsToLocation(pawn: HTMLElement, pawnId: number, section: string, color: string, index: string): [number, number] {
        let pawnLeft = this.parseDimensionToPx(window.getComputedStyle(pawn).left);
        let pawnTop = this.parseDimensionToPx(window.getComputedStyle(pawn).top);

        let [pawnAtLocationLeft, pawnAtLocationTop] = this.getPawnCoorinatesInPixelsAtLocation(section, color, index, pawnId);

        return [pawnAtLocationLeft - pawnLeft, pawnAtLocationTop - pawnTop];
    }

    parseDimensionToPx(dimension: string): number {
        let [, value, unit] = dimension.match(/([\d\.]+)([a-z%]+)/)!;
        if (!value || !unit) {
            this.showMessage('error', `Could not parse dimension to pixels: ${dimension}`);
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
        document.querySelectorAll('.possible-move-destination').forEach((div) => div.classList.remove('possible-move-destination'));
        document.querySelectorAll('.active-pawn').forEach((div) => div.classList.remove('active-pawn'));
        document.querySelectorAll('.selected-move').forEach((div) => div.classList.remove('selected-move'));
        document.querySelectorAll('[class*="for-pawn-"]').forEach((div) => {
            let a = div.classList.forEach((className) => {
                if (className.startsWith('for-pawn')) div.classList.remove(className);
            });
        });
    }

    shuffleCards(rank: string): void {
        document.getElementById('reveal-card')!.classList.add('hidden');

        const shuffleCards = document.getElementById('shuffle-cards')!;
        let ranks = ['1', '2', '3', '4', '5', '7', '8', '10', '11', '12', 'sorry'];
        ranks = ranks.filter((r) => r !== rank);
        this.shuffleArray(ranks);
        ranks.push(rank);

        let i = 0;
        shuffleCards.querySelectorAll('.card.front').forEach((card) => {
            let cardFront = card as HTMLElement;
            cardFront.dataset['rank'] = ranks[i];
            i++;
        });
        shuffleCards.classList.add('shuffling');
        shuffleCards.classList.remove('hidden');

        document.getElementById('discard-card')!.classList.add('hidden');
    }

    shuffleArray(array: any[]): void {
        for (let i = array.length - 1; i >= 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }

    jumpPawnStep(
        pawn: HTMLElement,
        zero: number,
        durationMilliseconds: number,
        startingLeft: number,
        startingTop: number,
        offsetLeft: number,
        offsetTop: number,
        timestamp: number
    ) {
        const elapsedMilliseconds = timestamp - zero;
        if (elapsedMilliseconds >= durationMilliseconds) {
            pawn.style.left = `${startingLeft + offsetLeft}px`;
            pawn.style.top = `${startingTop + offsetTop}px`;
            pawn.style.removeProperty('z-index');
            return;
        }

        const elapsedFraction = elapsedMilliseconds / durationMilliseconds;
        const easeInOutScaling = 0.5 * (Math.sin((elapsedFraction - 0.5) * Math.PI) + 1);
        pawn.style.left = `${startingLeft + easeInOutScaling * offsetLeft}px`;
        pawn.style.top = `${startingTop + easeInOutScaling * offsetTop}px`;
        pawn.style.zIndex = '2';

        if (elapsedFraction < 0.5) pawn.style.transform = `scale(${1 + 0.5 * easeInOutScaling}) translate3d(0, 0, ${100 * easeInOutScaling}px)`;
        else pawn.style.transform = `scale(${1 + 0.5 * (1 - easeInOutScaling)}) translate3d(0, 0, ${100 * (1 - easeInOutScaling)}px)`;

        requestAnimationFrame((timestamp) =>
            this.jumpPawnStep(pawn, zero, durationMilliseconds, startingLeft, startingTop, offsetLeft, offsetTop, timestamp)
        );
    }

    slidePawnStep(
        pawn: HTMLElement,
        zero: number,
        durationMilliseconds: number,
        startingLeft: number,
        startingTop: number,
        offsetLeft: number,
        offsetTop: number,
        timestamp: number
    ) {
        const elapsedMilliseconds = timestamp - zero;
        if (elapsedMilliseconds >= durationMilliseconds) {
            pawn.style.left = `${startingLeft + offsetLeft}px`;
            pawn.style.top = `${startingTop + offsetTop}px`;
            pawn.style.removeProperty('z-index');
            return;
        }

        const elapsedFraction = elapsedMilliseconds / durationMilliseconds;
        const easeInOutScaling = 0.5 * (Math.sin((elapsedFraction - 0.5) * Math.PI) + 1);
        pawn.style.left = `${startingLeft + easeInOutScaling * offsetLeft}px`;
        pawn.style.top = `${startingTop + easeInOutScaling * offsetTop}px`;
        pawn.style.zIndex = '2';

        requestAnimationFrame((timestamp) =>
            this.slidePawnStep(pawn, zero, durationMilliseconds, startingLeft, startingTop, offsetLeft, offsetTop, timestamp)
        );
    }
}

define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui', 'ebg/counter'], dojo.declare('bgagame.sorry', ebg.core.gamegui, new Sorry()));
