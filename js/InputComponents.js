class FormRequestElement extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        // Basically, we want to attach the FormRequest to the <form-request> element.
        // This WebComponent should allow us to make form bots a thing of the past.
        this.request = new FormRequest(this, { asJSON: true, errorField: this.querySelector(".error") });
        if (this.request.autosave === false) {
            this.querySelector("button[type='submit'],input[type='button']").addEventListener('click', (e) => {
                this.submit(e);
            });
        }
    }
}

customElements.define("form-request", FormRequestElement);

class InputSwitch extends HTMLElement {
    /** InputSwitch gives us a handy way of assigning dynamic functionality to custom
     * HTML tags.
     */
    constructor() {
        super();
        this.tabIndex = "0"; // We want this element to be tab-able
        this.checked = this.getAttribute("checked"); // Let's also get 
        this.disabled = ["true", "disabled"];
    }

    /** The CONNECTED CALLBACK is the function that is executed when the element
     *  is added to the DOM.
     */
    connectedCallback() {
        // Let's figure out if our switch is checked or not and prepare for appending
        // the legit checkbox in the proper state
        let checked = (["true", "checked"].includes(this.checked)) ? " checked=\"checked\"" : "";
        this.innerHTML = `<!-- <input type='hidden' name="${this.getAttribute("name")}"> -->
        <input type="checkbox" name="${this.getAttribute("name")}"${checked}>
        <span aria-hidden="true">`;
        // Now let's find our checkbox
        this.checkbox = this.querySelector("input[type='checkbox']");
        // Check if our checkbox is "indeterminate". This is useful since there's no
        // native HTML way of setting a checkbox to its "indeterminate" state.
        if (['indeterminate', 'unknown', 'null', 'maybe'].includes(this.checked)) this.checkbox.indeterminate = true;
        // Init our listeners for this element
        this.initListeners();
    }

    /** Initialize the listeners on this element */
    initListeners() {
        const disabled = this.getAttribute("disabled")
        if (this.disabled.includes(disabled)) return;
        this.addEventListener("click", this.flipElement);
        this.addEventListener("keyup", this.keyElement);
        this.checkbox.addEventListener('change', this.clearIntermediateState);
    }

    clearListeners() {
        this.removeEventListener("click", this.flipElement);
        this.removeEventListener("keyup", this.keyElement);
        this.checkbox.removeEventListener('change', this.clearIntermediateState);
    }

    /** Flip the checkbox's value */
    flipElement() {
        this.clearIntermediateState({ target: this.checkbox });
        this.checkbox.checked = !this.checkbox.checked;
        const change = new Event("change");
        this.checkbox.dispatchEvent(change);
    }

    clearIntermediateState(e) {
        e.target.indeterminate = false;
    }

    keyElement(e) {
        if (!["Space", "Enter", "Return"].includes(e.code)) return;
        this.flipElement();
    }

    /** When an attribute is changed, this method is called */
    attributeChangedCallback(name, oldValue, newValue) {
        const method = "handle_" + name;
        if (method in this && typeof this[method] === "function") this[method](newValue, oldValue);
    }

    handle_disabled(newValue) {
        if (this.disabled.includes(newValue)) this.clearListeners()
        else this.initListeners();
    }
}

customElements.define("input-switch", InputSwitch);

class RadioButton extends HTMLElement {
    constructor() {
        super();
    }
    connectedCallback() {
        /** MAKE THIS */
    }
}

customElements.define("radio-button", RadioButton)