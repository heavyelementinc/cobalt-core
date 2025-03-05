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


class CaptchaButton extends CustomButton {
    constructor() {
        super();
    }

    connectedCallback() {
        this.innerHTML = `
        <div class="status"></div>
        <div class="container">
            <h1>${(this.hasAttribute("title")) ? this.getAttribute("title") : "Are You Human?"}</h1>
            <p>We need to check to make sure bots aren't sending spam our way.</p>
        </div>
        `;
        this.tabIndex = 1;
        // this.inert = true;
        this.clickHandler();
    }

    clickHandler() {
        this.addEventListener("mouseup", (e) => {
            if(this._xauth_element) return;
            console.log(e);
            const status = this.querySelector(".status");
            status.innerHTML = "<loading-spinner></loading-spinner>";
            this.classList.add('checking');
            setTimeout(() => {
                this.classList.remove('checking');
                this.classList.add('activated');
            }, 1300);
            setTimeout(() => {
                const input = document.createElement("input")
                input.type = 'hidden';
                input.name = '_xauth';
                input.value = document.querySelector('[name="mitigation"]').getAttribute("content");
                this._xauth_element = input;
                this.appendChild(input);
                this.dispatchEvent(new CustomEvent("captcha"));
                let form = this.closest("form-request");
                switch(this.submit) {
                    case "submit":
                        form.submit();
                        break;
                    case "validate":
                        form.dispatchEvent(new CustomEvent("captcha"));
                        break;
                }
                status.innerHTML = "";
            }, 1800);
        })
    }

    get submit() {
        return this.getAttribute("submit") ?? "validate";
    }
}

customElements.define("captcha-button", CaptchaButton);