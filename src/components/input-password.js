/**
 * `<input-password>` valid attibutes include:
 *   * value - 
 */
 class InputPassword extends HTMLElement {

    constructor() {
        super();
        this.passwordVisible = false;
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
        this.innerHTML = "<input type='password'><button></button>";
        this.input = this.querySelector("input");
        this.input.addEventListener("change", (e) => {
            e.stopPropagation();
            this.dispatchEvent(new Event("change"));
        });

        this.input.addEventListener("input", (e) => {
            e.stopPropagation();
            this.dispatchEvent(new Event("input"));
        });

        this.updateValue();
        this.button = this.querySelector("button");
        this.initButton();
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

customElements.define("input-password", InputPassword);