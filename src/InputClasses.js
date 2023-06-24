/**
 * Any element used by form-request will instance this (or an extended version
 * of this) class. This class acts as a sort-of normalization interface so we can
 * use both built in HTML inputs and our custom inputs.
 * 
 * With that being said, every element using this interface supports the
 * following attributes:
 * 
 *  * name     - the field's variable name
 *  * for      - Use the `for` attribute to update the innerText of any element
 * in the page with the value of the field after it's successfully saved. Value 
 * of attr must be a valid CSS selector.
 */
class InputClass_default {
    constructor(element, { form = null }) {
        this.element = element;
        this.type = element.type || "text";
        this.name = element.name || element.getAttribute("name") || "";
        this.form = form || this.get_form();
        this.error = false;
        this.was = this.element.value;
        try {
            this.update = (this.element.getAttribute("for")) ? document.querySelectorAll(this.element.getAttribute("for")) : false;
        } catch (error) {
            this.update = false;
        }
        if (typeof element === "string") this.element = document.querySelector(element);
        if (this.element === null) throw new Error("Can't find element " + element);
        this.callbacks();
    }

    get value() {
        return this.element.value;
    }

    set value(set = null) {
        this.was = this.value;
        this.element.value = set;
        return set;
    }

    validity_check() {
        if ("validity" in this === false) return true;
        const check = Object.values(this.validity).reduce((a, b) => {
            return a + b;
        });

        if (check !== 0) return false;
        return true;
    }

    get_form() {
        if (this.form === null) this.form = this.element.closest("form-request");
        if (this.form === null) throw new Error("Can't find reference <form-request>");
    }

    callbacks() {
        this.element.addEventListener('focusout', async e => {
            await this.dismiss_error();
        })
        if (this.update) {
            this.form.addEventListener("requestSuccess", e => {
                for (const i of this.update) {
                    this.perform_update(i, this.value);
                }
            });
        }
    }

    perform_update(target, value) {
        target.innerText = value;
    }

    set_error(message) {
        this.message = message;
        this.create_error(this.insert_before_element())
    }

    async create_error(before) {
        let el = document.createElement("validation-issue");
        const spawnIndex = spawn_priority(before);
        if (spawnIndex) el.style.zIndex = spawnIndex + 1;
        el.addEventListener('click', () => {
            if (el) {
                el.parentNode.removeChild(el);
                this.error = false;
            }
        });
        el.classList.add("form-request--field-issue-message");
        el.innerText = this.message;
        el.setAttribute('for', this.name);
        el.addEventListener("click", e => {
            this.dismiss_error(e);
            this.store_error(false);
        })
        this.store_error(el);

        const offsets = get_offset(before);
        el.style.top = `${offsets.bottom}px`;
        el.style.left = `${offsets.x}px`;
        el.style.width = `${offsets.w}px`;
        document.body.appendChild(el);
        this.element.setAttribute("invalid", "invalid");
        await wait_for_animation(el, "form-request--issue-fade-in");
    }

    insert_before_element() {
        return this.element;
    }

    insert_after_element() {
        return this.element.nextSibling;
    }

    store_error(element) {
        this.error = element;
    }

    async dismiss_error() {
        this.element.invalid = false;
        this.element.removeAttribute("invalid");
        if (!this.error === false) return;
        await wait_for_animation(this.error, "form-request--issue-fade-out");
        if (!this.error.parentNode) return;

        this.error.parentNode.removeChild(this.error);
        this.error = false;
    }

    
}

class InputClass_date extends InputClass_default {
    set value(set = null) {
        this.was = this.value;
        if (typeof set === "string") return this.element.value = set;
        if ("$date" in set && "$numberLong" in set.$date) return this.element.value = mongoDate(set.$date.$numberLong)
    }
}

class InputClass_checkbox extends InputClass_default {
    get value() {
        return this.element.checked
    }
    set value(set) {
        this.was = this.value;
        this.element.checked = set;
        return set;
    }

