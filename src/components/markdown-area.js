class MarkdownArea extends HTMLElement {
    constructor() {
        super();
        this.props = {
            originalTextContent: "",
            changed: false,
        }
        this.editor = null;
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        if(this.editor !== null) return;
        this.props.originalTextContent = this.innerHTML;
        this.innerHTML = `
        <div class='toolbar'></div>
        <textarea class='editor'>${this.props.originalTextContent}</textarea>
        `
        const element = this.querySelector(".editor")
        element.addEventListener("change", event => {
            event.stopPropagation();
            event.stopImmediatePropagation();
        });

        this.editor = new SimpleMDE({
            autoDownloadFontAwesome: false,
            element,
            placeholder: this.getAttribute("placeholder")
        });
        // this.value = this.props.originalTextContent;

        // this.editor.addEventListener("change", e => {
        //     e.stopPropagation();
        // });

        this.editor.codemirror.on("change", e => {
            this.props.changed = true;
        });

        this.addEventListener("focusout", e => {
            this.triggerAutosaveChangeEvent();
        });
    }

    triggerAutosaveChangeEvent() {
        if(this.props.changed === false) return;
        const event = this.dispatchEvent(new Event("change", {}));
        this.props.changed = false;
    }

    get value() {
        if(this.editor) return this.editor.value();
    }

    set value(val) {
        if(this.editor) return this.editor.value(val);
        this.props.originalTextContent = val;
    }

    get disabled() {
        return this.props.disabled;
    }

    set disabled(val) {
        if(typeof val !== "boolean") val = Boolean(val);
        this.props.disabled = val;
    }

    get name() {
        return this.getAttribute("name");
    }
}

customElements.define("markdown-area", MarkdownArea);