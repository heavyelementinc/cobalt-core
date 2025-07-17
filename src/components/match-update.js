
class MatchUpdate extends HTMLElement {
    hydratedOptions = [];
    connectedCallback() {
        this.hydratedOptions = [];
    }

    enumerateOptions() {
        const opts = this.querySelectorAll("option");
        for(const opt of opts) {
            const item = new MatchOption(opt, this);
            if(item.reset) this.reset = item;
            else this.hydratedOptions.push(item);
        }

        const resetTarget = this.reset.target;
        
        // Let's run resetTarget first
        if(resetTarget) {
            this.applyListeners(resetTarget);
        }

        for(const opt of this.hydratedOptions) {
            this.applyListeners(opt);
        }        
    }

    applyListeners(option) {
        for(const evt of option.on) {
            this.form.addEventListener(evt.trim(), event => {
                option.executeMatchListener(event);
            });
        }
    }
}

customElements.define("match-update", MatchUpdate);

class MatchOption {
    /**
     * @param {HTMLOptionElement} option
     * @param {MatchUpdate} updater 
     */
    constructor(option, updater) {
        this.option = option;
        this.updater = updater;
        this.reset = this.option.hasAttribute("reset");
        this.form = this.option.closest("form-request");
    }
    
    get type() {
        return this.option.getAttribute("type");
    }

    get target() {
        return document.querySelector(this.option.getAttribute("query"));
    }

    get on() {
        return this.option.getAttribute("on");
    }

    get value() {
        return this.option.getAttribute("value");
    }

    _run(evalutedAsTrue, event) {
        const query = this.option.getAttribute("query");
        const target = document.querySelector(query);
        if(!target) {
            console.warn(`Failed to locate a suitable target for query ${query}`);
            return;
        }

        const value = this.form.getFormElementValue(target, event);
        let evaluatedAsTrue = false;
        switch(this.type) {
            case "gt":
                evaluatedAsTrue = this.value > value;
                break;
            case "gte":
                evaluatedAsTrue = this.value >= value;
                break;
            case "lt":
                evaluatedAsTrue = this.value < value;
                break;
            case "lte":
                evaluatedAsTrue = this.value <= value;
                break;
            case "ne":
                evaluatedAsTrue = this.value != value;
                break;
            // case "in":
                // 
            case "is":
            default:
                evaluatedAsTrue = this.value == value;
                break;
        }
        if(evalutedAsTrue) {
            const updater = new UpdateOperation(this.form);
            const instructions = JSON.parse(this.option.innerText);
            updater.exec(instructions);
            return true;
        }
        return false;
    }
}