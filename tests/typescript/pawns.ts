// tsc-watch .\pawns.ts --module amd --outFile pawns.js

/// <amd-module name="bgagame/sorry/tests"/>
/// <reference path="../../src/typescript/types/all-bga-types.d.ts"/>

import * as domAttr from 'dojo/dom-attr';
import * as query from 'dojo/query';
import * as gfx from 'dojox/gfx';
import {PawnGraphic} from '../../src/typescript/graphics/PawnGraphic';

function runTest() {
    query('.pawn').forEach((pawn) => {
        const color = domAttr.get(pawn, 'data-color') as string;
        new PawnGraphic().draw({container: pawn, color: color});
        const pawnGraphicSurface = (pawn as any).gfxSurface as gfx.Surface;
        const pawnGraphic = pawnGraphicSurface.children[0]!;
        pawnGraphic.setTransform(gfx.matrix.scaleAt(10, 10, 0, 0));
    });
}

window.onload = () => {
    console.log('window is loaded');
    runTest();
};
