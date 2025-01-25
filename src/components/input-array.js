
/**
 * `<input-array>` valid attibutes include:
 *   * name - the name of the array
 *   * value - a JSON-encoded array (can be &quot; escaped)
 *   * readonly - [false] 'true' disables addition and removal of items from list
 *   * allow-custom - [false] 'true' allow the user to add custom entries
 *   * pattern - [""] the pattern for custom elements to be matched against
 */
class InputArray extends AutoCompleteInterface {

    constructor() {
        super();
        this.TAG_CLASS = "input-array--tag";
        this.readonly = false;
        this.tags = null;
        this.action = this.getAttribute("action");
        this.allowCustom = string_to_bool(this.getAttribute("allow-custom")) ?? false;
        this.excludeCurrent = string_to_bool(this.getAttribute("exclude-current")) ?? true;
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.change_handler_value(this.getAttribute("value"));
        this.tags = document.createElement("ol");
        this.tags.classList.add("input-array--tag-container");
        this.prepend(this.tags);
        for(const i of this.value) {
            const el = this.querySelector(`option[value='${i}']`);
            if(el === null) this.drawCustomTag(i, true);
            this.drawTag(el);
        }

        this.initAutocomplete();

        // Let's set the initial value stores so that we don't end up throwing
        // erroneous change events when setting the value of this element.
        if(!this.was) {
            this.was = [...this.value ?? []];
            this.values = [...this.was ?? []];
        }
    }

