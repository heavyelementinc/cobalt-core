/**
 * Use a <button type="submit" [name="somename" value="somevalue"]>Submit</button>
 * to submit your form.
 * 
 * For stepped forms, you can use <button type="back" name="step" value="1">Back</button>
 * to step backwards (or to arbitrary steps in the process).
 * 
 * @attribute method   - The method to use when submitting data, use the special "NAVIGATE" to submit a traditional GET request
 * @attribute action   - The endpoint to submit data to
 * @attribute autosave - [false, element, autosave, fieldset, form] If no submit button is found, then defaults to "element"
 * @attribute enctype  - "application/json; charset=utf-8"
 * @attribute headers  - A semicolon-delimited list of headers to send with each request
 * @emits submission   - Fires when an element wants to submit the form
 * @emits submit       - Fires when AsyncFetch begins submitting, cancellable
 * @emits aborted      - Fires when AsyncFetch submit is cancelled or abort is called
 * @emits error        - Fires when AsyncFetch results in an error
 * @emits done         - Fires when AsyncFetch finishes successfully
 * @emits next         - Fires when the 'next' step in a form is finished
 */

class NewFormRequest extends HTMLElement {
    constructor() {
        super();
        this.validAutoSaveValues = ['false', 'none', 'element', 'field', 'autosave', 'fieldset', 'form', 'enter'];
        
        this.abort = () => {}; // Call to abort request
        this.getMethods = ["GET", "NAVIGATE"];
        this.postMethods = ["POST","PUT","DELETE"];
        this.fileUploadFields = [];
        this.fieldsRequiringFeedback = [];
        this.tabNavTabsWithErrors = [];
        this.feedbackTracker = [];
        this.originalState = {};
        this.childrenReady = false;
        this.childWebComponentPromises = [];
        this.PROGRESS_BACK_CLASS = "form-request--backwards";
        this.enterButtonFunction = e => {
            if(!['Enter', 'Return'].includes(e.key)) return;
            this.dispatchEvent(new CustomEvent("submission", {
                ...event,
                detail: {
                    target: e.currentTarget || e.target
                }
            }));
        }
        this.addEventListener("clearall", () => {
            this.clearAll()
        });
    }

    get headers() {
        return this.getHeadersFromAttribute();
    }

