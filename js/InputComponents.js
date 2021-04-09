class FormRequestElement extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.setup_content();

        // Basically, we want to attach the FormRequest to the <form-request> element.
        // This WebComponent should allow us to make form bots a thing of the past.
        this.request = new FormRequest(this, { asJSON: true, errorField: this.querySelector(".error") });
        if (this.request.autosave === false) {
            this.querySelector("button[type='submit'],input[type='button']").addEventListener('click', (e) => {
                this.send();
            });
        }
        let error = this.querySelector(".error");
        if (!error) {
            error = document.createElement("div");
            error.classList.add("error");
            let button = this.stages[0].querySelector("button[type='submit']");
            this.stages[0].appendChild(error);
        }
        this.request.errorField = error;
    }

    async send() {
        let allow_final_stage = false;
        await this.advance();
        try {
            await this.request.send(this.request.build_query());
            allow_final_stage = true;
        } catch (error) {
            console.log(error);
            await this.regress();
        }
        if (!allow_final_stage) return;
        try {
            await this.confirm_stage();
            await this.advance();
        } catch (error) {
            this.stages[1].innerHTML("Your data was submitted.");
        }
    }

    setup_content() {
        this.pointer = 0;
        this.stages = [];
        this.stages[0] = document.createElement("section");
        this.stages[0].innerHTML = this.innerHTML;
        this.stages[0].classList.add("form-request--actual");
        this.innerHTML = "";
        this.appendChild(this.stages[0]);

        this.stages[1] = document.createElement("section");
        this.stages[1].innerHTML = "<loading-spinner></loading-spinner>";
        this.stages[1].classList.add("form-request--processing", "next");
        this.appendChild(this.stages[1]);

        this.stages[2] = document.createElement("section");
        this.stages[2].classList.add("form-request--complete", "next");
        this.appendChild(this.stages[2]);
    }

    async confirm_stage() {
        let confirm = this.getAttribute("success");
        let page = "<p>Your form was submitted successfully.</p>";
        if (confirm) page = await new ApiFetch(`/api/v1/page?route=${encodeURI()}`, "GET", {});
        this.stages[2].innerHTML = page;
    }

    advance() {
        return new Promise((resolve, reject) => {
            this.stages[this.pointer].addEventListener("transitionend", () => {
                resolve();
                clearTimeout(failsafe);
            }, { once: true })
            this.stages[this.pointer].classList.add("previous");
            this.stages[this.pointer].classList.remove("current");
            this.pointer++;
            this.stages[this.pointer].classList.add("current");
            this.stages[this.pointer].classList.remove("previous");
            let failsafe = setTimeout(() => {
                resolve();
            }, 600)
        })
    }

    regress() {
        return new Promise((resolve, reject) => {
            this.stages[this.pointer].addEventListener("transitionend", () => {
                resolve();
                clearTimeout(failsafe);
            }, { once: true })
            this.stages[this.pointer].classList.add("next");
            this.stages[this.pointer].classList.remove("current");
            this.pointer--;
            this.stages[this.pointer].classList.add("current");
            this.stages[this.pointer].classList.remove("previous");
            let failsafe = setTimeout(() => {
                resolve();
            }, 600)
        })
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

/**
 * @todo complete this.
 */
class AudioPlayer extends HTMLElement {
    constructor() {
        super();
    }
    connectedCallback() {
        this.innerHTML = `<audio preload="metadata" controls>${this.innerHTML}If you can see this, you need a better web browser.</audio>`;
        // this.audio = this.querySelector("audio");
        // this.progress = this.querySelector("[name='progress']");
        // this.volume = this.querySelector("[name='volume']");
        // this.duration = this.querySelector("[name='duration']");
    }

    controls() {
        return `
            <!--<button name="playpause"><ion-icon name="play"></ion-icon><ion-icon name="pause"></ion-icon></button>
            <input type="range" name="progress" min="0" max="100">
            <span name="duration">0:00</span>
            <input type="range" name="volume" min="0" max="100">-->
        `;
    }

    initListeners() {
        this.audio.addEventListener('loadedmetadata', () => {
            this.duration.textContent = this.calculateTime(this.audio.duration);
        })
        // this.
    }

    calculateTime(to_convert) {
        const minutes = Math.floor(to_convert / 60);
        const seconds = Math.floor(to_convert % 60);
        const returnedSeconds = seconds < 10 ? `0${seconds}` : `${seconds}`;
        return `${minutes}:${returnedSeconds}`;
    }
}

customElements.define("audio-player", AudioPlayer)