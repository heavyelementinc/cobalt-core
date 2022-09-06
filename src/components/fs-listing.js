class CobaltListing extends HTMLElement {
    constructor() {
        super()
        this.listItems = [];
        this.customMenuOptions = {};
    }

    connectedCallback() {
        this.getCustomMenuOptions();
        this.listItems = this.querySelectorAll(".cobalt--fs-directory-listing [data-id]");
        this.change_handler_edit_action(this.getAttribute("edit-action"));
        this.change_handler_delete_action(this.getAttribute("delete-action"));
        this.change_handler_rename_action(this.getAttribute("rename-action"));
        this.change_handler_sort_action(this.getAttribute("sort-action"));

        this.initListItemMenu();
        this.initSorting();
        this.observer = new MutationObserver(mutations => {
            this.initListItemMenu();
        });
        this.observer.observe(this, {childList: true});
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

            if(this.renameAction) {
                menu.registerAction({
                    label: 'Rename',
                    request: {
                        method: this.getAttribute('rename-method') ?? "PUT",
                        action: this.renameAction
                    },
                    
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

            for(const i in this.customMenuOptions) {
                const element = this.customMenuOptions[i];
                const test = ('label' in element && 'action' in element);
                if(!test) {
                    console.warn("Missing a required attribute for a custom cobalt-listing action.");
                    continue;
                }
                menu.registerAction({
                    label: element.label,
                    request: {
                        method: element.method ?? "PUT",
                        action: this.actionUrl(element.action, el.dataset.id)
                    },
                    callback: () => {
                        return true;
                    }
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
        return ['edit-action', 'delete-action', 'sort-action', 'rename-action'];
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

    change_handler_delete_action(newValue) {
        this.deleteAction = newValue;
    }

    change_handler_sort_action(newValue) {
        this.sortAction = newValue;
    }

    change_handler_rename_action(newValue) {
        this.renameAction = newValue;
    }

    getCustomMenuOptions() {
        const attributes = this.attributes;
        let attrs = {};
        for(const attr of attributes) {
            const name = attr.name;
            const split = name.split("-");
            if(split[0] !== "custom") continue;

            // Check if index is not a number
            let index = split[split.length - 1];
            if(isNaN(index)) continue;
            index = parseFloat(index);

            if(typeof attrs[index] === 'undefined') attrs[index] = {};
            attrs[index][split[1]] = attr.value;
        }
        if(attrs[0] === 'undefined') attrs[0].pop();
        this.customMenuOptions = attrs;
    }

    initSorting() {
        if(this.sortAction === null) return;
        const container = this.querySelector(".cobalt--fs-directory-listing");
        console.log(container.children)
        this.sortable = new Sortable(container.children, container.children, container);
        this.sortable.container.addEventListener("cobtaltsortcomplete",() => {
            const fetch = new ApiFetch(this.sortAction, this.getAttribute("sort-method") ?? "POST", {});
            let sortData = [];
            for(const i of container.children) {
                sortData.push(i.dataset.id);
            }
            fetch.send(sortData);
        });
        this.sortable.initialize();
    }
}

customElements.define("cobalt-listing", CobaltListing);