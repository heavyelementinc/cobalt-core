import ICustomInput from "./ICustomInput.js";

export default class InputRadio extends ICustomInput {
    get value() {
        return this.datalist.querySelector("[checked='checked']")?.value ?? null;
    }

    set value(val) {
        const candidate = this.datalist.querySelector(`input[value="${val}"]`);
        if(!candidate) throw new Error("Invalid selection");
        candidate.checked = true;
    }
    
    get datalist() {
        return document.querySelector(`#${this.getAttribute("datalist")}`) ?? this;
    }

    connectedCallback() {
        this.options = this.datalist.querySelectorAll("option");
        if(this.customInputReady.state === this.customInputReady.PROMISE_RESOLVED) return;
        this.createRadioButtons();
        this.customInputReady.resolve(true);
    }

    createRadioButtons() {
        for(const opt of this.options) {
            this.appendChild(this.radioElement(opt));
        }
    }

    /** @param {HTMLOption} element */
    radioElement(element) {
        const label = document.createElement("label");
        let checked = "";
        if(element.selected == true) {
            checked = " checked='checked'";
        }
        label.innerHTML = `<input type="radio" name="${this.name}" value="${element.value}"${checked}> ${element.innerHTML}`;
        return label;
    }
}