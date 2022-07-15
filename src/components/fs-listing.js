class CobaltListing extends HTMLElement {
    constructor() {
        super()
        this.listItems = [];
    }

    connectedCallback() {
        this.listItems = this.querySelectorAll(".cobalt--fs-directory-listing [data-id]");
        this.change_handler_edit_action(this.getAttribute("edit-action"));
        this.change_handler_delete_action(this.getAttribute("delete-action"));

        this.initListItemMenu();
    }

    initListItemMenu() {
        for(const el of this.listItems) {
            this.listItemElement(el);
        }
    }

    listItemElement(el) {
        el.addEventListener("contextmenu",event => {
            const menu = new ActionMenu({
                event: event,
            });

            if(this.editAction) {
                menu.registerAction({
                    label: "Edit",
                    request: {
                        method: "PUT",
                        action: this.actionUrl(this.editAction, el.dataset.id)
                    },
                    callback: () => {
                        return true;
                    }
                });
            }

            if(this.deleteAction) {
                menu.registerAction({
                    label: "Delete",
                    request: {
                        method: "DELETE",
                        action: this.actionUrl(this.deleteAction, el.dataset.id),
                    },
                    callback: (event,other,result) => {
                        const url = el.href ?? el.src ?? el.srcset ?? null;
                        if(url === null) return false;
                        const items = document.querySelectorAll(`[data-id="${result}"]`);
                        items.forEach(e => {
                            let parent = e.parentNode
                            let child = e;
                            if(parent.tagName === "PICTURE") {
                                child = parent;
                                parent = parent.parentNode;
                            }
                            parent.removeChild(child);
                        });
                        return true;
                    },
                    dangerous: true
                });
            }

            menu.draw();
        })
    }

    actionUrl(action, id) {
        const pos = action.indexOf("{id}");
        if(pos !== -1) return action.replace("{id}",id);
        
        if(action[action.length + 1] !== "/") return `${action}/${id}`;

        return action + id;
    }

    observedAttributes() {
        return ['edit-action', 'delete-action'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_edit_action(newValue) {
        this.editAction = newValue;
    }

    change_handler_delete_action(newValue, oldValue) {
        this.deleteAction = newValue;
    }

    
}

customElements.define("cobalt-listing", CobaltListing);