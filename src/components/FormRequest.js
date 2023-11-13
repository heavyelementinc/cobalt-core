/**
 * @attribute method   - The method to use when submitting data
 * @attribute action   - The endpoint to submit data to
 * @attribute autosave - [false, element, autosave, fieldset, form] If no submit button is found, then defaults to "element"
 * @attribute enctype  - "application/json; charset=utf-8"
 * @emits submission   - Fires when an element wants to submit the form
 * @emits submit       - Fires when AsyncFetch begins submitting, cancellable
 * @emits aborted      - Fires when AsyncFetch submit is cancelled or abort is called
 * @emits error        - Fires when AsyncFetch results in an error
 * @emits done         - Fires when AsyncFetch finishes successfully
 */

class NewFormRequest extends HTMLElement {
    constructor() {
        super();
        this.validAutoSaveValues = ['false', 'element', 'autosave', 'fieldset', 'form'];
        
        this.abort = () => {}; // Call to abort request
        this.fileUploadFields = [];
        this.fieldsRequiringFeedback = [];
        this.feedbackTracker = [];
        this.originalState = {};
        this.childrenReady = false;
        this.childWebComponentPromises = [];
    }

    get unsavedChanges() {
        if(["true","confirm-unsaved",null].includes(this.getAttribute("confirm-unsaved")) == false) return false;
        if(this.childrenReady === false) return false;
        const currentValue = this.value;
        for(const i in currentValue) {
            if(i in this.originalState === false) return true;
            if(this.originalState[i] !== currentValue[i]) return true;
        }
        return false;
    }

    connectedCallback() {
        this.initSubmissionListeners();
        if(!this.submitButton && !this.autoSave) this.autoSave = "form"; // Default forms without a save button to autosave
        this.addEventListener("submission", event => {
            const data = this.buildSubmission(event);
            this.submit(data, event);
        });
        
        const elements = this.querySelectorAll(universal_input_element_query);
        for(const node of elements) {
            if(!isRegisteredWebComponent(node.tagName)) continue;
            let resolver = null;
            this.childWebComponentPromises.push(new Promise(resolve => {
                resolver = resolve
            }))
            node.addEventListener("componentready", () => {
                resolver(true);
            }, {once: true})
        }
        
        this.initOriginalState();
    }

    async initOriginalState() {
        await Promise.all(this.childWebComponentPromises);
        this.originalState = this.value;
        this.childrenReady = true;
    }

    disconnectedCallback() {
        this.removeFeedback()
    }

    get value() {
        if(this.childrenReady !== true) console.warn("This element has children that are not ready!", this);
        const elements = this.querySelectorAll(universal_input_element_query);
        let value = {};
        for(const input of elements) {
            value[input.name ?? input.getAttribute("name")] = this.getFieldValue(input)//.value;
        }
        return value;
    }

    async submit(data = null, event = {}) {
        const method  = this.getAttribute('method');
        const action  = this.getAttribute('action');
        const enctype = this.getAttribute('enctype') ?? "application/json; charset=utf-8";
        
        const api = new AsyncFetch(action, method, {format: enctype, form: this});
        api.addEventListener('submit', e => this.handleAsyncSubmitEvent(e, event));
        api.addEventListener('error',  e => this.handleAsyncErrorEvent(e, event));
        api.addEventListener('done',   e => this.handleAsyncDoneEvent(e, event));

        this.abort = api.abort;
        let result = {};
        try{
            result = await api.submit(data || this.buildSubmission({target: null}));
            this.originalState = this.value;
        } catch(error) {
            this.handleAsyncErrorEvent(error, event);
        }
        this.abort = () => {};
    }

    initSubmissionListeners() {
        this.initSubmitButton();
        this.initAutoSaveListeners();
    }

    initSubmitButton() {
        this.submitButton = this.querySelector("button[type='submit'],input[type='submit'],split-button option[type='submit'],split-button[type='submit']");
        if(this.submitButton) this.submitButton.addEventListener("click", e => this.dispatchEvent(new CustomEvent("submission", e)));
    }

    initAutoSaveListeners() {
        function autoSaveListener(event) {
            if(this.autoSave) this.dispatchEvent(new CustomEvent("submission", {...event, detail: {element: event.target || event.currentTarget || event.srcElement}}));
        }
        const elements = this.querySelectorAll(universal_input_element_query);
        for(const el of elements) {
            if(["file", "files"].includes(el.type)) this.fileUploadFields.push(el);
            el.removeEventListener("change", autoSaveListener.bind(this));
            el.addEventListener("change", autoSaveListener.bind(this));
        }
    }

