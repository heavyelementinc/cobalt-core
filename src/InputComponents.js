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
        this.dispatchEvent(new CustomEvent("componentready"));
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
        this.checked = string_to_bool(this.getAttribute("checked")); // Let's also get 
        this.disabled = ["true", "disabled"];
        this.checkbox = document.createElement("input");
        this.checkbox.type = "checkbox";
        this.checkbox.checked = this.checked;

        this.thumb = document.createElement("span");
        this.setAttribute("__custom-input", "true");
    }

    get value() {
        return this.checkbox.checked;
    }

    set value(val) {
        this.checkbox.checked = val;
        this.checked = val;
        this.setAttribute("checked", JSON.stringify(val));
    }

    // get checked() {
    //     return this.checkbox.checked;
    // }

    /** The CONNECTED CALLBACK is the function that is executed when the element
     *  is added to the DOM.
     */
    connectedCallback() {
        // Let's figure out if our switch is checked or not and prepare for appending
        // the legit checkbox in the proper state
        let checked = (["on", "yes", "true", "checked"].includes(this.checked)) ? " checked=\"checked\"" : "";
        this.appendChild(this.checkbox);
        this.appendChild(this.thumb);

        // Now let's find our checkbox
        this.checkbox = this.querySelector("input[type='checkbox']");
        // Allow forms to use the switch
        if(!this.closest("form-request")) this.checkbox.name = this.getAttribute("name");
        // Check if our checkbox is "indeterminate". This is useful since there's no
        // native HTML way of setting a checkbox to its "indeterminate" state.
        if (['indeterminate', 'unknown', 'null', 'maybe'].includes(this.checked)) this.checkbox.indeterminate = true;
        // Init our listeners for this element
        this.initListeners();
        this.dispatchEvent(new CustomEvent("componentready"));
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
        this.checked = this.checkbox.checked;
        this.setAttribute("checked", JSON.stringify(this.checked));
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

// class SwitchContainer extends HTMLElement {
//     connectedCallback() {
//         this.switch = this.querySelector("input-switch");

//         this.addEventListener("click", (e) => {
//             for(const i of e.path) {
//                 if(i.tagName === "INPUT-SWITCH") return;
//             }
//             this.switch.value = !this.switch.value;
//         });
//     }
// }

// customElements.define("switch-container", SwitchContainer);

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
        this.selected = this.getAttribute("selected") ?? this.getAttribute("value") ?? this.getAttribute("checked");
        this.default = this.getAttribute("default");

        let first = this.querySelector("[input='radio']");
        if (first) this.name = first.getAttribute("name");
        if (this.selected) this.updateSelected(this.selected);
        else if (this.default) this.updateSelected(this.default);
        this.setAttribute("__custom-input", "true");
    }

    get value() {
        this.ariaInvalid = false;
        const node = this.querySelector("[type='radio']:checked");
        if(!node) {
            if(this.props.required || this.getAttribute("required") !== "false") {
                this.ariaInvalid = true;
                throw new Error("This field is required");
            }
            return "";
        }
        return node.value;
    }

    set value(val) {
        this.ariaInvalid = false;
        const node = this.querySelector(`[type='radio'][value='${val}']`);
        if(!node && !val) return;
        if(!node) throw new Error("No button with specified value");
        node.checked = true;
    }

    get name() {
        return this.getAttribute("name") || this.querySelector(`[type='radio']`).name || this.querySelector(`[type='radio'][name]`).name;
    }

    connectedCallback() {
        this.dispatchEvent(new CustomEvent("componentready"));
    }

    updateSelected(selected) {
        let updateQuery = "";
        if (this.name) updateQuery = `[name="${this.name}"]`
        const candidate = this.querySelector(`${updateQuery}[value="${selected}"]`);
        if (candidate) candidate.checked = true;
    }

}

customElements.define("radio-group", RadioGroup)

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
        const size = this.getAttribute("scale") ?? "1em";
        const height =  size,
        width = size;
        return `<svg xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:cc="http://creativecommons.org/ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 100 100" version="1.1" id="svg1091"><circle class="spinner-dashes" style="fill:none;stroke:${getComputedStyle(this).color};stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:4;stroke-opacity:1" id="path1964" cx="50" cy="50" r="43.098995" /></svg>`
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

class DisplayDate extends HTMLElement {
    constructor() {
        super();
        this.formatKeywords = {
            default: "m/d/Y",
            verbose: "l, F jS Y g:i A",
            long: "l, F jS Y",
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
        this.dispatchEvent(new CustomEvent("componentready"));
    }

    getValue() {
        return this.getAttribute("value") || this.innerText || null
    }


    execute() {
        if (this.relative === "true" || this.getAttribute("format") === "relative") {
            return this.startRelativeTime();
        }
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
        let date = new DateConverter(this.date, this.formatKeywords.verbose);
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
        this.withAdditional = string_to_bool(this.getAttribute("with-additional")) || false;
        this.values = [];

        this.fieldItems = [];
        // this.initInterface();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        let json = this.querySelector("var");
        if (this.hasAttribute("value")) {
            try {
                this.value = JSON.parse(this.getAttribute("value")) || [];
            } catch (e) {
                console.warn("Field has invalid parse data", this);
            }
        } else if (json && "innerText" in json) {
            try { this.value = JSON.parse(json.innerText) || [] } catch (error) { }
        } else {
            this.value = [];
        }
        this.dispatchEvent(new CustomEvent("ObjectArrayReady"));
        this.dispatchEvent(new CustomEvent("componentready"));
    }

    initInterface() {
        this.shadow.innerHTML = "";
        this.style();
        this.addButton();
        let index = -1;
        for (const i of this.values) {
            this.addFieldset(i, index++);
        }
        if (this.withAdditional === true) this.addFieldset(); // Start with an empty one
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
        if (!value) return;
        this.values = value;
        this.initInterface();
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
        if (!newValue) {
            this.values = [];
            return;
        }
        this.values = JSON.parse(newValue);
    }
}

customElements.define("input-object-array", InputObjectArray);

class HelpSpan extends HTMLElement {

