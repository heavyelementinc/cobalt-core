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
        element.addEventListener("input", event => {
            event.bubbles = true;
        })

        let renderConfig = {}
        if(this.hasAttribute("syntax-highlighting") && this.getAttribute("true")) {
            // renderConfig.codeSyntaxHighlighting = true;
        }
        this.editor = new SimpleMDE({
            autoDownloadFontAwesome: false,
            element,
            placeholder: this.getAttribute("placeholder"),
            renderingConfig: renderConfig
        });
        // this.value = this.props.originalTextContent;

        // this.editor.addEventListener("change", e => {
        //     e.stopPropagation();
        // });

        this.editor.codemirror.on("change", e => {
            this.props.changed = true;
        });

        this.editor.codemirror.on("paste", (cm, event) => {
            this.imagePasteListener(event);
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

    async imagePasteListener(event) {
        const items = event.clipboardData?.items;
        if (!items) return;

        for (const item of items) {
            if (!item.type.startsWith('image/')) continue;
            const file = item.getAsFile();
            if (file) {
                event.preventDefault(); // Prevents default text-pasting behavior
                const image = await this.imageUpload(file);
                this.editor.codemirror.replaceSelection(`![](${image.file.url})`);
                break;
            }
        }
    }

    async imageUpload(image) {
        const endpoint = this.getAttribute("action") ?? "/api/v1/block-editor/upload/";
        const formData = new FormData();
        formData.append('image', image, 'pasted-image.png');
        const response = await fetch(endpoint, {
            method: "POST",
            body: formData
        });
        if(!response.ok) {
            new StatusError("Failed to upload");
            return;
        }
        const result = await response.json();
        return result;
    }
}