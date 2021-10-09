/** Async Buttons are meant to run a single async command and, optionally, submit
 * its value attribute. If a value attribute is specified, the request is sent as
 * a POST request, otherwise it's sent as a GET.
 * 
 * Async Buttons will show a button-working inside them while the request processes,
 * will show a checkmark when completed, and will show an error icon on failure.
 * 
 * Use the following attributes:
 *  - action  [url fragment] the API endpoint to reach out to
 *  - once    [true|false] will disable the button once completed
 *  - value   [string] the value to be submitted as a post request
 */
class AsyncButton extends HTMLButtonElement {
    constructor() {
        super();
        this.request = null;
        this.data = null; // The "value" attribute
        this.once = false;
        this.statusMessage = null;
    }

    connectedCallback() {
        this.innerHTML = this.spinner() + this.innerHTML;
        this.spinner = this.querySelector("svg");
        this.addEventListener("click", (e) => this.send());
    }

    static get observedAttributes() {
        return ['value', 'once', 'action'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this[name] = newValue;
    }

    /**
     * @param {string} data
     */
    set value(data) {
        let json = null;
        try {
            // Let's try parsing the data as JSON
            json = JSON.parse(data);
            this.data = json;
        } catch (e) {
            // If parsing fails, set this.data to data
            this.data = data;
        }
    }

    async send() {
        this.requestStart();
        const action = this.getAttribute("action");
        this.request = new ApiFetch(action, this.getMethod(), {});
        try {
            let result = await this.request.send(this.data);
            this.requestSuccess();
        } catch (e) {
            this.requestFailure(e, action);
        }
    }

    getMethod() {
        if (this.getAttribute("method")) return this.getAttribute("method");
        if (this.data) return "POST";
        return "GET";
    }

    requestStart() {
        if (this.statusMessage !== null) {
            console.log(this.statusMessage);
            this.statusMessage.close();
        }
        this.classList.add("working");
        this.classList.remove("error");
    }

    requestSuccess() {
        const dims = get_offset(this);
        this.classList.remove("working");
        this.style.width = `${dims.width}px`;
        this.innerHTML = this.getAttribute("success") || "Success";
        let classes = ["done"];
        if (this.once) classes.push("final");
        this.classList.add(...classes);
    }

    requestFailure(e, action) {
        this.classList.remove("working");
        this.classList.add("error");
        this.statusMessage = new StatusError({ message: e.result.error, id: action });
    }

    spinner() {
        //            transform="matrix(3.7795276,0,0,3.7795276,-0.68538802,-1.1508607)">
        return `<svg version="1.1" id="spinner" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
        <circle
        style="fill:none;stroke:currentColor;stroke-width:4;stroke-linecap:round;"
        id="path2632"
        cx="8"
        cy="8"
        r="5" />
    </svg>`
    }
}

customElements.define("async-button", AsyncButton, { extends: 'button' });