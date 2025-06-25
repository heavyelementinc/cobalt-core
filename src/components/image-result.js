class ImageResult extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", true);
        this.fileField = this.querySelector("input[type='file']");
        this.fileField.name = this.name;
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

    get name() {
        return this.getAttribute("name");
    }

    set name(name) {
        this.setAttribute("name", name);
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
        // event.preventDefault();
        // event.stopPropagation();
        // this.dispatchEvent(new Event("change", {...event, detail: {target: event.target}}));
    }
}

customElements.define("image-result", ImageResult);

class ImageEditor extends HTMLElement {
    constructor() {
        super();
        this.actionMenu = null;
        this.actionMenuButton = null;
        this.img = this.querySelector("img");
        this.btn = this.querySelector("button");
    }

    connectedCallback() {
        this.img = this.querySelector("img");
        this.init();
        // this.addEventListener("contextmenu", event => {
        //     this.actionMenu.openMenu();
        // });
    }

    get _id() {
        return this.dataset.id;
    }

    get src() {
        return this.img.getAttribute("src");
    }

    init() {
        if(this.actionMenuButton) return;
        this.actionMenuButton = document.createElement("button");
        this.actionMenuButton.innerHTML = `<i class="dots-vertical"></i>`;
        this.appendChild(this.actionMenuButton);

        this.actionMenu = new ActionMenu(this.actionMenuButton, "modal");
        this.actionMenu.title = `<img src="${this.src}" height="100" width="200" 
            style="object-fit: contain;"><br><span style="font-size: 1.1rem;">
            ${this.src.replace("/res/fs/","")}</span>`;
        
        const renameAction = this.actionMenu.registerAction();
        renameAction.label = "Rename";
        renameAction.callback = async (a, b, details) => {
            details.menu.close();
            const file = this.querySelector("img");
            const src = file.getAttribute("src").replace("/res/fs/", "");
            const result = await modalInput(`
                <img height="200" width="380" src="${file.src}" style="object-fit: contain">
                <p>Rename this file.</p>`, {
                value: src,
                placeholder: src,
            });

            if(!result) return;
            const api = new AsyncFetch(`/api/v1/crudable-files/${this._id}/rename`, "POST", {});
            api.submit({name: result});
        }
        
        const metadataAction = this.actionMenu.registerAction();
        metadataAction.label = "Reset Metadata";
        metadataAction.requestMethod = "GET";
        metadataAction.requestAction = `/api/v1/crudable-files/${this._id}/reset`;

        const deleteAction = this.actionMenu.registerAction();
        deleteAction.label = "Delete";
        deleteAction.requestMethod = "DELETE";
        deleteAction.requestAction = `/api/v1/crudable-files/${this._id}`; 
        deleteAction.dangerous = true;
    }

    // init() {
    //     if(this.actionMenu) return;
    //     this.actionMenuButton = document.createElement("button");
    //     this.actionMenuButton.innerHTML = `<i class="dots-vertical"></i>`
    //     this.appendChild(this.actionMenuButton)
    //     this.actionMenu = new ActionMenu(this.actionMenuButton, "popover");

    //     const defaultActions = ['registerReplace', 'registerRename', 'registerDelete'];
    //     for(const action of defaultActions) {
    //         switch(action){
    //             case "registerDelete":
    //                 this.registerDelete(this.actionMenu);
    //                 break;
    //             case "registerRename":
    //                 this.registerRename(this.actionMenu);
    //                 break;
    //             case "registerReplace":
    //                 this.registerReplace(this.actionMenu);
    //                 break;
    //         }
    //     }
        
    // }

    // registerDelete(menu) {
    //     const deleteRoute = this.getAttribute("delete-action");
    //     if(!deleteRoute) return;
    //     const action = menu.registerAction();
    //     action.label = "Delete";
    //     action.requestMethod = "DELETE"
    //     action.requestAction = deleteRoute
    // }

