class InputSwitch extends HTMLElement {
    constructor() {
        super();
        this.tabIndex = 0;
        this.valueInitialized = false;
        this.ariaRoleDescription = "button";
        this.checkbox = document.createElement("input");
        this.checkbox.type = "checkbox";
        this.appendChild(this.checkbox);
        this.addEventListener("keyup", e => {
            if (!["Space", "Enter", "Return"].includes(e.code)) return;
            this.value = !this.value;
            this.dispatchEvent(new Event("change",{bubbles: true}));
        });
    }

    connectedCallback() {
        if(this.valueInitialized == false) {
            let isChecked = this.getAttribute("checked");
            if(isChecked == "null" || isChecked == null) isChecked = this.getAttribute("value");
            if(isChecked == "null" || isChecked == null) isChecked = this.checked;
            this.value = isChecked;
            this.valueInitialized = true;
        }
    }
    
    get value() {
        return this.checked;
    }

    set value(val) {
        this.checked = val;
    }

    get checked() {
        switch(this.checkbox.checked) {
            case true:
            case false:
                return this.checkbox.checked;
            default:
                return null;
        }
    }

    set checked(val) {
        this.valueInitialized = true;
        if(typeof val == "string") val = val.toLowerCase();
        switch(val) {
            case true:
            case "true":
            case "checked":
            case "on":
                // this.setAttribute("value", "true");
                this.checkbox.checked = true;
                this.checkbox.indeterminate = false;
                this.ariaChecked = true;
                break;
            case false:
            case "false":
            case "unchecked":
            case "off":
                // this.setAttribute("value", "false");
                this.checkbox.checked = false;
                this.checkbox.indeterminate = false;
                this.ariaChecked = false;
                break;
            case null:
            case "indeterminate":
            default:
                // this.setAttribute("value", "null");
                this.checkbox.checked = false;
                this.checkbox.indetermine = true;
                this.ariaChecked = null;
        }
    }

    get disabled() {
        return (this.getAttribute("disabled") === "disabled") ? true : false;
    }

    set disabled(val) {
        if(typeof val == "string") val = val.toLowerCase();
        switch(val) {
            case true:
            case "true":
            case "disabled":
                this.setAttribute("disabled", "disabled");
                this.ariaDisabled = true;
            default:
                this.removeAttribute("disabled");
                this.ariaDisabled = false;
        }
    }

}

customElements.define("input-switch", InputSwitch);