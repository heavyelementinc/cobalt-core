class InputText extends HTMLElement {

    constructor() {
        super();
        this.input = document.createElement("input");
        this.min = this.getAttribute("min");
        this.max = this.getAttribute("max");
        this.pattern = this.getAttribute("pattern");
        this.name = this.getAttribute("name");
        this.setAttribute("__custom-input", "true");
    }
    
    connectedCallback() {
        this.input.type = "text";
        this.appendChild(this.input);
        this.value = this.getAttribute("value");

        // if(this.min) this.input.minLength = this.min;
        // if(this.max) this.input.maxLength = this.max;
        if(this.pattern) this.input.pattern = this.pattern;

        this.charDisplay = document.createElement("span");
        this.charDisplay.classList = "input-text--char-count";
        this.appendChild(this.charDisplay);
        this.updateCharCount();
        this.initMinMax();
    }

    observedAttributes() {
        return [
            'min',
            'max',
            'value'
        ];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if(!this.isConnected) return;
        switch(name) {
            case "value":
                this.input = newValue;
                break;
        }
    }

    get value() {
        return this.input.value;
    }

    set value(val) {
        this.input.value = val;
    }

    initFields() {

    }

    initMinMax() {
        this.input.addEventListener("input", (e) => {
            this.updateCharCount();
        });
    }

    updateCharCount() {
        let charCount = this.value.length
        let flag = false;
        if(this.min > charCount) flag = "This field contains too few characters";
        else if (this.max < charCount) flag = "This field contains too many characters";

        this.charDisplay.innerText = charCount;

        if(flag) {
            this.ariaInvalid = true;
            this.setAttribute("invalid", "invalid");
            // switch(flag) {
            //     case 1:
            //     case 2:
            //         this.input.validity.valid = false;
            //         break;
            // }
            this.charDisplay.title = flag;
            return;
        }

        // for(const i of ['valid']) {
        //     this.input.validity[i] = true;
        // }
        this.charDisplay.title = "";
        this.ariaInvalid = false;
        this.removeAttribute("invalid");
    }
}

customElements.define("input-text", InputText);
