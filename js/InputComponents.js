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
            this.querySelector("button[type='submit'],input[type='submit']").addEventListener('click', (e) => {
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


class LoadingSpinner extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        let mode = app("loading_spinner");
        console.log(mode);
        this.classList.add(`mode--${mode}`);
        this.innerHTML = `${this[mode]()}`;

    }

    dashes() {
        return `<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" width="calc(2em * 4)" height="calc(2em * 4)" viewBox="0 0 100 100" version="1.1" id="svg1091"><circle class="spinner-dashes" style="fill:none;stroke:${getComputedStyle(this).color};stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4;stroke-opacity:1" id="path1964" cx="50" cy="50" r="43.098995" /></svg>`
    }

    he() {
        let color = getComputedStyle(this).color;
        return `<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" width="calc(2em * 4)" height="calc(2em * 4)" viewBox="0 0 80 80" version="1.1" id="hE_spinner">
        <g id="hE_spinner--rect" style="stroke-width:7;stroke-miterlimit:4;stroke-dasharray:none">
           <rect style="fill:none;stroke:${color};stroke-width:7;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1" id="rect1399" width="70.463562" height="70.463562" x="4.7682199" y="4.7682199" ry="0" />
        </g>
        <g aria-label="hE" id="hE_spinner--text" style="fill:${color};fill-opacity:1;stroke:none;stroke-width:1.00508" transform="matrix(0.42502761,0,0,0.42502761,-4.5868625,-40.820276)">
           <path d="M 98.342363,225.37105 H 85.412998 v -28.36742 q 0,-5.98224 -2.219219,-8.78039 -2.21922,-2.89463 -6.271707,-2.89463 -1.736781,0 -3.666537,0.7719 -1.929756,0.77191 -3.666536,2.21922 -1.736781,1.35083 -3.184098,3.28059 -1.447317,1.92975 -2.122731,4.24546 v 29.52527 H 51.352804 v -70.4361 H 64.28217 v 29.23581 q 2.798146,-4.92088 7.526048,-7.52605 4.82439,-2.70166 10.613658,-2.70166 4.920878,0 8.008488,1.73678 3.087609,1.64029 4.82439,4.43844 1.73678,2.79815 2.412195,6.36819 0.675414,3.57005 0.675414,7.33308 z" id="path2109" />
           <path d="m 158.45409,213.69602 v 11.67503 H 110.8856 v -68.50634 h 46.7001 v 11.67502 h -33.38478 v 16.49942 h 28.05756 l 0.79229,10.80663 h -28.84985 v 17.85024 z" id="path2111" />
        </g>
     </svg>`
    }
}

if (app("loading_spinner") !== false) customElements.define("loading-spinner", LoadingSpinner)