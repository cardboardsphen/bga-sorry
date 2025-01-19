/// <amd-module name="bgagame/sorry"/>
/// <reference path="./types/all-bga-types.d.ts"/>

import 'ebg/counter';
import * as GameGui from 'ebg/core/gamegui';

import * as dom from 'dojo/dom';
import * as domAttr from 'dojo/dom-attr';
import * as domClass from 'dojo/dom-class';
import * as domConstruct from 'dojo/dom-construct';
import * as domProp from 'dojo/dom-prop';
import * as domStyle from 'dojo/dom-style';
import * as fx from 'dojo/fx';
import * as gfx from 'dojox/gfx';
import * as gfxFx from 'dojox/gfx/fx';
import * as on from 'dojo/on';
import * as query from 'dojo/query';
import {PawnGraphic} from './graphics/PawnGraphic';

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
        if (this.isCurrentPlayerActive()) domClass.add('draw-pile', 'possible-move');
    }

    leavingState_drawCard(): void {
        this.clearPossibleMoves();
    }

    updateActionButtons_drawCard(args: any): void {
        if (this.isCurrentPlayerActive())
            this.addActionButton('draw-card-btn', 'Draw a Card', () => {
                this.clearPossibleMoves();
                this.bgaPerformAction('actDrawCard');
            });
    }

    enteringState_selectPawn(args: any): void {
        this.clearPossibleMoves();

        if (this.isCurrentPlayerActive()) {
            for (let pawnId in args.args.possibleMoves) {
                domClass.add(`pawn-${args.args.player}-${pawnId}`, 'possible-move');

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    let locationElement = dom.byId(locationId)!;
                    domClass.add(locationElement, 'possible-move-destination');
                    domClass.add(locationElement, `for-pawn-${pawnId}`);
                }
            }

            if (args.args.selectedMove) {
                domClass.add(`pawn-${args.args.player}-${args.args.selectedMove.id}`, 'selected-move');
                domClass.add(
                    this.getLocationId(args.args.selectedMove.section, args.args.selectedMove.color, args.args.selectedMove.index),
                    'selected-move'
                );
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
                domClass.add(`pawn-${args.args.player}-${pawnId}`, ['active-pawn', `for-pawn-${pawnId}`]);

                for (let location of args.args.possibleMoves[pawnId]) {
                    let locationId = this.getLocationId(location.section, location.color, location.index);
                    domClass.add(locationId, ['possible-move', `for-pawn-${pawnId}`]);
                }
            }

            if (args.args.selectedMove) {
                domClass.add(`pawn-${args.args.player}-${args.args.selectedMove.id}`, 'selected-move');
                domClass.add(
                    this.getLocationId(args.args.selectedMove.section, args.args.selectedMove.color, args.args.selectedMove.index),
                    'selected-move'
                );
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

        if (!this.bgaAnimationsActive() || domClass.contains(card, 'hidden')) {
            domAttr.set('discard-card', 'data-rank', args.rank);
            domClass.remove('discard-card', 'hidden');
            domClass.add(card, 'hidden');
            return;
        }

        domStyle.set(card, 'transform', '');
        const animations: DojoJS.Animation[] = [];
        animations.push(this.slideToObject(card, 'discard-pile', 2000));
        animations.push(
            dojo.animateProperty({
                node: card,
                properties: {
                    transformRotate: {start: 0, end: -90, units: 'deg'},
                    translateZ: {start: 100, end: 0, unts: 'px'},
                    transformScale: {start: 1.8, end: 1, units: ' '},
                },
                duration: 2000,
                easing: fx.easing.linear,
                onAnimate(p: any) {
                    domStyle.set(card, 'transform', `scale(${p.transformScale}) translateZ(${p.translateZ}) rotate(${p.transformRotate})`);
                },
                onEnd() {
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
        domClass.add('reveal-card', 'hidden');

        if (!this.bgaAnimationsActive()) {
            domClass.add('shuffle-cards', 'hidden');
            domClass.remove('draw-card', 'hidden');
            domClass.add('discard-card', 'hidden');
            return;
        }

        const cardPile = dom.byId('shuffle-cards')!;
        const cards = query('.shuffle-card', cardPile);
        const fronts = query('.card.front', cardPile);
        const backs = query('.card.back', cardPile);

        // shuffle shuffle cards
        let ranks = ['1', '2', '3', '4', '5', '7', '8', '10', '11', '12', 'sorry'];
        ranks = ranks.filter((r) => r != args.rank);
        this.shuffleArray(ranks);
        ranks.push(args.rank);
        for (let i = 0; i < 11; i++) domAttr.set(fronts[i]!, 'data-rank', ranks[i]!);

        cards.style('transform', '');
        cards.style('zIndex', '3');
        backs.style('transform', 'rotateY(180deg)');
        fronts.style('transform', '');
        domClass.remove(cardPile, 'hidden');
        this.placeOnObject(cardPile, 'discard-pile');
        domClass.add('discard-card', 'hidden');

        let animations: DojoJS.Animation[] = [];
        animations.push(this.slideToObject(cardPile, 'draw-pile', 3000));
        cards.forEach((card) => {
            const front = query('.front', card)[0]!;
            const back = query('.back', card)[0]!;

            let maxAngle = Math.floor(Math.random() * 800);
            let finalAngle = Math.ceil(maxAngle / 360) * 360 + 360;
            if (Math.random() < 0.5) {
                maxAngle *= -1;
                finalAngle *= -1;
            }
            const maxX = Math.floor(Math.random() * 800) - 400;
            const maxY = Math.floor(Math.random() * 800) - 400;
            animations.push(
                fx.chain([
                    dojo.animateProperty({
                        node: card,
                        properties: {
                            transformRotate: {start: 0, end: maxAngle, units: 'deg'},
                            translateX: {start: 0, end: maxX, units: 'px'},
                            translateY: {start: 0, end: maxY, units: 'px'},
                            translateZ: {start: 0, end: 50, units: 'px'},
                            transformScale: {start: 1, end: 1.4, units: ' '},
                        },
                        duration: 1000,
                        easing: fx.easing.sineIn,
                        onAnimate(p: any) {
                            domStyle.set(
                                card,
                                'transform',
                                `translate3d(${p.translateX}, ${p.translateY}, ${p.translateZ}) scale(${p.transformScale}) rotate(${p.transformRotate})`
                            );
                        },
                    }),
                    dojo.animateProperty({
                        node: card,
                        duration: 0,
                        onEnd() {
                            domStyle.set(card, 'zIndex', `${Math.ceil(Math.random() * 15) - 4}`);
                        },
                    }),
                    dojo.animateProperty({
                        node: card,
                        properties: {
                            transformRotate: {start: maxAngle, end: finalAngle, units: 'deg'},
                            translateX: {start: maxX, end: 0, units: 'px'},
                            translateY: {start: maxY, end: 0, units: 'px'},
                            translateZ: {start: 50, end: 100, units: 'px'},
                            transformScale: {start: 1.4, end: 1.8, units: ' '},
                        },
                        duration: 1400,
                        easing: fx.easing.sineOut,
                        onAnimate(p: any) {
                            domStyle.set(
                                card,
                                'transform',
                                `translate3d(${p.translateX}, ${p.translateY}, ${p.translateZ}) scale(${p.transformScale}) rotate(${p.transformRotate})`
                            );
                        },
                    }),
                    dojo.animateProperty({
                        node: card,
                        properties: {
                            translateZ: {start: 100, end: 0, units: 'px'},
                            transformScale: {start: 1.8, end: 1, units: ' '},
                        },
                        duration: 600,
                        easing: fx.easing.quadIn,
                        onAnimate(p: any) {
                            domStyle.set(card, 'transform', `translate3d(0, 0, ${p.translateZ}) scale(${p.transformScale})`);
                        },
                    }),
                ])
            );
            animations.push(
                dojo.animateProperty({
                    node: front,
                    properties: {rotateY: {start: 0, end: -180, units: 'deg'}},
                    duration: 600,
                    delay: 2400,
                    easing: fx.easing.quadIn,
                    onAnimate(p: any) {
                        domStyle.set(front, 'transform', `rotateY(${p.rotateY})`);
                    },
                })
            );
            animations.push(
                dojo.animateProperty({
                    node: back,
                    properties: {rotateY: {start: 180, end: 0, units: 'deg'}},
                    duration: 600,
                    delay: 2400,
                    easing: fx.easing.quadIn,
                    onAnimate(p: any) {
                        domStyle.set(back, 'transform', `rotateY(${p.rotateY})`);
                    },
                })
            );
        });

        await this.playAllAnimations(animations).finally(() => {
            domClass.remove('draw-card', 'hidden');
            domClass.add(cardPile, 'hidden');
        });
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

        await this.playAllAnimations(animations).finally(() => {
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
        domConstruct.place(
            `<div id="board">
                 <div id="pawns"></div>
             </div>`,
            'game_play_area',
            'last'
        );

        const board = dom.byId('board')!;

        // start spaces
        domConstruct.place(
            `<div id="start-red" class="circle"></div>
             <div id="start-blue" class="circle"></div>
             <div id="start-yellow" class="circle"></div>
             <div id="start-green" class="circle"></div>`,
            board,
            'last'
        );

        // outer rows
        for (let y = 0; y < 15; y++) {
            const offset = y * 50;
            domConstruct.place(
                `<div id="margin-red-${y}" class="square" style="left: ${51 + offset}px; top: 51px;"></div>
                 <div id="margin-blue-${y}" class="square" style="left: 801px; top: ${51 + offset}px;"></div>
                 <div id="margin-yellow-${y}" class="square" style="left: ${801 - offset}px; top: 801px;"></div>
                 <div id="margin-green-${y}" class="square" style="left: 51px; top: ${801 - offset}px;"></div>`,
                board,
                'last'
            );
        }

        // safety zones
        for (let y = 0; y < 5; y++) {
            const offset = y * 50;
            domConstruct.place(
                `<div id="safety-red-${y}" class="square" style="left: 151px; top: ${101 + offset}px;"></div>
                 <div id="safety-blue-${y}" class="square" style="left: ${751 - offset}px; top: 151px;"></div>
                 <div id="safety-yellow-${y}" class="square" style="left: 701px; top: ${751 - offset}px;"></div>
                 <div id="safety-green-${y}" class="square" style="left: ${101 + offset}px; top: 701px;"></div>`,
                board,
                'last'
            );
        }

        // homes
        domConstruct.place(
            `<div id="home-red" class="circle"></div>
             <div id="home-blue" class="circle"></div>
             <div id="home-yellow" class="circle"></div>
             <div id="home-green" class="circle"></div>`,
            board,
            'last'
        );
        query('.square, #home-red, #home-blue, #home-yellow, #home-green').on('click', (e) => {
            const clickedSquare = e.currentTarget as HTMLElement;
            if (domClass.contains(clickedSquare, 'possible-move') && this.checkAction('actSelectSquare', true))
                this.bgaPerformAction('actSelectSquare', {
                    squareId: clickedSquare.id,
                });
        });

        // discard pile
        domConstruct.place(
            `<div id="discard-pile" class="card-pile">
                 <div class="card hidden" id="discard-card"></div>
             </div>`,
            board,
            'last'
        );

        // draw pile
        domConstruct.place(
            `<div id="draw-pile" class="card-pile">
                 <div class="card hidden" data-rank="back" id="draw-card"></div>
             </div>`,
            board,
            'last'
        );
        query('#draw-pile').on('click', (e) => {
            if (domClass.contains(e.currentTarget as HTMLElement, 'possible-move')) {
                this.clearPossibleMoves();
                this.bgaPerformAction('actDrawCard');
            }
        });

        // reveal pile
        domConstruct.place(`<div id="reveal-pile" class="card-pile"></div>`, board, 'last');

        // reveal card
        domConstruct.place(
            `<div class="hidden" id="reveal-card">
                 <div class="card back" data-rank="back"></div>
                 <div class="card front"></div>
             </div>`,
            board,
            'last'
        );

        // cards for shuffle animation
        domConstruct.place(`<div id="shuffle-cards" class="hidden"></div>`, board, 'last');
        for (let rank of [1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 'sorry']) {
            domConstruct.place(
                `<div class="shuffle-card">
                     <div class="card back" data-rank="back"></div>
                     <div class="card front" data-rank="${rank}"></div>
                 </div>`,
                'shuffle-cards',
                'last'
            );
        }

        // create pawns
        for (let pawn of gamedatas.pawns) {
            const pawnElementId = `pawn-${pawn.player}-${pawn.id}`;

            const pawnElement = domConstruct.place(`<div class="pawn" data-color="${pawn.color}" id="${pawnElementId}"></div>`, 'pawns', 'last');
            on(pawnElement, 'click', (e) => {
                const clickedPawn = e.currentTarget as HTMLElement;
                if (domClass.contains(clickedPawn, 'possible-move') && this.checkAction('actSelectPawn', true))
                    this.bgaPerformAction('actSelectPawn', {pawnId: clickedPawn.id.match(/\d+$/)});
            });

            const color = domAttr.get(pawnElement, 'data-color') as string;
            new PawnGraphic().draw({container: pawnElement, color: color});
        }
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
            domClass.remove('draw-card', 'hidden');
        } else {
            domClass.add('draw-card', 'hidden');
        }

        if (cards.revealCard) {
            domClass.remove('reveal-card', 'hidden');
            this.drawCard(cards.revealCard, false);
        } else {
            domClass.add('reveal-card', 'hidden');
        }

        if (cards.lastCard) {
            domClass.remove('discard-card', 'hidden');
            domAttr.set('discard-card', 'data-rank', cards.lastCard);
        } else {
            domClass.add('discard-card', 'hidden');
        }
    }

    getLocationId(section: string, color: string, index: number | null): string {
        let destinationId = `${section}-${color}`;
        if (index !== null) destinationId += `-${index}`;
        return destinationId;
    }

    drawCard(rank: string, animate: boolean = true) {
        let card = dom.byId('reveal-card')!;
        let frontFace = query('.front', card)[0]!;
        let backFace = query('.back', card)[0]!;

        domAttr.set(frontFace, 'data-rank', rank);
        domClass.remove(card, 'hidden');

        if (!this.bgaAnimationsActive() || !animate) {
            this.placeOnObject(card, 'reveal-pile');
            domStyle.set(card, 'transform', 'translateZ(100px) scale(1.8)');
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
                properties: {
                    transformScale: {start: 1, end: 1.4, units: ' '},
                    translateZ: {start: 0, end: 33, units: 'px'},
                    transformRotate: {start: -90, end: -90, units: 'deg'},
                },
                duration: 1000,
                easing: fx.easing.linear,
            }),
            dojo.animateProperty({
                node: card,
                properties: {
                    transformScale: {start: 1.4, end: 1.6, units: ' '},
                    translateZ: {start: 33, end: 67, units: 'px'},
                    transformRotate: {start: -90, end: -45, units: 'deg'},
                },
                duration: 1000,
                easing: fx.easing.linear,
            }),
            dojo.animateProperty({
                node: card,
                properties: {
                    transformScale: {start: 1.6, end: 1.8, units: ' '},
                    translateZ: {start: 67, end: 100, units: 'px'},
                    transformRotate: {start: -45, end: 0, units: 'deg'},
                },
                duration: 1000,
                easing: fx.easing.linear,
            }),
        ]);
        dojo.connect(liftCard, 'onAnimate', (p: any) =>
            domStyle.set(card, 'transform', `scale(${p.transformScale}) translateZ(${p.translateZ}) rotate(${p.transformRotate})`)
        );
        animations.push(liftCard);
        animations.push(
            dojo.animateProperty({
                node: frontFace,
                properties: {rotateY: {start: 180, end: 0, units: 'deg'}},
                duration: 2000,
                delay: 1000,
                easing: fx.easing.linear,
                onAnimate(p: any) {
                    domStyle.set(frontFace, 'transform', `rotateY(${p.rotateY})`);
                },
            })
        );
        animations.push(
            dojo.animateProperty({
                node: backFace,
                properties: {rotateY: {start: 0, end: -180, units: 'deg'}},
                duration: 2000,
                delay: 1000,
                easing: fx.easing.linear,
                onAnimate(p: any) {
                    domStyle.set(backFace, 'transform', `rotateY(${p.rotateY})`);
                },
            })
        );

        this.playAllAnimations(animations);
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

        const tempDiv = domConstruct.create('div', {position: 'absolute', left: dimension}, document.body);
        const pixels = domProp.get(tempDiv, 'offsetLeft');
        domConstruct.destroy(tempDiv);

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

        const duration =
            move.durationSeconds !== null
                ? parseFloat(move.durationSeconds) * 1000
                : parseFloat(move.durationSecondsPerSquare) * 1000 * (Math.sqrt(offsetLeft ** 2 + offsetTop ** 2) / 50);

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
        let moveAnimations = [
            dojo.animateProperty({
                node: move.pawn,
                properties: {
                    left: {start: move.startingLeft, end: move.startingLeft + move.offsetLeft},
                    top: {start: move.startingTop, end: move.startingTop + move.offsetTop},
                },
                delay: (move.startMoveAtPercentage / 100) * moveDelayReference,
                duration: move.durationMilliseconds,
                easing(x) {
                    return 0.56815808768082454 * Math.sin((0.7 * x - 0.3) * Math.PI) + 0.45964954842535866;
                },
                onBegin() { domStyle.set(move.pawn, {zIndex: '4'}); },
                onEnd() { domStyle.set(move.pawn, {zIndex: ''}); },
            })
        ];

        if (move.moveType === 'jump') {
            moveAnimations.push(
                fx.chain([
                    dojo.animateProperty({
                        node: move.pawn,
                        properties: {
                            translateZ: {start: 0, end: 50, units: 'px'},
                        },
                        delay: (move.startMoveAtPercentage / 100) * moveDelayReference,
                        duration: move.durationMilliseconds / 2,
                        onAnimate(p: any) {
                            domStyle.set(move.pawn, 'transform', `translateZ(${p.translateZ})`);
                        },
                    }),
                    dojo.animateProperty({
                        node: move.pawn,
                        properties: {
                            translateZ: {start: 50, end: 0, units: 'px'},
                        },
                        duration: move.durationMilliseconds / 2,
                        onAnimate(p: any) {
                            domStyle.set(move.pawn, 'transform', `translateZ(${p.translateZ})`);
                        },
                    }),
                ])
            );
            const pawnGraphicSurface = (move.pawn as any).gfxSurface as gfx.Surface;
            const pawnGraphic = pawnGraphicSurface.children[0]!;
            moveAnimations.push(
                fx.chain([
                    gfxFx.animateTransform({
                        shape: pawnGraphic,
                        transform: [
                            { name: "scaleAt", start: [1, 1, 20, 20], end: [1.25, 1.25, 20, 20] }
                        ],
                        delay: (move.startMoveAtPercentage / 100) * moveDelayReference,
                        duration: move.durationMilliseconds / 2,
                    }),
                    gfxFx.animateTransform({
                        shape: pawnGraphic,
                        transform: [
                            { name: "scaleAt", start: [1.25, 1.25, 20, 20], end: [1, 1, 20, 20] }
                        ],
                        duration: move.durationMilliseconds / 2,
                        onEnd() {
                            pawnGraphic.setTransform(null);
                        },
                    })
                ])
            );
        }
        return fx.combine(moveAnimations);
    }

    playAllAnimations(animations: DojoJS.Animation[]): Promise<void> {
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
