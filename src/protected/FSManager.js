class FSManager {
    /**
     * @param {HTMLElement} target 
     */
    constructor(target) {
        this.target = target;
        this.init();
    }

    init() {
        const t = this.target;
        let actions = [];
        let id = t.getAttribute("data-id");

        if(id) {
            actions.push({
                label: "Rename",
                dangerous: false,
                callback: async (element, event, asyncRequest) => {
                    await modalView(`/admin/settings/fs-manager/${id}/rename`);
                    return true;
                }
            })

            actions.push({
                label: "Delete",
                dangerous: true,
                request: {
                    method: "DELETE",
                    action: t.getAttribute("data-delete") ?? `/api/v1/fs-manager/${id}/delete`
                }
            });
        }

        t.addEventListener("contextmenu", event => {
            const menu = new ActionMenu({
                event,
                title: `Update ${t.getAttribute("data-name") ?? "Image"}`,

            })

            actions.forEach(e => {
                menu.registerAction(e);
            });

            actions.draw();
        })
    }
}