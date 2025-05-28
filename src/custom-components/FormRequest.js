import ICustomInput from './ICustomInput.js';
import {ObjectGallery} from './ObjectGallery.js';

class ProgressWizard extends HTMLElement {
    __frame1__ = null;
    constructor() {
        super();
    }

    connectedCallback() {
        if(this.progressable) {
            this.createFrame1();
        }
    }

    get progressable() {
        return ["true", "progressable"].includes(this.getAttribute("progressable"));
    }
    
    set progressable(value) {
        const state = [true, 'true', 'progressable'].includes(value);
        if(!state) {
            this.removeAttribute("progressable");
            return;
        }
        this.setAttribute("progressable");
    }

    get FRAME_COMMON() {
        return "form-request--frame";
    }
    get CURRENT_FRAME() {
        return "form-request--current-frame";
    }
    get PREVIOUS_FRAME() {
        return "form-request--previous-frame";
    }
    get NEXT_FRAME() {
        return "form-request--next-frame";
    }

    createFrame1() {
        let frame1 = this.querySelector(this.CURRENT_FRAME);
        // If we don't have a current frame, let's collect all the form children
        // and move them to a frame
        if(!frame1) {
            frame1 = document.createElement("div");
            for(const element of Array.from(this.children)) {
                frame1.appendChild(element)
            }
            this.appendChild(frame1);
            setTimeout(() => {
                this.setHeight(frame1);
            }, 50);
        }
        // Let's handle the classes for our current (now previous) frame
        frame1.classList.add(this.FRAME_COMMON);
        this.__frame1__ = frame1;
        return frame1;
    }

    setHeight(element) {
        let height = Math.ceil(element.getBoundingClientRect().height);
        this.style.setProperty("--height", `${height}px`);
    }

    /** This function will append the next step in the form fields, send an 
     * update('@form', ['next' => <HTML>]) command from the action controller.
     * 
     * Use <button type="submit" name="someName" value="2">Submit</button>
     * Use <button type="back" name="someName" value="1">Back</button>
     * @param {string} html 
     * @param {boolean} allowFormRegression
     */
    async next(html, callback = () => {}) {
        const FRAME_COMMON = this.FRAME_COMMON;
        const CURRENT_FRAME = this.CURRENT_FRAME;
        const PREVIOUS_FRAME = this.PREVIOUS_FRAME;
        const NEXT_FRAME = this.NEXT_FRAME;
        
        let frame1 = this.createFrame1();

        // frame2 is the next item
        const frame2 = document.createElement("div");
        frame2.style.position = "relative";
        frame2.classList.add(FRAME_COMMON,NEXT_FRAME);
        frame2.innerHTML = html;
        // document.body.appendChild(frame2);
        
        
        this.appendChild(frame2);

        frame2.style.position = "";
        if("nextFrameCallback" in this) this.nextFrameCallback(frame2, frame1);

        // Let's listen for our transition for finish
        const waitForTransition = new Deferred(() => {});
        frame2.addEventListener("transitionend", () => {
            waitForTransition.resolve();
        }, {once: true});

        // Let's wait just a little bit before we trigger all our
        // transitions happening
        setTimeout(() => {
            frame1.classList.remove(CURRENT_FRAME);
            frame1.classList.add(PREVIOUS_FRAME);
            frame2.classList.remove(NEXT_FRAME);
            this.setHeight(frame2);
            // this.style.setProperty("height", Math.ceil(this.style.getProperty("--to-height")));
        }, 50);
        
        // Let's wait for our transition to fulfill before we continue
        await waitForTransition.promise;
        
        // Now that we've finished, let's run any callback we've been handed
        callback();
        // Let's dispatch an event to anyone who might be listening
        this.dispatchEvent(new CustomEvent('next', {detail: {target: this}}));
        // Finally, let's remove the previous frame
        frame1.parentNode.removeChild(frame1);
        this.classList.remove(this.PROGRESS_BACK_CLASS);
    }
}

/**
 * @attribute [progressable="progressable"] Indicates that this form should start as a progressable
 * 
 * @emits submission
 * @emits aborted
 * @emits invalid
 * @emits error
 * @emits done
 * @emits next
 */

export default class FormRequest extends ProgressWizard {
    __fieldsRequiringFeedback = [];
    __feedbackTracker = [];
    __validationMessages = [];
    __autoSaveTimeout = null;

