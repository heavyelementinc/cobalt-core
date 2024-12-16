class FSManager {
    /**
     * @param {HTMLElement} target 
     */
    constructor() {
        this.init();
    }

    init() {
        const files = document.querySelectorAll(".fs-filemanager");
        for(const file of files) {
            this.setUpTarget(file);
        }
    }

    setUpTarget(target) {        
        let id = target.getAttribute("data-id");
        if(!id) return;
        
        const actionMenu = new ActionMenu(target, "modal");
        actionMenu.title = `<img src="${target.querySelector("img").src}"> ${id}`;
        
        const renameAction = actionMenu.registerAction();
        renameAction.label = "Rename";
        renameAction.callback = async (a, b, details) => {
            details.menu.close();
            const file = target.querySelector("img");
            const src = file.getAttribute("src").replace("/res/fs/", "");
            const result = await modalInput(`
                <img height="200" width="380" src="${file.src}" style="object-fit: contain">
                <p>Rename this file.</p>`, {
                value: src,
                placeholder: src,
            });
            console.log(result);
            if(!result) return;
            const api = new AsyncFetch(`/api/v1/crudable-files/${id}/rename`, "POST", {});
            api.submit({name: result});
        }
        
        const metadataAction = actionMenu.registerAction();
        metadataAction.label = "Reset Metadata";
        metadataAction.requestMethod = "GET";
        metadataAction.requestAction = `/api/v1/crudable-files/${id}/reset`;

        const deleteAction = actionMenu.registerAction();
        deleteAction.label = "Delete";
        deleteAction.requestMethod = "DELETE";
        deleteAction.requestAction = `/api/v1/crudable-files/${id}`; 

    }

    highlightError(target) {
        target.classList.add("error");
    }
}