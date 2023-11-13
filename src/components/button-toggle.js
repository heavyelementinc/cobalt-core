class ButtonToggle extends HTMLButtonElement {
    constructor() {
        super();
        this.setState();
        this.removeAttribute("value");
        this.addEventListener("click", () => {
            this.value = !this.value;
        });
        this.setAttribute("__custom-input", "true");
    }

    setState(state = null) {
        if(state === null) state = this.getState();
        if(state === "false") this.setAttribute("aria-pressed", "false");
        else this.setAttribute("aria-pressed", "true");
    }

    getState(isBool = false) {
        const state = this.getAttribute("aria-pressed") || this.getAttribute("default") || this.getAttribute("value") || "false";
        if(isBool) return (state === "true") ? true : false;
        return state;
    }

    get value() {
        return this.getState(true);
    }

    set value(val) {
        if(typeof val !== "boolean") throw new TypeError("Cannot set button to a non-boolean state!");
        this.setState(JSON.stringify(val));
    }
}

customElements.define("button-toggle", ButtonToggle, {extends: 'button'});
