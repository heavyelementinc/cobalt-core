/**
 * form-request supports the following attributes
 * 
 *  * method - functions identically to a <form method=""> attribute
 *  * action - functions identically to a <form action=""> attribute
 *  * display-mode - 
 *     - "edit" - (default) will allow you to continue editing the form after saving
 *     - "done" - will disable the form after saving and present a "complete" screen
 *  * success-route - a web route to be displayed when display-mode="done"
 *  * success-message - a message to be displayed
 */

class FormRequestElement extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.setup_content();
        this.getRequest();
        if (this.request.autosave === false) {
            // let searchForButtons = this;
            let queries = "button[type='submit'],input[type='submit']";
            // if (this.getAttribute("submit")) {
            //     searchForButtons = this.closest("modal-container");
            //     queries = "button.modal-button-okay";
            // }
            let elements = this.querySelector(queries);
            if (elements) {
                elements.addEventListener('click', (e) => {
                    this.send(e.shiftKey);
                });
            }
        }
        let error = this.querySelector(".error");
        if (!error) {
            error = document.createElement("div");
            error.classList.add("error");
            let button = this.stages[0].querySelector("button[type='submit']");
            this.stages[0].appendChild(error);
        }
        this.error = error;
        this.request.errorField = error;
    }

    getRequest() {
        // Basically, we want to attach the FormRequest to the <form-request> element.
        // This WebComponent should allow us to make form bots a thing of the past.
        this.request = new FormRequest(this, { asJSON: true, errorField: this.querySelector(".error") });
    }

    async send(allowDangerous = false) {
        let allow_final_stage = false;
        let has_error = false;

        await this.advance();
        this.request.reset_errors();
        if (allowDangerous) this.request.headers['X-Confirm-Dangerous'] = "true";
        else delete this.request.headers['X-Confirm-Dangerous'];

        try {
            await this.request.send(this.request.build_query());
            allow_final_stage = true;
        } catch (error) {
            await this.regress();
            has_error = true;
            return has_error;
        }

        this.mode = this.getAttribute("display-mode") ?? "edit";
        if (this.mode === "edit" && allow_final_stage) {
            await this.regress();
            this.error.innerText = this.getAttribute("success-message") || "Success";
            allow_final_stage = false;
        }

        if (!allow_final_stage) return has_error;
        try {
            await this.confirm_stage();
            await this.advance();
        } catch (error) {
            this.stages[1].innerHTML("Your data was submitted.");
        }

        return has_error;
    }

    async submit(allowDangerous = false) {
        return this.send(allowDangerous);
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
        let confirm = this.getAttribute("success-route");
        let page = this.getAttribute("success-message") || "<p>Your form was submitted successfully.</p>";
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


class LoginForm extends HTMLElement {
    connectedCallback() {
        // super.connectedCallback();
        this.button = this.querySelector("button[type='submit']");
        this.getRequest();
        this.button.addEventListener('click', e => this.request.send(e));
        this.addEventListener('keyup', e => {
            if (e.key === "Enter") this.request.send(e)
        })
        this.addEventListener("requestSuccess", e => {
            window.location.reload();
        })
        this.addEventListener("requestFailure", async e => {
            await wait_for_animation(this, "status-message--no")
        })
    }

    getRequest() {
        this.request = new LoginFormRequest(this, {});
    }

}

customElements.define("login-form-request", LoginForm);


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

    get value() {
        return this.checkbox.checked;
    }

    set value(val) {
        this.checkbox.checked = val;
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
        this.dispatchEvent(change);
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

/**
 * radio-groups support the following attributes:
 *
 *  * selected - the name of the radio box to be selected
 *  * default - the default checkbox to be selected
 * 
 * There should only ever be *ONE* name per radio-group
 */
class RadioGroup extends HTMLElement {
    constructor() {
        super();
        this.selected = this.getAttribute("selected");
        this.default = this.getAttribute("default");

        let first = this.querySelector("[input='radio']");
        if (first) this.name = first.getAttribute("name");
        if (this.selected) this.updateSelected(this.selected);
        else if (this.default) this.updateSelected(this.default);
    }

    updateSelected(selected) {
        let updateQuery = "";
        if (this.name) updateQuery = `[name="${this.name}"]`
        const candidate = this.querySelector(`${updateQuery}[value="${selected}"]`);
        if (candidate) candidate.checked = true;
    }

}

customElements.define("radio-group", RadioGroup)


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

/**
 * `<input-array>` valid attibutes include:
 *   * name - the name of the array
 *   * value - a JSON-encoded array (can be &quot; escaped)
 *   * readonly - [false] 'true' disables addition and removal of items from list
 *   * multiselect - [false] 'true' allows a single <option> to be added multiple times
 *   * allow-custom - [false] 'true' allow the user to add custom entries
 *   * pattern - [""] the pattern for custom elements to be matched against
 */
class InputArray extends HTMLElement {

    static get observedAttributes() {
        return ['value', 'allow-custom', 'multiselect',
            // 'readonly'
        ];
    }

    constructor() {
        super();

        /** Establish our values */
        this.optHTML = "";
        this.searchField = null;
        this.fieldSet = null;
        this.arrowKeySelectionIndex = -1;
        this.customElementClass = "input-array--list-custom";

        this.name = this.getAttribute("name") || null;
        this.readonly = this.getAttribute("readonly") || "false";
        this.multiSelect = this.getAttribute("multiselect") || "false";
        this.allowCustomInputs = this.getAttribute("allow-custom") || "false";
        this.pattern = this.getAttribute("pattern") || "";
        this.placeholder = this.getAttribute("placeholder") || "Search";
        this.limit = 0;

        /** Start initializing things */
        this.value = this.initValue() || [];
        this.options = this.initOptions();

        this.initUI();

        this.initSearchField();
    }

    /** Init the value of the current component */
    initValue() {
        let val = this.getAttribute("value");
        if (typeof val === "string" && val) return JSON.parse(val);
        return null;
    }

    /** Init the valid options of the current component */
    initOptions() {
        const opts = this.querySelectorAll("option");
        let options = {};
        let optHTML = "";
        opts.forEach(e => { // Loop through our options
            options[e.getAttribute("value")] = {
                "search": e.innerText, // Create searchable index
                "label": e.innerHTML   // Create label index
            };
            optHTML += e.outerHTML // We rebuild our opts in <optgroup> later
        });
        this.optHTML = optHTML;
        return options;
    }

    initUI() {
        const ro = this.searchElements();
        this.innerHTML = `<fieldset></fieldset><optgroup>${this.optHTML}</optgroup>${ro}`;
        // delete this.optHTML; // Cleanup. We don't need this anymore.

        /** Establish our UI elements */
        this.fieldSet = this.querySelector("fieldset");
        this.searchField = this.querySelector("input[type='search']");
        // this.searchResults = this.querySelector("ul.search-results");

        this.initSelectedValues();
    }

    initSelectedValues() {
        this.fieldSet.innerHTML = ""; // Clobber the existing selected values

        let tags = ""; // Create our new elements
        let tempOpts = this.options;
        for (const i of this.value) {
            /** Check if we allow custom values */
            if (this.allowCustomInputs === "true" && i in tempOpts === false) tempOpts[i] = i;
            if (i in tempOpts === false) continue;

            tags += this.addTag(i, tempOpts[i].label, this.readonly);
        }

        this.fieldSet.innerHTML += tags;
    }

    searchElements() {
        if (this.readonly === "readonly") return "";
        let placeholder = this.placeholder;
        let pattern = "";
        if (this.allowCustomInputs && this.pattern) pattern = " " + this.pattern;
        this.searchResults = document.createElement("ul");
        this.searchResults.classList.add("input-array--search-results");
        return `<input type="search" placeholder="${placeholder}"${pattern}>`;
    }

    initSearchField() {
        if (!this.searchField) return;
        this.searchField.addEventListener("keyup", e => {
            switch (e.key) {
                case "Enter":
                    this.selectFromEnter();
                    return;
                case "ArrowDown":
                case "ArrowUp":
                    e.preventDefault();
                    this.selectFromArrows(e.key);
                    return;
            }
            this.arrowKeySelectionIndex = -1;
            this.searchResults.innerHTML = "";
            let toSearch = e.target.value;
            if (toSearch === "") return;

            const val = new RegExp(`${toSearch}`, 'i');

            let tempOpts = { ...this.options };
            if (this.allowCustomInputs === "true") tempOpts[toSearch] = {
                "search": toSearch,
                "label": toSearch,
                "custom": true
            }

            if (!this.parentNode.contains(this.searchResults)) this.parentNode.insertBefore(this.searchResults, this)

            this.searchResults.style.top = this.searchField.offsetTop + this.searchField.offsetHeight - 1 + "px";
            this.searchResults.style.left = this.searchField.offsetLeft + 4 + "px";
            this.searchResults.style.width = this.searchField.offsetWidth - 8 + "px";

            for (var i in tempOpts) {
                const matchAgainst = tempOpts[i].search;
                // Ignore any options we've already selected
                if (this.multiSelect === "false" && this.querySelector(`input-array-item[value='${i}']`) !== null) continue;

                // Test if we have a match
                if (val.test(matchAgainst)) {
                    // Append the result of searchFieldListResults to the results element
                    this.searchResults.appendChild(this.listSearchFieldResults(i, tempOpts, matchAgainst))
                }

            }
        })

        this.searchField.addEventListener("focusout", e => {
            this.focusOutTimeout = setTimeout(() => this.focusOutHandler(e), 600);
        })

        this.searchField.addEventListener("focus", e => {
            clearTimeout(this.focusOutTimeout)
        })
    }

    focusOutHandler(e) {
        this.searchResults.innerHTML = "";
    }

    listSearchFieldResults(i, options, matchAgainst) {
        let listItem = document.createElement("li");
        listItem.tabIndex = 0;

        /** Convert our search param into a regular expression we can use to
         higlight our search results with */
        const regex = new RegExp(`(${this.searchField.value})`, 'i');

        /** Check if this is the custom option and add a file */
        if ("custom" in options[i]) {
            listItem.classList.add(this.customElementClass);
            matchAgainst = `${escapeHtml(matchAgainst)}`;
            i = escapeHtml(i);
        }

        listItem.innerHTML = matchAgainst.replace(regex, "<strong>$1</strong>");
        listItem.setAttribute("value", i);
        listItem.setAttribute("label", options[i]);


        listItem.addEventListener("click", e => this.selectSearchResult(e.target));
        listItem.addEventListener("keydown", e => {
            if (e.key === "Enter") this.selectSearchResult(e.target);
            // if (e.key === "DownArrow") 
        });
        return listItem;
    }

    selectSearchResult(target) {
        let tag = document.createElement("div");
        let val = target.getAttribute('value');
        let label = val;
        if (val in this.options) label = this.options[val].label;

        tag.innerHTML = this.addTag(val, label);

        this.searchResults.innerHTML = "";
        this.searchField.value = "";

        this.fieldSet.appendChild(tag.querySelector("input-array-item"));
    }

    addTag(val, label, readonly = false) {
        let ro = (readonly === "readonly") ? ' readonly="readonly"' : "";
        return `<input-array-item value="${val}"${ro}><span>${label}</span></input-array-item>`;
    }

    selectFromEnter() {
        if (this.searchResults.childNodes.length === 0) return;
        if (this.arrowKeySelectionIndex === -1) return this.selectSearchResult(this.searchResults.childNodes[0]);
        if (this.searchResults.childNodes[this.arrowKeySelectionIndex]) return this.selectSearchResult(this.searchResults.childNodes[this.arrowKeySelectionIndex]);
    }

    selectFromArrows(keyCode) {
        const arrows = { "ArrowUp": -1, "ArrowDown": 1 };
        const nodes = this.searchResults.childNodes;
        if (this.arrowKeySelectionIndex === -1 && keyCode === "ArrowUp") return;
        if (nodes.length === 0) return;
        let index = this.arrowKeySelectionIndex + arrows[keyCode];
        if (index === -1) return;
        if (nodes.length - 1 < index) return;
        if (nodes[index]) this.arrowKeySelectionIndex = index;
        const selectOnEnter = "input-array--will-select-on-enter";
        nodes.forEach(e => e.classList.remove(selectOnEnter));
        nodes[index].classList.add(selectOnEnter)
    }



    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_allow_custom(newValue, oldValue) {
        if (newValue === "true") this.allowCustomInputs = "true";
        else this.allowCustomInputs = "false";
    }

    change_handler_multiselect(newValue) {
        if (newValue === "true") this.multiSelect = "true";
        else this.multiSelect = "false";
    }

    change_handler_readonly(newValue) {
        if (newValue === "readonly") this.readonly = "readonly";
        else this.readonly = "false";

        this.initUI();
    }

    change_handler_value(newValue, oldValue) {
        try {
            const val = JSON.parse(newValue);
            this.value = val;
            this.initSelectedValues();
        } catch (error) {
        }
    }

    change_handler_limit(newValue) {
        this.limit = newValue;
    }
}

customElements.define("input-array", InputArray)

class InputArrayItem extends HTMLElement {
    constructor() {
        super();
        this.readonly = this.getAttribute("readonly");
        this.init();
    }

    init() {
        if (this.readonly !== "readonly") this.addDeleteButton();
    }

    addDeleteButton() {
        const button = document.createElement("input");
        button.type = "button";
        button.value = "✖";
        button.addEventListener("click", e => {
            this.parentNode.removeChild(this);
        })
        this.appendChild(button);
    }
}

customElements.define("input-array-item", InputArrayItem)


class DisplayDate extends HTMLElement {
    constructor() {
        super();
        this.formatKeywords = {
            default: "m/d/Y",
            long: "l, F jS Y g:i A",
            verbose: "l, F jS Y",
            "12-hour": "g:i a",
            "24-hour": "H:i",
            "seconds": "g:i:s A"
        };
    }

    connectedCallback() {
        this.date = this.getValue();
        // this.format = this.getAttribute("format") || this.formatKeywords.default;
        if ((this.getAttribute("format") || "default") in this.formatKeywords) this.format = this.formatKeywords[this.format];
        else this.format = this.getAttribute("format");
        this.relative = this.getAttribute("relative") || "false";

        if (typeof this.date !== "string") this.date = this.date.$date.$numberLong;
    }

    getValue() {
        return this.getAttribute("value") || this.innerText || null
    }


    execute() {
        if (this.relative === "true") return this.startRelativeTime();
        let date = new DateConverter(this.date, this.format);
        this.innerText = date.format();
    }

    startRelativeTime() {
        // clearTimeout(this.timeout);
        // this.relative = "false";
        if (/[\d]+/.test(this.date) === false) this.date = JSON.parse(this.date);
        else this.date = Number(this.date);
        let result = relativeTime(new Date(this.date), null, "object");
        if (result === false) {
            this.relative = "false";
            this.execute();
            return;
        }
        this.innerText = result.result;
        let date = new DateConverter(this.date, this.longFormat);
        this.setAttribute("title", date.format());

        // if (!["second", "moment"].includes(result.unit)) return;
        // this.timeout = setTimeout(() => {
        //     this.startRelativeTime();
        // }, 60 * 60);
    }

    static get observedAttributes() {
        return ['value', 'format', 'relative',];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_value(newValue) {
        this.date = newValue;
        this.execute();
    }

    change_handler_format(newValue) {
        this.format = newValue;
        if ((newValue || "default") in this.formatKeywords) this.format = this.formatKeywords[newValue];
        this.execute();
    }

    change_handler_relative(newValue) {
        this.relative = newValue;
        this.execute();
    }
}

customElements.define("date-span", DisplayDate);

class InputObjectArray extends HTMLElement {
    constructor() {
        super();
        this.shadow = this.attachShadow({ mode: 'open' });
        this.template = this.querySelector("template").innerHTML;
        this.withAdditional = this.getAttribute("with-additional") || "false";
        if (this.hasAttribute("with-additional")) this.withAdditional = "true";
        let json = this.querySelector("var");
        this.values = [];
        if (this.getAttribute("value")) {
            this.values = JSON.parse(this.getAttribute("value"));
        } else if (json && "innerText" in json) {
            try { this.values = JSON.parse(json.innerText); } catch (error) { }
        }
        this.fieldItems = [];
        this.initInterface();
    }

    initInterface() {
        this.style();
        this.addButton();
        let index = -1;
        for (const i of this.values) {
            this.addFieldset(i, index++);
        }
        if (this.withAdditional === "true") this.addFieldset(); // Start with an empty one
        else if (this.values.length < 1) this.addFieldset();

    }

    style() {
        const main = document.querySelector("#style-main");
        const links = document.querySelectorAll("link[rel='stylesheet']");
        let styleLinks = "";
        for (const i of links) {
            styleLinks += i.outerHTML;
        }

        const style = document.createElement("head");

        style.innerHTML = `<style>
        ${main.textContent}
        fieldset > label {
            display:block;
        }
        fieldset{
            padding:0;
            border:none;
        }
        input-fieldset {
            border: 1px solid var(--project-color-input-border-nofocus);
            background: var(--project-color-input-background);
            border-radius: 4px;
            position: relative;
            padding: .2rem;
        }
        
        input-fieldset button.input-fieldset--delete-button{
            border: none;
            border-left: inherit;
            border-bottom: inherit;
            border-radius: 0 4px;
            background: inherit;
            position:absolute;
            color: var(--project-color-input-border-nofocus);
            top:0;
            right:0;
            padding: 1px 4px;
        }</style>${styleLinks}`;
        this.shadow.append(style);
    }

    addButton() {
        this.button = document.createElement("button");
        this.button.classList.add("input-object-array--add-button")
        this.button.innerText = "+";
        this.button.addEventListener("click", (e) => {
            this.addFieldset();
        })
        this.shadow.appendChild(this.button);

    }

    addFieldset(values = {}, index = null) {
        if (!index) index = this.fieldItems.length || this.values.length;

        const fieldset = document.createElement("input-fieldset");

        fieldset.innerHTML = this.template;

        if (index in this.fieldItems === false) this.fieldItems[index] = {};
        this.fieldItems[index] = get_form_elements(fieldset);

        for (const i in values) {
            const field = fieldset.querySelector(`[name='${i}']`);
            if (!field) continue;
            this.fieldItems[index][i].value = values[i];
        }

        this.addFieldsetButton(fieldset)
        this.shadow.insertBefore(fieldset, this.button);
    }

    addFieldsetButton(field) {
        let button = document.createElement("button");
        button.classList.add("input-fieldset--delete-button");
        button.innerText = "✖";
        button.addEventListener("click", (e) => {
            const index = [...field.children].indexOf(field)
            field.parentNode.removeChild(field);
            delete this.fieldItems[index];
            this.fieldItems = [...Object.values(this.fieldItems)];
        });
        field.appendChild(button);
    }

    get value() {
        let data = {};
        const objects = this.shadow.querySelectorAll("input-fieldset");
        objects.forEach((e, i) => {
            data[i] = {}
            const fieldElements = get_form_elements(e);
            Object.values(fieldElements).forEach(el => {
                data[i][el.name] = el.value;
            })
        })
        return data;
    }

    set value(value) {
        this.values = value;
    }

    static get observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_value(newValue) {
        this.value = JSON.parse(newValue);
    }
}

customElements.define("input-object-array", InputObjectArray);

class HelpSpan extends HTMLElement {

    constructor() {
        super();
        this.message = document.createElement("article");
        this.message.classList.add("help-span-article");
        this.trunkatingContainer = this.closest("form-request") || document.body;
        this.justifyRightClass = "help-span-article--right-justified";
    }

    connectedCallback() {
        this.articleShown = "help-span-article--shown";

        this.message.innerText = this.value || this.getAttribute("value");
        this.appendChild(this.message);

        this.message.classList.remove(this.articleShown);
        this.addEventListener("mouseover", e => {
            this.message.classList.add(this.articleShown);
            // this.message.style.top = this.top();
            this.justifyRight();
        })

        this.addEventListener("mouseout", e => {
            this.message.classList.remove(this.articleShown);
        })
    }

    get value() {
        return this.val;
    }

    set value(val) {
        this.setAttribute("value", val);
    }

    static get observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_value(val) {
        this.val = val;
        this.message.innerText = val;
    }

    justifyRight() {
        this.message.classList.remove(this.justifyRightClass)

        let container = this.getOffsets(this.trunkatingContainer);
        let message = this.getOffsets(this.message);

        let rightmostEdgeContainer = container.w;
        let rightmostEdgeMessage = message.xPrime + message.w;
        if (rightmostEdgeContainer <= rightmostEdgeMessage) this.message.classList.add(this.justifyRightClass)
    }

    getOffsets(element) {
        let offsets = {
            x: element.offsetLeft,
            y: element.offsetTop,
            xPrime: element.parentNode.offsetLeft + element.offsetLeft,
            yPrime: element.parentNode.offsetTop + element.offsetTop,
            h: element.offsetHeight,
            w: element.offsetWidth
        }
        return offsets;
    }

    top() {
        const offsets = this.getOffsets().height;
        let height = offsets.height;
        let span = this.element.offsetHeight / 2;

        return `${(height / 2) - span}px`;
    }
}

customElements.define("help-span", HelpSpan);

class CopySpan extends HTMLElement {

    constructor() {
        super();
        this.val = document.createElement("input");
        this.val.readOnly = true;
        this.appendChild(this.val);

        this.button = document.createElement("button");
        this.button.innerHTML = this.clipboard();
        this.button.addEventListener("click", e => {
            this.copy();
        })
        this.appendChild(this.button);
    }

    connectedCallback() {

    }

    get value() {
        return this.val.value;
    }

    set value(val) {
        this.setAttribute("value", val);
    }

    static get observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_value(val) {
        this.val.value = val;
    }

    clipboard(size = 1.8) {
        return `<svg
        width="${size}em"
        height="${size}em"
        viewBox="0 0 30 30"
        version="1.1"
        id="svg5"
        inkscape:version="1.1 (c4e8f9ed74, 2021-05-24)"
        sodipodi:docname="clipboard.svg"
        xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
        xmlns:sodipodi="http://sodipodi.sourceforge.net/DTD/sodipodi-0.dtd"
        xmlns="http://www.w3.org/2000/svg"
        xmlns:svg="http://www.w3.org/2000/svg"
        >
        <path
         id="path1525"
         style="fill:none;stroke:currentColor;stroke-width:10;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none"
         d="M 27.685547 13.644531 C 24.79831 13.644531 22.474609 15.968232 22.474609 18.855469 L 22.474609 78.603516 C 22.474609 81.490753 24.79831 83.814453 27.685547 83.814453 L 42.724609 83.814453 L 42.724609 35.332031 C 42.724609 35.241805 42.727936 35.151573 42.732422 35.0625 C 42.736908 34.973427 42.743089 34.886641 42.751953 34.798828 C 42.760818 34.711016 42.772021 34.623554 42.785156 34.537109 C 42.798292 34.450665 42.812779 34.364266 42.830078 34.279297 C 42.847377 34.194328 42.867317 34.110729 42.888672 34.027344 C 42.910027 33.943958 42.933681 33.860992 42.958984 33.779297 C 42.984288 33.697602 43.011871 33.617006 43.041016 33.537109 C 43.07016 33.457213 43.101888 33.378772 43.134766 33.300781 C 43.167644 33.22279 43.201777 33.144337 43.238281 33.068359 C 43.274785 32.992382 43.313493 32.917607 43.353516 32.84375 C 43.393538 32.769893 43.435082 32.696629 43.478516 32.625 C 43.521949 32.553371 43.566545 32.483356 43.613281 32.414062 C 43.660018 32.344769 43.70788 32.277787 43.757812 32.210938 C 43.807745 32.144088 43.859089 32.077971 43.912109 32.013672 C 43.96513 31.949373 44.02017 31.88586 44.076172 31.824219 C 44.132174 31.762578 44.189172 31.703406 44.248047 31.644531 C 44.306922 31.585656 44.368047 31.526705 44.429688 31.470703 C 44.491328 31.414701 44.554842 31.361615 44.619141 31.308594 C 44.68344 31.255573 44.747603 31.204229 44.814453 31.154297 C 44.881303 31.104364 44.950238 31.054549 45.019531 31.007812 C 45.088824 30.961076 45.15884 30.91648 45.230469 30.873047 C 45.302098 30.829614 45.375362 30.788069 45.449219 30.748047 C 45.523076 30.708025 45.59785 30.669316 45.673828 30.632812 C 45.749806 30.596309 45.826306 30.562175 45.904297 30.529297 C 45.982288 30.496419 46.062681 30.464691 46.142578 30.435547 C 46.222475 30.406402 46.303071 30.378819 46.384766 30.353516 C 46.466461 30.328212 46.547474 30.306511 46.630859 30.285156 C 46.714245 30.263801 46.799797 30.243862 46.884766 30.226562 C 46.969734 30.209263 47.054181 30.192823 47.140625 30.179688 C 47.227069 30.166552 47.314531 30.157302 47.402344 30.148438 C 47.490156 30.139573 47.578896 30.131439 47.667969 30.126953 C 47.757042 30.122467 47.847274 30.121094 47.9375 30.121094 L 75.023438 30.121094 L 75.023438 18.855469 C 75.023438 15.968232 72.697784 13.644531 69.810547 13.644531 L 27.685547 13.644531 z "
         transform="scale(0.26458333)" />
      <rect
         style="fill:none;stroke:currentColor;stroke-width:2.64583333;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4;stroke-dasharray:none"
         id="rect1371-3"
         width="13.903321"
         height="18.565735"
         x="11.304461"
         y="7.9694405"
         ry="1.3789077" />
        
        </svg>`;
    }

    copy() {
        this.val.select();
        this.val.setSelectionRange(0, this.val.value.length);
        document.execCommand("copy");
        this.addConfirmMessage();
    }

    async addConfirmMessage() {
        clearTimeout(this.timeout);
        if (this.confirm && this.confirm.parentNode == this) this.removeChild(this.confirm);
        this.confirm = document.createElement("div");
        this.confirm.classList.add("copy-span--confirm");
        this.confirm.innerText = "Copied to clipboard.";
        this.appendChild(this.confirm);
        this.confirm.addEventListener("click", e => {
            this.removeChild(this.confirm);
        });
        await wait_for_animation(this.confirm, "copy-span--spawn");
        this.timeout = setTimeout(async () => {
            await wait_for_animation(this.confirm, "copy-span--disappear");
            this.removeChild(this.confirm);
        }, 2000);
    }
}

customElements.define("copy-span", CopySpan);

class FlexTable extends HTMLElement {
    connectedCallback() {
        let max = 0;
        let same = 0;
        let columns = this.querySelectorAll("flex-row");


        for (const i of columns) {
            if (i.childElementCount > max) max = i.childElementCount;
            if (i.childElement === max) {
                same += 1;
                if (same >= 3) break;
            }
        }

        this.style.setProperty("--column-count", max);
    }

    // static get observedAttributes() {
    //     return ['columns'];
    // }

    // attributeChangedCallback(name, oldValue, newValue) {
    //     const callable = `change_handler_${name.replace("-", "_")}`;
    //     if (callable in this) {
    //         this[callable](newValue, oldValue);
    //     }
    // }

    // change_handler_columns(val) {
    //     this.style.setProperty("--column-count", val);
    // }
}

customElements.define("flex-table", FlexTable);

class AutoComplete extends HTMLElement {
    constructor() {
        super();
        this.val = null; // Where we store our current value
        this.readonly = false;
        this.value = "";
        this.allowCustomInputs = false;
        this.url = false;
        this.min = 1;
        this.options = {};
        this.validity = {
            notAvailableError: false
        };
        this.selectOnEnter = "input-array--will-select-on-enter";
        this.placeholder = "Search";
        this.customElementClass = "input-array--list-custom";
    }

    get value() {
        return this.val;
    }

    set value(val) {
        this.updated = false;
        if (this.options && val in this.options) {
            this.val = val;
            this.searchField.value = this.options[val].search;
            this.updated = true;
        } else if (this.allowCustomInputs) {
            this.val = val;
            this.searchField.value = val;
            this.updated = true;
        }
        // else if (Object.values(this.options).includes(val)) {
        //     this.value = Object.keys(this.options)[Object.values(this.options).indexOf(val)];
        //     updated = true;
        // }

        this.setValidity("notAvailableError", !this.updated);
        // if(this.updated === false) this.val = null;
        return this.updated;
    }

    inOptions(val) {
        if (val in this.options) return true;
        return false;
    }

    valueInOptions(val) {
        for (const i in this.options) {
            if (val === i['search']) return true;
        }
        return false;
    }

    setValidity(name, value) {
        const classes = (value) ? "add" : "remove";
        if (!this.validity) this.validity = {};
        this.validity[name] = value;
        this.classList[classes]("invalid");
    }

    connectedCallback() {
        this.getOptions();
        this.searchElements();
        this.initSearchField();
        this.value = this.getAttribute("value");
    }

    getOptions() {
        const opts = this.querySelectorAll("option");
        for (const i of opts) {
            this.options[i.getAttribute('value')] = {
                search: i.innerText,
                label: i.innerHTML
            };
        }
        if (!opts) this.options = {};
    }


    /*** Handle attribute changes ***/
    static get observedAttributes() {
        return ['value', 'allow-custom', 'url', 'min', 'readonly', 'placeholder'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_allow_custom(newValue) {
        this.allowCustomInputs = string_to_bool(newValue);
    }

    change_handler_placeholder(newValue) {
        this.placeholder = newValue;
    }

    change_handler_readonly(newValue) {
        this.readonly = string_to_bool(newValue);
    }

    change_handler_value(newValue) {
        this.value = newValue;
    }

    change_handler_min(newValue) {
        this.limit = Number(newValue);
    }

    change_handler_url(newValue) {
        this.url = newValue;
    }



    /* ======== *\
    HANDLE  SEARCH
    \* ======== */
    /** Renders the search results container (the unordered list of elements)
     * which gets updated on every input as well as the search field.
     * */
    searchElements() {
        if (this.readonly === true) return "";
        let placeholder = this.placeholder;
        let pattern = "";
        if (this.allowCustomInputs && this.pattern) pattern = " " + this.pattern;
        this.searchResults = document.createElement("ul");
        this.searchResults.classList.add("input-array--search-results");
        this.searchField = document.createElement('input');
        this.searchField.type = 'search';
        this.searchField.placeholder = placeholder;
        this.searchField.pattern = pattern;
        this.appendChild(this.searchField);
    }

    /** Initializes the search field so it listens for appropriate button presses
     * and can disappear the searchResults container on focusout
     */
    initSearchField() {
        if (!this.searchField) return;
        this.searchField.addEventListener("keyup", e => this.handleSearchKeyUp(e));

        this.addEventListener("focusin", e => {
            this.updated = false;
            clearTimeout(this.focusOutTimeout)
            this.setValidity("incompleteEntry", false);
            if (this.searchField.value === "") this.drawSearchResults(this.options, "", false);
        })

        this.addEventListener("focusout", e => {
            this.focusOutTimeout = setTimeout(() => {
                this.focusOutHandler(e);
            }, 600);

            // if(!this.updated && !this.valueInOptions(this.searchField.value)) this.setValidity("incompleteEntry", true);
        })
    }

    handleSearchKeyUp(e) {
        let toSearch = e.target.value;
        let tempOpts = { ...this.options };
        if (this.allowCustomInputs === true && toSearch !== "") tempOpts[toSearch] = {
            "search": toSearch,
            "label": toSearch,
            "custom": true
        }

        switch (e.key) {
            case "Enter":
                this.selectFromEnter();
                return;
            case "ArrowDown":
            case "ArrowUp":
                e.preventDefault();
                this.selectFromArrows(e.key);
                return;
        }
        this.arrowKeySelectionIndex = -1;
        this.searchResults.innerHTML = "";

        if (toSearch === "") return;

        const val = new RegExp(`${toSearch}`, 'i');

        this.drawSearchResults(tempOpts, val, true);

        if (!this.parentNode.contains(this.searchResults)) this.appendChild(this.searchResults);

        this.searchResults.style.top = this.searchField.offsetTop + this.searchField.offsetHeight - 1 + "px";
        this.searchResults.style.left = this.searchField.offsetLeft + 4 + "px";
        this.searchResults.style.width = this.searchField.offsetWidth - 8 + "px";
    }

    drawSearchResults(tempOpts, val, match = true) {
        let index = 0;
        for (var i in tempOpts) {
            const matchAgainst = tempOpts[i].search;
            // Ignore any options we've already selected
            if (this.multiSelect === "false" && this.querySelector(`input-array-item[value='${i}']`) !== null) continue;

            // Test if we have a match
            if (match && !val.test(matchAgainst)) continue;
            // Append the result of searchFieldListResults to the results element
            const result = this.listSearchFieldResults(i, tempOpts, matchAgainst);
            if (index === 0) result.classList.add(this.selectOnEnter);
            this.searchResults.appendChild(result);
            result.addEventListener("focus", () => {
                clearTimeout(this.focusOutTimeout);
            })
            result.addEventListener("keyup", e => {
                switch (e.key) {
                    case "ArrowDown":
                    case "ArrowUp":
                        this.selectFromArrows(e.key)
                }
            })
            index++;
        }
    }

    /** Clears the search results */
    focusOutHandler(e) {
        this.searchResults.innerHTML = "";
    }

    /** Updates the search results on every keyup */
    listSearchFieldResults(i, options, matchAgainst) {
        let listItem = document.createElement("li");
        listItem.tabIndex = 0;

        /** Convert our search param into a regular expression we can use to
         higlight our search results with */
        const regex = new RegExp(`(${this.searchField.value})`, 'i');

        /** Check if this is the custom option and add a file */
        if ("custom" in options[i]) {
            listItem.classList.add(this.customElementClass);
            matchAgainst = `${escapeHtml(matchAgainst)}`;
            i = escapeHtml(i);
        }

        listItem.innerHTML = matchAgainst.replace(regex, "<strong>$1</strong>");
        listItem.setAttribute("value", i);
        listItem.setAttribute("label", options[i]);


        listItem.addEventListener("click", e => this.selectSearchResult(e.target));
        listItem.addEventListener("keydown", e => {
            if (e.key === "Enter") this.selectSearchResult(e.target);
            // if (e.key === "DownArrow") 
        });
        return listItem;
    }

    /** Selects the correct value from the searchResults container */
    selectSearchResult(target) {
        this.updated = true;
        let val = target.getAttribute('value');

        let label = val;
        if (val in this.options) label = this.options[val].label;

        this.value = val;
        this.searchField.value = label;
        this.focusOutHandler(target);
    }

    /** Handles enter button events */
    selectFromEnter() {
        if (this.searchResults.childNodes.length === 0) return;
        if (this.arrowKeySelectionIndex === -1) return this.selectSearchResult(this.searchResults.childNodes[0]);
        if (this.searchResults.childNodes[this.arrowKeySelectionIndex]) return this.selectSearchResult(this.searchResults.childNodes[this.arrowKeySelectionIndex]);
    }

    /** Handles up and down arrows */
    selectFromArrows(keyCode) {
        const arrows = { "ArrowUp": -1, "ArrowDown": 1 };
        const nodes = this.searchResults.childNodes;
        if (this.arrowKeySelectionIndex === -1 && keyCode === "ArrowUp") this.searchField.focus();
        if (nodes.length === 0) return;
        let index = this.arrowKeySelectionIndex + arrows[keyCode];
        if (index === -1) return;
        if (nodes.length - 1 < index) return;
        if (nodes[index]) this.arrowKeySelectionIndex = index;

        nodes.forEach(e => e.classList.remove(this.selectOnEnter));
        nodes[index].classList.add(this.selectOnEnter)
        nodes[index].focus();
    }

}

customElements.define("input-autocomplete", AutoComplete);
