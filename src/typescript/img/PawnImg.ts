import * as dom from 'dojo/dom';
import * as gfx from 'dojox/gfx';
import * as Images from './Img';

export class PawnImg extends Images.ImgBase implements Images.Img {
    draw(options: {container: HTMLElement | Node | string; color: string}): void {
        const containerElement = dom.byId(options.container) as HTMLElement;
        const surface: gfx.Surface = gfx.createSurface(containerElement, 400, 400);

        const centerX = 200;
        const centerY = 200;

        let shapes: gfx.Shape[] = [];
        let colors: {shadow: string; mid: string; bright: string};
        if (options.color === 'red') {
            colors = {
                shadow: '#b30000',
                mid: '#990000',
                bright: '#ff0000',
            };
        } else if (options.color === 'blue') {
            colors = {
                shadow: '#0000b3',
                mid: '#000099',
                bright: '#4169e1',
            };
        } else if (options.color === 'green') {
            colors = {
                shadow: '#00b300',
                mid: '#009900',
                bright: '#32cd32',
            };
        } else {
            // yellow
            colors = {
                shadow: '#b58b00',
                mid: '#dec20b',
                bright: '#ffbf00',
            };
        }

        // Base
        shapes.push(
            surface
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
                        {offset: 0, color: colors.bright},
                        {offset: 1, color: colors.shadow},
                    ],
                })
        );

        // Top ball
        shapes.push(
            surface
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
                }).setStroke({color: 'black', width: 1})
        );

        // Draw a highlight on the top to enhance the 3D effect
        shapes.push(
            surface
                .createEllipse({
                    cx: centerX - 10,
                    cy: centerY - 90,
                    rx: 10,
                    ry: 20,
                })
                .setFill('#ffffff')
                .setStroke({color: 'transparent'})
        );

        shapes.forEach((shape) => surface.add(shape));

        this.resizeAllShapes(surface, containerElement.clientHeight / 400, containerElement.clientHeight / 400);
        surface.setDimensions(containerElement.clientWidth, containerElement.clientHeight);
    }
}
