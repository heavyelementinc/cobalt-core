class InputDateTime extends HTMLElement {
    constructor() {
        super();
        this.props = {
            originalValue: null,
            value: null,
            dateInput: null,
            timeInput: null,
        }
        
        this.datePicker = document.createElement("date-picker");
        this.addEventListener("click", e => {
            this.toggleDatePicker();
        })
    }

    connectedCallback() {
        this.setAttribute("__custom-input", "true");
        document.body.appendChild(this.datePicker);

        this.innerHTML = "";
        this.initUI();
        this.datePicker.hide();
        this.datePicker.addEventListener("dateselect", (e) => {
            this.value = e.detail;
            this.datePicker.hide();
            this.dispatchEvent(new Event("change"));
        });
    }

    disconnectedCallback() {
        this.datePicker.parentNode.removeChild(this.datePicker); // Clean up
    }

    observedAttributes() {
        return ['value'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        switch(name) {
            case "value":
                this.value = newValue;
                break;
        }
    }

    get value() {
        if(!this.props.value) return null;
        if(this.props.value.toString() === 'Invalid Date') return null;
        switch(this.to) {
            case "string":
                return this.props.value.toString();
            case "seconds":
            case "php":
            case "time":
            case "unix":
                return this.props.value.getTime() / 1000;
            case "milliseconds":
                return this.props.value.getTime();
            case "u":
                return this.props.value.getTime() * 1000;
            case "ISO 8601":
            default:
                return this.props.value.toISOString();
            // default:
            //     return this.props.value;
        }
    }

    parseValue(val) {
        if(val instanceof Date) return val;
        switch(this.from) {
            case "string":
                return this.fromString(val);
            case "seconds":
            case "php":
            case "time":
            case "unix":
                return this.fromSeconds(val);
            case "milliseconds":
                return this.fromMilliseconds(val);
            case "u":
                return this.fromMicroseconds(val);
            case "ISO 8601":
            default:
                return new Date(val);
        }
    }

    set value(val) {
        this.props.originalValue = val;
        this.props.value = this.parseValue(val);
        this.updateUI(this.props.value);
    }

    get from() {
        let attr = this.getAttribute("from") || this.getAttribute("type") || "ISO 8601";
        return attr || "milliseconds";
    }

    get to() {
        let attr = this.getAttribute("to") || this.getAttribute("format") || "ISO 8601";
        return attr;
    }

    get tz() {
        let attr = this.getAttribute("tz") || Intl.DateTimeFormat().resolvedOptions().timeZone;
        return attr;
    }

    get locale() {
        let attr = this.getAttribute("locale") || Intl.DateTimeFormat().resolvedOptions().locale;
        return attr;
    }

    initUI() {
        this.pickerButton = document.createElement("button");
        this.pickerButton.innerHTML = "<i name='chevron-down'></i>";
        // this.pickerButton.addEventListener("click", e => {
        //     this.toggleDatePicker();
        // });
        this.props.dateInput = document.createElement("time")
        this.props.dateInput.innerHTML = "No date selected";

        // if(!this.props.dateInput) this.props.dateInput = document.createElement("input");
        // this.props.dateInput.type = "date";
        
        // this.props.dateInput.addEventListener("change", e => {
        //     e.stopPropagation();
        //     this.storeValueFromChangeCallback();
        // });

        // if(!this.props.timeInput) this.props.timeInput = document.createElement("input");
        // this.props.timeInput.type = "time";
        // this.props.timeInput.addEventListener("change", e => {
        //     e.stopPropagation();
        //     this.storeValueFromChangeCallback();
        // });

        this.appendChild(this.props.dateInput);
        // this.appendChild(this.props.timeInput);
        this.appendChild(this.pickerButton);

        if(this.hasAttribute('value')) this.value = this.getAttribute("value");
    }

    updateUI(value) {
        if(!value) return;
        if(!this.props.dateInput) this.initUI();
        const date = `${value.getFullYear()}-${this.prefixSingleDigits(value.getMonth() + 1)}-${this.prefixSingleDigits(value.getDate())}`;
        this.props.dateInput.innerHTML = value.toLocaleString(this.locale, {timeZone: this.tz});
        // const time = `${this.prefixSingleDigits(value.getHours())}:${this.prefixSingleDigits(value.getMinutes())}`;
        // this.props.timeInput.value = time;
    }

    toggleDatePicker() {
        if(this.datePicker.hidden === true) {
            // console.log(this.props.value)
            this.datePicker.show();
            this.datePicker.value = this.props.value || new Date();
            this.setDatePickerPosition();
            return;
        }
        this.datePicker.hide();
    }

    setDatePickerPosition() {
        const offsets = get_offset(this);
        this.datePicker.style.top = `${offsets.y + offsets.h}px`;
        this.datePicker.style.left = `${offsets.x}px`;
        this.datePicker.style.position = "absolute";
        const pickerOffset = get_offset(this.datePicker);
        const scrollH = window.innerHeight + window.scrollY;
        if(pickerOffset.bottom > scrollH) this.datePicker.style.top = `${offsets.y - pickerOffset.h}px`;
    }


    storeValueFromChangeCallback() {
        let date = this.props.dateInput.value;
        // let time = this.props.timeInput.value;
        let datetime = "";
        datetime += date;
        if(datetime) datetime = `${datetime} `
        // datetime += time;
        this.value = this.fromString(`${datetime}`);
        this.dispatchEvent(new Event("change"));
    }

    prefixSingleDigits(digit) {
        let string = String(digit);
        if(string.length <= 1) string = `0${string}`;
        return string;
    }

   

    fromSeconds(val) {
        return new Date(val * 1000);
    }

    fromMilliseconds(val) {
        const date = new Date();
        date.setTime(Number(val));
        return date;
    }

    fromMicroseconds(val) {
        const date = new Date();
        date.setTime(Number(val) / 1000);
        return date;
    }

    fromString(val) {
        const date = new Date(val);
        return date;
    }
}

customElements.define("input-datetime", InputDateTime);
