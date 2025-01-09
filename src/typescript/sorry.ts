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

import 'ebg/counter';
import * as GameGui from 'ebg/core/gamegui';

import * as dom from 'dojo/dom';
import * as domAttr from 'dojo/dom-attr';
import * as domClass from 'dojo/dom-class';
import * as domStyle from 'dojo/dom-style';
import * as fx from 'dojo/fx';
import * as on from 'dojo/on';
import * as query from 'dojo/query';

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
        if (this.isCurrentPlayerActive()) dom.byId('draw-pile')!.classList.add('possible-move');
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
                dom.byId(`pawn-${args.args.player}-${pawnId}`)!.classList.add('possible-move');

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    let locationElement = dom.byId(locationId)!;
                    locationElement.classList.add('possible-move-destination');
                    locationElement.classList.add(`for-pawn-${pawnId}`);
                }
            }

            if (args.args.selectedMove) {
                dom.byId(`pawn-${args.args.player}-${args.args.selectedMove.id}`)!.classList.add('selected-move');
                document
                    .getElementById(this.getLocationId(args.args.selectedMove.section, args.args.selectedMove.color, args.args.selectedMove.index))!
                    .classList.add('selected-move');
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
                dom.byId(`pawn-${args.args.player}-${pawnId}`)!.classList.add('active-pawn', `for-pawn-${pawnId}`);

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    dom.byId(locationId)!.classList.add('possible-move', `for-pawn-${pawnId}`);
                }
            }

            if (args.args.selectedMove) {
                dom.byId(`pawn-${args.args.player}-${args.args.selectedMove.id}`)!.classList.add('selected-move');
                document
                    .getElementById(this.getLocationId(args.args.selectedMove.section, args.args.selectedMove.color, args.args.selectedMove.index))!
                    .classList.add('selected-move');
            }
        }
    }

    leavingState_selectSquare(): void {}

    updateActionButtons_selectSquare(args: any): void {
        if (!this.isCurrentPlayerActive()) return;

        if (args.canSkip)
            this.addActionButton('skip-turn-btn', 'Skip Turn', () =>
                this.bgaPerformAction('actSelectSquare', {
                    squareId: -1,
                })
            );

        this.addActionButton('undo-btn', 'Undo', () => this.bgaPerformAction('actUndoSelection'), undefined, undefined, 'red');
    }

    ///////////////////////////////////////////////////
    //// Notification Handling
    async notification_drawCard(args: any): Promise<void> {
        console.log('Received notification: drawCard');
        this.clearPossibleMoves();

        this.drawCard(args.rank);

        if (args.hasMoreToDraw) domClass.remove('draw-card', 'hidden');
        else domClass.add('draw-card', 'hidden');

        await this.wait(2000); // return early so card is logged about when it is visible
    }

    async notification_discardCard(args: any): Promise<void> {
        console.log('Received notification: discardCard');
        let card = dom.byId('reveal-card')!;
        query('.front', card).attr('data-rank', args.rank);

        if (!this.bgaAnimationsActive()) {
            domAttr.set('discard-card', 'data-rank', args.rank);
            domClass.remove('discard-card', 'hidden');
            domClass.add(card, 'hidden');
            return;
        }

        const animations: DojoJS.Animation[] = [];
        animations.push(this.slideToObject(card, 'discard-pile', 2000));
        animations.push(
            dojo.animateProperty({
                node: card,
                properties: {
                    angle: {start: 0, end: -90},
                    scale: {start: 1.8, end: 1},
                },
                duration: this.bgaAnimationsActive() && !domClass.contains(card, 'hidden') ? 2000 : 0,
                easing: fx.easing.linear,
                onAnimate: (p: any) => domStyle.set(card, 'transform', `scale(${p.scale.slice(0, -2)}) rotate(${p.angle.slice(0, -2)}deg)`),
                onEnd: () => {
                    domAttr.set('discard-card', 'data-rank', args.rank);
                    domClass.remove('discard-card', 'hidden');
                    domClass.add(card, 'hidden');
                },
            })
        );

        domClass.remove(card, 'hidden');
        await this.playAllAnimations(animations);
    }

    async notification_shuffleDeck(args: any): Promise<void> {
        console.log('Received notification: shuffleDeck');
        dom.byId('reveal-card')!.classList.add('hidden');

        if (!this.bgaAnimationsActive()) {
            dom.byId('draw-card')!.classList.remove('hidden');
            dom.byId('discard-card')!.classList.add('hidden');
            return;
        }

        const shuffleCards = dom.byId('shuffle-cards')!;
        let ranks = ['1', '2', '3', '4', '5', '7', '8', '10', '11', '12', 'sorry'];
        ranks = ranks.filter((r) => r != args.rank);
        this.shuffleArray(ranks);
        ranks.push(args.rank);

        let i = 0;
        shuffleCards.querySelectorAll('.card.front').forEach((card) => {
            let cardFront = card as HTMLElement;
            cardFront.dataset['rank'] = ranks[i];
            i++;
        });
        shuffleCards.classList.add('shuffling');
        shuffleCards.classList.remove('hidden');

        dom.byId('discard-card')!.classList.add('hidden');
        await this.wait(3000);
    }

    async notification_movePawns(args: PawnMoveArgs): Promise<void> {
        console.log('Received notification: movePawns');
        this.clearPossibleMoves();

        const move = this.getMoveFromMoveArgs(args.move);
        const otherMoves = args.otherMoves.map((otherMoveArgs) => this.getMoveFromMoveArgs(otherMoveArgs));

        if (!this.bgaAnimationsActive()) {
            this.movePawnDirectly(move);
            for (let otherMove of otherMoves) this.movePawnDirectly(otherMove);
            return;
        }

        const animations: DojoJS.Animation[] = [];
        animations.push(this.getPawnMoveAnimation(move));
        for (let otherMove of otherMoves) animations.push(this.getPawnMoveAnimation(otherMove, move.durationMilliseconds));

        await this.playAllAnimations(animations)
            .catch((error) => {
                this.movePawnDirectly(move);
                for (let otherMove of otherMoves) this.movePawnDirectly(otherMove);
            })
            .finally(() => {
                query('.jumpping').removeClass('jumpping');
            });
    }

    async notification_score(args: any): Promise<void> {
        console.log('Received notification: score');
        this.scoreCtrl[args.playerId]!.setValue(args.score);
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
        dom.byId('game_play_area')!.insertAdjacentHTML(
            'beforeend',
            `
                <div id="board">
                    <div id="pawns"></div>
                </div>
            `
        );

        const board = dom.byId('board')!;

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
        dom.byId('draw-pile')!.addEventListener('click', (e) => {
            if ((e.currentTarget as HTMLElement).classList.contains('possible-move') && this.checkAction('actDrawCard', true))
                this.bgaPerformAction('actDrawCard');
        });

        // reveal pile
        board.insertAdjacentHTML('beforeend', `<div id="reveal-pile" class="card-pile"></div>`);

        // reveal card
        board.insertAdjacentHTML(
            'beforeend',
            `<div class="hidden" id="reveal-card">
                <div class="card back" data-rank="back"></div>
                <div class="card front"></div>
            </div>`
        );

        board.insertAdjacentHTML('beforeend', `<div id="shuffle-cards" class="card-pile hidden"></div>`);
        for (let rank of [1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 'sorry']) {
            dom.byId('shuffle-cards')!.insertAdjacentHTML(
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
        dom.byId('shuffle-cards')!.addEventListener('animationend', this.shuffleCardsAnimationStopped.bind(this));
        dom.byId('shuffle-cards')!.addEventListener('animationcancel', this.shuffleCardsAnimationStopped.bind(this));

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
            let pawnElement = dom.byId(pawnElementId)!;
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
            dom.byId('draw-card')!.classList.remove('hidden');
        } else {
            dom.byId('draw-card')!.classList.add('hidden');
        }

        if (cards.revealCard) {
            domClass.remove('reveal-card', 'hidden');
            this.drawCard(cards.revealCard);
        } else {
            dom.byId('reveal-card')!.classList.add('hidden');
        }

        if (cards.lastCard) {
            dom.byId('discard-card')!.classList.remove('hidden');
            dom.byId('discard-card')!.dataset['rank'] = cards.lastCard;
        } else {
            dom.byId('discard-card')!.classList.add('hidden');
        }
    }

    getLocationId(section: string, color: string, index: number | null): string {
        let destinationId = `${section}-${color}`;
        if (index !== null) destinationId += `-${index}`;
        return destinationId;
    }

    drawCard(rank: string) {
        let card = dom.byId('reveal-card')!;
        let frontFace = query('.front', card)[0]!;
        let backFace = query('.back', card)[0]!;

        domAttr.set(frontFace, 'data-rank', rank);
        domClass.remove(card, 'hidden');

        if (!this.bgaAnimationsActive()) {
            this.placeOnObject(card, 'reveal-pile');
            domStyle.set(card, 'transform', 'scale(1.8)');
            domStyle.set(frontFace, 'transform', 'none');
            domStyle.set(backFace, 'transform', 'rotateY(-180deg)');
            return;
        }

        this.placeOnObject(card, 'draw-pile');
        domStyle.set(frontFace, 'transform', 'rotateY(180deg)');
        domStyle.set(backFace, 'transform', 'none');

        const animations: DojoJS.Animation[] = [];
        animations.push(this.slideToObject(card, 'reveal-pile', 3000));
        const liftCard = fx.chain([
            dojo.animateProperty({
                node: card,
                properties: {transformScale: {start: 1, end: 1.4}, transformRotate: {start: -90, end: -90}},
                duration: 1000,
                easing: fx.easing.linear,
            }),
            dojo.animateProperty({
                node: card,
                properties: {transformScale: {start: 1.4, end: 1.6}, transformRotate: {start: -90, end: -45}},
                duration: 1000,
                easing: fx.easing.linear,
            }),
            dojo.animateProperty({
                node: card,
                properties: {transformScale: {start: 1.6, end: 1.8}, transformRotate: {start: -45, end: 0}},
                duration: 1000,
                easing: fx.easing.linear,
            }),
        ]);
        dojo.connect(liftCard, 'onAnimate', (p: any) =>
            domStyle.set(card, 'transform', `scale(${p.transformScale.slice(0, -2)}) rotate(${p.transformRotate.slice(0, -2)}deg)`)
        );
        animations.push(liftCard);
        animations.push(
            dojo.animateProperty({
                node: frontFace,
                properties: {rotateY: {start: 180, end: 0}},
                duration: 2000,
                delay: 1000,
                easing: fx.easing.linear,
                onAnimate: (p: any) => domStyle.set(frontFace, 'transform', `rotateY(${p.rotateY.slice(0, -2)}deg)`),
            })
        );
        animations.push(
            dojo.animateProperty({
                node: backFace,
                properties: {rotateY: {start: 0, end: -180}},
                duration: 2000,
                delay: 1000,
                easing: fx.easing.linear,
                onAnimate: (p: any) => domStyle.set(backFace, 'transform', `rotateY(${p.rotateY.slice(0, -2)}deg)`),
            })
        );

        this.playAllAnimations(animations);
    }

    shuffleCardsAnimationStopped(e: AnimationEvent): void {
        let shuffleCards = e.currentTarget as HTMLElement;
        if (shuffleCards.classList.contains('shuffling')) {
            shuffleCards.classList.remove('shuffling');
            shuffleCards.classList.add('hidden');
            dom.byId('draw-card')!.classList.remove('hidden');
        }
    }

    /**
     * Gets the position of a pawn at a given space on the board.
     *
     * @param pawnId The id of the pawn to be placed at the position. This can be omitted if the pawn is not at start or home.
     * @returns An array of the form [left, top]. The values are strings which can be used as CSS values.
     */
    getPawnCoorinatesInPixelsAtLocation(section: string, sectionColor: string, sectionIndex: string, pawnId: number): [number, number] {
        let locationId = this.getLocationId(section, sectionColor, sectionIndex === null ? null : parseInt(sectionIndex));
        let boardLocationStyle = window.getComputedStyle(dom.byId(locationId)!);
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
        query('.possible-move').removeClass('possible-move');
        query('.possible-move-destination').removeClass('possible-move-destination');
        query('.active-pawn').removeClass('active-pawn');
        query('.selected-move').removeClass('selected-move');
        query('[class*="for-pawn-"]').removeClass('for-pawn-0 for-pawn-1 for-pawn-2 for-pawn-3');
    }

    getMoveFromMoveArgs(move: SinglePawnMoveArgs): PawnMove {
        const pawn = dom.byId(`pawn-${move.playerId}-${move.pawnId}`)!;
        const startingLeft = this.parseDimensionToPx(window.getComputedStyle(pawn).left);
        const startingTop = this.parseDimensionToPx(window.getComputedStyle(pawn).top);

        const [offsetLeft, offsetTop] = this.getPawnOffsetInPixelsToLocation(pawn, move.pawnId, move.section, move.color, move.index);

        const distance = Math.sqrt(offsetLeft ** 2 + offsetTop ** 2) / 50;
        const duration = parseFloat(move.durationSecondsPerSquare) * 1000 * distance;
        return {
            pawn: pawn,
            moveType: move.moveType,
            durationMilliseconds: duration,
            startMoveAtPercentage: parseInt(move.startMoveAtPercentage ?? 0),
            startingLeft: startingLeft,
            startingTop: startingTop,
            offsetLeft: offsetLeft,
            offsetTop: offsetTop,
        };
    }

    movePawnDirectly(move: PawnMove): void {
        domStyle.set(move.pawn, {
            left: `${move.startingLeft + move.offsetLeft}px`,
            top: `${move.startingTop + move.offsetTop}px`,
            transform: '',
            zIndex: '',
        });
    }

    getPawnMoveAnimation(move: PawnMove, moveDelayReference: number = 0): DojoJS.Animation {
        return dojo.animateProperty({
            node: move.pawn,
            properties: {
                left: {start: move.startingLeft, end: move.startingLeft + move.offsetLeft},
                top: {start: move.startingTop, end: move.startingTop + move.offsetTop},
            },
            delay: (move.startMoveAtPercentage / 100) * moveDelayReference,
            duration: move.durationMilliseconds,
            easing: (x) => 0.56815808768082454 * Math.sin((0.7 * x - 0.3) * Math.PI) + 0.45964954842535866,
            beforeBegin: () => {
                domStyle.set(move.pawn, {zIndex: '2'});
                if (move.moveType === 'jump') {
                    domStyle.set(move.pawn, {
                        animationDuration: `${move.durationMilliseconds}ms`,
                    });
                    domClass.add(move.pawn, 'jumpping');
                }
            },
            onEnd: () => {
                domClass.remove(move.pawn, 'jumpping');
                domStyle.set(move.pawn, {zIndex: ''});
            },
        });
    }

    playAllAnimations(animations: DojoJS.Animation[]): Promise<void> {
        console.log('starting animation');
        return new Promise<void>((resolve) => {
            const animation = fx.combine(animations);
            dojo.connect(animation, 'onEnd', () => resolve());
            animation.play();
        });
    }

    shuffleArray(array: any[]): void {
        for (let i = array.length - 1; i >= 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
    }
}

require(['dojo/_base/declare'], function (declare) {
    declare('bgagame.sorry', GameGui, new Sorry());
});
