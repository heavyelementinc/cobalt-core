class ObjectArrayItem extends HTMLElement {
    constructor() {
        super();
        this.props = {
            value: {}
        };
        this.fields = {};
        this.closeBtn = document.createElement("button");
        this.closeBtn.classList.add("close");
        this.closeBtn.addEventListener("click", () => {
            const parent = this.parentNode;
            this.parentNode.removeChild(this);
            parent.dispatchEvent(new Event("change",{bubbles: true}));
        });
    }

    connectedCallback() {
        this.insertBefore(this.closeBtn, this.firstChildElement);
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
        if(!this.isConnected) return;

        for(const key in object) {
            const field = this.fields[key];
            if(!field) continue;
            const val = object[key];
            switch(field.tagName) {
                // case "INPUT-SWITCH":
                //     this.handleSwitch(field, val);
                //     break;
                // case "INPUT":
                //     switch(field.type) {
                //         case "checkbox":
                //         case "check":
                //             this.handleCheckBox(field, val);
                //             break;
                //         case "radio":
                //             // this.handleRadio(field, val);
                //             break;
                //     }
                // break;
                default:
                    field.value = val;
                    break;
            }
        }
    }

    handleCheckBox(element, value) {
        element.checked = value;
    }

    handleSwitch(inputSwitch, value) {
        inputSwitch.value = value;
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

        if(val.length === 0) val.push({}); // Start with an empty object
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