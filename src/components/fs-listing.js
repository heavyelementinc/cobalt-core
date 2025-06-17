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
        let button = el.querySelector("button");
        let target = el.querySelector("[full-resolution]");
        if(!target) target = el;
        target.addEventListener("contextmenu",event => {
            this.actionMenu(event, target)
        })
        if(button) {
            button.addEventListener("mouseup", event => {
                this.actionMenu(event, explicitTarget)
            })
        }
    }

    actionMenu(event, target) {
        event.preventDefault();
        const menu = new ActionMenu(target, "modal");
        const el = target;
        if(this.editAction) {
            let edit = menu.registerAction();
            edit.label = "Edit";
            edit.requestMethod = "PUT";
            edit.requestAction = this.actionUrl(this.editAction, el.dataset.id);
            edit.requestData = el.dataset.id;
            edit.callback = () => true;
        }

        if(this.renameAction) {
            let rename = menu.registerAction();
            rename.label = "Rename";
            rename.callback = () => {
                let host = `${location.protocol}//${location.host}`
                let filename =  event.target.getAttribute("full-resolution") || event.target.getAttribute("src") || event.target.getAttribute("href");
                let url = new URL(host + filename.replace(host, ""));
                const charlen = url.pathname.lastIndexOf("/") + 1;
                const modal = new Modal({
                    body: `
                    <form-request method="${this.getAttribute('rename-method') ?? "PUT"}" action="${this.actionUrl(this.renameAction, el.dataset.id)}">
                        <fieldset>
                            <legend>Rename file <help-span value="This will rename the file and, if the file is an image with a thumbnail, the thumbnail as well."></help-span></legend>
                            <input name="rename" style="width: 100%; min-width: ${charlen + 10}ch; max-width: 80vw" value="${decodeURIComponent(url.pathname.substring(charlen))}">
                            <ul>
                                <li>Replace spaces with hyphens (-) where possible.</li>
                                <li>The current file extension will be appended to any name lacking an extension.</li>
                            </ul>
                        </fieldset>
                    </form-request>
                    `,
                    chrome: {
                        cancel: {
                            label: "Cancel",
                            dangerous: false,
                            callback: async () => true
                        },
                        okay: {
                            label: "Rename",
                            dangerous: false,
                            callback: async (event) => {
                                return new Promise((resolve, reject) => {
                                    const form = modal.dialog.querySelector("form-request");
                                    form.addEventListener("formRequestSuccess", (event) => {
                                        if("code" in event.detail && event.detail.code === 300) resolve(true);
                                        this.updateFilename(el, event.detail, event);
                                        resolve(event.detail);
                                    })
                                    form.addEventListener("formRequestFail", (event) => {
                                        console.log(event);
                                        if(event.detail.code === 300) resolve(true);
                                        resolve(false);
                                    });
                                    form.send();
                                })
                            }
                        }
                    }
                });
                modal.draw();
                return true;
            }
        }

        for(const i in this.customMenuOptions) {
            const element = this.customMenuOptions[i];
            const test = ('label' in element && 'action' in element);
            if(!test) {
                console.warn("Missing a required attribute for a custom cobalt-listing action.");
                continue;
            }
            let action = menu.registerAction();
            action.label = element.label;
            action.requestMethod = element.method ?? "PUT";
            action.requestAction = this.actionUrl(element.action, el.dataset.id);
            action.requestData = el.dataset.id;
            action.callback = (action, event, requestData) => {
                return true;
            }
        }

        if(this.deleteAction) {
            let deleteAction = menu.registerAction();
            deleteAction.label = "Delete";
            deleteAction.dangerous = true;
            deleteAction.requestMethod = "DELETE";
            deleteAction.requestAction = this.actionUrl(this.deleteAction, el.dataset.id);
            deleteAction.requestData = el.dataset.id;
            deleteAction.callback = (event,other,result) => {
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
            };
        }

        menu.draw();
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
        this.sortable = new Sortable(container.children, container.children, container, {orientation: "ltr"});
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

    updateFilename(el, names) {
        if(el.getAttribute('src') == el.getAttribute('full-resolution')) {
            el.setAttribute('src', names.name)
            el.setAttribute('full-resolution', names.name);
        } else {
            el.setAttribute('src', names.thumbnail || names.name);
            el.setAttribute('full-resolution', names.name);
        }
    }
}

customElements.define("cobalt-listing", CobaltListing);