    observedAttributes() {
        return ["value", "readonly", "allow-custom"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_value(newVal, old = "") {
        // if(!newVal && !this.tags) return;
        try{
            this.value = JSON.parse(newVal)
        } catch(error) {
            console.warn(`Failed to parse JSON while updating ${this.getAttribute("name")}'s value`);
        }
    }

    change_handler_readonly(newVal, old) {
        this.readonly = string_to_bool(newVal);
        (this.readonly) ? this.enableWrite() : this.disableWrite();
    }

    change_handler_allow_custom(newVal) {
        this.allowCustom = string_to_bool(this.getAttribute("allow-custom")) ?? false;
    }

    get options() {
        return this.querySelectorAll("option");
    }

    /**
     * Value is determined in the following order:
     *  * If a value has been set by the value setter, then `this.values` is used
     *  * If this.values is NULL, then the `value` attribute is parsed as JSON
     *  * If value is still null, then we look for selected option tags and make a list
     * 
     * The value of an input-array will always be an array.
     */
    get value() {
        let value = this.values ?? null;
        
        if(value === null) {
            try {
                value = JSON.parse(this.getAttribute("value"))
            } catch {
                return this.getValueFromOptionsTags();
            }
        }
        if(value === null) value = this.getValueFromOptionsTags();
        if(value === null) value = [];
        
        return value;
    }

    set value(val) {
        if(!Array.isArray(val)) throw new TypeError("Invalid assignment to input-array element");
        this.updateTags(val);
        this.values = val;
        // console.log(compare_arrays(this.was, this.values))
        if(!compare_arrays(this.was,this.values)) {
            // console.log("input-array is firing a change event",{was: this.was, is: this.values});
            this.dispatchEvent(new Event("input"));
            // this.dispatchEvent(new Event("change"));
        }
        this.was = [ ...this.values ?? []];
    }

    get name() {
        return this.getAttribute("name");
    }

    updateTags(val = null) {
        // Get the value of the element
        if(val === null) val = this.value;
        // Convert this.options to an object
        const opts = this.options;
        let validOpts = {};
        for(const i of opts) {
            validOpts[i.value] = i.innerHTML;
        }
        this.tags.innerHTML = "";
        // Loop through the argument values of this element and select them
        for(const i of val) {
            if(Object.keys(validOpts).includes(i)) {
                this.selectOption(i);
                continue;
            } else if(this.allowCustom) {
                this.drawCustomTag(i, true);
            }
        }
    }

    getValueFromOptionsTags() {
        let value = [];
        for(const i of this.querySelectorAll("option[selected='selected']")) {
            value.push(i.value);
        }
        // console.log(value);

        return value;
    }

    enableWrite() {
        this.updateTags()
    }

    disableWrite() {
        this.updateTags();
    }

    initAutocomplete() {
        const field = this.getAutocompleteSearchField();
        this.appendChild(field);

        this.addEventListener("autocompleteselect",(e) => {
            const val = this.value;
            val.push(e.detail.value);
            this.value = val;
            this.dispatchEvent(new Event("change", {...e, target: this}));
        })
    }

    // === SELECTION METHODS === 

    selectOption(value) {
        let el = this.querySelector(`option[value="${value}"]`);
        if(el === null) this.drawCustomTag(value, true);
        else this.drawTag(el);
        el.setAttribute("selected","selected");
    }

    // Called when removing the option from the array
    deselectOption(value) {
        let el = this.querySelector(`option[value='${value}']`);
        if(!el) return;
        el.removeAttribute("selected");
        this.removeTag(el);
        setTimeout(() => {
            // Why are we setting a timeout before dispatching this event? 
            // Well, it's because if we send the change event synchronously
            // `el` will still register as `selected` and therefore it will be
            // included in this.value output. So we wait 20ms before dispatching
            // the change event. This sucks. TODO fix this hacky bullshit.
            this.dispatchEvent(new Event("change", {target: this}));
        }, 20)
    }


    // === TAG METHODS === 
    drawTag(i) {
        if("value" in i === false) throw new TypeError("Missing property 'value' when drawing tag");
        if(this.tags === null) return;
        let tag = document.createElement("li");
        tag.classList.add(this.TAG_CLASS);
        tag.setAttribute("value", i.value);
        tag.innerHTML = `<label>${i.innerText}</label>`;
        this.initTagButton(tag);
        this.tags.appendChild(tag);
    }

    initTagButton(tag) {
        if(this.readonly) return;
        const button = document.createElement("input");
        button.type = "button";
        button.value = "✖";
        tag.appendChild(button);
        button.addEventListener("click", (e) => {
            this.stopBubbling(e)
            this.deselectOption(tag.getAttribute("value"));
            this.value = this.getSelectedByRemainingTags();
        });
    }

    getSelectedByRemainingTags() {
        const tags = this.querySelectorAll(`.${this.TAG_CLASS}`);
        let values = [];

        for(const i of tags) {
            values.push(i.getAttribute("value"));
        }

        return values;
    }

    drawCustomTag(i, selected = true) {
        if(this.allowCustom != true) {
            console.warn("Element does not accept custom entries but found a custom entry. This WILL result in data loss.");
            return;
        }
        const option = document.createElement("option");
        option.value = i;
        option.innerText = i;
        if(selected) option.selected = "selected";
        this.appendChild(option);
        this.drawTag(option);
    }

    removeTag(i) {
        if("value" in i === false) throw new TypeError("Missing property 'value' when drawing tag");
        const tag = this.querySelector(`.${this.TAG_CLASS}[value='${i.value}']`);
        if(tag) tag.parentNode.removeChild(tag);
    }

}

customElements.define("input-array", InputArray);

class InputBinary extends HTMLElement {
    constructor() {
        super();
        this.props = {
            options: [],
            tags: document.createElement("tag-container")
        }
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.props.options = this.querySelectorAll("option");
        this.props.tags.innerHTML = "";
        this.appendChild(this.props.tags);
        for(const opt of this.props.options) {
            this.props.tags.appendChild(this.createTag(opt));
        }
        if(this.value === 0 && this.hasAttribute("value")) {
            console.warn('Input fields should not use the "value" attribute');
            this.value = Number(this.getAttribute('value'));
        }
    }

    createTag(data) {
        const tag = document.createElement("button");
        tag.value = Number(data.value);
        tag.storedValue = Number(data.value);
        tag.ariaPressed = false;
        if(data.getAttribute("selected") === "selected") tag.ariaPressed = true;
        tag.innerHTML = data.innerHTML;
        // tag.dataset = data.dataset;
        
        tag.addEventListener("click", e => {
            if(this.readonly) return;
            tag.ariaPressed = !JSON.parse(tag.ariaPressed);
            this.dispatchEvent(new CustomEvent("change"));
        });

        return tag;
    }

    get value() {
        const tags = this.props.tags.querySelectorAll("button");
        let value = 0;
        for(const tag of tags) {
            if(JSON.parse(tag.ariaPressed) === false) continue;
            value += Number(tag.value);
        }
        return value;
    }

    set value(val) {
        const tags = this.props.tags.querySelectorAll("button");
        for(const tag of tags) {
            tag.ariaPressed = false;
            if(tag.storedValue & val) tag.ariaPressed = true;
        }
    }

    get readonly() {
        if(!this.hasAttribute("readonly")) return false;
        return true;
        // const val = this.getAttribute("readonly") ?? ""; 
        // if(['',"readonly", "true"].includes(val.toLowerCase())) return true;
        // return false;
    }

    set readonly(bool) {
        if(typeof bool === "boolean") throw new TypeError("Must be a boolean");

        this.setAttribute("readonly", JSON.stringify(bool));
    }

    get name() {
        return this.getAttribute("name");
    }
}

customElements.define("input-binary", InputBinary);

class InputUserArray extends InputArray {

    constructor(){ 
        super();
        this.tagLabels = {};
        this.setAttribute("__custom-input", "true");
    }

    // connectedCallback() {
    //     super.connectedCallback();
    // }

    renderOptions(opts) {
        // return opts;
        let finalOptions = [];
        for(const el in opts) {
            const label = this.drawLabel(opts[el],opts);
            finalOptions[el] = {
                search: label,
                label: label,
                value: opts[el]._id.$oid
            }
        }
        // this.options = finalOptions;
        return finalOptions;
    }

    updateTags(val = null) {
        // Get the value of the element
        if(val === null) val = this.value;
        // Convert this.options to an object
        const validOpts = this.tagLabels;
        
        // Loop through the argument values of this element and select them
        for(const i of val) {
            this.removeTag(i);
            if(Object.keys(validOpts).includes(i)) {
                this.selectOption(i);
                continue;
            }
        }
    }

    initAutocomplete() {
        const field = this.getAutocompleteSearchField();
        this.appendChild(field);

        this.addEventListener("autocompleteselect",(e) => {
            const val = this.value;
            val.push(e.detail.value);
            this.value = val;
            this.tagLabels[val] = e.detail.label;
        })
    }

    




    drawLabel(values) {
        return `
            <div class="cobalt-user--profile-display">
                <img src="${values.avatar.thumb.filename}" class="cobalt-user--avatar">
                <div class='vbox'>
                    <span>${values.fname} ${values.lname}</span>
                    <span title='${values._id.$oid}'>@${values.uname}</span>
                </div>
            </div>
        `;
    }

    drawTag(i){
        if("value" in i === false) throw new TypeError("Missing property 'value' when drawing tag");
        if(this.tags === null) return;
        let tag = document.createElement("li");
        tag.classList.add(this.TAG_CLASS);
        tag.setAttribute("value", i.value);
        tag.innerHTML = `<label>${this.drawLabel(i)}</label>`;
        this.initTagButton(tag);
        this.tags.appendChild(tag);
    }

    get name() {
        return this.getAttribute("name");
    }
}

customElements.define("input-user-array", InputUserArray);

class InputUser extends AutoCompleteInterface {

    constructor() {
        super();
        this.user = null;
    }

    connectedCallback() {
        // super.connectedCallback();
        this.searchField = this.getAutocompleteSearchField();
        this.appendChild(this.searchField);
        this.addEventListener("autocompleteselect", e => {
            this.setValue(e.detail);
        });
        const val = this.getAttribute("value");
        if(val) this.value = val;
    }

    get value() {
        if(this.user) return this.user.getAttribute('value');
        return "";
    }

    set value(val) {
        const option = this.querySelector(`option[value="${val}"]`);
        if(!option) throw new TypeError("Must be a valid user ID!");
        this.setValue({value: option.value, label: option.innerHTML});
    }

    get options() {
        const opts = this.querySelectorAll("option");
        return opts;
    }

    get name() {
        return this.getAttribute("name");
    }

    renderOptions(opts) {
        let finalOptions = [];
        for(const el in opts) {
            const label = this.drawLabel(opts[el],opts);
            finalOptions[el] = {
                search: label.outerHTML,
                label: label.outerHTML,
                value: opts[el]._id.$oid
            }
        }
        // this.options = finalOptions;
        return finalOptions;
    }

    drawLabel(values) {
        let user = document.createElement("div");
        user.classList.add("cobalt-user--profile-display");
        user.setAttribute("value", values.value);

        user.innerHTML = `
            <img src="${values.avatar?.thumb?.filename || "/core-content/img/unknown-user.thumb.jpg"}" class="cobalt-user--avatar">
            <div class='vbox'>
                <span>${values.fname} ${values.lname}</span>
                <span class='username'>@${values.uname}</span>
            </div>
        `;
        
        return user;
    }

    setValue(values) {
        this.user = document.createElement("div");
        this.user.innerHTML = values.label;
        this.user.setAttribute("value", values.value);
        this.appendChild(this.user);
        this.classList.add("value");

        this.clearButton = document.createElement("button");
        this.clearButton.innerHTML = "<i name='backspace'></i>";
        this.clearButton.addEventListener("click", e => {
            this.removeChild(this.user);
            delete this.user;
            this.removeChild(this.clearButton);
            this.classList.remove("value");
            this.dispatchEvent(new Event("change"));
        });
        this.appendChild(this.clearButton);
        this.dispatchEvent(new Event("change"));
    }

}

customElements.define("input-user", InputUser);