    insert_after_element() {
        if (this.element.parentNode.tagName === "LABEL") return this.element.parentNode;
        return this.element.nextSibling;
    }
}

class InputClass_switch extends InputClass_default {
    get value() {
        return this.element.value;
    }

    set value(set) {
        this.was = this.value;
        this.element.value = set;
        return set;
    }
}

class InputClass_radio extends InputClass_default {
    // value(set = null) {
    //     if (set === null) return this.get()
    //     this.set(set);
    // }

    get value() {
        const element = this.form.querySelector(`[name="${this.name}"]:checked`);
        if (!element) return null;
        return element.value;
    }

    set value(set) {
        this.was = this.value;
        if (!set) return;
        let candidate = this.form.querySelector(`[name="${this.name}"][value="${set}"]`);
        if (candidate !== null) candidate.checked = true;
    }

    insert_before_element() {
        return this.insert_after_element();
    }

    insert_after_element() {
        let last = this.form.querySelectorAll(`input[name='${this.name}']`);
        if (!last) return null;
        last = last[last.length - 1];
        let element;
        if (last.parentNode.tagName === "LABEL") {
            element = last.parentNode;
        } else element = last;
        console.log(element);
        return element;
    }

    async dismiss_error() {
        this.element.invalid = false;
        this.element.removeAttribute("invalid");
        if (!this.error === false) return;
        await wait_for_animation(this.error, "form-request--issue-fade-out");
        if (!this.error.parentNode) return;

        this.error.parentNode.removeChild(this.error);
        this.error = false;
    }
}

class InputClass_button extends InputClass_default {

}

class InputClass_number extends InputClass_default {
    get value() {
        return Number(this.element.value);
    }

    set value(set) {
        this.was = this.value;
        this.element.value = set;
        return Number(set);
    }
}

class InputClass_array extends InputClass_default {
    // get value() {
    //     return this.element.value
    // }
    // set value(set = null) {
    //     this.was = this.element.value;
    //     return this.element.value = set;
    //     // this.was = this.value;
    //     // this.setArrayElements();
    // }

    // collectArrayElements() {
    //     let elements = this.element.querySelectorAll('input-array-item');
    //     let array = [];
    //     for (var e of elements) {
    //         array.push(e.getAttribute("value"));
    //     }
    //     return array;
    // }
}

class InputClass_select extends InputClass_default {

    set value(set = null) {
        this.was = this.value;
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

class InputClass_object_array extends InputClass_default {

    constructor(element, { form = null }) {
        super(element, { form });
        this.members = [];
    }

    initMembers() {
        const fields = this.element.shadow.querySelectorAll("input-fieldset");
        for (const f of fields) {
            this.members.push(get_form_elements(f));
        }
    }

    set_error(message) {
        this.message = message;
        this.initMembers();
        for (const e in message) {
            const split = e.split(".");
            if (!this.members[split[0]]) return;
            const el = this.members[split[0]][split[1]];
            el.set_error(message[e])
        }
    }

    async dismiss_error(e = null) {
        this.element.invalid = false;
        this.element.removeAttribute("invalid");

        for (const field of this.members) {
            for (const el of field) {
                el.dismiss_error();
            }
        }
    }

    store_error(element) {
        this.error.push(element);
    }
}

class InputClass_file extends InputClass_default {
    perform_update(target, value) {
        target.innerText = value;
    }
}

class InputClass_tag_select extends InputClass_default {

}

class InputClass_radiogroup extends InputClass_default {

}

var classMap = {
    default: InputClass_default,
    password: InputClass_default,
    check: InputClass_checkbox,
    checkbox: InputClass_checkbox,
    switch: InputClass_switch,
    radio: InputClass_radio,
    button: InputClass_button,
    number: InputClass_number,
    array: InputClass_array,
    userArray: InputClass_array,
    objectArray: InputClass_object_array,
    tagSelect: InputClass_tag_select,
    radioGroup: InputClass_radiogroup,
}
