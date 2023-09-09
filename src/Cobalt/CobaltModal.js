/**
 * @emits modalopen    - When the dialog box opens
 * @emits modalclose   - When the dialog box closes
 * @emits modalcancel  - When the cancel button is pressed
 * @emits modalconfirm - When the confirm button is pressed
 * @emits modalsubmit  - When a form in the modal box is successfully submitted
 */
class Dialog extends EventTarget {
    constructor({
        id = random_string(8),
        classes = "",
        body = "",
        chrome = {},
        close_btn = true,
    }) {
        super();
        this.bodyLockClass = "scroll-locked";
        this.includesForm = false;

        this.container = document.createElement("div");
        this.container.id = id;
        this.container.classList.add("modal-dialog--container");
        this.dialog  = document.createElement("modal-dialog");
        this.content = document.createElement("div");
        this.content.classList.add('modal-dialog--content');
        if(classes) this.content.classList.add(classes.split(" "));
        if(body) this.bodyCache = body;
        this.chrome  = document.createElement("menu");
        this.cancelButton = false;
        this.confirmButton = false;
        if(!chrome) this.addCloseButton();

        if(close_btn) this.addModalClose();
    }

    draw(content = "") {
        if(content) this.body = content;
        else this.body = this.bodyCache;
        this.dialog.appendChild(this.content);
        this.dialog.appendChild(this.chrome);
        this.chrome.style.setProperty("--modal-chrome-count", this.chrome.children.length || 1);
        this.container.appendChild(this.dialog);
        document.body.appendChild(this.container);
        this.dialog.dispatchEvent(new CustomEvent("modalopen"));
        document.body.classList.add(this.bodyLockClass);
        // TODO place focus on first interactable element
    }

    /** 
     * Return false or nothing if you want to close
     */
    addButton(options) {
        const opts = {
            label: "Cancel",
            dangerous: false,
            dispatch: "modalcancel",
            detail: {},
            callback: async (event) => true,
            ...options
        }

        const li = document.createElement("li");
        const btn = document.createElement("button");
        li.appendChild(btn);
        btn.innerHTML = opts.label;
        this.chrome.appendChild(li);

        btn.addEventListener("click", async event => await this.handleChromeClick(event, btn, options));
    }

    addCloseButton(label = "Cancel", detail = {}) {
        if(this.cancelButton) return;
        this.addButton({
            label: label,
            dispatch: "modalcancel",
            detail,
            callback: e => false
        });
        this.cancelButton = true;
    }

    addConfirmButton(label = "Okay", detail = {}) {
        if(this.confirmButton) return;
        
        this.addButton({
            label: label,
            classes: ["modal-confirm-button"],
            dispatch: "modalconfirm",
            detail,
            callback: async e => {
                const formRequest = this.content.querySelector("form-request");
                if(formRequest && !formRequest.autoSave) {
                    const data = await formRequest.submit();
                    this.dispatchEvent(new CustomEvent("modalsubmit", {detail: data}));
                }
                return false
            }
        });

        this.confirmButton = true;
    }

    async handleChromeClick(event, button, options) {
        const evt = new CustomEvent(options.dispatch || "modalclose", {detail: options.detail || {}});
        this.dispatchEvent(evt);
        if(evt.defaultPrevented) return;
        const result = await options.callback(event, button, options, this);
        if(!result) this.close();
        return result;
    }

    addModalClose() {
        const btn = document.createElement("button");
        btn.classList.add("modal-dialog--close")
        btn.innerHTML = window.closeGlyph;
        this.container.appendChild(btn);
        btn.addEventListener("click", e => this.close());
    }

    close() {
        const event = new CustomEvent("modalclose", {detail: this}); 
        this.dispatchEvent(event);
        if(event.defaultPrevented) return;
        document.body.classList.remove(this.bodyLockClass);
        this.container.parentNode.removeChild(this.container);
    }

    set body(value) {
        this.content.innerHTML = value;
        if(this.content.querySelector("form-request")) this.addConfirmButton("Save");
    }

    get body() {
        return this.content.innerHTML;
    }
}