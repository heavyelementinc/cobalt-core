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
    }

    connectedCallback() {
        this.initSubmissionListeners();
        if(!this.submitButton) this.setAttribute("autosave", "element");
        this.addEventListener("submission", event => {
            const data = this.buildSubmission(event);
            this.submit(data, event);
        });
    }

    get value() {
        const elements = this.querySelectorAll(universal_input_element_query);
        let value = {};
        for(const input of elements) {
            value[input.name ?? input.getAttribute("name")] = input.value;
        }
        return value;
    }

    async submit(data = null, event = {}) {
        const method  = this.getAttribute('method');
        const action  = this.getAttribute('action');
        const enctype = this.getAttribute('enctype') ?? "application/json; charset=utf-8";
        
        const api = new AsyncFetch(action, method, {format: enctype});
        api.addEventListener('submit', e => this.handleAsyncSubmitEvent(e, event));
        api.addEventListener('error',  e => this.handleAsyncErrorEvent(e, event));
        api.addEventListener('done',   e => this.handleAsyncDoneEvent(e, event));

        this.abort = api.abort;
        await api.submit(data || this.buildSubmission({target: null}));
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
            if(this.autoSave) this.dispatchEvent(new CustomEvent("submission", event));
        }
        const elements = this.querySelectorAll(universal_input_element_query);
        for(const el of elements) {
            el.removeEventListener("change", autoSaveListener.bind(this));
            el.addEventListener("change", autoSaveListener.bind(this));
        }
    }

    buildSubmission(event) {
        if(event.target === null) return this.value;
        if(event.target === this.submitButton) return this.value;
        let submit = {};
        switch(this.autoSave) {
            case "none":
            case "false":
                return;
            case "element":
            case "autosave":
                submit[event.target.name || event.target.getAttribute("name")] = event.target.value;
                break;
            case "fieldset":
                const fieldset = event.target.closest("fieldset");
                for(const el of fieldset.querySelectorAll(universal_input_element_query)) {
                    submit[el.name || event.target.getAttribute("name")] = el.value;
                }
                break;
            case "form":
            default:
                submit = this.value;
                break;
        }
        return submit;
    }

    handleAsyncSubmitEvent(e, submission = {}) {
        this.dispatchEvent(new Event("submit", {...e, submitter: submission.target || null}));
    }

    handleAsyncErrorEvent(e, submission = {}) {
        this.dispatchEvent(new Event("error", e));
    }

    handleAsyncDoneEvent(e, submission = {}) {
        this.dispatchEvent(new CustomEvent("done", e));
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
}

customElements.define("new-form-request", NewFormRequest);
