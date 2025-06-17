export default class ICustomInput extends HTMLElement {
    DEFAULT_VALUE = null;
    DEFAULT_TIMEOUT = 1550;

    constructor() {
        super();
        this.setAttribute("cobalt-component", "cobalt-component");
        this.customInputReady = new Deferred(() => {
            // if("value" in this === false) throw new Error("ICustomInputs must have a ")
            this.DEFAULT_VALUE = this.value;
        });
    }

    get customInput() {
        return true;
    }

    get defaultValue() {
        return this.DEFAULT_VALUE;
    }

    get disabled() {
        return this.getAttribute("disabled") === "disabled";
    }

    set disabled(value) {
        if([true, "true", "disabled"].includes(value)) return this.setAttribute("disabled", "disabled");
        this.removeAttribute("disabled");
    }

    get form() {
        return this.closest("form-request");
    }

    get name() {
        return this.getAttribute("name");
    }

    set name(value) {
        this.setAttribute("name", value);
    }

    /**
     * @property {int} timeout the default duration in milliseconds to wait before
     * an autosave submission event should be triggered
     */
    get timeout() {
        const timeout = this.getAttribute("timeout");
        if(!timeout) return this.DEFAULT_TIMEOUT;
        return parseInt(timeout) ?? this.DEFAULT_TIMEOUT;
    }

    get type() {
        return this.getAttribute("type");
    }

    set type(value) {
        this.setAttribute("type", value);
    }

    get readOnly() {
        return this.getAttribute("readonly") === "readonly";
    }

    set readOnly(value) {
        if([true, "true", "readonly"].includes(value)) return this.setAttribute("readonly", "readonly");
        this.removeAttribute("readonly");
    }

    get required() {
        return this.getAttribute("required") === "required";
    }

    set required(value) {
        if([true, "true", "required"].includes(value)) return this.setAttribute("required", "required");
        this.removeAttribute("required");
    }

    _validity = {
        badInput: false,
        customError: false,
        patternMismatch: false,
        rangeOverflow: false,
        rangeUnderflow: false,
        stepMismatch: false,
        tooLong: false,
        tooShort: false,
        typeMismatch: false,
        valid: true,
        valueMissing: false,
    };
    _validationMessage = "";

    checkValidity() {
        if(this.isRequiredFulfilled() == false) {
            this._validationMessage = "This field is required!";
            this._validationMessage.customError = true;
            this._validity.tooShort = true;
            return false;
        } else this._validity.tooShort = false;
        if("_setCustomValidity" in this) return this._setCustomValidity(message);
        if("_checkValidity" in this) return this._checkValidity();
        return true;
    }

    setCustomValidity(message) {
        if(!message) {
            this._validationMessage = "";
            this._validity.customError = false;
            return;
        }
        this._validationMessage = message;
        this._validity.customError = true;
    }

    isRequiredFulfilled() {
        if(this.required == false) return true;
        if("_isRequiredFulfilled" in this) return this._isRequiredFulfilled();
        return !!this.value;
    }

    // handleSaveFeedback() {
    //     this.disabled = true;
    // }

    // clearSaveFeedback() {
    //     this.disabled = false;
    // }
}