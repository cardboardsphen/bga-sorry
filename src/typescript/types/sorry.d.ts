type SinglePawnMoveArgs = {
    moveType: string;
    durationSeconds: string;
    durationSecondsPerSquare: string;
    startMoveAtPercentage: string;
    playerId: number;
    pawnId: number;
    section: string;
    color: string;
    index: string;
};
type PawnMoveArgs = {
    move: SinglePawnMoveArgs;
    otherMoves: SinglePawnMoveArgs[];
};
type PawnMove = {
    pawn: HTMLElement;
    moveType: string;
    durationMilliseconds: number;
    startMoveAtPercentage: number;
    startingLeft: number;
    startingTop: number;
    offsetLeft: number;
    offsetTop: number;
};
