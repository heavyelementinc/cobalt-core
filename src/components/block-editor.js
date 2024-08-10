class BlockEditor extends HTMLElement {
    constructor() {
        super();
        this.props = {
            editor: null
        }
        this.setAttribute("__custom-input", "true");
        this.saveTimeout = null;
        this.hasChangeOccurred = false;
    }

    connectedCallback() {
        this.id = random_string(12);
        this.initEditor()
    }

    async initEditor() {
        await window.Cobalt.promises.ready;

        this.saveDataElement = this.querySelector("script[type='application/json']");
        this.saveData = this.saveDataElement?.innerText;
        if(!this.saveData) this.saveData = "{}";
        let data
        try {
            data = await JSON.parse(this.saveData);
        } catch (Error) {
            console.warn("Failed to parse JSON. Data loss is HIGHLY PROBABLE!")
        }
        this.props.editor = new EditorJS({
            holder: this,
            data: data,
            tools: {
                header: Header,
                quote: Quote,
                rawtool: RawTool,
                // simpleimage: SimpleImage,
                imagetool: {
                    class: ImageTool,
                    config: {
                        endpoints: {
                            byFile: "/api/v1/block-editor/upload/",
                            byUrl: "/api/v1/block-editor/upload/url/",
                        }
                    }
                },
                linktool: {
                    class: LinkTool,
                    config: {
                        endpoint: "/api/v1/block-editor/link-fetch/"
                    }
                },
                nestedlist: NestedList,
                codetool: CodeTool,
                embed: Embed,
                // checklist: Checklist,
                inlinecode: InlineCode,
                table: Table,
                marker: Marker,
            },
            onChange: (api, event) => {
                this.hasChangeOccurred = true;
                // clearTimeout(this.saveTimeout)
                // this.saveTimeout = setTimeout(() => {
                //     this.hasChangeOccurred = false;
                //     console.log(api, event);
                //     this.dispatchEvent(new Event("change", {detail: {api, event}}));
                // }, this.autosaveTimeout);
            }
        });
        this.addEventListener("focusout", event => {
            if(this.hasChangeOccurred === false) return;
            this.dispatchEvent(new Event("change", {detail: {api: {}, event}}))
            this.hasChangeOccurred = false;
        });
        this.addEventListener("focusin", event => {
            if(this.hasChangeOccurred === false) return;
            this.dispatchEvent(new Event("change", {detail: {api: {}, event}}));
            this.hasChangeOccurred = false;
        });
    }

    get name() {
        return this.getAttribute("name");
    }

    set name(value) {
        this.setAttribute("name", value);
    }

    get value() {
        return this.props.editor.save();
    }

    set value(val) {
        this.props.editor.data
    }

    get autosaveTimeout() {
        const num = this.getAttribute("autosave-timeout")
        if(num) return Number(num) * 1000;
        return 4 * 1000;
    }

}

customElements.define("block-editor", BlockEditor);