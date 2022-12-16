class CustomButton extends HTMLElement {
    constructor() {
        super();
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
    }

    initAriaAttributes() {
        if(!this.getAttribute("tabindex")) this.setAttribute("tabindex","0");
        this.setAttribute("tabindex","0");
        this.setAttribute("role","button");
    }
}