    constructor() {
        super();
        this.message = document.createElement("article");
        this.message.classList.add("help-span-article");
        this.trunkatingContainer = document.body;
        this.justifyRightClass = "help-span-article--right-justified";
        this.warning = this.hasAttribute("warning");
        if (this.warning) this.message.setAttribute("warning", "");
    }

    connectedCallback() {
        this.articleShown = "help-span-article--shown";

        this.message.innerText = this.value || this.getAttribute("value");

        this.message.classList.remove(this.articleShown);
        this.addEventListener("mouseover", e => {
            this.attach();
        })

        this.addEventListener("mouseout", e => {
            this.detatch();
        });

        this.dispatchEvent(new CustomEvent("componentready"));
    }

    attach() {
        document.body.appendChild(this.message);
        this.message.classList.add(this.articleShown);
        const offsets = this.getOffsets(this);
        this.message.style.top = `${offsets.y + offsets.h + 2}px`;
        this.message.style.left = `${offsets.x + (offsets.w / 2) - (this.getOffsets(this.message).w / 2)}px`
        this.message.style.zIndex = offsets.zIndex + 100;
        // this.message.style.top = this.top();
        this.justify(offsets);
    }

    justify(offsets) {
        this.message.classList.remove(this.justifyRightClass)

        let container = get_offset(this.trunkatingContainer);
        let message = get_offset(this.message);
        let diff = Math.abs(container.right - message.right);

        if (message.x < 0) {
            this.message.style.left = 0;
            return;
        } else if (container.right <= message.right) {
            this.message.style.left = `${message.x - diff}px`
        }
    }

    detatch() {
        this.message.classList.remove(this.articleShown);
        document.body.removeChild(this.message);
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

    getOffsets(element) {
        return get_offset(element);
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

        this.button.addEventListener("click", e => {
            this.copy();
        })
        this.appendChild(this.button);
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.button.innerHTML = this.clipboard(window.getComputedStyle(this, null).getPropertyValue('font-size'));
        this.dispatchEvent(new CustomEvent("componentready"));
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
        width="${size}"
        height="${size}"
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


class ProgressBar extends HTMLElement {
    constructor() {
        super();
        this.maxValue = this.getAttribute("max") || 100;
        this.progressValue = 0;
    }

    connectedCallback() {
        if(this.getAttribute("no-message") === null) {
            this.messageContainer = document.createElement("div");
            this.messageContainer.innerHTML = "&nbsp;";
        }
        this.bar = document.createElement("div");
        this.bar.classList.add("progress-bar--indicator");
        this.appendChild(this.bar);
        this.dimensions = get_offset(this);
        if(this.messageContainer) this.parentNode.insertBefore(this.messageContainer, this.nextSibling);
    }

    /**
     * @param {string} value
     */
    set percent(value) {
        this.setAttribute("percent", value);
    }

    set complete(value) {
        this.setAttribute("complete", value);
    }

    set max(value) {
        this.maxValue = value;
        this.setAttribute("max", value);
    }

    set progress(value) {
        this.progressValue += value;
        this.percent = Math.round((this.progressValue / this.maxValue) * 100);
    }

    set regress(value) {
        this.progressValue -= value;
        this.percent = Math.round((this.progressValue / this.maxValue) * 100);
    }

    set message(value) {
        this.messageContainer.innerText = value;
    }

    static get observedAttributes() {
        return ['percent','max'];
    }

    show() {
        this.style.height = `${this.dimensions.h}px`
    }

    hide() {
        this.style.height = 0;
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_percent(newValue, oldValue) {
        this.bar.style.width = `${newValue}%`;
    }

    change_handler_max(newValue, oldValue) {
        this.maxValue = newValue;
    }

    change_handler_complete(newValue, oldValue) {
        this.isComplete = newValue;
        const truthy = ["complete", "true", true, "done"];
        if (truthy.indexOf(newValue) === -1) this.show();
        else this.hide()
    }
}

customElements.define("progress-bar", ProgressBar);

class InputNumber extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.realField = document.createElement("input");
        this.realField.type = "number";
        this.realField.min = this.getAttribute("min");
        this.realField.max = this.getAttribute("max");
        this.realField.pattern = this.getAttribute("pattern");
        // this.realField.disabled = this.getAttribute("disabled");
        this.value = this.getAttribute("value");
        this.appendChild(this.realField);
        this.dispatchEvent(new CustomEvent("componentready"));
    }

    get value() {
        const val = this.realField.value;
        if(val === "") return Number(this.getAttribute('default')) || 0;
        return Number(this.realField.value);
    }

    set value(number) {
        this.realField.value = number;
    }

    get name() {
        return this.getAttribute("name");
    }
    
    set name(name) {
        this.setAttribute("name", name);
    }

    get disabled() {
        return this.realField.getAttribute("aria-disabled");
    }

    set disabled(value) {
        this.realField.ariaDisabled = value;
        this.realField.disabled = value;
    }

    get min() {
        return this.realField.min;
    }

    set min(value) {
        this.realField.min = value;
    }

    get max() {
        return this.realField.max;
    }

    set max(value) {
        this.realField.max = value;
    }
}

customElements.define("input-number", InputNumber);