    get autoSave() {
        let value = this.getAttribute("autosave") ?? "false";
        if(value === "false" || value === "none" || !this.validAutoSaveValues.includes(value)) return false;
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

    get disabled() {
        const value = this.getAttribute("disabled");
        switch(value) {
            case "disabled":
            case "true":
                return true;
            default:
                return false;
        }
    }

    set disabled(value) {
        switch(value) {
            case "disabled":
            case "true":
                this.setAttribute("disabled", "disabled");
                break;
            default:
                this.removeAttribute("disabled");
        }
    }

    
    /** @return FormData */
    get value() {
        if(this.childrenReady !== true) console.warn("This element has children that are not ready!", this);
        const elements = this.querySelectorAll(universal_input_element_query);
        let value = {};
        let errors = 0;
        for(const input of elements) {
            let name = input.name ?? input.getAttribute("name");
            let length = name.length;
            let appendToArray = false;
            if(name[length - 1] === "]" && name[length - 2] === "[") {
                appendToArray = true;
                name = name.substring(0, length - 2);
                if(!value[name]) value[name] = [];
                if(Array.isArray(value[name]) === false) value[name] = [value[name]];
                if(input.type === "checkbox" && !input.checked) continue;
            }
            try {
                if(appendToArray) value[name].push(this.getFieldValue(input));
                else value[name] = this.getFieldValue(input)//.value;
            } catch (Error) {
                errors += 1;
            }
        }
        if(errors) throw Error("Multiple errors were found. Aborting.");
        return value;
    }

    async getValue(allowErrors = false) {
        if(this.childrenReady !== true) console.warn("This element has children that are not ready!", this);
        const elements = this.querySelectorAll(universal_input_element_query);
        let value = {};
        let errors = 0;
        for(const input of elements) {
            let name = input.name ?? input.getAttribute("name");
            let length = name.length;
            let appendToArray = false;
            if(name[length - 1] === "]" && name[length - 2] === "[") {
                appendToArray = true;
                name = name.substring(0, length - 2);
                if(!value[name]) value[name] = [];
                if(Array.isArray(value[name]) === false) value[name] = [value[name]];
                if(input.type === "checkbox" && !input.checked) continue;
            }
            try {
                if(appendToArray) value[name].push(await this.getFieldValue(input));
                else value[name] = await this.getFieldValue(input)//.value;
            } catch (Error) {
                errors += 1;
            }
        }
        if(errors) throw new Error("Multiple errors found. Aborting.");
        return value;
    }

    attributeChangedCallback(attribute, old, newValue) {
        console.log({attribute, old, newValue})
    }

    async unsavedChanges() {
        return false;
        // if(["true","confirm-unsaved",null].includes(this.getAttribute("confirm-unsaved")) == false) return false;
        // if(this.childrenReady === false) return false;
        // const currentValue = await this.getValue();
        // for(const i in currentValue) {
        //     if(i in this.originalState === false) return true;
        //     if(this.originalState[i] !== currentValue[i]) return true;
        // }s
        // return false;
    }

    connectedCallback() {
        this.initSubmissionListeners();
        // this.initSubmitButton();
        let defaultValue = "field";
        if(this.getMethods.includes(this.method)) defaultValue = "none";
        if(!this.submitButton && !this.validAutoSaveValues.includes(this.autoSave)) this.autoSave = defaultValue; // Default forms without a save button to autosave
        this.addEventListener("submission", async event => {
            const data = await this.buildSubmission(event);
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
        // this.originalState = await this.getValue();
        this.childrenReady = true;
    }

    disconnectedCallback() {
        this.removeFeedback()
    }

    /** ==============================================
     *  =============== INITIALIZATION ===============
     *  ============================================== */

    initSubmissionListeners(queryTarget = this) {
        this.initEnterSaveListener(queryTarget);
        this.initAutoSaveListeners(queryTarget);
        this.initSubmitButton(queryTarget);
    }

    initSubmitButton(queryTarget) {
        this.submitButton = queryTarget.querySelectorAll("button[type='submit'],input[type='submit'],split-button option[type='submit'],split-button[type='submit']");
        if(this.submitButton.length) {
            this.submitButton.forEach(e => 
                e.addEventListener("click", e => {
                    this.dispatchEvent(new CustomEvent("submission", {
                        detail: {
                            target:e.currentTarget || e.target
                        }
                    }))
                })
            );
        }
        this.backButton = queryTarget.querySelectorAll("button[type='back'],input[type='back'],split-button option[type='back'],split-button[type='back']");
        if(this.backButton.length) {
            this.backButton.forEach(e => {
                if(!e.name && !e.value) {
                    e.disabled = true;
                    e.setAttribute("ariaDisabled", "true");
                }
                e.addEventListener("click", evt => {
                    this.classList.add(this.PROGRESS_BACK_CLASS);
                    let submission = {};
                    submission[e.name] = e.value
                    this.submit(submission);
                })
            });
        }
    }

    initAutoSaveListeners(queryTarget) {
        function autoSaveListener(event) {
            if(!this.autoSave) return;
            let element = event.target || event.srcElement;
            if(!element) return;
            if(!element.name && element.getAttribute("name") === null) {
                // If the element doesn't have a name specified, check if it has
                // an ancestor that does have one specified.
                element = element.closest("[name]");
                if(!element) return;
            }
            if(["true", "ignore"].includes(element.getAttribute("autosave-ignore"))) return;

            this.dispatchEvent(new CustomEvent("submission", {...event, detail: {target: element}}));
        }

        this.removeEventListener("change", autoSaveListener.bind(this));
        this.addEventListener("change", autoSaveListener.bind(this));

        // const elements = queryTarget.querySelectorAll(universal_input_element_query);
        // for(const el of elements) {
        //     // if(["file", "files"].includes(el.type)) this.fileUploadFields.push(el);
        //     if(el.tagName === "IMAGE-RESULT") this.fileUploadFields.push(el);
        //     el.removeEventListener("change", autoSaveListener.bind(this));
        //     el.addEventListener("change", autoSaveListener.bind(this));
        // }
    }

    initEnterSaveListener() {
        if(this.autoSave !== "enter") return;
        document.removeEventListener("keypress", this.enterButtonFunction);
        document.addEventListener("keypress", this.enterButtonFunction);
    }

    /** ==============================================
     *  ============= FIELD MANIPULATION =============
     *  ============================================== */
    clearAll() {
        const elements = this.querySelectorAll(universal_input_element_query)
        for(const input of elements) {
            switch(input.tagName) {
                case "SELECT":
                    break;
                case "INPUT-AUTOCOMPLETE":
                    input.dispatchEvent(new CustomEvent("clear"));
                    break;
                case "TEXTAREA":
                case "INPUT":
                default:
                    switch(input.type) { 
                        case "RADIO":
                        case "CHECKBOX":
                            input.checked = false
                            break;
                        default:
                            input.value = ""
                    }
                    break;
            }
        }
    }

    async getFieldValue(field) {
        if(this.isValid(field) === false) {
            appendElementInformation(field, field.validationMessage ?? "Validation failed");
            throw new Error("Validity failed");
        }
        if(field.tagName === "INPUT") {
            switch(field.type) {
                case "number":
                    return Number(field.value);
                case "files":
                    return field.files;
                default:
                    return field.value;
            }
        } else if (field.tagName === "BLOCK-EDITOR") {
            return await field.value;
        }
        // if(val instanceof FileList) {
        //     this.fileUploadFields.push(field);
        // }
        return field.value;
    }



    /** ==============================================
     *  =============== BUILD & SUBMIT ===============
     *  ============================================== */

    async submit(data = null, event = {}) {
        if(data == null) {
            // console.warn("`data` must not be null. Aborting.")
            // return
            data = await this.buildSubmission(event);
        }
        console.log(data)
        const method  = this.getAttribute('method');
        const action  = this.getAttribute('action');
        const enctype = this.getAttribute('enctype') ?? "application/json; charset=utf-8";
        
        if(this.getMethods.includes(method.toLocaleUpperCase())) {
            return this.submitGetRequest(data, event);
        }

        const api = new AsyncFetch(action, method, {format: enctype, form: this, headers: {'X-Keyboard-Modifiers': encodeClickModifiers(event), ...this.getHeadersFromAttribute()}});
        api.addEventListener('submit', e => this.handleAsyncSubmitEvent(e, event));
        api.addEventListener('error',  e => this.handleAsyncErrorEvent(e, event));
        api.addEventListener('done',   e => this.handleAsyncDoneEvent(e, event));
        api.addEventListener('abort',   e => this.handleAsyncDoneEvent(e, event));
        api.addEventListener('asyncfinished', e => this.removeFeedback());

        let requiresFormData = false;
        for(const field in data) {
            if(data[field] instanceof FileList === false) continue;
            requiresFormData = true;
            break;
        }
        if(requiresFormData) {
            data = this.encodeFormData(data);
        }
        this.abort = api.abort;
        let result = {};

        try{
            result = await api.submit(data);
            this.originalState = await this.getValue();
        } catch(error) {
            this.handleAsyncErrorEvent(error, event);
        }
        this.abort = () => {};
    }

    getHeadersFromAttribute() {
        if(this.hasAttribute('headers') === false) return {};
        let headers = {};
        for(const header of this.getAttribute("headers").split(";")) {
            const split = header.split(":");
            headers[split[0].trim()] = split[1].trim();
        }
        return headers;
    }

    submitGetRequest(data, event) {
        let encodedPairs = [];
        for(const key in data) {
            switch(typeof data[key]) {
                case "object":
                    if(Array.isArray(data[key])) {
                        data[key].forEach(el => {
                            encodedPairs.push(
                                `${encodeURIComponent(key)}[]=${encodeURIComponent(el)}`
                            );
                        })
                        break;
                    }
                    for(const d in data[key]) {
                        encodedPairs.push(
                            `${encodeURIComponent(key)}[${d}]=${encodeURIComponent(data[key][d])}`
                        );
                    }
                default:
                    encodedPairs.push(
                        `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`
                    );
            }
        }
        const fullUrl = `${this.getAttribute("action")??""}?${encodedPairs.join("&")}`;
        const method = this.getAttribute("method");
        switch(method.toLocaleUpperCase()) {
            case "NAVIGATE":
                return location = fullUrl;
            case "GET":
            default:
                return Cobalt.router.location = fullUrl;
        }
    }

    async buildSubmission(event) {
        this.fieldsRequiringFeedback = [];
        let target = event.detail?.element || event.detail?.target || event.currentTarget || event.target || event.srcElement;
        if(target === null) return await this.getValue();
        if(target === this.submitButton) return await this.getValue();
        let submit = {};
        switch(this.autoSave) {
            case "none":
            case "false":
                return;
            case "enter":
                return this.getValue();
            case "element":
            case "field":
            case "autosave":
                submit[target.name || target.getAttribute("name")] = await this.getFieldValue(target);//.value;
                this.addElementToFeedbackList(target);
                break;
            case "fieldset":
                const fieldset = target.closest("fieldset");
                for(const el of fieldset.querySelectorAll(universal_input_element_query)) {
                    submit[el.name || target.getAttribute("name")] = await this.getFieldValue(el);//.value;
                }
                this.addElementToFeedbackList(fieldset);
                break;
            case "form":
            default:
                const val = await this.getValue();
                submit = val;
                this.addElementToFeedbackList(this);
                break;
        }
        // if(target) {
        //     const name = target.getAttribute('name');
        //     const value = target.value;
        //     if(name && value) submit[name] = value;
        // }
        
        return submit;
    }

    addElementToFeedbackList(element) {
        if(element instanceof HTMLElement === false) throw new Error(`element is not an HTMLElement`, element);
        this.fieldsRequiringFeedback.push(element)
    }

    encodeFormData(data) {
        if(typeof data !== "object") return data;
        this.totalUploadSize = 0;

        const mutable = structuredClone(data);
        const form = new FormData();
        for( const field in data ) {
            if( data[field] instanceof FileList !== true ) continue;
            
            // Let's filter out form fields
            mutable[field] = [];
            for( const file of data[field] ){
                let fieldName = field;
                form.append(`${fieldName}[]`, file);
                this.totalUploadSize += parseFloat(file.size);
            }
        }

        form.append("json_payload", JSON.stringify(mutable));

        return form;
    }

    /** ==============================================
     *  ============ VALIDATION & FEEDBACK ===========
     *  ============================================== */

    handleAsyncSubmitEvent(e, submission = {}) {
        if(this.feedback) {
            this.applyFeedback(e);
        }
        this.dispatchEvent(new Event("submit", {...e, submitter: submission.target || null}));
    }

    handleAsyncErrorEvent(e, submission = {}) {
        this.removeFeedback(e);
        this.dispatchEvent(new Event("error", {detail: e.detail}));
    }

    handleAsyncDoneEvent(e, submission = {}) {
        this.removeFeedback(e);
        this.dispatchEvent(new CustomEvent("done", {detail: e.detail}));
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
                case "BLOCK-EDITOR":
                default:
                    this.createFeedback(e, 'top-right');
            }
        });
    }

