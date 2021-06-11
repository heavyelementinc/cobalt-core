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
            let searchForButtons = this;
            let queries = "button[type='submit'],input[type='submit']";
            // if (this.getAttribute("submit")) {
            //     searchForButtons = this.closest("modal-container");
            //     queries = "button.modal-button-okay";
            // }
            searchForButtons.querySelector(queries).addEventListener('click', (e) => {
                this.send(e.shiftKey);
            });
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
        }

        this.mode = this.getAttribute("display-mode") ?? "edit";
        if (this.mode === "edit" && allow_final_stage) {
            await this.regress();
            this.error.innerText = this.getAttribute("success-message") || "Success";
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
        console.log(this, this.button)
        this.getRequest();
        this.button.addEventListener('click', e => this.request.send(e));
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
            // this.focusOutTimeout = setTimeout(() => this.focusOutHandler(e), 600);
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
        console.log(callable, callable in this)
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
            console.log(newValue);
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
        this.date = this.getValue();
        this.format = this.getAttribute("format") || "m/d/Y";
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
        let date = new DateConverter(this.date, "l, F jS Y g:i A");
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
        if (json && "innerText" in json) {
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

        console.log(this.fieldItems);
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
            console.log(this.fieldItems);
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
            this.fieldItems[index][i].value(values[i]);
        }

        this.addFieldsetButton(fieldset)
        this.shadow.insertBefore(fieldset, this.button);
    }

    addFieldsetButton(field) {
        let button = document.createElement("button");
        button.classList.add("input-fieldset--delete-button");
        button.innerText = "✖";
        button.addEventListener("click", (e) => {
            console.log(field)
            const index = [...field.children].indexOf(field)
            field.parentNode.removeChild(field);
            delete this.fieldItems[index];
            this.fieldItems = [...Object.values(this.fieldItems)];
            console.log(this.fieldItems);
        });
        field.appendChild(button);
    }

    get value() {
        let data = {};
        const objects = this.shadow.querySelectorAll("input-fieldset");
        objects.forEach((e, i) => {
            data[i] = {}
            const fieldElements = get_form_elements(e);
            fieldElements.forEach(e => {
                data[i][e.name] = e.value();
            })
        })
        return data;
    }

    set value(value) {
        this.values = value;
    }
}

customElements.define("input-object-array", InputObjectArray);