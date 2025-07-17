import ICustomInput from "./ICustomInput.js";

/**
 * Values are ALWAYS an array.
 */
export class TagSelect extends ICustomInput {
    constructor() {
        super();
    }

    connectedCallback() {
        this.options = this.getOptions();
        this.removeEventListener('click',   this.onClick);
        this.addEventListener('click',      this.onClick);
        this.removeEventListener('keyDown', this.onKeyDown);
        this.addEventListener('keyDown',    this.onKeyDown);
    }

    get value() {
        let values = [];
        for (const i of this.querySelectorAll("[aria-pressed='true']")) {
            if (i.selected === true) {
                values.push(i.value ?? i.innerText);
            }
        }
        return values;
    }

    set value(toSet) {
        for (const i of this.options) {
            const value = i.value ?? i.innerText;

            // Check if the value is in the toSet variable 
            if (!toSet.includes(value)) {
                this.setUnselected(i);
                continue;
            }

            this.setSelection(i);
        }
    }

    setSelection(element) {
        element.setAttribute("aria-pressed", "true");
    }

    setUnselected(element) {
        element.setAttribute("aria-pressed", "false");
    }

    onClick = (e) => {
        console.log(e);
        this.toggleOptionSelection(e.target);
    }

    onKeyDown = (e) => {
        switch (e.key) {
            case " ":
            case "Enter":
                e.preventDefault();
                this.toggleOptionSelection(e.target);
                break;
        }
    }

    getOptions() {
        const datalist = this.getDatalist();
        if(!datalist) {
            console.warn(`Failed to locate options for field ${this.constructor.name}`);
            return;
        }
        const options = datalist.querySelectorAll("option");
        
        for (const el of options) {
            this.createButton(el);
        }

        return options;
    }

    createButton(opt) {
        const btn = document.createElement("button");
        btn.classList.add("toggleable");
        btn.innerHTML = `<div class="content">${opt.innerHTML}</div>`;
        btn.value = opt.value;
        btn.ariaPressed = opt.selected;
        this.appendChild(btn);
    }

    toggleOptionSelection(e) {
        e = e.closest("button.toggleable");
        if (e.ariaPressed == "true") {
            this.setUnselected(e);
        } else {
            this.setSelection(e);
        }

        this.dispatchEvent(new Event("input"));
        this.dispatchEvent(new Event("change",{bubbles: true}));
    }
}

// customElements.define("input-tag-select", TagSelect);
