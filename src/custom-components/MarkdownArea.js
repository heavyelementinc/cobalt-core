import ICustomInput from "./ICustomInput.js";

export default class MarkdownArea extends ICustomInput {
    constructor() {
        super();
        this.props = {
            originalTextContent: "",
            changed: false,
        }
        this.editor = null;
        // this.setAttribute("__custom-input", "true");
    }
    
    get value() {
        if(this.editor) return this.editor.value();
    }

    set value(val) {
        if(this.editor) return this.editor.value(val);
        this.props.originalTextContent = val;
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
            event.bubbles = true;
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
        this.customInputReady.resolve(true)
    }

    triggerAutosaveChangeEvent() {
        if(this.props.changed === false) return;
        const event = this.dispatchEvent(new Event("change", {bubbles: true}));
        this.props.changed = false;
    }

}