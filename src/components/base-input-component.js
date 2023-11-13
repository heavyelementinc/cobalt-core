/** AutoComplete - The Cobalt Engine Autocomplete webcomponent
 * 
 * 
 */

class BaseInput extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", "true");
    }
    base_observed() {
        return [];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        const callable = `change_handler_${name.replace("-", "_")}`;
        if (callable in this) {
            this[callable](newValue, oldValue);
        }
    }

    change_handler_(newValue, oldValue) {

    }
}