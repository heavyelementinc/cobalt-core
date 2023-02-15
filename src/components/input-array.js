
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
        this.readonly = false;
        this.tags = null;
        this.allowCustom = string_to_bool(this.getAttribute("allow-custom")) ?? false;
        this.excludeCurrent = string_to_bool(this.getAttribute("exclude-current")) ?? true;
    }

    connectedCallback() {
        this.change_handler_value(this.getAttribute("value"));
        this.tags = document.createElement("fieldset");
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
        console.log(compare_arrays(this.was, this.values))
        if(!compare_arrays(this.was,this.values)) {
            console.log("input-array is firing a change event",{was: this.was, is: this.values});
            this.dispatchEvent(new Event("input"));
            this.dispatchEvent(new Event("change"));
        }
        this.was = [ ...this.values ?? []];
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

        // Loop through the argument values of this element and select them
        for(const i of val) {
            this.deselectOption(i);
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
        })
    }

    // === SELECTION METHODS === 

    selectOption(value) {
        let el = this.querySelector(`option[value="${value}"]`);
        if(el === null) this.drawCustomTag(value, true);
        else this.drawTag(el);
        el.setAttribute("selected","selected");
    }

    deselectOption(value) {
        let el = this.querySelector(`option[value='${value}']`);
        if(!el) return;
        el.removeAttribute("selected");
        this.removeTag(el);
    }


    // === TAG METHODS === 
    drawTag(i) {
        if("value" in i === false) throw new TypeError("Missing property 'value' when drawing tag");
        if(this.tags === null) return;
        let tag = document.createElement("input-array-tag");
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
        const tags = this.querySelectorAll("input-array-tag");
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
        const tag = this.querySelector(`input-array-tag[value='${i.value}']`);
        if(tag) tag.parentNode.removeChild(tag);
    }

}

customElements.define("input-array", InputArray);