    buildSubmission(event) {
        this.fieldsRequiringFeedback = [];
        let target = event.detail?.element || event.target || event.currentTarget || event.srcElement;
        if(target === null) return this.value;
        if(target === this.submitButton) return this.value;
        let submit = {};
        switch(this.autoSave) {
            case "none":
            case "false":
                return;
            case "element":
            case "autosave":
                submit[target.name || target.getAttribute("name")] = this.getFieldValue(target);//.value;
                this.fieldsRequiringFeedback.push(target);
                break;
            case "fieldset":
                const fieldset = target.closest("fieldset");
                for(const el of fieldset.querySelectorAll(universal_input_element_query)) {
                    submit[el.name || target.getAttribute("name")] = this.getFieldValue(el);//.value;
                }
                this.fieldsRequiringFeedback.push(fieldset);
                break;
            case "form":
            default:
                submit = this.value;
                this.fieldsRequiringFeedback.push(this);
                break;
        }
        return (this.fileUploadFields.length === 0) ? submit : this.encodeFormData(submit);
    }

    encodeFormData(data) {
        const form = new FormData();
        form.append("json_payload", JSON.stringify(data));
        if(typeof data !== "object") return form;
        
        for( const field in data ) {
            const fields = this.querySelectorAll(`[name='${field}'][type='files'],[name='${field}'][type='file']`);
            if(!fields) continue;
            for( const el of fields ) {

                for( const file of el.files){
                    form.append(`${el.name}[]` || 'files[]', file);
                    this.totalUploadSize += parseFloat(file.size);
                }
            }
        }

        return form;
    }

    handleAsyncSubmitEvent(e, submission = {}) {
        if(this.feedback) {
            this.applyFeedback(e);
        }
        this.dispatchEvent(new Event("submit", {...e, submitter: submission.target || null}));
    }

    handleAsyncErrorEvent(e, submission = {}) {
        this.removeFeedback(e);
        this.dispatchEvent(new Event("error", e));
    }

    handleAsyncDoneEvent(e, submission = {}) {
        this.removeFeedback(e);
        this.dispatchEvent(new CustomEvent("done", e));
    }

    applyFeedback(event) {
        this.fieldsRequiringFeedback.forEach(e => {
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
                default:
                    this.createFeedback(e, 'top-right');
            }
        });
    }

    async createFeedback(target, type, padding = 5, disable = true) {

        const validTypes = ['top-right', 'center'];
        if(!validTypes.includes(type)) type = validTypes[0];
        target.setAttribute("disabled", "disabled");
        target.ariaDisabled = true;
        const offsets = get_offset(target);
        console.log(offsets);
        
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

        this.feedbackTracker.push(feedback);

        await wait_for_animation(feedback, "feedback-add");
    }

    removeFeedback() {
        this.fieldsRequiringFeedback.forEach(async (e, i) => {
            setTimeout(() => {
                e.removeAttribute("disabled");
                e.ariaDisabled = false;
            }, 200);
            await wait_for_animation(e, "feedback-remove");
            this.feedbackTracker[i]?.parentNode.removeChild(this.feedbackTracker[i])
        });
        this.feedbackTracker = [];
        this.fieldsRequiringFeedback = [];
    }

    get autoSave() {
        let value = this.getAttribute("autosave") ?? "false";
        if(value === "false" || !this.validAutoSaveValues.includes(value)) return false;
        return value;
    }

    set autoSave(value) {
        if(!this.validAutoSaveValues.includes(value)) throw new TypeError(`"${value}" is not a valid property`);
        this.setAttribute("autosave", value);
    }

    get feedback() {
        return JSON.parse(this.getAttribute("feedback") || "true");
    }

    set feedback(fdbk) {
        this.setAttribute("feedback", (['true', 'false'].includes(fdbk)) ? fdbk : "true");
    }

    attributeChangedCallback(attribute, old, newValue) {
        console.log({attribute, old, newValue})
    }

    getFieldValue(field) {
        if(field.tagName === "INPUT") {
            switch(field.type) {
                case "number":
                    return Number(field.value);
                default:
                    return field.value;
            }
        }
        return field.value;
    }
}

customElements.define("form-request", NewFormRequest);
