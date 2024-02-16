/**
 * Values are ALWAYS an array.
 */
class TagSelect extends HTMLElement {
    constructor() {
        super();
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.options = this.getOptions();
    }

    get value() {
        let values = [];
        for (const i of this.querySelectorAll("option[selected='selected']")) {
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
        element.setAttribute("selected", "selected");
    }

    setUnselected(element) {
        element.removeAttribute("selected");
    }

    getOptions() {
        const options = this.querySelectorAll("option");
        const evt = (e) => this.toggleOptionSelection(e);

        for (const el of options) {
            el.setAttribute("tabindex", "0");
            el.addEventListener('click', (e) => evt(el));
            el.addEventListener('keyDown', (e) => {
                switch (e.key) {
                    case " ":
                    case "Enter":
                        evt(el);
                        break;
                }
            });
        }

        return options;
    }

    toggleOptionSelection(e) {
        if (e.selected === true) return this.setUnselected(e);
        this.setSelection(e);

        this.dispatchEvent(new Event("input"));
        this.dispatchEvent(new Event("change"));
    }
}

customElements.define("input-tag-select", TagSelect);
