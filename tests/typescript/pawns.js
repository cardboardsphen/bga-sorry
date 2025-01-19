define("src/typescript/graphics/PawnGraphic", ["require", "exports", "dojo/dom", "dojox/gfx"], function (require, exports, dom, gfx) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.PawnGraphic = void 0;
    var PawnGraphic = /** @class */ (function () {
        function PawnGraphic() {
        }
        PawnGraphic.prototype.draw = function (options) {
            var containerElement = dom.byId(options.container);
            var surface = gfx.createSurface(containerElement, 40, 40);
            containerElement.gfxSurface = surface;
            var shapes = surface.createGroup();
            var centerX = 20;
            var centerY = 20;
            var colors;
            if (options.color === 'red') {
                colors = {
                    dark: '#790000',
                    shadow: '#d30000',
                    bright: '#ff0000', // player color
                    shine: '#ffbbbb',
                };
            }
            else if (options.color === 'blue') {
                colors = {
                    dark: '#000079',
                    shadow: '#0000d3',
                    bright: '#4169e1', // player color
                    shine: '#bbbbe1'
                };
            }
            else if (options.color === 'green') {
                colors = {
                    dark: '#007900',
                    shadow: '#10c010',
                    bright: '#32cd32', // player color
                    shine: '#bbffbb',
                };
            }
            else {
                // yellow
                colors = {
                    dark: '#453306',
                    shadow: '#d59b00',
                    bright: '#ffbf00', // player color
                    shine: '#ffffbb',
                };
            }
            // base and stem
            shapes
                .createEllipse({
                cx: centerX,
                cy: centerY,
                rx: 19,
                ry: 18.5,
            })
                .setFill({
                type: 'radial',
                cx: centerX - 14,
                cy: centerY - 4.5,
                r: 30,
                //x1: centerX - 10,
                //y1: centerY - 6,
                //x2: centerX + 16,
                //y2: centerY + 14,
                colors: [
                    { offset: 0, color: colors.shine },
                    { offset: 0.15, color: colors.bright },
                    { offset: 1, color: colors.shadow },
                ],
            })
                .setStroke({ color: colors.dark, width: 1 });
            // top ball
            shapes
                .createCircle({
                cx: centerX - 2,
                cy: centerY - 1,
                r: 5,
            })
                .setFill({
                type: 'radial',
                cx: centerX - 5,
                cy: centerY - 1,
                r: 7,
                colors: [
                    { offset: 0, color: colors.shine },
                    { offset: 0.1, color: colors.shine },
                    { offset: 0.3, color: colors.bright },
                    { offset: 1, color: colors.shadow },
                ]
            })
                .setStroke({ color: 'black', width: 1 })
                .setTransform(gfx.matrix.rotateAt(Math.PI / 10, centerX - 2, centerY - 1));
        };
        return PawnGraphic;
    }());
    exports.PawnGraphic = PawnGraphic;
});
// tsc-watch .\pawns.ts --module amd --outFile pawns.js
define("bgagame/sorry/tests", ["require", "exports", "dojo/dom-attr", "dojo/query", "dojox/gfx", "src/typescript/graphics/PawnGraphic"], function (require, exports, domAttr, query, gfx, PawnGraphic_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    function runTest() {
        query('.pawn').forEach(function (pawn) {
            var color = domAttr.get(pawn, 'data-color');
            new PawnGraphic_1.PawnGraphic().draw({ container: pawn, color: color });
            var pawnGraphicSurface = pawn.gfxSurface;
            var pawnGraphic = pawnGraphicSurface.children[0];
            pawnGraphic.setTransform(gfx.matrix.scaleAt(10, 10, 0, 0));
        });
    }
    window.onload = function () {
        console.log('window is loaded');
        runTest();
    };
});
