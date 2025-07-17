class UpdateOperation {
    /** @prop {FormRequest} form */
    form;
    constructor(form) {
        this.form = form;
    }

    exec(instructions) {
        for(const instruction of instructions) {
            switch(instruction.target) {
                case "sessionStorage":
                case "localStorage":
                    this.updateStorage(instruction);
                    break;
                case "history":
                    this.updateHistory(instruction);
                    break;
                case "@form":
                    this.updateForm(instruction);
                    break;
                case "@cookie":
                    this.updateCookie(instruction);
                    break;
                default:
                    this.updateElement(this.getElement(instruction.target), instruction);
                    break;
            }
        }
    }

    getElement(query) {
        const regex = /:closest\((.*)\)/;
        if(query.match(regex)) {
            const closestQuery = regex.exec(query)[1];
            const trueQuery = query.replace(`:closest(${closestQuery})`, ""); // Remove parent selector
            const elements = document.querySelectorAll(trueQuery);
            let closest = [];
            for(const el of elements) {
                const result = el.closest(closestQuery);
                closest.push(result);
            }
            return closest;
        }
        return document.querySelectorAll(query);
    }

    updateCookie(instructions) {
        for(const i in instructions) {
            switch(i) {
                case "set":
                    for(const c in instructions[i]) set_cookie(c, instructions[i][c]);
                    break;
                case "remove":
                    for(const c in instructions[i]) delete_cookie(c, instructions[i][c]);
                    break;
                default:
                    console.warn(`"${i}" is not a recognized updateCookie instruction`);
            }
        }
    }

    /**
     * 
     * @param  instructions 
     */
    updateStorage(instructions) {
        const target = instructions.target;
        for(const i in instructions) {
            const directive = `storage_${i}`;
            if(directive === "storage_target") continue;
            if(directive in this === false) {
                console.warn(`Unsupported storage directive: ${directive}`)
                continue;
            }
            
            this[directive](window[target], instructions[i]);
        }
    }

    updateHistory(instructions) {
        for(const i in instructions) {
            switch(i) {
                case "go":
                    history.go(instructions[i]);
                    break;
                case "back":
                    history.back();
                    break;
                case "forward":
                    history.forward();
                    break;
                case "pushState":
                    history.pushState(...instructions[i]);
                    break;
                case "replaceState":
                    history.replaceState(...instructions[i]);
                    break;
                default:
                    console.warn(`Unsupported history directive: ${i}`);
            }
        }
    }

    storage_set(target, values) {
        for(const i in values) {
            target.setItem(i, values[i]);
        }
    }

    storage_remove(target, values) {
        for(const i of values) {
            target.removeItem(i);
        }
    }

    updateForm(instructions) {
        if(!this.form) {
            console.warn("The form is not specified for this request type");
            return;
        }
        if("clear" in instructions) {
            if(instructions.clear === true) {
                this.form.dispatchEvent(new CustomEvent("clearall"))
            }
            delete instructions.clear
        }
        if("next" in instructions) {
            this.form.next(instructions.next);
        }
        this.updateElement(this.form, instructions)
    }

    updateElement(elements, instructions) {
        for(const i in instructions) {
            const directive = `fn_${i}`;
            if(directive in this === false) {
                if(directive === "fn_target") continue;
                if(directive === "fn_next") continue;
                console.warn(`Unsupported update directive: ${directive}`);
                continue;
            }
            // if(directive === `fn_invalid` && el.closest("form-request") !== this.form) continue;
            for(const el of elements) {
                this[directive](el, instructions[i], instructions);
            }
        }
    }

    fn_dispatchEvent(el, value, instructions) {
        let event
        switch(value) {
            case "click":
            case "mousedown":
            case "keydown":
            case "load":
            case "change":
                event = new Event(value, {detail: instructions});
                break;
            default:
                event = new CustomEvent(value, {detail: instructions});
                break;
        }
        el.dispatchEvent(event);
    }

    fn_value(el, value, instructions) {
        if("value" in el) el.value = value;
        else console.warn("Element lacks 'value' property", el);
    }

    fn_innerHTML(el, value, instructions) {
        if("innerHTML" in el) el.innerHTML = value;
    }

    fn_innerText(el, value, instructions) {
        if("innerText" in el) el.innerText = value;
        else console.warn("Element lacks 'innerText' property", el)
    }

    fn_outerHTML(el, value, instructions) {
        if("outerHTML" in el) el.outerHTML = value;
    }

    fn_invalid(el, value, instructions) {
        if("ariaInvalid" in el) {
            el.ariaInvalid = value;
            el.addEventListener("focusin", e => e.ariaInvalid = null, {once: true});
        }
    }

    fn_disabled(el, value, instructions) {
        if(!value) {
            el.ariaDisabled = false;
            return el.disabled = false;
        }
        el.ariaDisabled = true;
        return el.disabled = true;
    }

    fn_delete(el, value, instructions) {
        if(value !== true) return;
        el.parentNode.removeChild(el)
    }

    fn_remove(el, value, instructions) {
        try {
            if(typeof value === "string") el = el.querySelectorAll(value);
        } catch (Error) {
            console.warn("Malformed selector")
        }

        if(el instanceof NodeList || Array.isArray(el)) el.forEach(e => e.parentNode.removeChild(e));
        
        if(el.constructor.name === "Array") {
            el.forEach(e => e.parentNode?.removeChild(e))
        } else el.parentNode.removeChild(el)
    }

    fn_message(el, value, instructions) {
        const messageElement = appendElementInformation(el, value, instructions);
        el.dispatchEvent(new CustomEvent("validationissue", {bubbles: true}));
        el.addEventListener("focusin", e => messageElement.dispatchEvent(new Event("click", e)), {once: true});
    }

    fn_img(el, value, instructions){
        this.fn_src(el, value.filename, instructions);
        el.height = value.meta.height;
        el.width = value.meta.width;
    }

    fn_src(el, value, instructions) {
        el.setAttribute("src", value);
    }

    fn_href(el, value, instructions) {
        el.setAttribute("href", value);
    }

    fn_attribute(el, value, instructions) {
        this.fn_attributes(el, value, instructions);
    }

    fn_attributes(el, value, instructions) {
        for(const v in value) {
            el.setAttribute(v, value[v]);
        }
    }

    fn_style(el, value, instructions) {
        for(const v in value) {
            if(v.indexOf("-") >= 0) {
                el.style.setProperty(v, value[v]);
                continue;
            }
            el.style[v] = value[v];
        }
    }
}