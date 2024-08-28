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
        const deleteAction = actionMenu.registerAction();
        deleteAction.label = "Delete";
        deleteAction.requestMethod = "DELETE";
        deleteAction.requestAction = `/api/v1/crudable-files/${id}`;

        // target.addEventListener("click", event => {
        //     actionMenu.openMenu();
        // })
        // actions.push({
        //     label: "Rename",
        //     dangerous: false,
        //     callback: async (element, event, asyncRequest) => {
        //         await modalView(`/admin/settings/fs-manager/${id}/rename`);
        //         return true;
        //     }
        // })

        // actions.push({
        //     label: "Delete",
        //     dangerous: true,
        //     request: {
        //         method: "DELETE",
        //         action: t.getAttribute("data-delete") ?? `/api/v1/fs-manager/${id}/delete`
        //     }
        // });

    }

    highlightError(target) {
        target.classList.add("error");
    }
}