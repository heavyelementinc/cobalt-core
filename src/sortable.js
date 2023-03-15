class Sortable {

    constructor(dropTargets, sortableItems, eventContainer = null, options = {}) {
        this.dropTargets = dropTargets;
        this.sortableItems = sortableItems;
        this.container = eventContainer;
        this.dropIndicator = document.createElement("div");
        this.dropIndicator.classList.add("cobalt-sortable--drop-indicator");

        if(this.container === null) this.findContainers();

        this.container.addEventListener("drop", e => this.dragDrop());
        this.dragStartIndex;
        this.options(options);
    }

    findContainers() {
        const parentNodes = [];
        for(const el of this.dropTargets) {
            parentNodes.push(el.parentNode);
        }
        this.container = [...new Set(parentNodes)];
    }

    options({
        validTargetClass = 'cobalt-sortable--valid-drop-target',
        orientation = "portrait"
    }) {
        this.validTargetClass = validTargetClass;
        this.orientation = orientation;
    }

    initialize() {
        this.initSortAction();
    }

    initSortAction() {
        this.reindexSortables();

        // Set up the container as a valid drop target
        for(const i of this.dropTargets) {
            this.initDropTarget(i);
        }

        this.initSortableItems(this.sortableItems);
    }

    reindexSortables() {
        Array(this.sortableItems).forEach((element, i) => {
            if(!element.dataset) element.dataset = {};
            element.dataset.cobaltSortableIndex = i;
        });
    }

    initSortableItems(container) {
        for(const el of this.dropTargets) {
            this.initDropTarget(el);
        }
        this.initDropTarget(this.dropIndicator);
        for(const el of this.sortableItems) {
            this.initSortableItem(el);
        }
    }

    initSortableItem(item) {
        item.draggable = true;
        item.addEventListener('dragstart', event => this.dragStart(item, event));
    }

    initDropTarget(item) {
        item.addEventListener('drop',      event => this.dragDrop(item, event));
        item.addEventListener('dragover',  event => this.dragOver(item, event));
        item.addEventListener('dragenter', event => this.dragEnter(item, event));
        item.addEventListener('dragleave', event => this.dragLeave(item, event));
    }

    dragStart(element,event) {
        // this.dragStartIndex = +event.target.dataset.cobaltSortableIndex;
        this.currentDragItem = element;
        this.currentDragItem.classList.add("cobalt-sortable--current-drag-item");
    }

    dragDrop() {
        if(!this.currentDragItem) return;
        // const dragEndIndex = +event.target.dataset.cobaltSortableIndex;

        // event.target.classList.remove('cobalt-sortable--valid-drop-target', this.validTargetClass);
        // Let's get the parent node of the drop indicator
        const p = this.dropIndicator.parentNode;
        if(p) {
            // Insert the current drag item before the dropIndicator element
            p.insertBefore(this.currentDragItem, this.dropIndicator);
        }
        this.cleanupAfterDrop();
    }

    dragOver(element,event) {
        event.preventDefault();
        const target = this.getBeforeAfterFromOrientation(event.target,event);
        event.target.parentNode.insertBefore(this.dropIndicator, target);
    }

    dragEnter(element,event) {
        event.target.classList.add('cobalt-sortable--valid-drop-target',this.validTargetClass);
    }

    dragLeave(element,event) {
        event.target.classList.remove('cobalt-sortable--valid-drop-target',this.validTargetClass);
    }

    getBeforeAfterFromOrientation(el, event) {
        // return el.nextSibling;
        // Define constraints. If we're in `ltr` or `landscape` mode then we
        // should get the Y coordinates and the height value
        let constraint = ["x","w"];
        // Otherwise we should get the `y` coordinates and the width value
        if(['portrait', 'ttb'].includes(this.orientation)) constraint = ["y", "h"];
        const dims = get_offset(el);
        const x = constraint[0];
        const w = constraint[1];

        // Divide the element's chosen dimension in half
        const half = dims[w] / 2;

        const eventPosition = event[x]; // The event's position
        const dropTargetOffset = dims[x];
        const normalizedDropPosition = (eventPosition - dropTargetOffset);
        
        let target = (normalizedDropPosition < half) ? el : el.nextElementSibling
        return target;
    }

    triggerDropEvent() {
        if(Array.isArray(typeof this.container)) {
            for(const el of this.container) {
                this.dispatch(el);
            }
            return;
        }
        this.dispatch(this.container);
    }

    dispatch(el) {
        if(typeof el === "object" && "dispatchEvent" in el) {
            el.dispatchEvent(new CustomEvent("cobtaltsortcomplete"));
        }
    }

    cleanupAfterDrop() {
        if(!this.currentDragItem) return;
        this.currentDragItem.classList.remove("cobalt-sortable--current-drag-item");
        this.currentDragItem = null;
        this.triggerDropEvent();

        const p = this.dropIndicator.parentNode;
        if(p) p.removeChild(this.dropIndicator);
    }

    
}
