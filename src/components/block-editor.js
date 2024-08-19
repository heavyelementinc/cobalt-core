class BlockButton {

    constructor({data}) {
        this.LABEL_CLASS = "blockbutton--label";
        this.HREF_CLASS = "blockbutton--href";
        this.data = data;
    }

    static get toolbox() {
        return {
            title: "Button",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="17" height="17"><path d="M20 20.5C20 21.3 19.3 22 18.5 22H13C12.6 22 12.3 21.9 12 21.6L8 17.4L8.7 16.6C8.9 16.4 9.2 16.3 9.5 16.3H9.7L12 18V9C12 8.4 12.4 8 13 8S14 8.4 14 9V13.5L15.2 13.6L19.1 15.8C19.6 16 20 16.6 20 17.1V20.5M20 2H4C2.9 2 2 2.9 2 4V12C2 13.1 2.9 14 4 14H8V12H4V4H20V12H18V14H20C21.1 14 22 13.1 22 12V4C22 2.9 21.1 2 20 2Z" /></svg>'
        }
    }

    render() {
        const container = document.createElement("div");
        container.innerHTML = `
        <label style="margin: 0">Button</label><br>
        <input class="${this.LABEL_CLASS}" placeholder="Button Label" value="${this.data.label ?? ""}" style="width: 100%; box-sizing: border-box;">
        <input class="${this.HREF_CLASS}" placeholder="Button URL" value="${this.data.url ?? ""}" style="width: 100%; box-sizing: border-box;">
        `

        container.style.border = "1px solid var(--project-color-button-init)";
        container.style.backgroundColor = "var(--project-color-button-hover)";
        container.style.color = "var(--project-color-button-hover-text)";
        container.style.padding = ".7em";

        return container;
    }

    save(blockContent) {
        return {
            label: blockContent.querySelector(`.${this.LABEL_CLASS}`).value,
            url: blockContent.querySelector(`.${this.HREF_CLASS}`).value,
        }
    }

}

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
        this.initEditor();
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
                blockbutton: BlockButton
            },
            onChange: (api, event) => {
                this.hasChangeOccurred = true;
                console.log(`Change: ${this.hasChangeOccurred}`, {api, event});
            }
        });
        this.addEventListener("focusout", event => {
            if(this.hasChangeOccurred === false) {
                console.log(`Change: ${this.hasChangeOccurred}`)
                return;
            }
            this.dispatchEvent(new Event("change", {detail: {api: {}, event}}))
            this.hasChangeOccurred = false;
        });
        this.addEventListener("focusin", event => {
            if(this.hasChangeOccurred === false) {
                console.log(`Change: ${this.hasChangeOccurred}`);
                return;
            }
            this.dispatchEvent(new Event("change", {detail: {api: {}, event}}));
            this.hasChangeOccurred = false;
        });
        this.addEventListener("change", event => {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        });
        this.addEventListener("input", event => {
            // event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
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

    /** @var object{time: int, blocks: object, version: string} */
    set value(val) {
        this.props.editor.data = val;
    }

    get autosaveTimeout() {
        const num = this.getAttribute("autosave-timeout")
        if(num) return Number(num) * 1000;
        return 4 * 1000;
    }

}

customElements.define("block-editor", BlockEditor);