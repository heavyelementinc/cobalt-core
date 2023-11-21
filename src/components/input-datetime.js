class InputDateTime extends HTMLElement {
    constructor() {
        super();
        this.props = {
            originalValue: null,
            value: null,
            dateInput: null,
            timeInput: null,
        }
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        this.innerHTML = "";
        this.initUI();
        if(this.hasAttribute('value')) this.value = this.getAttribute("value");
    }

    initUI() {
        if(!this.props.dateInput) this.props.dateInput = document.createElement("input");
        this.props.dateInput.type = "date";
        this.props.dateInput.addEventListener("change", e => {
            e.stopPropagation();
            this.setValueFromChangeCallback();
        });
        if(!this.props.timeInput) this.props.timeInput = document.createElement("input");
        this.props.timeInput.type = "time";
        this.props.timeInput.addEventListener("change", e => {
            e.stopPropagation();
            this.setValueFromChangeCallback();
        });

        this.appendChild(this.props.dateInput);
        this.appendChild(this.props.timeInput);
    }

    setValueFromChangeCallback() {
        this.value = this.fromString(this.props.dateInput.value + " " + this.props.timeInput.value);
    }

    observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if(name === "value") this.value = newValue;
    }

    get value() {
        return this.props.value;
    }

    set value(val) {
        this.props.originalValue = val;
        this.props.value = this.parseValue(val);
        this.updateUI(this.props.value);
    }

    updateUI(value) {
        if(!this.props.dateInput) this.initUI();
        this.props.dateInput.value = `${value.getFullYear()}-${this.prefixSingleDigits(value.getMonth() + 1)}-${this.prefixSingleDigits(value.getDate())}`;
        this.props.timeInput.value = `${this.prefixSingleDigits(value.getHours())}:${this.prefixSingleDigits(value.getMinutes())}`;
    }

    prefixSingleDigits(digit) {
        let string = String(digit);
        if(string.length <= 1) string = `0${string}`;
        return string;
    }

    parseValue(val) {
        switch(this.from) {
            case "string":
                return this.fromString(val);
            case "seconds":
            case "php":
            case "time":
            case "unix":
                return this.fromSeconds(val);
            case "microsectonds":
            case "u":
                return this.fromMicroseconds(val);
        }
    }

    get from() {
        let attr = this.getAttribute("from") || this.getAttribute("type") || "microseconds";
        return attr || "microseconds";
    }

    fromSeconds(val) {
        return new Date(val * 1000);
    }

    fromMicroseconds(val) {
        return new Date(val);
    }

    fromString(val) {
        return new Date(val);
    }
}

customElements.define("input-datetime", InputDateTime);
