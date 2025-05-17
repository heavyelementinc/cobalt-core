import ICustomInput from "./ICustomInput.js";

/**
 * `<input-password>` valid attibutes include:
 *   * value - 
 */
 export default class InputPassword extends ICustomInput {
    passwordVisible = false;
    input = null;
    button = null;

    constructor() {
        super();
        // this.setAttribute("__custom-input", "true");
    }

    observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if(!this.isConnected) return;
        switch(name) {
            case "value":
                this.updateValue(newValue);
                break;
        }
    }

    get value() {
        return this.input.value;
    }

    set value(val) {
        this.updateValue(val);
    }

    connectedCallback() {
        let autocomplete = (this.hasAttribute("autocomplete")) ? ` autocomplete="${this.getAttribute('autocomplete')}"`:"";
        let placeholder = (this.hasAttribute("placeholder")) ? ` placeholder="${this.getAttribute("placeholder")}"` : "";
        let autofocus = (this.hasAttribute("autofocus")) ? ` autofocus="autofocus"` : "";
        this.innerHTML = `<input type='password'${placeholder}${autocomplete}${autofocus}><button></button>`;
        this.input = this.querySelector("input");
        this.input.addEventListener("change", (e) => {
            e.stopPropagation();
            this.dispatchEvent(new Event("change",{bubbles: true}));
        });

        this.input.addEventListener("input", (e) => {
            e.stopPropagation();
            this.dispatchEvent(new Event("input"));
        });

        this.updateValue();
        this.button = this.querySelector("button");
        this.initButton();
        this.input.focus();
        this.customInputReady.resolve(true)
    }

    updateValue(value = null) {
        if(value === null && this.getAttribute("value")) this.input.value = this.getAttribute("value");
        this.removeAttribute("value");
    }

    initButton() {
        this.button.addEventListener("click",() => {
            this.passwordVisible = !this.passwordVisible;
            if(this.passwordVisible) this.input.type = "text";
            else this.input.type = "password";
        });
    }

}