    // registerRename(menu) {
    //     const renameRoute = this.getAttribute("rename-action");
    //     if(!renameRoute) return;
    //     const action = menu.registerAction();
    //     action.label = "Rename";
    //     action.callback = async () => {
    //         const newName = await modalInput("Rename this file", {});
    //         const fetch = new AsyncFetch(renameRoute, "PUT");
    //         const result = await fetch.submit({rename: newName});
    //         this.dispatchEvent(new CustomEvent("rename", {detail: {newName, result}}));
    //     }
    // }

    // registerReplace(menu) {
    //     const replaceRoute = this.getAttribute("replace-action");
    //     if(!replaceRoute) return;
    //     const action = menu.registerAction();
    //     action.label = "Replace with an Existing Image";
    //     action.callback = async () => {
    //         const new_id = await modalForm(replaceRoute, {
    //             form_selector: ".filemanager-container", 
    //             // initialize_callback: (modalContainer, modalInstance) => {
    //             //     const okayButton = modalContainer.querySelector(".modal-button-okay");
    //             //     okayButton.disabled = true;
    //             //     const radioSelects = modalContainer.querySelectorAll(".filemanager-container input[type='radio']");
    //             //     radioSelects.forEach(e => {
    //             //         e.addEventListener("change", evt => okayButton.disabled = false);
    //             //     })
    //             // },
    //             // before_callback: (form, resolve, reject, modal) => {
    //             //     const selected = form.querySelector("input[type='radio']:checked");
    //             //     if(!selected) return false;
    //             //     resolve(selected.value);
    //             //     modal.close();
    //             //     return false;
    //             // }
    //         });
    //         // console.log(new_id);
    //         // this.dispatchEvent(new CustomEvent("existingimageselected", {detail: new_id}))
    //     }
    // }

}

customElements.define("image-editor", ImageEditor);

class ImageContainer extends HTMLElement {
    constructor() {
        super();
        this.img = this.querySelector("img");
        this.btn = this.querySelector("button");
    }

    connectedCallback() {
        if(!this.btn) {
            this.btn = document.createElement("button");
        }
        this.btn.innerHTML = '<i class="dots-vertical"></i>';
        this.initImg();
        this.init();
    }

    initImg() {
        if(!this.img) {
            this.img = document.createElement("img");
            this.appendChild(this.img);
        }
        if(!this.img.src) this.img.src = this.src;
        if(!this.img.width) this.img.width = this.width;
        if(!this.img.height) this.img.height = this.height;
    }

    get width() {
        return this.getAttribute("width") || this.img.width;
    }

    get height() {
        return this.getAttribute("height") || this.img.height;
    }

    get src() {
        return this.getAttribute("src") || this.img.src;
    }

    get _id() {
        return this.dataset.id || this.getAttribute("data-id") || this.id;
    }

    init() {
        const actionMenu = new ActionMenu(this.btn, "modal");
        actionMenu.title = `<img src="${this.src}"> ${this._id}`;
        
        const renameAction = actionMenu.registerAction();
        renameAction.label = "Rename";
        renameAction.callback = async (a, b, details) => {
            details.menu.close();
            const file = this.querySelector("img");
            const src = file.getAttribute("src").replace("/res/fs/", "");
            const result = await modalInput(`
                <img height="200" width="380" src="${file.src}" style="object-fit: contain">
                <p>Rename this file.</p>`, {
                value: src,
                placeholder: src,
            });

            if(!result) return;
            const api = new AsyncFetch(`/api/v1/crudable-files/${this._id}/rename`, "POST", {});
            api.submit({name: result});
        }
        
        const metadataAction = actionMenu.registerAction();
        metadataAction.label = "Reset Metadata";
        metadataAction.requestMethod = "GET";
        metadataAction.requestAction = `/api/v1/crudable-files/${this._id}/reset`;

        const deleteAction = actionMenu.registerAction();
        deleteAction.label = "Delete";
        deleteAction.requestMethod = "DELETE";
        deleteAction.requestAction = `/api/v1/crudable-files/${this._id}`; 
        deleteAction.dangerous = true;
    }
}

customElements.define("image-container", ImageContainer);
