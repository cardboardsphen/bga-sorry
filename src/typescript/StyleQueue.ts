export enum StyleType {
    Style,
    StyleRemove,
    ClassAdd,
    ClassRemove,
    SetData,
}

export class StyleQueue {
    private queue: Array<{type: StyleType; id: string; property: string; value: string}> = [];

    public get isEmpty() {
        return this.queue.length === 0;
    }

    public applyOrEnqueue(type: StyleType, id: string, property: string, value: string = ''): void {
        const item = {type, id, property, value};
        if (document.visibilityState === 'visible') this.apply(item);
        else this.queue.push(item);
    }

    public dequeueAllAndApply(): void {
        let initialLength = this.queue.length;
        while (this.queue.length > 0 && document.visibilityState === 'visible') this.apply(this.queue.shift()!);

        const applied = initialLength - this.queue.length;
        const notApplied = this.queue.length;
        if (applied > 0)
            console.log(`Visiblity restored. Applied ${applied} queued styles.${notApplied > 0 ? ` (${notApplied} were not applied.)` : ''}`);
    }

    private apply(item: {type: StyleType; id: string; property: string; value: string}): void {
        const element = document.getElementById(item.id);
        if (element) {
            switch (item.type) {
                case StyleType.Style:
                    element.style[item.property as any] = item.value;
                    break;
                case StyleType.StyleRemove:
                    element.style.removeProperty(item.property);
                case StyleType.ClassAdd:
                    element.classList.add(item.property);
                    break;
                case StyleType.ClassRemove:
                    element.classList.remove(item.property);
                    break;
                case StyleType.SetData:
                    element.dataset[item.property] = item.value;
                    break;
            }
        }
    }
}