    constructor() {
        super();
        this.addEventListener("submission", this.onsubmission.bind(this));
        this.addEventListener("change", this.onchange.bind(this));
        this.addEventListener("click", this.onclick.bind(this));
        this.addEventListener("keydown", this.onkeydown.bind(this));
        document.addEventListener("keydown", this.onhotkey.bind(this));
    }

    connectedCallback() {
        super.connectedCallback();
    }

    disconnectedCallback() {
        this.removeFeedback();
        this.removeValidationMessages();
        document.removeEventListener("keydown", this.onhotkey);
    }

    /**
     * Returns true if autosave attribute is set. If it's not set, then it's 
     */
    get autosave() {
        const autosave = this.getAttribute("autosave");
        if(autosave == "autosave") return true;
        // If we don't have an explicit autosave attribute set, we should 
        // check if this form-request contains a submit button.
        const hasAtLeastOneSubmitButton = this.querySelector("button[type='submit'], input[type='submit'], button[type='back'], input[type='back']");
        if(!hasAtLeastOneSubmitButton) return true;
        return false;
    }

    set autosave(value) {
        if(value) {
            this.setAttribute("autosave", "autosave");
        }
    }

    get action() {
        return this.getAttribute("action");
    }

    get method() {
        return this.getAttribute("method") ?? "GET";
    }

    get enctype() {
        return this.getAttribute("enctype") ?? "application/json; charset=utf-8";
    }

    get disabled() {
        return ['true', 'disabled'].includes(this.getAttribute("disabled"));
    }

    set disabled(value) {
        if([true, 'true', 'disabled'].includes(value)) {
            this.setAttribute("disabled", "disabled");
            return;
        }
        this.removeAttribute("disabled");
    }

    get headers() {
        if(this.hasAttribute('headers') === false) return {};
        let headers = {};
        for(const header of this.getAttribute("headers").split(";")) {
            const split = header.split(":");
            headers[split[0].trim()] = split[1].trim();
        }
        return headers;
    }

    onsubmission(event) {
        if("formData" in event.detail == false) {
            throw new Error("Malformed `submission` event!", event);
        }
        if(event.detail.formData instanceof FormRequestData == false) {
            throw new TypeError("`formData` details must be of type `FormRequestData`");
        }
        this.submit(event.detail.formData, event);
    }

    /** Handle input field events
     * @param {InputEvent} event
     * @returns 
     */
    onchange(event) {
        if(this.autosave === false) {
            return;
        }
        event.stopPropagation();
        event.preventDefault();
        clearTimeout(this.__autoSaveTimeout);
        const formData = new FormRequestData(this);
        const target = event.target.closest("[name]");
        formData.set(target.name ?? target.getAttribute("name"), this.getFormElementValue(target));
        let timeout = 1000;
        switch(target.tagName) {
            case "INPUT":
                if(["date", "time", "datetime-local"].includes(target.type)) {
                    timeout = 1550;
                }
                break;
            default:
                if(target.timeout) timeout = target.timeout;
                break;
        }

        this.__autoSaveTimeout = setTimeout(() => {
            this.dispatchEvent(new CustomEvent("submission", {
                detail: {
                    type: "autosave",
                    formData: formData
                }
            }));
        }, timeout);
    }

    /** Handle submit button events
     * @param {PointerEvent} event
     */
    async onclick(event) {
        const submitButtonTypes = ['submit', 'back'];
        // Let's search for a button
        const target = event.target.closest("button,input");
        if(!target) return;
        // We're trying to filter out clicks on non-submit buttons here so we'll
        // just return from this method if the click didn't target a submit button
        switch(target.tagName) {
            case "BUTTON":
                if(submitButtonTypes.includes(target.getAttribute("type")) !== true) return;
                break;
            case "INPUT":
                if(submitButtonTypes.includes(target.type) !== true) return;
                break;
            default:
                return;
        }
        event.stopPropagation();
        event.preventDefault();
        let details = {type: "submit"};
        details.formData = this.buildSubmission([], event);

        this.dispatchEvent(new CustomEvent("submission", {detail: details}));
    }

