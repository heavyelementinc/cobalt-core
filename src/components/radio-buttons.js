// RadioButtons is a wrapper element that sets up toggleable radio buttons in a
// ARIA-compliant way.
class RadioButtons extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.buttons = this.querySelectorAll("button, async-button, input[type='button']");
        this.initButtons();
    }

    initButtons() {
        for(const el of this.buttons) {
            this.initButton(el);
        }
        if(this.getAttribute("clear")) this.initClearButton();
    }

    initButton(el) {
        el.addEventListener("click", event => {
            this.buttonPressed(el, true, true);
        });

        if(
            this.getAttribute("controls") === "hash" && location.hash.substring(1) === el.value
            || el.getAttribute("aria-pressed") == "true"
            || el.getAttribute("selected")
        ) this.buttonState(el, true, false);
    }

    buttonPressed(btn, state, triggerEvent = false) {
        this.resetButtons();
        this.buttonState(btn, state, triggerEvent);
        this.buttonControls(btn.value, this.getAttribute("controls"), btn);
    }

    resetButtons() {
        for(const el of this.buttons) {
            this.buttonState(el, false);
        }
    }

    buttonState(btn, state, triggerEvent = false) {
        btn.pressed = state;
        btn.setAttribute("aria-pressed", JSON.stringify(state));
        if(triggerEvent) btn.dispatchEvent(new Event("change",{bubbles: true}));
    }

    togglePressedState(btn, triggerEvent = false) {
        this.buttonState(btn, !btn.state, triggerEvent);
    }

    buttonControls(value, controller, btn) {
        switch(controller) {
            case "hash":
                window.Cobalt.router.hash = `${value}`;
                window.dispatchEvent(new Event("hashchange"));
                break;
        }
    }

    initClearButton() {
        this.clearButton = document.createElement("button");
        this.clearButton.innerHTML = "<i name='backspace'></i>";
        this.clearButton.addEventListener("click", () => {
            switch(this.getAttribute("controls")) {
                case "hash":
                    window.Cobalt.router.hash = "";
                    window.dispatchEvent(new Event("hashchange"));
                    break;
            }
            this.resetButtons();
            this.clearButton.dispatchEvent(new Event("change",{bubbles: true}));
        });
        this.appendChild(this.clearButton);
    }
}

customElements.define("radio-buttons", RadioButtons);
