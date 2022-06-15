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
        this.label = this.innerHTML;
    }

    connectedCallback() {
        this.innerHTML = this.spinner() + this.innerHTML;
        this.spinner = this.querySelector("svg");
        this.addEventListener("click", (e) => this.send());
    }

    static get observedAttributes() {
        return ['value', 'once', 'action', 'method',];
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
        let result;
        try {
            result = await this.request.send(this.data);
        } catch (e) {
            this.requestFailure(e, action);
        }
        this.requestSuccess(result);
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

    requestSuccess(result) {
        const dims = get_offset(this);
        this.classList.remove("working");
        this.style.width = `${dims.width}px`;
        if (this.once) {
            this.innerHTML = this.getAttribute("success") || "Success";
            classes.push("final");
            let classes = ["done"];
            this.classList.add(...classes);
        } else {
            this.innerHTML = this.label;
            // Add some visual confirmation the action has been taken.
        }
        

        // Implement X-Location, X-Next-Request, & Updating Content
        const headers = this.request.headers;
        if("X-Location" in headers) window.location = headers['X-Location'];
        if("X-Next-Request" in headers) this.handleNextRequest(headers['X-Next-Request']);
        this.handleUpdatingContent(result);
    }

    handleNextRequest(headers) {
        if(headers === null) return;
        if(typeof headers !== "object") return;
        if('action' in headers) this.setAttribute("action", headers['action']);
        if('method' in headers) this.setAttribute('method', headers['method']);
    }

    handleUpdatingContent(result) {
        if(typeof result !== "object") return;
        
        // Let's set up if we are trying to get a page from the API
        const target = this.getAttribute("target"),
            key = this.getAttribute("key") ?? "body";
        // Add it to our results so that the following loop can do it for us
        if(target && key in result) result[target] = result[key];

        for(const i in result) {
            let value_matches = document.querySelectorAll(`[name="${i}"]`);
            if(value_matches) {
                for(const v of value_matches) {
                    v.value = result[i];
                }
            }
            let matches = false;
            switch(i[0]) {
                case "[":
                    if(i[i.length - 1] !== "]") break;
                case "#":
                case ".":
                    matches = document.querySelectorAll(i);
                break;
            }
            if(!matches) continue;
            for(var l of matches) {
                l.outerHTML = result[i];
            }
        }
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