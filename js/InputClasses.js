class InputClass_default {
    constructor(element, { }) {
        this.element = element;
        this.type = element.type || "text";
        this.name = element.name || "";
        if (typeof element === "string") this.element = document.querySelector(element);
        if (this.element === null) throw new Error("Can't find element " + element);
    }

    value(set = null) {
        if (set === null) return this.element.value;
        this.element.value = set;
        return set;
    }
}

class InputClass_checkbox extends InputClass_default {
    value(set = null) {
        if (set === null) return this.element.checked
        this.element.checked = set;
        return set;
    }
}

class InputClass_radio extends InputClass_default {
    value(set) {

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
    checkbox: InputClass_checkbox,
    radio: InputClass_radio,
    button: InputClass_button,
    number: InputClass_number,
}