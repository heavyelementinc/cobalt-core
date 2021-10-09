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
 *  * progress  - [simple]|paginated
 */

class FormRequestElement extends HTMLElement {
    constructor() {
        super();
        let progress = this.getAttribute("progress");
        this.formType = this.getProgressType(this.getAttribute("progress"));
    }

    getProgressType(type) {
        switch (type) {
            case ["paginated", "true"].includes(type):
                return "paginated";
            case ["simple", "false", null, false].includes(type):
            default:
                return "simple";
        }
    }

    connectedCallback() {
        this[`setup_content_${this.formType}`]();
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
            let button;
            if (this.stages) {
                this.stages[0].appendChild(error);
                button = this.stages[0].querySelector("button[type='submit']");
            } else {
                this.appendChild(error);
                this.querySelector("button[type='submit']");
            }
        }
        this.error = error;
        this.request.errorField = error;
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
        if (this.request.statusMessage) this.request.statusMessage.close();

        let allow_final_stage = false;
        let has_error = false;

        if (this.request.hasFiles.length !== 0 && !this.request.progressBar || this.hasAttribute('watch') && !this.request.progressBar) {
            const ref = document.createElement("progress-bar");
            this.stages[1].appendChild(ref);
            this.request.progressBar = ref;
            this.request.progressBar.message = `Working`;
        }

        await this.advance();
        this.request.reset_errors();
        if (allowDangerous) this.request.headers['X-Confirm-Dangerous'] = "true";
        else delete this.request.headers['X-Confirm-Dangerous'];

        try {
            await this.send_and_subscribe();
            allow_final_stage = true;
        } catch (error) {
            await this.regress();
            has_error = true;
            return has_error;
        }

        this.mode = this.getAttribute("display-mode") ?? "edit";
        if (this.mode === "edit" && allow_final_stage) {
            await this.regress();
            this.error.innerText = this.getAttribute("success-message") || "Success";
            allow_final_stage = false;
        }

        if (!allow_final_stage) return has_error;
        try {
            await this.confirm_stage();
            await this.advance();
        } catch (error) {
            this.stages[1].innerHTML("Your data was submitted.");
        }

        return has_error;
    }

    async send_and_subscribe() {
        return new Promise(async (resolve, reject) => {
            try {
                var request = await this.request.send(this.request.build_query());
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
        return this.send(allowDangerous);
    }

    setup_content_simple() {
        this.pointer = null;
        this.stages = [
            this,
            this
        ];
    }

    setup_content_paginated() {
        this.pointer = 0;
        this.stages = [];
        this.stages[0] = document.createElement("section");
        this.stages[0].innerHTML = this.innerHTML;
        this.stages[0].classList.add("form-request--actual");
        this.innerHTML = "";
        this.appendChild(this.stages[0]);

        this.stages[1] = document.createElement("section");
        this.stages[1].innerHTML = "<loading-spinner></loading-spinner>";
        this.stages[1].classList.add("form-request--processing", "next");
        this.appendChild(this.stages[1]);

        this.stages[2] = document.createElement("section");
        this.stages[2].classList.add("form-request--complete", "next");
        this.appendChild(this.stages[2]);
    }

    async confirm_stage() {
        let confirm = this.getAttribute("success-route");
        let page = this.getAttribute("success-message") || "<p>Your form was submitted successfully.</p>";
        if (confirm) page = await new ApiFetch(`/api/v1/page?route=${encodeURI()}`, "GET", {});
        this.stages[2].innerHTML = page;
    }

    advance() {
        if (this.formType === "paginated") return new Promise((resolve, reject) => {
            this.stages[this.pointer].addEventListener("transitionend", () => {
                resolve();
                clearTimeout(failsafe);
            }, { once: true })
            this.stages[this.pointer].classList.add("previous");
            this.stages[this.pointer].classList.remove("current");
            this.pointer++;
            this.stages[this.pointer].classList.add("current");
            this.stages[this.pointer].classList.remove("previous");
            let failsafe = setTimeout(() => {
                resolve();
            }, 600);
        })
        for (const i of this.request.el_list) {
            console.log(i)
        }
        return new Promise(resolve => resolve());
    }

    regress() {
        if (this.formType === "paginated") return new Promise((resolve, reject) => {
            this.stages[this.pointer].addEventListener("transitionend", () => {
                resolve();
                clearTimeout(failsafe);
            }, { once: true })
            this.stages[this.pointer].classList.add("next");
            this.stages[this.pointer].classList.remove("current");
            this.pointer--;
            this.stages[this.pointer].classList.add("current");
            this.stages[this.pointer].classList.remove("previous");
            let failsafe = setTimeout(() => {
                resolve();
            }, 600);
        })

        return new Promise(resolve => resolve());
    }
}

customElements.define("form-request", FormRequestElement);