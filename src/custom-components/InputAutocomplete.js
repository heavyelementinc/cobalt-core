import IAutocompleteInput from "./IAutocompleteInput.js";

export default class InputAutocomplete extends IAutocompleteInput {
    TYPE_WEAK = "weak";
    searchField = document.createElement("input");
    
    connectedCallback() {
        this.innerHTML = "";
        this.searchField.type = "search";
        this.searchField.setAttribute("list", this.getAttribute("datalist"));
        this.searchField.value = this.getAttribute("value") ?? "";
        this.appendChild(this.searchField);
    }

    get type() {
        return this.getAttribute("type");
    }

    get value() {
        const datalist = document.querySelector(`#${this.list}`);
        for(const option of datalist.querySelectorAll("option")) {
            if(option.innerText == this.searchField.value) return option.value;
        }
        if(this.type == this.TYPE_WEAK) return this.searchField.value;
        return "";
    }

    set value(val) {
        for(const type in this._validity) {
            this._validity[type] = false;
        }
        this.setCustomValidity("");
        this.classList.remove("invalid");

        const candidate = document.querySelector(`#${this.list} [value="${val}"]`);
        // If there's no result and we have a weak type, set the value
        if(this.type === this.TYPE_WEAK && !candidate) {
            this.searchField.value = val;
            return;
        }
        // If we have no candidate and we're not a weak type, return
        if(!candidate) {
            this._validity.badInput = true;
            this.setCustomValidity(`"${val}" is not a valid selection`);
            this.classList.add("invalid");
            return;
        }
        this.searchField.value = candidate.innerText;
    }
}

