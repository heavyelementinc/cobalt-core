class MarkdownArea extends HTMLElement {
    constructor() {
        super();
        this.props = {
            originalTextContent: "",
            changed: false,
        }
        this.editor = null;
    }

    connectedCallback() {
        if(this.editor !== null) return;
        this.props.originalTextContent = this.innerHTML;
        this.innerHTML = `
        <div class='toolbar'></div>
        <textarea class='editor'>${this.props.originalTextContent}</textarea>
        `
        this.editor = new SimpleMDE({
            autoDownloadFontAwesome: false,
            element: this.querySelector(".editor"),
            placeholder: this.getAttribute("placeholder"),

        });
        // this.value = this.props.originalTextContent;

        // this.editor.addEventListener("change", e => {
        //     e.stopPropagation();
        // });

        this.editor.codemirror.on("change", e => {
            this.props.changed = true;
        });

        this.editor.codemirror.on("blur", e => {
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
}

customElements.define("markdown-area", MarkdownArea);