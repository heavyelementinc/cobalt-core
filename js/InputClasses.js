class InputClass_default {
    constructor(element, { form = null }) {
        this.element = element;
        this.type = element.type || "text";
        this.name = element.name || "";
        this.form = form || this.get_form();
        this.error = false;
        if (typeof element === "string") this.element = document.querySelector(element);
        if (this.element === null) throw new Error("Can't find element " + element);
        this.callbacks();
    }

    value(set = null) {
        if (set === null) return this.element.value;
        this.element.value = set;
        return set;
    }

    get_form() {
        if (this.form === null) this.form = this.element.closest("form-request");
        if (this.form === null) throw new Error("Can't find reference <form-request>");
    }

    callbacks() {
        this.element.addEventListener('focusout', e => {
            this.dismiss_error();
        })
    }

    set_error(message) {
        let el = document.createElement("pre");
        el.classList.add("form-request--field-issue-message");
        el.innerText = message;
        el.setAttribute('for', this.name);
        el.addEventListener("click", e => {
            el.parentNode.removeChild(el);
        })
        this.error = el;

        this.element.parentNode.insertBefore(el, this.element);
        this.element.setAttribute("invalid", "invalid")
    }

    dismiss_error() {
        this.element.invalid = false;
        this.element.removeAttribute("invalid");
        if (this.error === false) return;
        this.error.parentNode.removeChild(this.error);
        this.error = false;
    }
}

class InputClass_date extends InputClass_default {
    value(set = null) {
        if (set === null) return this.element.value;
        if (typeof set === "string") return this.element.value = set;
        if ("$date" in set && "$numberLong" in set.$date) return this.element.value = mongoDate(set.$date.$numberLong)
    }
}

class InputClass_checkbox extends InputClass_default {
    value(set = null) {
        if (set === null) return this.element.checked
        this.element.checked = set;
        return set;
    }
}

class InputClass_switch extends InputClass_default {
    value(set = null) {
        if (set === null) return this.element.querySelector("input[type='checkbox']").checked;
        this.element.querySelector("input[type='checkbox']").checked = set;
        return set;
    }
}

class InputClass_radio extends InputClass_default {
    value(set = null) {
        if (set === null) return this.get()
        this.set(set);
    }

    get() {
        const element = this.form.querySelector(`[name="${this.name}"]:checked`);
        return element.value;
    }

    set(set) {
        if (!set) return;
        let candidate = this.form.querySelector(`[name="${this.name}"][value="${set}"]`);
        if (candidate !== null) candidate.checked = true;
    }
}

class InputClass_button extends InputClass_default {

}

class InputClass_number extends InputClass_default {
    value(set = null) {
        if (set === null) return Number(this.element.value);
        this.element.value = set;
        return Number(set);
    }
}

class InputClass_array extends InputClass_default {
    value(set = null) {
        if (set === null) return this.collectArrayElements();
        else {
            this.setArrayElements();
        }
    }

    collectArrayElements() {
        let elements = this.element.querySelectorAll('input-array-item');
        let array = [];
        for (var e of elements) {
            array.push(e.getAttribute("value"));
        }
        return array;
    }
}

class InputClass_select extends InputClass_default {
    value(set = null) {
        if (set === null) return this.element.value
        // Query for the matching option
        let options = this.element.querySelector(`option[value='${set}']`);
        let found = false;
        // Check if the option has been found:
        if (options !== null) {
            options.selected = "selected";
            found = options;
        } else {
            options = this.element.querySelectorAll("option");
        }
        for (const i of options) {
            if (found === null && i.innerText === set) {
                found = i;
            }
            i.selected = "";
        }

        if (found) found.selected = "selected";
        else {
            // If the element doesn't have the value we've set, add it
            let missing = document.createElement("option");
            missing.innerText = set;
            missing.setAttribute("value", set);
            missing.setAttribute("selected", "selected");
            this.element.appendChild(missing);
        }
        return set;
    }
}

var classMap = {
    default: InputClass_default,
    check: InputClass_checkbox,
    checkbox: InputClass_checkbox,
    switch: InputClass_switch,
    radio: InputClass_radio,
    button: InputClass_button,
    number: InputClass_number,
    array: InputClass_array,
}