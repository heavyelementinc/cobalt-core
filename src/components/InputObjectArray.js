class ObjectArrayItem extends HTMLElement {
    constructor() {
        super();
        this.props = {
            value: {}
        };
        this.fields = {};
        this.closeBtn = document.createElement("button");
        this.closeBtn.innerHTML = "<i class='close'></i>";
        this.closeBtn.addEventListener("click", () => {
            const parent = this.parentNode;
            this.parentNode.removeChild(this);
            parent.dispatchEvent(new Event("change"));
        });        
    }

    connectedCallback() {
        this.appendChild(this.closeBtn);
        this.fields = {};
        const namedItems = this.querySelectorAll("[name]");
        for(const item of namedItems ) {
            const name = item.getAttribute("name");
            this.fields[name] = item;
            item.dataset.name = name;
            item.removeAttribute("name");
        }
        this.value = this.props.value;
    }

    get disabled() {
        return (this.getAttribute("disabled") == "disabled") ? true : false;
    }

    set disabled(value) {
        switch(value) {
            case "true":
            case "disabled":
                this.setAttribute("disabled", "disabled");
                break;
            case "false":
            default:
                this.removeAttribute("disabled");
        }
    }

    get value() {
        let object = {};
        for(const input in this.fields) {
            object[input] = this.fields[input].value;
        }
        return object;
    }

    set value(object) {
        if(typeof object !== 'object') throw new TypeError("object-array-items must have a value of type 'object'");
        if(Array.isArray(object)) throw new TypeError("object-array-items may not be set to an array");
        
        this.props.value = object;
        console.log(this.props.value);
        if(!this.isConnected) return;

        for(const key in object) {
            const field = this.fields[key];
            if(!field) continue;
            const val = object[key];
            switch(field.tagName) {
                case "INPUT-SWITCH":
                    this.handleSwitch(field, val);
                    break;
                case "INPUT":
                    switch(field.type) {
                        case "checkbox":
                        case "check":
                            this.handleCheckBox(field, val);
                            break;
                        case "radio":
                            // this.handleRadio(field, val);
                            break;
                    }
                default:
                    field.value = val;
                    break;
            }
        }
    }

    handleCheckBox(element, value) {
        element.checked = value;
    }

    handleSwitch(element, value) {
        element.checked = value ? "true" : "false";
    }
}

customElements.define("object-array-item", ObjectArrayItem);

class InputObjectArray extends HTMLElement {
    OBJECT_ARRAY_ITEM = "object-array-item";
    bootstrapped = false;
    button;
    constructor() {
        super();
        // this.shadow = this.attachShadow({ mode: 'open' });
        this.template = this.querySelector("template").innerHTML;

        // this.initInterface();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        // this.inheritStyles();
        this.bootstrapElement();
    }

    inheritStyles() {
        const styleTags = document.querySelectorAll("style,link[rel='stylesheet']");
        for(const tag of styleTags) {
            this.appendChild(tag.cloneNode());
        }
    }

    bootstrapElement() {
        this.createAddButton();
        const initializationValues = this.querySelector("script[type='application/json']");
        // If there is no initializationValues element, set to blank.
        if(!initializationValues) {
            return this.value = [];
        }
        let val = [];
        try {
            val = JSON.parse(initializationValues.innerText);
            if(val === null) val = [];
        } catch(error) {
            throw new TypeError("Error initializing input-object-array! JSON parse failed!");
        }
        if(typeof val !== "object" || !Array.isArray(val)) {
            throw new TypeError("Error initializing input-object-array! Found a non-array value!");
        }

        if(this.startWithEmptyObject) val.push({}); // Start with an additional empty object
        this.value = val;
    }

    createAddButton() {
        // Cleanup any existing buttons
        if(this.button) this.button.parentNode.removeChild(this.button);
        const button = document.createElement("button");
        button.addEventListener("click", () => {
            this.addToSet({});
        });
        button.classList.add("add-new-object-button");
        this.appendChild(button);
        this.button = button;
    }

    get startWithEmptyObject() {
        // If the element has failed to bootstrap for any reason, start with an empty object
        if(this.bootstrapped === false) return true;
        // If the element has 
        if(this.getObjectElements().length === 0) return true;
        if(this.getAttribute("with-additional") === "false") return false;
        return false;
    }

    get value() {
        const objects = this.getObjectElements();
        let value = [];
        /** @const {ObjectArrayItem} obj */
        for(const obj of objects) {
            value.push(obj.value);
        }
        return value;
    }

    set value(array_of_objects) {
        // Validate we were handed an array
        if(!Array.isArray(array_of_objects)) throw new TypeError("Argument is not an array!");

        // Clobber the data stored
        const objects = this.getObjectElements();
        for(const obj of objects) {
            obj.parentNode.removeChild(obj);
        }

        for(const obj of array_of_objects) {
            this.addToSet(obj);
        }

        this.bootstrapped = true;
    }

    addToSet(object) {
        // Add the object to the set
        const template = this.getTemplate();
        if(!this.button) {
            this.appendChild(template);
            template.value = object;
            return;
        }
        this.insertBefore(template, this.button);
        template.value = object;
    }

    getObjectElements() {
        return this.querySelectorAll(this.OBJECT_ARRAY_ITEM);
    }

    getTemplate() {
        // Find the template we want to use
        const template = this.querySelector("template");
        if(!template) throw new Error("input-object-array must have a <template> element specified");
        // Let's create our object-array-item element
        const element = document.createElement(this.OBJECT_ARRAY_ITEM);
        element.innerHTML = template.innerHTML; // Transfer the inner template content to the object-array-item element
        return element;
    }
}

customElements.define("input-object-array", InputObjectArray);


class InputObjectArrayOld extends HTMLElement {
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
                throw new Error("Failed to parse JSON from 'value' attribute");
            }
        } else if (json && "innerText" in json) {
            let js = [];
            try {
                js = JSON.parse(json.innerText) || [];
            } catch (error) {
                throw new Error("Failed to parse JSON from <var> tag");
            }
            this.value = js;
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