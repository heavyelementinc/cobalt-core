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
        this.PARAMS = {}
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
        if(this.search_param !== null) url.searchParams.set("search", this.search_param);
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
                select: {
                    label: "Select",
                    classes: "modal-confirm-button",
                    dangerous: false,
                    callback: this.makeSelection.bind(this)
                }
            }
        });
        this.modal.draw();
        this.body = document.createElement("div")
        this.body.classList.add("object-gallery--selection-window");
        const dialog = this.modal.content
        dialog.appendChild(this.body);
        this.page(0);
        
        const button_row = document.createElement("div");
        button_row.classList.add("hbox");
        dialog.appendChild(button_row);
        
        const search_box = document.createElement("input");
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

        const prev_page = document.createElement("button");
        prev_page.innerHTML = "<i name='chevron-left'></i>";
        prev_page.addEventListener("click", () => this.page(-1));
        button_row.appendChild(prev_page);

        const next_page = document.createElement("button");
        next_page.innerHTML = "<i name='chevron-right'></i>";
        next_page.addEventListener("click", () => this.page(1));
        button_row.appendChild(next_page);
    }

    async page(direction) {
        if(typeof direction !== "number") direction = 0;
        this.PARAMS.page += direction;
        const api = new AsyncFetch(this.action, this.method);
        const result = await api.submit();
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
                html: el.nextSibling.innerHTML
            });
        }
        this.dispatchEvent(new CustomEvent("selection", {detail: selection}));
    }
}

customElements.define("object-picker", ObjectPicker);