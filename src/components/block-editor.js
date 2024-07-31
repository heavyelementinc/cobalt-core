import EditorJS from "/core-content/js/components/editorjs.mjs";

class BlockEditor extends HTMLElement {
    constructor() {
        super();
        this.props = {
            editor: null
        }
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.props.editor = new EditorJS({holder: this});
    }

    get name() {
        return this.getAttribute("name");
    }

    set name(value) {
        this.setAttribute("name", value);
    }

    get value() {
        return this.props.editor.value
    }

}

customElements.define("block-editor", BlockEditor);