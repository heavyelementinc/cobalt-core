class ObjectGallery extends HTMLElement {
    uploadField;
    ITEM_QUERY = "gallery-item";
    DRAG_IN_PROGRESS = "drag-in-progress";
    DROP_TARGET_CLASS = "drop-target--class";
    DROP_TARGET_NEXT  = "drop-target--next";

    constructor() {
        super();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.initObjectPicker();
        this.initDragAndDrop();
    }
    
    initObjectPicker() {
        this.uploadField = document.createElement("div");
        this.uploadField.classList.add("object-picker-container");
        this.uploadField.method = this.getAttribute("method");
        this.uploadField.action = this.getAttribute("action");

        const picker = document.createElement("object-picker");
        this.uploadField.appendChild(picker);
        picker.addEventListener("selection", event => {
            this.addObjectsToList(event.detail, true)
        });
        this.appendChild(this.uploadField);
    }

    addObjectsToList(elements, triggerChange) {
        for(const element of elements) {
            this.addObjectToList(element.id, element.html, false);
        }
        if(triggerChange) this.dispatchEvent(new Event("change",{bubbles: true}));
    }

    addObjectToList(id, html = "", triggerChange = false) {
        const temp = document.createElement("div");
        temp.innerHTML = html;

        let obj = temp.querySelector(`.${this.ITEM_QUERY}`);
        if(!obj) {
            obj = document.createElement(this.ITEM_QUERY);
            obj.dataset.id = id;
            obj.innerHTML = html;
        }
        this.insertBefore(obj, this.uploadField);
        
        if(triggerChange) this.dispatchEvent(new Event("change",{bubbles: true}));
    }

    initDragAndDrop() {
        const items = this.querySelectorAll(this.ITEM_QUERY);
        const firstElement = items[0].getBoundingClientRect();
        const secondElement = items[1].getBoundingClientRect();
        if(firstElement.y !== secondElement.y) {
            // this.dragOrientation = ['clientY', 'y', 'height'];
            // this.setAttribute("orientation", "list");
        }
        for(const el of items) {
            el.setAttribute("draggable", "true");
            el.addEventListener("dragstart", this.dragStart.bind(this));
            el.addEventListener("drag", this.dragAround.bind(this));
            el.addEventListener("dragend", this.dragEnd.bind(this));
            el.addEventListener("dragenter", this.dragEnter.bind(this));
            el.addEventListener("dragleave", this.dragLeave.bind(this));
        }
        
    }

    dragOrientation = ['clientX', 'x', 'width'];
    dragTarget = null;
    dropTarget = null;
    dropAfter = false;

    dragStart(event) {
        this.dragTarget = event.currentTarget;
        this.setAttribute(this.DRAG_IN_PROGRESS, "true");
    }

    dragEnd(event) {
        if(!this.dragTarget) {
            console.log("There's no drag target");
            return this.cleanUpAfterDragEvent();
        }
        if(!this.dropTarget) {
            console.log("There's no drop target");
            return this.cleanUpAfterDragEvent();
        }
        let trueDropTarget = this.dropTarget;
        if(this.dropAfter) {
            this.dropTarget.nextElementSibling;
        }
        console.log(trueDropTarget, this.dropAfter);
        this.insertBefore(this.dragTarget, trueDropTarget);
        this.cleanUpAfterDragEvent();

        this.dispatchEvent(new Event("change",{bubbles: true}));
    }

    cleanUpAfterDragEvent() {
        this.dragTarget = null;
        this.dropTarget = null;
        this.setAttribute(this.DRAG_IN_PROGRESS,"");
        this.dragEnterCounter = 0;
        this.querySelectorAll(`.${this.DROP_TARGET_CLASS}, .${this.DROP_TARGET_NEXT}`).forEach(el => {
            el.classList.remove(this.DROP_TARGET_CLASS, this.DROP_TARGET_NEXT);
        });
    }

    dragEnterCounter = 0;

    dragEnter(event) {
        const target = event.currentTarget;
        this.dragEnterCounter += 1;
        if(this.dragEnterCounter !== 1) return;
        this.dropTarget = target;
        this.dropTarget.classList.add(this.DROP_TARGET_CLASS);
    }
    
    dragAround(event) {
        // If we haven't dragged over an element, do nothing
        if(!this.dropTarget) return;
        
        const mouse = event[this.dragOrientation[0]];
        if(mouse === 0) return;
        const rect = this.dropTarget.getBoundingClientRect();
        const relativeCursor = mouse - rect[this.dragOrientation[1]];
        const halfElementWidth = rect[this.dragOrientation[2]] / 2;
        if(relativeCursor >= halfElementWidth) {
            this.dropAfter = true;
            this.dropTarget.classList.add(this.DROP_TARGET_NEXT);
        } else {
            this.dropAfter = false;
            this.dropTarget.classList.remove(this.DROP_TARGET_NEXT);
        }
        // this.insertBefore(this.dropIndicator, (this.dropAfter) ? this.dropTarget.nextSibling : this.dropTarget);
    }

    dragLeave(event) {
        const target = event.currentTarget;
        this.dragEnterCounter += -1;
        if(this.dragEnterCounter !== 0) return;
        // this.dropTarget = null;
        target.classList.remove(this.DROP_TARGET_CLASS, this.DROP_TARGET_NEXT);
        return true;
    }

    get value() {
        const items = this.querySelectorAll(this.ITEM_QUERY);
        let value = [];
        for(const el of items) {
            value.push(el.dataset.id);
        }
        return value;
    }

}

customElements.define("object-gallery", ObjectGallery);

class FileGallery extends ObjectGallery {
    initObjectPicker() {
        super.initObjectPicker();
        let field = this.querySelector("input[type='file']");
        if(!field) {
            field = document.createElement("input");
            field.type = "file";
            field.multiple = "multiple";
            field.accept = this.getAttribute("accept") ?? "";
        }

        this.uploadField.appendChild(field);
        // this.dropIndicator = document.createElement("drop-indicator");
        // this.appendChild(this.dropIndicator);
    }
    get value() {
        const uploadButton = this.uploadField.querySelector("input[type='file']");
        console.log(uploadButton);
        if(uploadButton && uploadButton.files.length !== 0) {
            const files = uploadButton.files;
            // uploadButton.value = null;
            return files;
        }
        return super.value;
    }
}

customElements.define("file-gallery", FileGallery);

class GalleryItem extends HTMLElement {
    constructor() {
        super();
    }
    connectedCallback() {
        // this.actionMenu = this.querySelector("action-menu");
        this.initActionMenu();
    }

    initActionMenu() {
        if(!this.closest("object-gallery,file-gallery")) return;
        this.delete = document.createElement("button");
        this.delete.innerHTML = "<i name='close'></i>";
        this.insertBefore(this.delete, this.firstElementChild);
        this.delete.addEventListener("click", () => {
            const target = this.parentNode;
            this.parentNode.removeChild(this);
            target.dispatchEvent(new Event("change",{bubbles: true}));
        });
    }
}

customElements.define("gallery-item", GalleryItem);