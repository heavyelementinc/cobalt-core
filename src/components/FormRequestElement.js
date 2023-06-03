/**
 * form-request supports the following attributes
 * 
 *  * method - functions identically to a <form method=""> attribute
 *  * action - functions identically to a <form action=""> attribute
 *  * display-mode - 
 *     - "edit" - (default) will allow you to continue editing the form after saving
 *     - "done" - will disable the form after saving and present a "complete" screen
 *  * success-route - a web route to be displayed when display-mode="done"
 *  * success-message - a message to be displayed
 */

 class FormRequestElement extends HTMLElement {
    constructor() {
        super();
        this.excludedClass = "form-request--excluded-element";
    }

    connectedCallback() {
        this.getRequest();
        if (this.request.autosave === false) {

            let queries = "button[type='submit'],input[type='submit']";

            let elements = this.querySelector(queries);
            if (elements) {
                elements.addEventListener('click', (e) => {
                    this.send(e.shiftKey);
                });
            }
        }
        let error = this.querySelector(".error");
        if (!error) {
            error = document.createElement("div");
            error.classList.add("error");
        }
        this.error = error;
        this.request.errorField = error;
        this.additionalContent = null;
        

        if(this.getAttribute("success-message")) this.successMessage();
        if(this.getAttribute("success-route")) this.successAction();

        this.initExcludeFields();

        // this.fieldExclusion();
    }

    disconnectedCallback() {
        if (this.request.statusMessage) this.request.statusMessage.close();
    }

    getRequest() {
        // Basically, we want to attach the FormRequest to the <form-request> element.
        // This WebComponent should allow us to make form bots a thing of the past.
        this.request = new FormRequest(this, { asJSON: true, errorField: this.querySelector(".error") });
    }

    async send(allowDangerous = false) {
        return new Promise( async (resolve, reject) => {
            if (this.request.statusMessage) this.request.statusMessage.close();
            
            this.setup_feedback_container();

            if(this.request.progressBar) this.request.progressBar.message = `Working`;

            await this.working_spinner_on();
            this.request.reset_errors();
            if (allowDangerous) this.request.headers['X-Confirm-Dangerous'] = "true";
            else delete this.request.headers['X-Confirm-Dangerous'];
            const evt = this.dispatchEvent(new CustomEvent("submit", {detail: this.request, cancelable: true}));
            if(evt === false) {
                this.working_spinner_off();
                this.dispatchEvent(new CustomEvent("abort"), {detail: this.request});
                return;
            }
            let result = {};
            try {
                result = await this.send_and_subscribe();
                resolve(result);
                this.dispatchEvent(new CustomEvent("formRequestSuccess", {detail: result}));
                this.dispatchEvent(new CustomEvent("success", {detail: result}));
            } catch (error) {
                this.working_spinner_off();
                reject(result);
                this.dispatchEvent(new CustomEvent("formRequestFail", {detail: result}));
                this.dispatchEvent(new CustomEvent("failure", {detail: result}));
            }
            this.working_spinner_off();
        });
    }

    async send_and_subscribe() {
        return new Promise(async (resolve, reject) => {
            var request = "";
            let data = this.request.build_query();
            this.dispatchEvent(new CustomEvent("formRequestSubmit", {detail: data}));
            // Do not dispatch a submit event because apparently we're listening for a submit event and it duplicates the send.
            // this.dispatchEvent(new CustomEvent("submit", {detail: data}));

            try {
                request = await this.request.send(data);
            } catch (e) {
                reject(e);
            }

            if (this.hasAttribute("watch")) {
                let subscription = new EventSource(this.getAttribute("watch"), { withCredentials: true });
                subscription.addEventListener("completed", e => {
                    subscription.close();
                    this.request.progressBar.percent = 100;
                    setTimeout(e => {
                        resolve(request);
                    }, 1000)
                });
                subscription.addEventListener("update", async e => {
                    if (!e.data) return;
                    let value = await JSON.parse(e.data.trim());
                    this.request.progressBar.percent = value.percent;
                    if (value.message) this.request.progressBar.message = value.message;
                });
                subscription.addEventListener("error", e => {
                    new StatusError("There was an error");
                    subscription.close();
                    reject(request);
                });
            } else {
                resolve(request);
            }
        });
    }

    async submit(allowDangerous = false) {
        return await this.send(allowDangerous);
    }

    setup_feedback_container() {
        if(!this.additionalContent) {
            this.additionalContent = document.createElement("div");
            this.appendChild(this.additionalContent);
            this.additionalContent.classList.add("form-request--working-spinner");
        }
        if (this.request.hasFiles.length !== 0 && !this.request.progressBar || this.hasAttribute('watch') && !this.request.progressBar) {
            const ref = document.createElement("progress-bar");
            this.additionalContent.innerHTML = "";
            this.additionalContent.appendChild(ref);
            this.request.progressBar = ref;
        } else {
            this.additionalContent.innerHTML = "<loading-spinner></loading-spinner>";
        }
    }

    async working_spinner_on() {
        return new Promise((resolve,reject) => {
            setTimeout(() => {
                this.additionalContent.dispatchEvent(new Event("transitionend"));
            },1500);
            this.additionalContent.addEventListener("transitionend", () => {
                resolve();
                setTimeout(() => resolve(),1000);
            },{once: true})
            this.additionalContent.classList.add("form-request--displayed");
        })
    }

    async working_spinner_off() {
        return new Promise((resolve,reject) => {
            this.additionalContent.addEventListener("transitionend", () => {
                resolve();
                setTimeout(() => resolve(),1000);
            },{once: true})
            this.additionalContent.classList.remove("form-request--displayed");
        })
    }

    successMessage() {
        this.addEventListener("formRequestSuccess", (e) => {
            let message = this.getAttribute("success-message");
            let matches = message.match(new RegExp(/\$(\w+)/g));
            matches.forEach((n) => {
                const varName = n.substring(1);
                if (typeof e.detail === "object") {
                    if(varName in e.detail) message.replace(n, e.detail[varName]);
                } else {
                    let form = this.querySelector(`[name='${varName}']`);
                    if(form.value) message = message.replace(n, escapeHtml(form.value));
                }
            });
            this.displayItem(`<div>${message}</div>`).classList.add("success");
        });
    }

    successAction() {
        return null;
    }

    displayItem(messageContent) {
        const child = document.createElement("form-next-item");
        child.innerHTML = messageContent;
        this.appendChild(child);
        setTimeout(() => {
            child.classList.add("displayed");
        }, 100);
        return child;
    }

    get value() {
        return this.request.build_query();
    }

    // fieldExclusion() {
    //     const exclude = this.querySelectorAll('[data-exclude]');
    //     exclude.forEach(e => {
    //         const origin = e;
    //         switch(e.tagName) {
    //             case "OPTION":
    //             case "OPTGROUP":
    //                 e = e.closest("select, input-array, tag-select");
    //                 break;
    //         }
    //         e.addEventListener('change', event => {
    //             const currentlyExcluded = this.querySelectorAll(this.excludedClass);
    //             for(const ex of currentlyExcluded) {
    //                 ex.classList.remove(this.excludedClass);
    //             }
    //             const query = origin.dataset.exclude;
    //             this.querySelector(query).forEach(this.excludeElement.bind(this))
    //         });
    //     })
    // }


    initExcludeFields() {
        // const exclude = this.querySelectorAll("[data-exclude]");
        // if(!exclude) return;
        // for(let el of exclude) {
        //     const query = el.getAttribute("data-exclude");
        //     if(!query) continue;
        //     this.handleFieldExclusions(el, query);
        // }
    }

    handleFieldExclusions(element, query){
        let listenerElement = element;
        let value = listenerElement.value
        if(element.tagName === "OPTION") {
            listenerElement = element.closest("select, input-autocomplete, input-array, input-multiselect");
        }
        const namedExclusion = `${this.excludedClass}--${listenerElement.getAttribute("name")}`;
        
        const eventHandler = event => {
            if(listenerElement !== element) {
                if(listenerElement.value !== value) return;
            }
            // Reset all fields that have been excluded because of this field
            const previouslyHidden = this.querySelectorAll(namedExclusion);
            for(const excluded of previouslyHidden) {
                this.includeElement(excluded, namedExclusion);
            }

            const toHide = this.querySelectorAll(query);
            // Exclude the fields for this element
            for(const excluded of toHide) {
                this.excludeElement(excluded, namedExclusion);
            }
        }

        listenerElement.addEventListener("change", eventHandler);
        eventHandler();
    }

    excludeElement(element, namedExclusion) {
        element.classList.add(this.excludedClass, namedExclusion);
        const label = this.getElementLabelsAndContainers(element);
        if(label) label.add(this.excludedClass, namedExclusion);
    }
    
    includeElement(element, namedExclusion) {
        element.classList.remove(this.excludedClass, namedExclusion);
        const label = this.getElementLabelsAndContainers(element);
        if(label) label.remove(this.excludedClass, namedExclusion);
    }

    getElementLabelsAndContainers(excludedElement) {
        let elligibleParent = excludedElement.closest("label, switch-container");
        if(elligibleParent) return elligibleParent.classList.add(this.excludedClass);

        let elligibleLabel = excludedElement.previousElementSibling;
        if(!elligibleLabel) elligibleLabel = this.querySelector(`[for='${excludedElement.name}],[for='#${excludedElement.id || excludedElement.name}]`);
        
        if(elligibleLabel) return elligibleLabel;
    }

    // async confirm_stage() {
    //     let confirm = this.getAttribute("success-route");
    //     let page = this.getAttribute("success-message") || "<p>Your form was submitted successfully.</p>";
    //     if (confirm) page = await new ApiFetch(`/api/v1/page?route=${encodeURI()}`, "GET", {});
    //     this.stages[2].innerHTML = page;
    // }

    // advance() {
    //     return new Promise((resolve, reject) => {
    //         this.stages[this.pointer].addEventListener("transitionend", () => {
    //             resolve();
    //             clearTimeout(failsafe);
    //         }, { once: true })
    //         this.stages[this.pointer].classList.add("previous");
    //         this.stages[this.pointer].classList.remove("current");
    //         this.pointer++;
    //         this.stages[this.pointer].classList.add("current");
    //         this.stages[this.pointer].classList.remove("previous");
    //         let failsafe = setTimeout(() => {
    //             resolve();
    //         }, 600)
    //     })
    // }

    // regress() {
    //     return new Promise((resolve, reject) => {
    //         this.stages[this.pointer].addEventListener("transitionend", () => {
    //             resolve();
    //             clearTimeout(failsafe);
    //         }, { once: true })
    //         this.stages[this.pointer].classList.add("next");
    //         this.stages[this.pointer].classList.remove("current");
    //         this.pointer--;
    //         this.stages[this.pointer].classList.add("current");
    //         this.stages[this.pointer].classList.remove("previous");
    //         let failsafe = setTimeout(() => {
    //             resolve();
    //         }, 600)
    //     })
    // }
}

customElements.define("form-request", FormRequestElement);
