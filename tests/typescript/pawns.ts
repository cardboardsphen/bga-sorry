// tsc-watch .\pawns.ts --module amd --outFile pawns.js

/// <amd-module name="bgagame/sorry/tests"/>
/// <reference path="../../src/typescript/types/all-bga-types.d.ts"/>

import * as domAttr from 'dojo/dom-attr';
import * as query from 'dojo/query';
import {PawnImg} from '../../src/typescript/img/PawnImg';

function runTest() {
    query('.pawn').forEach((pawn) => {
        const color = domAttr.get(pawn, 'data-color') as string;
        new PawnImg().draw({container: pawn, color: color});
    });
}

window.onload = () => {
    console.log('window is loaded');
    runTest();
};