    async createFeedback(target, type, padding = 5, disable = true) {
        const validTypes = ['top-right', 'center'];
        if(!validTypes.includes(type)) type = validTypes[0];
        
        // if(target.offsetParent === null) return this.feedbackForTabNav(target);

        target.setAttribute("disabled", "disabled");
        target.ariaDisabled = true;
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
        document.removeEventListener("keypress", this.enterButtonFunction);
    }

    isValid(field) {
        if("checkValidity" in field === false) return true;
        return field.checkValidity();
    }

    /** This function will append the next step in the form fields,
     * send an update('@form', ['next' => '<input name="somedetail">'])
     * from the action controller
     * Use <button type="submit" name="someName" value="2">Submit</button>
     * Use <button type="back" name="someName" value="1">Back</button>
     * @param {string} html 
     * @param {boolean} allowFormRegression
     */
    async next(html, callback = () => {}) {
        const FRAME_COMMON = "form-request--frame";
        const CURRENT_FRAME = "form-request--current-frame";
        const PREVIOUS_FRAME = "form-request--previous-frame";
        const NEXT_FRAME = "form-request--next-frame";
        let frame1 = this.querySelector(CURRENT_FRAME);
        // If we don't have a current frame, let's collect all the form children
        // and move them to a frame
        if(!frame1) {
            frame1 = document.createElement("div");
            for(const element of Array.from(this.children)) {
                frame1.appendChild(element)
            }
            this.appendChild(frame1);
            this.style.setProperty("--height", `${Math.ceil(frame1.getBoundingClientRect().height)}px`);
        }
        // Let's handle the classes for our current (now previous) frame
        frame1.classList.add(FRAME_COMMON);

        // frame2 is the next item
        const frame2 = document.createElement("div");
        frame2.style.position = "relative";
        frame2.classList.add(FRAME_COMMON,NEXT_FRAME);
        frame2.innerHTML = html;
        // document.body.appendChild(frame2);
        
        
        this.appendChild(frame2);
        let height = Math.ceil(frame2.getBoundingClientRect().height);
        // for(const e of frame2.children) {
        //     height += Math.ceil(e.getBoundingClientRect().height)
        // }

        console.log(height);
        frame2.style.position = "";
        this.initSubmissionListeners(frame2);

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
            // this.style.setProperty("height", Math.ceil(this.style.getProperty("--to-height")));
            this.style.setProperty("--height", `${height}px`);
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

customElements.define("form-request", NewFormRequest);

// class LoginFormRequest extends NewFormRequest {
//     async submit() {
//         if (this.asJSON === false) return;
//         let error_container = this.form.querySelector(".error");
//         error_container.innerText = "";

//         let data = this.build_query();
//         let headers = {};
//         const encoded = btoa(`${data.username}:${data.password}`);
//         headers = { ...this.headers, "Authentication": encoded }
//         data.Authentication = encoded;
        
//         delete data.username;
//         delete data.password;
//         const post = new ApiFetch(this.action, this.method, { headers: headers });
//         try {
//             var result = await post.send(data, {});
//         } catch (error) {
//             // error_container.innerText = error.result.error
//             this.errorHandler(error);
//             return;
//         }

//         this.onsuccess = new CustomEvent("requestSuccess", { detail: result });
//         this.form.dispatchEvent(this.onsuccess);
//         // if (result.login === "successful") window.location.reload();
//         // console.log(result)
//     }
// }