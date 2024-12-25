var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g = Object.create((typeof Iterator === "function" ? Iterator : Object).prototype);
    return g.next = verb(0), g["throw"] = verb(1), g["return"] = verb(2), typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
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
define("bgagame/sorry", ["require", "exports", "ebg/core/gamegui", "dojo", "dojo/_base/declare", "ebg/counter"], function (require, exports, GameGui) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    /**
     * Client implementation of Sorry.
     */
    var Sorry = /** @class */ (function (_super) {
        __extends(Sorry, _super);
        function Sorry() {
            var _this = _super.call(this) || this;
            console.log('sorry constructor');
            return _this;
        }
        Sorry.prototype.setup = function (gamedatas) {
            var _this = this;
            console.log('Starting game setup');
            this.buildBoard(gamedatas);
            this.setupNotifications = function () {
                console.log('notifications subscriptions setup');
                _this.bgaSetupPromiseNotifications({ prefix: 'notification_' });
            };
            this.setupNotifications();
            console.log('Ending game setup');
        };
        Sorry.prototype.onEnteringState = function (stateName, args) {
            console.log('Entering state: ' + stateName, args);
            var methodName = 'enteringState_' + stateName;
            if (typeof this[methodName] === 'function') {
                console.log('Calling ' + methodName);
                this[methodName](args);
            }
        };
        Sorry.prototype.onLeavingState = function (stateName) {
            console.log('Leaving state: ' + stateName);
            var methodName = 'leavingState_' + stateName;
            if (typeof this[methodName] === 'function') {
                console.log('Calling ' + methodName);
                this[methodName]();
            }
        };
        Sorry.prototype.onUpdateActionButtons = function (stateName, args) {
            console.log('onUpdateActionButtons: ' + stateName, args);
            var methodName = 'updateActionButtons_' + stateName;
            if (typeof this[methodName] === 'function') {
                console.log('Calling ' + methodName);
                this[methodName](args);
            }
        };
        ///////////////////////////////////////////////////
        //// State Handling
        Sorry.prototype.enteringState_drawCard = function (args) {
            this.clearPossibleMoves();
            if (this.isCurrentPlayerActive())
                document.getElementById('draw-pile').classList.add('possible-move');
        };
        Sorry.prototype.leavingState_drawCard = function () {
            this.clearPossibleMoves();
        };
        Sorry.prototype.updateActionButtons_drawCard = function (args) {
            var _this = this;
            if (this.isCurrentPlayerActive())
                this.addActionButton('draw-card-btn', 'Draw a Card', function () { return _this.bgaPerformAction('actDrawCard'); }, undefined, undefined, args.color);
        };
        Sorry.prototype.enteringState_selectPawn = function (args) {
            this.clearPossibleMoves();
            if (this.isCurrentPlayerActive()) {
                for (var pawnId in args.args.possibleMoves)
                    document.getElementById("pawn-".concat(args.args.player, "-").concat(pawnId)).classList.add('possible-move');
            }
        };
        Sorry.prototype.leavingState_selectPawn = function () {
            this.clearPossibleMoves();
        };
        Sorry.prototype.updateActionButtons_selectPawn = function (args) {
            var _this = this;
            if (!this.isCurrentPlayerActive())
                return;
            if (args.canSkip)
                this.addActionButton('skip-turn-btn', 'Skip Turn', function () {
                    return _this.bgaPerformAction('actSelectPawn', {
                        pawnId: -1,
                    });
                }, undefined, undefined, args.color);
        };
        Sorry.prototype.enteringState_selectSquare = function (args) { };
        Sorry.prototype.leavingState_selectSquare = function () { };
        Sorry.prototype.updateActionButtons_selectSquare = function (args) { };
        ///////////////////////////////////////////////////
        //// Notification Handling
        Sorry.prototype.notification_drawCard = function (args) {
            return __awaiter(this, void 0, void 0, function () {
                var card, cardFront;
                return __generator(this, function (_a) {
                    this.clearPossibleMoves();
                    card = document.getElementById('reveal-card');
                    cardFront = card.querySelector('.front');
                    cardFront.dataset['rank'] = args.rank;
                    card.removeAttribute('class');
                    card.classList.add('revealing');
                    if (args.hasMoreToDraw)
                        document.getElementById('draw-card').classList.remove('hidden');
                    else
                        document.getElementById('draw-card').classList.add('hidden');
                    return [2 /*return*/];
                });
            });
        };
        Sorry.prototype.notification_discardCard = function (args) {
            return __awaiter(this, void 0, void 0, function () {
                var card, cardFront;
                return __generator(this, function (_a) {
                    card = document.getElementById('reveal-card');
                    cardFront = card.querySelector('.front');
                    cardFront.dataset['rank'] = args.rank;
                    if (card.classList.contains('revealed')) {
                        card.removeAttribute('class');
                        card.classList.add('discarding');
                    }
                    else {
                        document.getElementById('discard-card').dataset['rank'] = args.rank;
                        document.getElementById('discard-card').classList.remove('hidden');
                    }
                    return [2 /*return*/];
                });
            });
        };
        Sorry.prototype.notification_shuffleDeck = function (args) {
            return __awaiter(this, void 0, void 0, function () {
                return __generator(this, function (_a) {
                    this.shuffleCards();
                    return [2 /*return*/];
                });
            });
        };
        ///////////////////////////////////////////////////
        //// Utility functions
        Sorry.prototype.buildBoard = function (gamedatas) {
            var _this = this;
            document.getElementById('game_play_area').insertAdjacentHTML('beforeend', "\n                <div id=\"board\">\n                    <div id=\"pawns\"></div>\n                </div>\n            ");
            // TODO: Set up your game interface here, according to "gamedatas"
            var board = document.getElementById('board');
            // start spaces
            board.insertAdjacentHTML('beforeend', "\n                <div id=\"start-red\" class=\"circle\"></div>\n                <div id=\"start-blue\" class=\"circle\"></div>\n                <div id=\"start-yellow\" class=\"circle\"></div>\n                <div id=\"start-green\" class=\"circle\"></div>\n            ");
            // outer rows
            for (var y = 0; y < 15; y++) {
                var offset = y * 50;
                board.insertAdjacentHTML('beforeend', "\n                    <div id=\"margin-red-".concat(y, "\" class=\"square\" style=\"left: ").concat(51 + offset, "px; top: 51px;\"></div>\n                    <div id=\"margin-blue-").concat(y, "\" class=\"square\" style=\"left: 801px; top: ").concat(51 + offset, "px;\"></div>\n                    <div id=\"margin-yellow-").concat(y, "\" class=\"square\" style=\"left: ").concat(801 - offset, "px; top: 801px;\"></div>\n                    <div id=\"margin-green-").concat(y, "\" class=\"square\" style=\"left: 51px; top: ").concat(801 - offset, "px;\"></div>\n                "));
            }
            // safety zones
            for (var y = 0; y < 5; y++) {
                var offset = y * 50;
                board.insertAdjacentHTML('beforeend', "\n                    <div id=\"safety-red-".concat(y, "\" class=\"square\" style=\"left: 151px; top: ").concat(101 + offset, "px;\"></div>\n                    <div id=\"safety-blue-").concat(y, "\" class=\"square\" style=\"left: ").concat(751 - offset, "px; top: 151px;\"></div>\n                    <div id=\"safety-yellow-").concat(y, "\" class=\"square\" style=\"left: 701px; top: ").concat(751 - offset, "px;\"></div>\n                    <div id=\"safety-green-").concat(y, "\" class=\"square\" style=\"left: ").concat(101 + offset, "px; top: 701px;\"></div>\n                "));
            }
            // homes
            board.insertAdjacentHTML('beforeend', "\n                <div id=\"home-red\" class=\"circle\"></div>\n                <div id=\"home-blue\" class=\"circle\"></div>\n                <div id=\"home-yellow\" class=\"circle\"></div>\n                <div id=\"home-green\" class=\"circle\"></div>\n            ");
            // discard pile
            board.insertAdjacentHTML('beforeend', "<div id=\"discard-pile\" class=\"card-pile\">\n                <div class=\"card hidden\" id=\"discard-card\"></div>\n            </div>");
            // draw pile
            board.insertAdjacentHTML('beforeend', "<div id=\"draw-pile\" class=\"card-pile\">\n                <div class=\"card hidden\" data-rank=\"back\" id=\"draw-card\"></div>\n            </div>");
            document.getElementById('draw-pile').addEventListener('click', function (e) {
                if (e.currentTarget.classList.contains('possible-move') && _this.checkAction('actDrawCard', true))
                    _this.bgaPerformAction('actDrawCard');
            });
            // reveal card
            board.insertAdjacentHTML('beforeend', "<div class=\"hidden\" id=\"reveal-card\">\n                <div class=\"card back\" data-rank=\"back\"></div>\n                <div class=\"card front\"></div>\n            </div>");
            document.getElementById('reveal-card').addEventListener('animationend', this.revealCardAnmiationStopped.bind(this));
            document.getElementById('reveal-card').addEventListener('animationcancel', this.revealCardAnmiationStopped.bind(this));
            document.getElementById('discard-pile').insertAdjacentHTML('beforeend', "<div id=\"shuffle-cards\" class=\"hidden\"></div>");
            for (var rank_1 in [1, 2, 3, 4, 5, 7, 8, 10, 11, 12, 'sorry'])
                document.getElementById('shuffle-cards').insertAdjacentHTML('beforeend', "<div class=\"card\" data-rank=\"".concat(rank_1, "\"></div>"));
            // create pawns
            for (var i in gamedatas.pawns) {
                var pawn = gamedatas.pawns[i];
                var pawnElementId = "pawn-".concat(pawn.player, "-").concat(pawn.id);
                document
                    .getElementById('pawns')
                    .insertAdjacentHTML('beforeend', "<div class=\"pawn\" data-color=\"".concat(pawn.color, "\" id=\"").concat(pawnElementId, "\"></div>"));
                this.jumpPawnToLocation(pawnElementId, pawn.id, pawn.boardSection, pawn.boardSectionColor, pawn.boardSectionIndex);
            }
            document.querySelectorAll('.pawn').forEach(function (pawn) {
                return pawn.addEventListener('click', function (e) {
                    var clickedPawn = e.currentTarget;
                    if (clickedPawn.classList.contains('possible-move') && _this.checkAction('actSelectPawn', true))
                        _this.bgaPerformAction('actSelectPawn', {
                            pawnId: clickedPawn.id.match(/\d+$/),
                        });
                });
            });
            // show the visible cards
            if (gamedatas.nextCard) {
                document.getElementById('draw-card').classList.remove('hidden');
            }
            if (gamedatas.revealCard) {
                document.getElementById('reveal-card').removeAttribute('class');
                document.getElementById('reveal-card').classList.add('revealed');
                document.querySelector('#reveal-card .front').dataset['rank'] = gamedatas.revealCard;
            }
            if (gamedatas.lastCard) {
                document.getElementById('discard-card').classList.remove('hidden');
                document.getElementById('discard-card').dataset['rank'] = gamedatas.lastCard;
            }
        };
        Sorry.prototype.revealCardAnmiationStopped = function (e) {
            var card = e.currentTarget;
            if (card.classList.contains('revealing')) {
                card.removeAttribute('class');
                card.classList.add('revealed');
            }
            if (card.classList.contains('discarding')) {
                var rank_2 = card.querySelector('.front').dataset['rank'];
                document.getElementById('discard-card').dataset['rank'] = rank_2;
                document.getElementById('discard-card').classList.remove('hidden');
                card.removeAttribute('class');
                card.classList.add('hidden');
            }
        };
        Sorry.prototype.jumpPawnToLocation = function (pawnElementId, pawnId, section, sectionColor, sectionIndex) {
            var pawnElement = document.getElementById(pawnElementId);
            var locationId = "".concat(section, "-").concat(sectionColor);
            if (sectionIndex)
                locationId += "-".concat(sectionIndex);
            // fan out the pawns if on start or home
            if (section == 'start' || section == 'home') {
                var boardLocationStyle = window.getComputedStyle(document.getElementById(locationId));
                var left = this.parseDimensionToPx(boardLocationStyle.left);
                var top_1 = this.parseDimensionToPx(boardLocationStyle.top);
                var leftOffset = Math.floor(pawnId / 2) * 40;
                var topOffset = (pawnId % 2) * 40;
                pawnElement.style.left = "".concat(left + 9 + leftOffset, "px");
                pawnElement.style.top = "".concat(top_1 + 9 + topOffset, "px");
                return;
            }
            this.placeOnObject(pawnElementId, locationId);
        };
        Sorry.prototype.parseDimensionToPx = function (dimension) {
            var _a = dimension.match(/([\d\.]+)([a-z%]+)/), value = _a[1], unit = _a[2];
            if (!value || !unit) {
                this['sendMessage']('error', "Could not parse dimension to pixels: ".concat(dimension));
                return 0;
            }
            var numericValue = Number.parseFloat(value);
            if (unit === 'px') {
                return numericValue;
            }
            var tempDiv = document.createElement('div');
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = dimension;
            document.body.appendChild(tempDiv);
            var pixels = tempDiv.offsetLeft;
            document.body.removeChild(tempDiv);
            return pixels;
        };
        Sorry.prototype.clearPossibleMoves = function () {
            document.querySelectorAll('.possible-move').forEach(function (div) { return div.classList.remove('possible-move'); });
        };
        Sorry.prototype.shuffleCards = function () {
            return;
            document.getElementById('reveal-card').classList.add('hidden');
            // have 12 shuffle cards, one of each rank
            // animate them to spin and flip and move to draw pile when set to class='shuffle'
            document.querySelectorAll('.shuffle-card').forEach(function (card) {
                card.classList.add('shuffle');
                card.classList.remove('hidden');
            });
            document.getElementById('discard-card').classList.add('hidden');
            setTimeout(function () {
                document.getElementById('draw-card').dataset['rank'] = 'back';
                document.getElementById('draw-card').classList.remove('hidden');
                document.querySelectorAll('.shuffle-card').forEach(function (card) {
                    card.classList.add('hidden');
                    card.classList.remove('shuffle');
                }, 1000);
            });
        };
        return Sorry;
    }(GameGui));
    exports.default = Sorry;
    define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui', 'ebg/counter'], dojo.declare('bgagame.sorry', ebg.core.gamegui, new Sorry()));
});
//# sourceMappingURL=sorry.js.map