import ICustomInput from "./ICustomInput.js";

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

class Reply {
    constructor({data}) {
        this.REPLY_TO_CLASS = "reply--to-url";
        this.REPLY_TO_QUOTE = "reply--to-quote";
        this.REPLY_RESPONSE = "reply--response";
        this.data = data;
    }
    static get toolbox() {
        return {
            title: "Reply (Webmention)",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>reply</title><path d="M10,9V5L3,12L10,19V14.9C15,14.9 18.5,16.5 21,20C20,15 17,10 10,9Z" /></svg>'
        }
    }

    render() {
        const container = document.createElement("div");
        container.classList.add("content-editor--webmention-group")
        container.innerHTML = `
        <h2>${this.constructor.toolbox.icon} ${this.constructor.toolbox.title}</h2>
        <details class="content-editor--small">
            <summary>How do Replies work?</summary>
            <p>The Reply tool allows you to reply to content on other websites 
            that support <a href="https://indieweb.org/Webmention">Webmentions</a>. This 
            requires at least a URL and a Comment to be specified.</p>
            <p>Other sites which support Webmentions may even show your reply as a 
            comment on their page!</p>
            <small>"Replies" requires this site to have sending Webmentions enabled.</small>
        </details>
        <label>URL</label>
        <input class="${this.REPLY_TO_CLASS}" value="${this.data.to_url || ""}"><br>
        <label>Quote</label>
        <input class="${this.REPLY_TO_QUOTE}" value="${this.data.to_quote || ""}"><br>
        <label>Comment</label>
        <textarea class="${this.REPLY_RESPONSE}">${this.data.response || ""}</textarea><br>
        `
        return container;
    }

    save(blockContent) {
        return {
            to_url:  blockContent.querySelector(`.${this.REPLY_TO_CLASS}`).value,
            to_quote: blockContent.querySelector(`.${this.REPLY_TO_QUOTE}`).value,
            response: blockContent.querySelector(`.${this.REPLY_RESPONSE}`).value,
        }
    }
}

class Like {
    constructor({data}) {
        this.REPLY_TO_CLASS = "reply--to-url";
        this.data = data;
    }
    static get toolbox() {
        return {
            title: "Like (Webmention)",
            icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><title>heart</title><path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z" /></svg>'
        }
    }

    render() {
        const container = document.createElement("div");
        container.classList.add("content-editor--webmention-group")
        container.innerHTML = `
        <h2>${this.constructor.toolbox.icon} ${this.constructor.toolbox.title}</h2>
        <details class="content-editor--small">
            <summary>How do Likes work?</summary>
            <p>The Like tool allows you to "like" content on other websites which 
            support <a href="https://indieweb.org/Webmention">Webmentions</a>. This 
            requires a URL to be specified.</p>
            <p>Other sites which support Webmentions may show your like on their page!</p>
            <small>"Likes" requires this site to have sending Webmentions enabled.</small>
        </details>
        <label>URL</label>
        <input class="${this.REPLY_TO_CLASS}" value="${this.data.to_url || ""}"><br>
        `
        return container;
    }

    save(blockContent) {
        return {
            to_url:  blockContent.querySelector(`.${this.REPLY_TO_CLASS}`).value,
        }
    }
}

export default class BlockEditor extends ICustomInput {
    DEFAULT_TIMEOUT = 2550;
    /** @property {EditorJS|null} __editor__ */
    __editor__ = null;
    __synchronousValue__ = {};
    constructor() {
        super();
        this.props = {
            editor: null
        }
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
        this.__editor__ = new EditorJS({
            holder: this,
            data: data,
            tools: {
                header: Header,
                quote: Quote,
                rawtool: RawTool,
                reply: Reply,
                like: Like,
                simpleimage: SimpleImage,
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
                embed: {
                    class: Embed,
                    inlineToolbar: true,
                    config: {
                        services: {
                            steam: {
                                // regex: /https?:\/\/codepen.io              \/([^\/\?\&]*)\/pen\/([^\/\?\&]*)/,
                                regex: /https?:\/\/store.steampowered.com\/app\/([^\/\?\&]*)\/([^\/\?\&]*)\//,
                                embedUrl: 'https://store.steampowered.com/widget/<%= remote_id %>',
                                html: '<iframe frameborder="0" width="646" height="190" allowtransparency="true"></iframe>',
                                height: 190,
                                width: 646,
                                id: (groups) => groups.join('/embed/')
                            }
                        }
                    }
                },
                // checklist: Checklist,
                inlinecode: InlineCode,
                table: Table,
                marker: Marker,
                blockbutton: BlockButton,
            },
            onChange: (api, event) => {
                event.preventDefault();
                event.stopPropagation();
                this.hasChangeOccurred = true;
                // this.setSyncValue().then(() => {
                //     this.dispatchEvent(new Event("change", {bubbles: true}));
                // });
                // this.dispatchEvent(new Event("change", {bubbles: true}));
            }
        });
        // Let's wait for the editor to solve this
        this.__editor__.isReady.then(() => {
            this.customInputReady.resolve(true);
        });

        this.addEventListener("focusout", async event => {
            if(this.hasChangeOccurred === false) {
                return;
            }
            // await this.setSyncValue();
            this.dispatchEvent(new Event("change", {bubbles: true, detail: {api: {}, event}}))
            this.hasChangeOccurred = false;
        });
        this.addEventListener("focusin", async event => {
            if(this.hasChangeOccurred === false) {
                return;
            }
            // await this.setSyncValue();
            this.dispatchEvent(new Event("change", {bubbles: true, detail: {api: {}, event}}));
            this.hasChangeOccurred = false;
        });
    }

    async setSyncValue() {
        await this.__editor__.isReady;
        this.__synchronousValue__ = await this.__editor__.save();
    }

    get value() {
        return this.__editor__.save();
    }

    /** @var object{time: int, blocks: object, version: string} */
    set value(val) {
        this.__editor__.data = val;
    }
}