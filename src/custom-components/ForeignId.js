import {ObjectGallery} from "./ObjectGallery.js";
export default "";
export class ForeignId extends ObjectGallery {
    get value() {
        const item = this.querySelector(this.ITEM_QUERY);
        return item?.dataset.id ?? null;
    }
    initDragAndDrop() {
        // Do nothing. We don't support drag and drop on this item
    }
}

export class FileId extends ForeignId {
    initObjectPicker() {
        super.initObjectPicker();
        let field = this.querySelector("input[type='file']");
        if(!field) {
            field = document.createElement("input");
            field.type = "file";
            // field.multiple = "multiple";
            field.accept = this.getAttribute("accept") ?? "";
        }

        this.uploadField.appendChild(field);
        this.customInputReady.resolve(true);
        // this.dropIndicator = document.createElement("drop-indicator");
        // this.appendChild(this.dropIndicator);
    }
    get value() {
        const uploadButton = this.uploadField?.querySelector("input[type='file']");
        if(uploadButton && uploadButton.files.length !== 0) {
            const files = uploadButton.files;
            // uploadButton.value = null;
            return files;
        }
        return super.value;
    }
}