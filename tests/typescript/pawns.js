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
define("src/typescript/img/Img", ["require", "exports", "dojox/gfx"], function (require, exports, gfx) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.ImgBase = void 0;
    var ImgBase = /** @class */ (function () {
        function ImgBase() {
        }
        ImgBase.prototype.resizeAllShapes = function (container, scaleX, scaleY) {
            var _this = this;
            container.children.forEach(function (child) {
                alert(child.shape.type);
                if (child instanceof gfx.Group) {
                    _this.resizeAllShapes(child, scaleX, scaleY);
                }
                else {
                    if (child instanceof gfx.Circle) {
                        child.setShape({
                            cx: child.shape.cx * scaleX,
                            cy: child.shape.cy * scaleY,
                            r: child.shape.r * Math.min(scaleX, scaleY),
                        });
                    }
                    else if (child instanceof gfx.Rect) {
                        child.setShape({
                            x: child.shape.x * scaleX,
                            y: child.shape.y * scaleY,
                            width: child.shape.width * scaleX,
                            height: child.shape.height * scaleY,
                        });
                    }
                    else if (child instanceof gfx.Line) {
                        child.setShape({
                            x1: child.shape.x1 * scaleX,
                            y1: child.shape.y1 * scaleY,
                            x2: child.shape.x2 * scaleX,
                            y2: child.shape.y2 * scaleY,
                        });
                    }
                    else if (child instanceof gfx.Ellipse) {
                        child.setShape({
                            cx: child.shape.cx * scaleX,
                            cy: child.shape.cy * scaleY,
                            rx: child.shape.rx * scaleX,
                            ry: child.shape.ry * scaleY,
                        });
                    }
                    else if (child instanceof gfx.Polyline) {
                        child.setShape({
                            points: child.shape.points.map(function (point) { return ({
                                x: point.x * scaleX,
                                y: point.y * scaleY,
                            }); }),
                        });
                    }
                }
            });
        };
        return ImgBase;
    }());
    exports.ImgBase = ImgBase;
});
define("src/typescript/img/PawnImg", ["require", "exports", "dojo/dom", "dojox/gfx", "src/typescript/img/Img"], function (require, exports, dom, gfx, Images) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.PawnImg = void 0;
    var PawnImg = /** @class */ (function (_super) {
        __extends(PawnImg, _super);
        function PawnImg() {
            return _super !== null && _super.apply(this, arguments) || this;
        }
        PawnImg.prototype.draw = function (options) {
            var containerElement = dom.byId(options.container);
            var surface = gfx.createSurface(containerElement, 400, 400);
            var centerX = 200;
            var centerY = 200;
            var shapes = [];
            var colors;
            if (options.color === 'red') {
                colors = {
                    shadow: '#b30000',
                    mid: '#990000',
                    bright: '#ff0000',
                };
            }
            else if (options.color === 'blue') {
                colors = {
                    shadow: '#0000b3',
                    mid: '#000099',
                    bright: '#4169e1',
                };
            }
            else if (options.color === 'green') {
                colors = {
                    shadow: '#00b300',
                    mid: '#009900',
                    bright: '#32cd32',
                };
            }
            else {
                // yellow
                colors = {
                    shadow: '#b58b00',
                    mid: '#dec20b',
                    bright: '#ffbf00',
                };
            }
            // Base
            shapes.push(surface
                .createEllipse({
                cx: centerX,
                cy: centerY,
                rx: 200,
                ry: 180,
            })
                .setFill({
                type: 'linear',
                x1: centerX - 100,
                y1: centerY - 100,
                x2: centerX + 60,
                y2: centerY + 40,
                colors: [
                    { offset: 0, color: colors.bright },
                    { offset: 1, color: colors.shadow },
                ],
            }));
            // Top ball
            shapes.push(surface
                .createCircle({
                cx: centerX,
                cy: centerY,
                r: 250,
            })
                .setFill({
                type: 'radial',
                cx: 100,
                cy: 0,
                r: 200,
            }).setStroke({ color: 'black', width: 1 }));
            // Draw a highlight on the top to enhance the 3D effect
            shapes.push(surface
                .createEllipse({
                cx: centerX - 10,
                cy: centerY - 90,
                rx: 10,
                ry: 20,
            })
                .setFill('#ffffff')
                .setStroke({ color: 'transparent' }));
            shapes.forEach(function (shape) { return surface.add(shape); });
            this.resizeAllShapes(surface, containerElement.clientHeight / 400, containerElement.clientHeight / 400);
            surface.setDimensions(containerElement.clientWidth, containerElement.clientHeight);
        };
        return PawnImg;
    }(Images.ImgBase));
    exports.PawnImg = PawnImg;
});
// tsc-watch .\pawns.ts --module amd --outFile pawns.js
define("bgagame/sorry/tests", ["require", "exports", "dojo/dom-attr", "dojo/query", "src/typescript/img/PawnImg"], function (require, exports, domAttr, query, PawnImg_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    function runTest() {
        query('.pawn').forEach(function (pawn) {
            var color = domAttr.get(pawn, 'data-color');
            new PawnImg_1.PawnImg().draw({ container: pawn, color: color });
        });
    }
    window.onload = function () {
        console.log('window is loaded');
        runTest();
    };
});
