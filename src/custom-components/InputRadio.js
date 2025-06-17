import ICustomInput from "./ICustomInput.js";

export default class InputRadio extends ICustomInput {
    get value() {
        return this.querySelector("[checked='checked']")?.value ?? null;
    }

    set value(val) {
        const candidate = this.querySelector(`input[value="${val}"]`);
        if(!candidate) throw new Error("Invalid selection");
        candidate.checked = true;
    }

    connectedCallback() {
        this.options = this.querySelectorAll("option");
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