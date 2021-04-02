class InputClass_default {
    constructor(element, { }) {
        this.element = element;
        this.type = element.type || "text";
        this.name = element.name || "";
        if (typeof element === "string") this.element = document.querySelector(element);
        if (this.element === null) throw new Error("Can't find element " + element);
    }

    value(set = null) {
        if (set !== null) return this.element.value;
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
        // if(set === null)
    }
}

var classMap = {
    default: InputClass_default,
    checkbox: InputClass_checkbox,
    radio: InputClass_radio,
    button: InputClass_button,
    number: InputClass_number,
}