    onkeydown(event) {
        switch(event.key) {
            case "Enter":
                if(this.getAttribute("autosave") !== "enter") return;
                this.dispatchEvent(new CustomEvent("submission", {detail: {type: "keyup", formData: this.buildSubmission()}}));
                break;
            case "s":
                if(!navigator.platform.match("Mac") ? event.metaKey : event.ctrlKey) return;
                event.preventDefault();
                event.stopPropagation();
                this.dispatchEvent(new CustomEvent("submission", {detail: {type: "save", formData: this.buildSubmission()}}));
                break;
        }
    }

    onhotkey(event) {
        console.log("HOTKEY", event);
        switch(event.key) {
            
        }
    }

    /**
     * This function only handles building the submission data
     * @param {Array|NodeList} targets 
     * @returns {FormRequestData}
     */
    buildSubmission(targets = [], event = null) {
        if(targets.length === 0) {
            targets = this.querySelectorAll(universal_input_element_query);
        }
        const formData = new FormRequestData(this, event?.target ?? null);
        for(const element of targets) {
            const name = element.name ?? element.getAttribute("name");
            const value = this.getFormElementValue(element);
            formData.set(name, value);
        }

        return formData;
    }

    /** This function only handles sending content to the backend
     * @param {FormRequestData} formRequestData
     */
    async submit(formRequestData, event = null) {
        switch(this.method) {
            case "GET":
                this.__get(formRequestData);
                return;
            case "NAVIGATE":
                this.__navigate(formRequestData);
                return;
        }
        this.__post(formRequestData, event);
    }

    /**
     * The GET request will do a Single-Page App GET request
     * @param {FormRequestData} data 
     */
    async __get(data) {
        if(data instanceof FormRequestData == false) throw new TypeError("submission must be an instance of FormRequestData");
        Cobalt.router.location = `${this.getAttribute("action")}?${new URLSearchParams(await data.toFormData()).toString()}`
    }

    /**
     * The NAVIGATE method will do a traditional GET request by navigating to
     * the [action] attribute. This does not honor the Single Page App-ness of
     * your application.
     * @param {FormRequestData} data 
     */
    async __navigate(data) {
        if(data instanceof FormRequestData == false) throw new TypeError("submission must be an instance of FormRequestData");
        location = `${this.getAttribute("action")}?${new URLSearchParams(await data.toFormData()).toString()}`
    }

    /**
     * 
     * @param {FormRequestData} formRequestData 
     */
    async __post(formRequestData, event = null) {
        if(formRequestData instanceof FormRequestData == false) throw new TypeError("submission must be an instance of FormRequestData");
        let apiOptions = {
            form: this,
            format: this.enctype,
            headers: {
                'X-Keyboard-Modifiers': encodeClickModifiers(event),
                ...this.headers
            }
        }
        // Check if we're trying to encode formdata
        if(formRequestData.hasFiles()) apiOptions.format = "multipart/form-data";

        this.api = new AsyncFetch(this.action, this.method, apiOptions);
        this.api.addEventListener('submit', e => this.handleAsyncSubmitEvent(e, event));
        this.api.addEventListener('error',  e => this.handleAsyncErrorEvent(e, event));
        this.api.addEventListener('done',   e => this.handleAsyncDoneEvent(e, event));
        this.api.addEventListener('abort',   e => this.handleAsyncDoneEvent(e, event));
        this.api.addEventListener('asyncfinished', e => this.removeFeedback());
        
        // Let's wait for any FormRequestData Promises to be fulfilled
        await formRequestData.ready();
        this.response = await this.api.submit(await formRequestData.value);
        return this.response;
    }

    handleAsyncSubmitEvent(e, event) {
        this.__fieldsRequiringFeedback.forEach(e => {
            e.disabled = true;
            switch(e.tagName) {
                case "FIELDSET":
                case "FORM-REQUEST":
                    this.createFeedback(e, 'center');
                    break;
                case "INPUT-SWITCH":
                    this.createFeedback(e, "center");
                    break;
                case "TEXTAREA":
                    // Dumb hack because of how SimpleMDE handles text input
                    if(e.closest("markdown-area") !== null) break;
                case "MARKDOWN-AREA":
                case "BLOCK-EDITOR":
                default:
                    this.createFeedback(e, 'top-right', 5, false);
            }
        });
    }
    handleAsyncErrorEvent(e, event) {
        this.removeFeedback();
    }
    handleAsyncDoneEvent(e, event) {
        this.removeFeedback();
    }

    /* ================ ELEMENT MANAGEMENT FUNCTIONS ================ */

