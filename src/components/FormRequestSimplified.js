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
    }

    connectedCallback() {
        this.getRequest();
        if (this.request.autosave === false) {
            // let searchForButtons = this;
            let queries = "button[type='submit'],input[type='submit']";
            // if (this.getAttribute("submit")) {
            //     searchForButtons = this.closest("modal-container");
            //     queries = "button.modal-button-okay";
            // }
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
            // let button = this.stages[0].querySelector("button[type='submit']");
            // this.stages[0].appendChild(error);
        }
        this.error = error;
        this.request.errorField = error;
        this.additionalContent = null;
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
    
            let result = {};
            try {
                result = await this.send_and_subscribe();
                resolve(result);
            } catch (error) {
                this.working_spinner_off();
                reject(result);
                throw new Error("Bad news!");
            }
            this.working_spinner_off();
        });
    }

    async send_and_subscribe() {
        return new Promise(async (resolve, reject) => {
            var request = "";
            try {
                request = await this.request.send(this.request.build_query());
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
                    console.log(e);
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
        console.log("Transitioning");
        return new Promise((resolve,reject) => {
            this.additionalContent.addEventListener("transitionend", () => {
                console.log("Transition Ended")
                resolve();
                setTimeout(() => resolve(),1000);
            },{once: true})
            this.additionalContent.classList.add("form-request--displayed");
        })
    }

    async working_spinner_off() {
        return new Promise((resolve,reject) => {
            this.additionalContent.addEventListener("transitionend", () => {
                console.log("Transition Ended 2")
                resolve();
                setTimeout(() => resolve(),1000);
            },{once: true})
            this.additionalContent.classList.remove("form-request--displayed");
        })
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