class Sortable {

    constructor(dropTargets, sortableItems, eventContainer = null, options = {}) {
        this.dropTargets = dropTargets;
        this.sortableItems = sortableItems;
        this.container = eventContainer;

        if(this.container === null) this.findContainers();

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
        
    }

    dragDrop(element,event) {
        if(!this.currentDragItem) return;
        const dragEndIndex = +event.target.dataset.cobaltSortableIndex;

        event.target.classList.remove('cobalt-sortable--valid-drop-target', this.validTargetClass);
        
        const dropTarget = this.getBeforeAfterFromOrientation(element, event);
        console.log(dropTarget);
        
        // if(this.currentDragItem.parentNode) this.currentDragItem.parentNode.removeChild(this.currentDragItem);

        element.parentNode.insertBefore(this.currentDragItem,dropTarget);
        
        this.currentDragItem = null;
        this.triggerDropEvent();
    }

    dragOver(element,event) {
        event.preventDefault();
    }

    dragEnter(element,event) {
        event.target.classList.add('cobalt-sortable--valid-drop-target',this.validTargetClass);
    }

    dragLeave(element,event) {
        event.target.classList.remove('cobalt-sortable--valid-drop-target',this.validTargetClass);
    }

    getBeforeAfterFromOrientation(el, dropEvent) {
        // return el.nextSibling;
        // Define constraints. If we're in `ltr` or `landscape` mode then we
        // should get the Y coordinates and the height value
        let constraint = ["x","w"];
        // Otherwise we should get the `x` coordinates and the width value
        if(['portrait', 'ttb'].includes(this.orientation)) constraint = ["y", "h"];
        const dims = get_offset(el);
        // Divide the element's chosen dimension in half
        const half = dims[constraint[1]] * .5;
        const droppedAt = dropEvent[constraint[0]];
        const dropTargetOffset = dims[constraint[0]];
        const normalizedDropPosition = dims[constraint[1]] - (droppedAt - dropTargetOffset);
        // Check if the event's constraint action is less than or greater than
        // the half threshold of the drop element
        console.log({check: normalizedDropPosition > half, normalizedDropPosition, half, dropTargetOffset, constraint, droppedAt});
        // console.log(normalizedDropPosition > half);
        return (normalizedDropPosition > half) ? el : el.nextElementSibling;
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
}
