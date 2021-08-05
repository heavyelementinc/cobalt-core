class AsyncWizard extends HTMLElement {
    constructor() {
        this.steps = {};
        this.pointer = -1;
    }

    connectedCallback() {
        this.addChrome();
    }

    next() {

    }

    previous() {

    }

    async getRouteFromAPI(route) {
        const api = new ApiFetch(`/api/v1/pages/?route=${route}`);
        const result = await api.send();

    }

    static get observedAttributes() {
        return ['initial', 'steps', 'back'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_initial(val) {
        this.setProperty("--column-count", val);
    }

    change_handler_back(val) {
        this.backButton = string_to_bool(val);
    }


    addChrome() {

    }
}

