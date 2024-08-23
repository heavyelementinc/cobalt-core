class ImageResult extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", true);
        this.fileField = this.querySelector("input[type='file']");
        this.colorField = this.querySelector("input[type='color']");
        this.altField = this.querySelector("input.alt-text");
        
        this.fileField?.addEventListener("change", this.catchChangeEvents.bind(this));
        this.colorField?.addEventListener("change", this.catchChangeEvents.bind(this));
        this.altField?.addEventListener("change", this.catchChangeEvents.bind(this));

        this.heightCell = this.querySelector(".height-target");
        this.widthCell = this.querySelector(".width-target");
        this.heightWidthRow = this.heightCell?.closest("flex-row");
        this.previewImg = this.querySelector("image-editor img");
        this.urlRow = this.querySelector(".url-row")
        this.urlCopySpan = this.urlRow?.querySelector("copy-span");
        this.urlCell = this.urlRow?.querySelector(".nowrap");
    }

    get value() {
        const val = {};
        if(this.fileField.files.length >= 1) {
            val.url = this.getFileFieldValue();
        }
        if(this.altField.value) val.alt = this.altField.value;
        if("url" in val === false) val.accent = this.colorField.value;
        return val;
    }

    set value(val) {
        this.setHeightWidth(val ?? {})
        this.setUrl(val ?? {});
        if("accent" in val) this.colorField.value = val.accent;
        if("alt" in val) this.altField.value = val.alt;
    }

    getFileFieldValue(){
        // const data = new FormData(this)
        // data.append(this.fileField.files);
        return this.fileField.files;
    }

    setHeightWidth(val) {
        if("height" in val === false) return;
        if("width" in val === false) return;
        this.heightCell.innerText = val.height;
        this.widthCell.innerText = val.width;
        this.heightWidthRow.setAttribute("title", `${val.height} x ${val.width}`);
    }
    setUrl(val) {
        if("url" in val === false) return;
        this.previewImg.src = val.url;
        this.urlRow.setAttribute("title", val.url);
        this.urlCopySpan.value = val.url;
        this.urlCell.innerText = val.url;
    }

    catchChangeEvents(event) {
        event.preventDefault();
        event.stopPropagation();
        this.dispatchEvent(new Event("change", event));
    }
}

customElements.define("image-result", ImageResult);

class ImageEditor extends HTMLElement {
    constructor() {
        super();
        this.actionMenu = null
    }

    connectedCallback() {
        this.init();
    }

    init() {
        if(this.actionMenu) return;
        this.actionMenuButton = document.createElement("button");
        this.actionMenuButton.innerHTML = `<i class="dots-vertical"></i>`
        this.actionMenu = new ActionMenu(this.actionMenuButton, "popover");

        const defaultActions = ['registerDelete', 'registerRename'];
        for(const action of defaultActions) {
            switch(action){
                case "registerDelete":
                    this.registerDelete(this.actionMenu);
                    break;
                case "registerRename":
                    this.registerRename(this.actionMenu);
                    break;
            }
        }
        
        this.appendChild(this.actionMenuButton)
    }

    registerDelete(menu) {
        const deleteRoute = this.getAttribute("delete-action");
        if(!deleteRoute) return;
        const action = menu.registerAction();
        action.label = "Delete";
        action.requestMethod = "DELETE"
        action.requestAction = deleteRoute
    }

    registerRename(menu) {
        const renameRoute = this.getAttribute("rename-action");
        if(!renameRoute) return;
        const action = menu.registerAction();
        action.label = "Rename";
        action.callback = async () => {
            const newName = await modalInput("Rename this file", {});
            const fetch = new AsyncFetch(renameRoute, "PUT");
            const result = await fetch.submit({rename: newName});
            this.dispatchEvent(new CustomEvent("rename", {detail: {newName, result}}));
        }
    }

}

customElements.define("image-editor", ImageEditor);
