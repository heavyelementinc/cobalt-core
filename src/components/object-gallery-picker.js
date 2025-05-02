class ObjectPicker extends HTMLElement {
    PARAMS = {
        limit: 50,
        page: 0,
    }

    INPUT_TARGET = "label > input[type='checkbox']";

    modal;
    body;
    constructor(method, action) {
        super()
        this.METHOD = method;
        this.ACTION = action;
    }

    connectedCallback() {
        // this.setAttribute("role", "button");
        this.setAttribute("__custom-input", "true");
        this.innerText = "Select Elements";
        this.addEventListener("click", () => {
            this.open();
        });
    }

    get action() {
        let action = this.getAttribute("action");
        if(!action) {
            const closest = this.closest("object-gallery, file-gallery");
            if(!closest) throw new Error("Cannot find an action");
            action = closest.getAttribute("action");
        }
        const url = new URL(action, location.origin);
        url.searchParams.set("limit", this.PARAMS.limit);
        url.searchParams.set("page", this.PARAMS.page);
        if(this.search_param !== null) url.searchParams.set("query", this.search_param);
        return url.toString();
    }

    set action(value) {
        this.setAttribute("action", value);
    }

    get method() {
        let method = this.getAttribute("method");
        if(method) return method;
        const closest = this.closest("object-gallery, file-gallery");
        if(!closest) throw new Error("Cannot find an method");
        return closest.getAttribute("method");
    }

    set method(value) {
        this.setAttribute("method", value);
    }

    async open() {
        this.modal = new Dialog({
            body: "",
            chrome: {
                close: {
                    label: "Cancel",
                    classList: ["modal-close-button"],
                    dangerous: false,
                    callback: () => true
                },
                select: {
                    label: "Select",
                    classList: ["modal-confirm-button"],
                    dangerous: false,
                    callback: this.makeSelection.bind(this)
                }
            }
        });
        this.modal.draw(" ");
        this.body = document.createElement("div")
        this.body.classList.add("object-gallery--selection-window");
        const dialog = this.modal.content
        dialog.innerHTML = "";
        dialog.appendChild(this.body);
        this.page(0);
        
        const button_row = document.createElement("div");
        button_row.classList.add("hbox");
        dialog.appendChild(button_row);
        
        const search_box = document.createElement("input");
        search_box.type = "search";
        search_box.placeholder = "Search here...";
        search_box.addEventListener("input", e => {
            if(search_box.value.length <= 3) {
                this.search_param = null;
                return;
            }
            this.search_param = search_box.value;
            this.PARAMS.page = 0;
            this.page(0);
        });
        button_row.appendChild(search_box);

        this.prev_page = document.createElement("button");
        this.prev_page.disabled = true;
        this.prev_page.innerHTML = "<i name='chevron-left'></i>";
        this.prev_page.addEventListener("click", () => this.page(-1));
        button_row.appendChild(this.prev_page);

        this.next_page = document.createElement("button");
        this.next_page.disabled = true;
        this.next_page.innerHTML = "<i name='chevron-right'></i>";
        this.next_page.addEventListener("click", () => this.page(1));
        button_row.appendChild(this.next_page);
    }

    async page(direction) {
        if(typeof direction !== "number") direction = 0;
        this.PARAMS.page += direction;
        if(this.PARAMS.page < 0) this.PARAMS.page = 0;
        const api = new AsyncFetch(this.action, this.method);
        const result = await api.submit();

        const count = result.count;
        this.prev_page.disabled = false;
        this.next_page.disabled = false;

        if(this.PARAMS.page <= 0) this.prev_page.disabled = true;
        if((this.PARAMS.page * this.PARAMS.limit) > count) this.prev_page.disabled = true;

        this.body.innerHTML = result.html;
        
        const okay_button = this.modal.chrome.querySelector(".modal-confirm-button");

        this.targets = this.body.querySelectorAll(this.INPUT_TARGET);
        this.targets.forEach(e => e.addEventListener("change", event => {
            
            okay_button.disabled = true;
            for(const el of this.targets) {
                if(el.checked) {
                    return okay_button.disabled = false;
                }
            }
        }));
    }

    makeSelection() {
        const selected = document.querySelectorAll(`${this.INPUT_TARGET}:checked`);
        let selection = [];
        for(const el of selected) {
            selection.push({
                id: el.value,
                html: el.nextSibling?.innerHTML
            });
        }
        this.dispatchEvent(new CustomEvent("selection", {detail: selection}));
    }
}

customElements.define("object-picker", ObjectPicker);