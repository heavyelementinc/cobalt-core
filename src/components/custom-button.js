class CustomButton extends HTMLElement {
    constructor() {
        super();
        this.props = {
            disabled: false
        }
        this.setAttribute("__custom-input", "true");
    }
    
    get disabled() {
        return this.ariaDisabled || false;
    }

    set disabled(state) {
        if(typeof state === "boolean") state = Boolean(state);
        this.ariaDisabled = state;
    }

    connectedCallback() {
        this.initListeners();
        this.initAriaAttributes();
    }

    initListeners() {
        this.addEventListener("keydown", event => {
            switch(event.key) {
                case "Space":
                case "Enter":
                case "Return":
                    this.dispatchEvent(new Event("click"));
                    break;
            }
        });
        this.addEventListener("click", event => {
            const disabled = this.disabled;
            if(disabled === true || disabled === "true") {
                event.stopImmediatePropagation();
                event.stopPropagation();
                event.preventDefault()
                this.shakeNo();
            }
        });
    }

    initAriaAttributes() {
        if(!this.getAttribute("tabindex")) this.setAttribute("tabindex","0");
        this.setAttribute("tabindex","0");
        this.setAttribute("role","button");
    }

    shakeNo() {
        this.addEventListener("animationend", () => this.classList.remove("status-message--no"), {once: true});
        this.classList.add("status-message--no");
    }
}
