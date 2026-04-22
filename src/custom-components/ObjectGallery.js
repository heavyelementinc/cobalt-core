import ICustomInput from "./ICustomInput.js";
export default {}
export class ObjectGallery extends ICustomInput {
    uploadField;
    ITEM_QUERY = "gallery-item";
    DRAG_IN_PROGRESS = "drag-in-progress";
    DROP_TARGET_CLASS = "drop-target--class";
    DROP_TARGET_NEXT  = "drop-target--next";
    VISUALLY_HIDDEN_CLASS = "object-gallery--visually-hidden";

    constructor() {
        super();
    }

    connectedCallback() {
        this.initObjectPicker();
        this.initDragAndDrop();
        this.initActionMenu();
        if(this.constructor.name === "ObjectGallery") this.customInputReady.resolve(true);
    }
    
    initObjectPicker() {
        this.uploadField = document.createElement("div");
        this.uploadField.classList.add("object-picker-container");
        this.uploadField.method = this.getAttribute("method");
        this.uploadField.action = this.getAttribute("action");
        this.uploadField.max = this.max;

        const picker = document.createElement("object-picker");
        picker.innerText = "Add Existing...";
        picker.title = "Append existing elements to this gallery";
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
        
        if(items.length > 1) {
            const firstElement = items[0].getBoundingClientRect();
            const secondElement = items[1].getBoundingClientRect();
            
            if(firstElement.y !== secondElement.y) {
                // this.dragOrientation = ['clientY', 'y', 'height'];
                // this.setAttribute("orientation", "list");
            }
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
    dropAfter = true;

    dragStart(event) {
        this.dragTarget = event.currentTarget;
        this.visualDropTarget = this.dragTarget.cloneNode(true);
        this.visualDropTarget.classList.add("object-gallery--visual-drop-target");
        document.body.appendChild(this.visualDropTarget);

        this.dragTarget.classList.add(this.VISUALLY_HIDDEN_CLASS);

        this.setAttribute(this.DRAG_IN_PROGRESS, "true");
    }

    dragEnd(event) {
        if(this.visualDropTarget && this.visualDropTarget.parentNode) {
            this.visualDropTarget.parentNode.removeChild(this.visualDropTarget);
        }
        if(!this.dragTarget) {
            console.log("There's no drag target");
            return this.cleanUpAfterDragEvent();
        }
        if(!this.dropTarget) {
            console.log("There's no drop target");
            return this.cleanUpAfterDragEvent();
        }
        this.dragTarget.classList.remove(this.VISUALLY_HIDDEN_CLASS);
        let trueDropTarget = this.dropTarget;
        if(this.dropAfter) {
            this.dropTarget.nextElementSibling;
        }
        console.log(trueDropTarget, this.dropAfter);
        // this.insertBefore(this.dragTarget, trueDropTarget);
        this.cleanUpAfterDragEvent();

        this.dispatchEvent(new Event("change",{bubbles: true}));
    }

    cleanUpAfterDragEvent() {
        this.dragTarget = null;
        this.dropTarget = null;
        this.dropAfter = false;
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
        // this.dropTarget.classList.add(this.DROP_TARGET_CLASS);
    }
    
    dragAround(event) {
        this.visualDropTarget.style.left = `${event.clientX - (this.visualDropTarget.clientWidth / 2)}px`;
        this.visualDropTarget.style.top = `${event.clientY - (this.visualDropTarget.clientHeight / 2)}px`;
        // If we haven't dragged over an element, do nothing
        if(!this.dropTarget) return;
        
        const mouse = event[this.dragOrientation[0]];
        if(mouse === 0) return;
        const rect = this.dropTarget.getBoundingClientRect();
        // const relativeCursor = mouse - rect[this.dragOrientation[1]];
        // const halfElementWidth = rect[this.dragOrientation[2]] / 2;
        // if(relativeCursor >= halfElementWidth) {
        //     this.dropAfter = true;
        //     // this.dropTarget.classList.add(this.DROP_TARGET_NEXT);
        // } else {
        //     this.dropAfter = false;
        //     // this.dropTarget.classList.remove(this.DROP_TARGET_NEXT);
        // }
        this.insertBefore(this.dragTarget, (this.dropAfter) ? this.dropTarget.nextSibling : this.dropTarget);
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
            value.push(el.value);
        }
        return value;
    }

    initActionMenu() {
        const menu = this.querySelector("action-menu");
        if(!menu) return;
        // menu.addEventListener("promptdata", e => {
        //     this.dispatchEvent(new Event("change"));
        // });
    }
}

export class FileGallery extends ObjectGallery {
    initObjectPicker() {
        super.initObjectPicker();
        const label = document.createElement("label");
        label.classList = "button";
        label.innerText = "Upload Files";
        let field = document.createElement("input");
        label.appendChild(field);
        field.type = "file";
        field.multiple = this.multiple ?? "multiple";
        field.accept = this.getAttribute("accept") ?? "";
        // }

        this.uploadField.appendChild(label);
        this.customInputReady.resolve(true);
        // this.dropIndicator = document.createElement("drop-indicator");
        // this.appendChild(this.dropIndicator);
    }

