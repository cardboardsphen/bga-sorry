import * as dom from 'dojo/dom';
import * as gfx from 'dojox/gfx';
import * as Images from './Img';

export class PawnImg extends Images.ImgBase implements Images.Img {
    draw(container: HTMLElement | Node | string): void {
        const containerElement = dom.byId(container) as HTMLElement;
        const surface: gfx.Surface = gfx.createSurface(containerElement, 400, 400);

        const centerX = 200;
        const centerY = 200;

        let shapes: gfx.Shape<{}>[] = [];

        // Draw the base (circle with shading)
        shapes.push(
            surface
                .createEllipse({
                    cx: centerX,
                    cy: centerY + 40, // Slightly offset for perspective
                    rx: 60, // Horizontal radius
                    ry: 30, // Vertical radius (to give it a flattened look)
                })
                .setFill({
                    type: 'linear',
                    x1: centerX - 60,
                    y1: centerY + 40,
                    x2: centerX + 60,
                    y2: centerY + 40,
                    colors: [
                        {offset: 0, color: '#b30000'}, // Darker red
                        {offset: 1, color: '#ff6666'}, // Lighter red
                    ],
                })
        );

        // Draw the stem (cylinder)
        shapes.push(
            surface
                .createEllipse({
                    cx: centerX,
                    cy: centerY - 20,
                    rx: 20, // Flattened perspective
                    ry: 60,
                })
                .setFill({
                    type: 'linear',
                    x1: centerX - 20,
                    y1: centerY - 80,
                    x2: centerX + 20,
                    y2: centerY + 40,
                    colors: [
                        {offset: 0, color: '#ff4d4d'}, // Bright red
                        {offset: 1, color: '#990000'}, // Shadowed red
                    ],
                })
        );

        // Draw the top (sphere)
        shapes.push(
            surface
                .createCircle({
                    cx: centerX,
                    cy: centerY - 80,
                    r: 30,
                })
                .setFill({
                    type: 'radial',
                    cx: centerX,
                    cy: centerY - 80,
                    r: 30,
                    colors: [
                        {offset: 0, color: '#ff6666'}, // Highlight
                        {offset: 1, color: '#b30000'}, // Shadow
                    ],
                })
        );

        // Draw a highlight on the top to enhance the 3D effect
        shapes.push(
            surface
                .createCircle({
                    cx: centerX - 10,
                    cy: centerY - 90,
                    r: 10,
                })
                .setFill('#ffffff')
                .setStroke({color: 'transparent'})
        );

        shapes.forEach((shape) => surface.add(shape));

        this.resizeAllShapes(surface, containerElement.clientHeight / 400, containerElement.clientHeight / 400);
        surface.setDimensions(containerElement.clientWidth, containerElement.clientHeight);
    }
}