    /**
     * This method handles client-side input validation and casting certain
     * HTML elements like [type='number'] or [type='files']
     * @param {HTMLInputElement|ICustomInput|HTMLElement} field 
     * @returns 
     */
    getFormElementValue(field) {
        if(this.isValid(field) === false) {
            this.__validationMessages.push(appendElementInformation(field, field.validationMessage ?? "Validation failed"));
            throw new TypeError("Validity check failed");
        }
        this.__fieldsRequiringFeedback.push(field);
        if(field.tagName === "INPUT") {
            switch(field.type) {
                case "number":
                case "range":
                    return field.valueAsNumber;
                case "date":
                    return field.valueAsDate;
                case "datetime-local":
                    if(field.value) return new Date(field.value);
                    return null;
                case "files":
                    return field.files;
            }
        }
        return field.value;
    }

    /**
     * @param {HTMLElement} element 
     */
    isValid(element) {
        if("checkValidity" in element === false) return true;
        const validity = element.checkValidity();
        if(!validity) element.dispatchEvent(new Event("invalid"));
        return validity;
    }

    async createFeedback(target, type, padding = 5, disable = true) {
        const validTypes = ['top-right', 'center'];
        if(!validTypes.includes(type)) type = validTypes[0];
        
        // if(target.offsetParent === null) return this.feedbackForTabNav(target);

        if(disable) target.disabled = true;
        // target.ariaDisabled = true;
        const offsets = get_offset(target);
        // console.log(offsets);
        
        const feedback = document.createElement("loading-spinner");
        feedback.classList.add("form-request--feedback");
        feedback.style.position = "absolute";
        feedback.style.height = `1em`;
        document.body.appendChild(feedback);
        const feedbackSizing = get_offset(feedback);

        let x, y;
        switch(type) {
            case "center":
                x = offsets.x + (offsets.w * .5) - (feedbackSizing.w * .5);
                y = offsets.y + (offsets.h * .5) - (feedbackSizing.h * .5);
                break;
            default:
                x = offsets.x + offsets.w - padding - feedbackSizing.w;
                y = offsets.y + (feedbackSizing.h / 2);
        }
        
        feedback.style.left = `${x}px`;
        feedback.style.top  = `${y}px`;
        if(offsets.zIndex && offsets.zIndex !== "auto") {
            feedback.style.zIndex = Number(offsets.zIndex) + 10;
        }

        this.__feedbackTracker.push(feedback);

        await wait_for_animation(feedback, "feedback-add");
    }

    removeFeedback() {
        // 
        this.__fieldsRequiringFeedback.forEach(e => {
            e.disabled = false;
        })
        this.__fieldsRequiringFeedback = [];

        this.__feedbackTracker.forEach(e => {
            e.parentNode.removeChild(e);
        })
        this.__feedbackTracker = [];
    }

    removeValidationMessages() {
        this.__validationMessages.forEach(e => {
            if("clearMessage" in e) e.clearMessage();
        });
        this.__validationMessages = [];
    }
}

class FormRequestData {
    __formData__ = {};
    __promises__ = {};
    __form__ = null;
    __submitter__ = null;
    __hasFileList__ = false;
    __uploadSize__ = 0;

    constructor(formRequestElement = null, submitterElement = null) {
        this.form = formRequestElement;
        this.submitter = submitterElement;
    }

    get form() {
        return this.__form__;
    }
    set form(element) {
        if(!element) {
            this.__form__ = null;
            return;
        }
        if(element instanceof FormRequest !== true) throw TypeError("Form must be an instance of FormRequest");
        this.__form__ = element;
    }

    get submitter() {
        return this.__submitter__;
    }

    /** Submitters DO NOT SUPPORT asynchronous values */
    set submitter(element) {
        if(!element) {
            this.__submitter__ = null;
            return;
        }
        if(element instanceof HTMLElement !== true) throw TypeError("Submitter must be an instance of HTMLElement");
        this.__submitter__ = element;
    }

    /**
     * @returns {Object|FormData|Promise}
     */
    get value() {
        if(this.__submitter__) {
            this.__formData__[this.__submitter__.name] = this.__submitter__.value;
        }
        // If there are no promises, then we should return normal object
        if(Object.values(this.__promises__).length === 0) {
            if(this.hasFiles()) return this.formDataWithJSONPayload();
            return this.__formData__;
        }
        return this.__read();
    }

    get uploadSize() {
        return this.__uploadSize__;
    }

