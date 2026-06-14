import ICustomInput from "./ICustomInput.js";

export default class InputModel extends ICustomInput {
    __activeUi;
    __props = {
        value: {}
    }

    connectedCallback() {
        this.__template = this.querySelector("template");
        this.__activeUi = document.createElement("div");
        this.__activeUi.classList.add("input-model--container");
        this.__activeUi.innerHTML = this.__template.innerHTML;
        this.appendChild(this.__activeUi);
        this.__valueElement = this.querySelector("script");
        this.value = JSON.parse(this.__valueElement.innerText);
    }

    set value(value) {
        if(typeof value !== "object") {
            this.setCustomValidity("Must be a object");
            throw new Error("Must be an object");
        }
        if(Array.isArray(value)) {
            this.setCustomValidity("Must not be an array");
            throw new Error("Must not be an array");
        }
        this.__props.value = value;
        
        for(const name in value) {
            const el = this.__activeUi.querySelector(`[name='${name}']`);
            el.value = value[name];
        }
    }

    get value() {
        const elements = this.fields;
        for(const el of elements) {
            this.__props.value[el.name ?? el.getAttribute("name")] = el.value;
        }

        return this.__props.value;
    }

    get fields() {
        return this.querySelectorAll(universal_input_element_query);
    }
}