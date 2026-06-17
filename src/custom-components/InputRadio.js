import ICustomInput from "./ICustomInput.js";

export default class InputRadio extends ICustomInput {
    get value() {
        return this.querySelector("input[type='radio']:checked")?.value ?? null;
    }

    set value(val) {
        const candidate = this.querySelector(`input[value="${val}"]`);
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
        const radio = label.querySelector("input");
        if(!radio) throw new Error("Failed to create radio button");
        radio.addEventListener("change", (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            this.dispatchEvent(new Event("change", e));
        });
        return label;
    }
}