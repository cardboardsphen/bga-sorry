import * as gfx from 'dojox/gfx';

export interface Img {
    draw(container: HTMLElement | Node | string): void;
    /**
     * Recursively resizes all shapes in a container (surface or group).
     *
     * @param container - The gfx container (Surface or Group) to resize.
     * @param scaleX - The scaling factor for the x-axis.
     * @param scaleY - The scaling factor for the y-axis.
     */
}

export class ImgBase {
    resizeAllShapes(container: gfx.Surface | gfx.Group, scaleX: number, scaleY: number): void {
        container.children.forEach((child) => {
            if (child instanceof gfx.Group) {
                this.resizeAllShapes(child, scaleX, scaleY);
            } else {
                if (child instanceof gfx.Circle) {
                    child.setShape({
                        cx: child.shape.cx * scaleX,
                        cy: child.shape.cy * scaleY,
                        r: child.shape.r * Math.min(scaleX, scaleY),
                    });
                } else if (child instanceof gfx.Rect) {
                    child.setShape({
                        x: child.shape.x * scaleX,
                        y: child.shape.y * scaleY,
                        width: child.shape.width * scaleX,
                        height: child.shape.height * scaleY,
                    });
                } else if (child instanceof gfx.Line) {
                    child.setShape({
                        x1: child.shape.x1 * scaleX,
                        y1: child.shape.y1 * scaleY,
                        x2: child.shape.x2 * scaleX,
                        y2: child.shape.y2 * scaleY,
                    });
                } else if (child instanceof gfx.Ellipse) {
                    child.setShape({
                        cx: child.shape.cx * scaleX,
                        cy: child.shape.cy * scaleY,
                        rx: child.shape.rx * scaleX,
                        ry: child.shape.ry * scaleY,
                    });
                } else if (child instanceof gfx.Polyline) {
                    child.setShape({
                        points: child.shape.points.map((point: {x: number; y: number}) => ({
                            x: point.x * scaleX,
                            y: point.y * scaleY,
                        })),
                    });
                }
            }
        });
    }
}
