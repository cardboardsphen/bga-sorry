import * as dom from 'dojo/dom';
import * as gfx from 'dojox/gfx';

export class PawnGraphic {
    draw(options: {container: HTMLElement | Node | string; color: string}): void {
        const containerElement = dom.byId(options.container) as HTMLElement;
        const surface: gfx.Surface = gfx.createSurface(containerElement, 40, 40);
        (containerElement as any).gfxSurface = surface;
        const shapes: gfx.Group = surface.createGroup();

        const centerX = 20;
        const centerY = 20;

        let colors: {dark: string, shadow: string; bright: string, shine: string};
        if (options.color === 'red') {
            colors = {
                dark: '#790000',
                shadow: '#d30000',
                bright: '#ff0000', // player color
                shine: '#ffbbbb',
            };
        } else if (options.color === 'blue') {
            colors = {
                dark: '#000079',
                shadow: '#0000d3',
                bright: '#4169e1', // player color
                shine: '#bbbbe1'
            };
        } else if (options.color === 'green') {
            colors = {
                dark: '#007900',
                shadow: '#10c010',
                bright: '#32cd32', // player color
                shine: '#bbffbb',
            };
        } else {
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
                    {offset: 0, color: colors.shine},
                    {offset: 0.15, color: colors.bright},
                    {offset: 1, color: colors.shadow},
                ],
            })
            .setStroke({color: colors.dark, width: 1});

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
                    {offset: 0, color: colors.shine},
                    {offset: 0.1, color: colors.shine},
                    {offset: 0.3, color: colors.bright},
                    {offset: 1, color: colors.shadow},
                ]
            })
            .setStroke({color: 'black', width: 1})
            .setTransform(gfx.matrix.rotateAt(Math.PI / 10, centerX - 2, centerY - 1));
    }
}