    get value() {
        const uploadButton = this.uploadField?.querySelector("input[type='file']");
        console.log(uploadButton);
        if(uploadButton && uploadButton.files.length !== 0) {
            const files = uploadButton.files;
            // uploadButton.value = null;
            return files;
        }
        return super.value;
    }
}

export class GalleryItem extends HTMLElement {
    constructor() {
        super();
    }
    _props = {
        meta: {}
    }
    get value() {
        const object = {
            ...this.dataset,
            ...this._props.meta
        };
        return object;
    }

    set value(val) {
        if(typeof val !== "object") return;
        if(Array.isArray(val)) return;
        this._props.meta = {...this._props.meta, ...val};
    }

    get container() {
        return this.closest("object-gallery,file-gallery,foreign-id,file-id");
    }

    connectedCallback() {
        // this.actionMenu = this.querySelector("action-menu");
        this.initActionMenu();
    }

    initActionMenu() {
        if(!this.container) return;
        this.delete = document.createElement("button");
        this.delete.innerHTML = "<i name='backspace'></i>";
        this.insertBefore(this.delete, this.firstElementChild);
        this.delete.addEventListener("click", () => {
            const target = this.parentNode;
            this.parentNode.removeChild(this);
            target.dispatchEvent(new Event("change",{bubbles: true}));
        });

        const actionMenu = this.querySelector('action-menu');
        if(!actionMenu) return;
        actionMenu.addEventListener("click", e => {
            e.preventDefault();
            // actionMenu.open();
            this.createMetaEditor(actionMenu);
        });

        actionMenu.addEventListener("promptdata", event => {
            this.value = event.detail.promptData;
            this.dispatchEvent(new Event("change", {bubbles: true}));
        });

        this.addEventListener("contextmenu", e => {
            e.preventDefault();
            // actionMenu.open();
            // dialog.open();
            this.createMetaEditor(actionMenu)
        });
    }

    metaEditor = null;

    createMetaEditor(actionMenu) {
        this.metaEditor = document.createElement("dialog");
        this.metaEditor.classList.add("object-gallery--meta-editor");
        document.body.appendChild(this.metaEditor);

        this.metaEditor.addEventListener("close", () => {
            this.metaEditor.parentNode.removeChild(this.metaEditor);
        });

        const form = document.createElement("form");
        this.metaEditor.appendChild(form);
        const listPanel = document.createElement("ul");
        listPanel.classList.add("list-panel");
        form.appendChild(listPanel);
        for(const option of actionMenu.querySelectorAll("option")) {
            const li = document.createElement("li");
            li.innerHTML = `<label>${option.getAttribute("title")}</label>`;
            const input = document.createElement("input");
            switch(option.getAttribute("name")) {
                case "accent_color":
                case "contrast_color":
                    input.type = "color";
                    break;
                default:
                    input.type = "text";
                    break;
            }
            input.name = option.getAttribute("name");
            input.value = option.getAttribute("value");
            li.appendChild(input);
            listPanel.appendChild(li);
        }
        const closebtn = document.createElement("button");
        closebtn.innerText = "Cancel";
        form.appendChild(closebtn);
        closebtn.addEventListener("click", e => {
            e.preventDefault();
            this.metaEditor.parentNode.removeChild(this.metaEditor);
        });

        const btn = document.createElement("button");
        btn.type = "submit";
        btn.innerText = "Submit";
        form.appendChild(btn);

        form.addEventListener("submit", e => {
            const inputs = form.querySelectorAll("input");
            const value = {};
            for(const el of inputs){ 
                value[el.name] = el.value;
            }
            this.value = value;
            this.metaEditor.parentNode.removeChild(this.metaEditor)
            this.dispatchEvent(new Event("change", {bubbles: true}));
        });
        this.metaEditor.showModal();
    }
}
