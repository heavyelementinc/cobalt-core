class AsyncButton extends CustomButton{
    constructor() {
        super();
        this.checkmarkQuery = ".doc_id_mark input[name='_id'][type='checkbox']";
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.addEventListener("click", e => {
            this.submit();
        })
        if(this.type !== "multidelete") return;
        this.setDisabledState();
        const checkboxes = document.querySelectorAll(this.checkmarkQuery);
        for(const check of checkboxes) {
            check.addEventListener("change", () => {
                this.setDisabledState(checkboxes);
            });
        }
    }

    submit() {
        if(this.disabled === true) {
            this.shakeNo();
            return;
        }
        this.ariaInvalid = false;
        const api = new AsyncFetch(this.getAttribute("action") || this.getAttribute("href"), this.getAttribute("method") ?? "POST", {});
        api.addEventListener("submit",  e => this.startSpinner.bind(this));
        api.addEventListener("aborted", e => this.endSpinner.bind(this));
        api.addEventListener("error",   e => this.error.bind(this));
        api.addEventListener("done",    e => this.done.bind(this));
        api.submit(this.value, {});
    }

    get value() {
        let val = {};
        switch(this.type) {
            case "multidelete":
                const id_boxes = document.querySelectorAll(this.checkmarkQuery);
                val._ids = [];
                for(const box of id_boxes) {
                    if(!box.checked) continue;
                    val._ids.push(box.value);
                }
                break;
            default:
                val = this.getAttribute("value");
                if(val) {
                    try {
                        val = JSON.parse(val);
                    } catch (error) {}
                }
                break
        }
        return val;
    }

    set value(val) {
        this.setAttribute("value", JSON.stringify(val));
    }

    get type() {
        return this.getAttribute("type");
    }

    spinner() {
        let spinner = document.createElement("div");
        spinner.innerHTML(`<svg version="1.1" id="spinner" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg"><circle style="fill:none;stroke:currentColor;stroke-width:4;stroke-linecap:round;" id="path2632" cx="8" cy="8" r="5" /></svg>`);
        return spinner.children[0];
    }

    startSpinner(event) {
        this.dispatchEvent(new Event("submit", event));
        if(!this.spinnerInstance) this.spinnerInstance = this.spinner();
        this.appendChild(this.spinnerInstance);
    }

    endSpinner(event) {
        this.removeChild(this.spinnerInstance);
        this.dispatchEvent(new CustomEvent("aborted", event));
    }

    error(event) {
        this.ariaInvalid = true;
        this.dispatchEvent(new Event("error", event));
    }

    done(event) {
        this.ariaInvalid = false;
        this.dispatchEvent(new CustomEvent("done", event));
    }

    setDisabledState() {
        let val = this.value._ids
        if(val.length === 0) {
            this.disabled = true;
            return;
        }
        
        this.disabled = false;
    }
}

customElements.define("async-button", AsyncButton);// { extends: 'button' });