    set uploadSize(value) {
        this.__uploadSize__ = value;
    }

    /**
     * This method will push a value to an array or create an array with the value
     * as the first element if the field does not exist.
     * @param {string} item 
     * @param {*} value 
     * @returns 
     */
    append(item, value) {
        // Let's check if we have a FileList
        if(value instanceof FileList) this.__hasFileList__ = true;
        if(item in this.__formData__ === false) {
            this.__formData__[item] = [value];
            return;
        }
        if(!Array.isArray(fieldValue)) this.__formData__[item] = [fieldValue];
        this.__formData__[item].push(value);
    }

    /**
     * This method will return a boolean value. A true value is returned if the
     * name exists in the __formData__, as a promise, or if the submitter's name
     * matches the `name`
     * @param {string} name 
     * @returns 
     */
    has(name) {
        if(name in this.__promises__) return true;
        if(name in this.__formData__) return true;
        if(this.__submitter__.name == name) return true;
        return false;
    }

    /**
     * This method will set the existing 
     * @param {string} item 
     * @param {*} value 
     * @returns 
     */
    set(item, value) {
        // Handle any promises
        if(value instanceof Promise) {
            this.__promises__[item] = value;
            // Wait for the promise to resolve and store the value
            value.then(v => { this.__set(item, v); });
            return;
        }
        this.__set(item, value);
    }
    
    /** A private method to unify setting a value */
    __set(item, value) {
        this.__formData__[item] = value;
        if(value instanceof FileList) this.__hasFileList__ = true;
    }

    /**
     * Determines if this field is (or should be) an array based on the last two
     * characters of the field name. If the field ends in "[]" then the square
     * brackets are stripped from the name and the supplied value is passed to
     * the `append` method. 
     * 
     * Otherwise, the name and value will be passed to the `set` method.
     * @param {string} item 
     * @param {*} value 
     * @returns {void}
     */
    add(item, value) {
        if(item.substring(item.length - 2) == "[]") {
            this.append(item.substring(0,item.length - 2), value);
            return;
        }
        this.set(item, value);
    }

    async get(item) {
        if(!this.has(item)) return null;
        if(this.__submitter__.name === item) return this.__submitter__.value;
        return this.__promises__[item] ?? this.__formData__[item];
    }

    /**
     * Returns true if this data contains a FileList, otherwise it returns false
     * @returns {boolean} 
     */
    hasFiles() {
        return this.__hasFileList__;
    }

    /** @returns Promise */
    ready() {
        return Promise.all(Object.values(this.__promises__));
    }

    async toFormData() {
        await this.ready();
        let formData = new FormData();
        for(const name in this.__formData__) {
            formData.append(name, this.__formData__[name]);
        }
        return formData;
    }

    /**
     * 
     * @returns {FormData}
     */
    async formDataWithJSONPayload() {
        // Let's wait for this FormRequestData to be ready
        await this.ready();
        const magicFilesString = "$_FILES_$";
        let formData = new FormData();
        // Set up a clone of our __formData__ so we can remove any FileLists from
        // the JSON stringification process
        const mutable = structuredClone(this.__formData__);
        for(const name in this.__formData__) {
            const data = this.__formData__[name];
            if(Array.isArray(data)) {
                this.__handleNestedFileLists(formData, name, data, mutable, magicFilesString);
            }
            if(data instanceof FileList !== true) continue;
            mutable[name] = magicFilesString;
            this.__handleFileList(formData, name, data);
        }
        formData.append("json_payload", JSON.stringify(mutable));
        return formData;
    }

    __handleNestedFileLists(formData, name, data, mutable, magicFilesString) {
        let index = 0;
        for(const n of data) {
            if(n instanceof FileList) {
                mutable[name][index] = magicFilesString;
                this.__handleFileList(formData, name, n);
            }
            index += 1;
        }
    }

    /**
     * 
     * @param {FormData} formData 
     * @param {string} name 
     * @param {FileList} value 
     */
    __handleFileList(formData, name, value) {
        for(const file of value) {
            formData.append(`${name}[]`, file);
            this.uploadSize += file.size;
        }
    }

    __read() {
        return new Promise(async resolve => {
            await this.ready();
            if(this.hasFiles()) {
                resolve(await this.formDataWithJSONPayload());
                return;
            }
            resolve(this.__formData__);
        });
    }
}