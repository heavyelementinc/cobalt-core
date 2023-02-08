/** 
 * @event requestSuccess Will return results in e.detail
 * @event requestFail Will return error messages in e.detail
 * 
 * @param
 */
class FormRequest {
    constructor(form, { asJSON = true, errorField = null }) {
        this.onsuccess = new Event("requestSuccess");
        this.onfail = new Event("requestFailure");
        this.asJSON = asJSON;
        if (typeof form === "string") this.form = document.querySelector(form);
        else this.form = form;
        this.form.addEventListener("submit", e => { this.submit(e) });
        this.action = this.form.getAttribute("action");
        this.method = this.form.getAttribute("method");
        this.format = this.form.getAttribute("format") || "application/json; charset=utf-8";
        this.token = this.form.getAttribute("token") || "";
        this.update = this.form.getAttribute("update-on-success") || "true";
        this.revert = this.form.getAttribute("revert-on-failure") || "true";
        this.headers = {
            'X-Mitigation': this.token
        };
        this.autosave = (["true", "autosave", "form"].includes(this.form.getAttribute("autosave"))) ? true : false;
        this.include = this.form.getAttribute("include") ?? null;
        this.form_elements();
        this.errorField = errorField;
        this.files();
    }

    /** Add all the form elements to this instance's list of elements */
    form_elements() {
        // this.el_list = get_form_elements(this.form);
        // return this.el_list;
        this.elements = this.form.querySelectorAll(window.universal_input_element_query);
        this.el_list = [];
        for (let el of this.elements) {
            this.add(el);
        }
        return this.el_list;
    }

    /** Add an individual item to this list */
    add(el) {
        const name = el.getAttribute("name");
        // if (!name) return false;
        // let type = el.getAttribute("type") || "default";
        // switch (el.tagName) {
        //     case "TEXTAREA":
        //         type = 'textarea';
        //         break;
        //     case "SELECT":
        //         type = 'select';
        //         break;
        //     case "INPUT-SWITCH":
        //         type = "switch";
        //         break;
        //     case "INPUT-ARRAY":
        //         type = "array";
        //         break;
        // }
        // if (type in classMap === false) type = "default";
        // const className = "InputClass_" + type;
        // new classMap[type](el, { form: this.form });
        this.el_list[name] = get_form_input(el, this.form);
        if (this.autosave) el.addEventListener("change", event => this.autosave_handler(this.el_list[name], event));
        return true;
    }

    /** Submit the entire form data */
    async submit(e) {
        this.reset_errors();
        if (this.asJSON === false) return;
        // if
        e.preventDefault();
        // let formdata = new FormData(this.form);
        // let data = Object.fromEntries(formdata);

        let data = this.build_query();
        this.files();

        let result = await this.send(data);
        console.log("submit", result);
        return result;
    }

    files() {
        this.hasFiles = this.form.querySelectorAll("[type='file'],[type='files']");
    }

    async send(data) {
        this.reset_errors();
        this.files();
        let post;
        if (this.hasFiles.length === 0) post = new ApiFetch(this.action, this.method, { headers: this.headers });
        else {
            post = new ApiFile(this.action, this.method, { headers: this.headers, progressBar: this.progressBar });
        }
        let result;
        try {
            result = await post.send(data, {});
            this.lastResult = result;
        } catch (error) {
            this.errorHandler(post, result, post);
            // throw new Error(error);
        }

        if (this.update) this.update_fields(result);
        if (this.revert) this.revert_fields();

        this.onsuccess = new CustomEvent("requestSuccess", { detail: result });
        this.form.dispatchEvent(this.onsuccess);

        this.handleNextRequest(post.headers["X-Next-Request"]);
        return result;
    }

    /** Autosave handler */
    async autosave_handler(element, event) {
        const el = element.element;
        el.classList.add("form-request--autosave-feedback");
        
        const autosave_spinner = this.autosave_feedback(el);

        const time = Date.now();
        
        let data = this.build_query([element]);
        if (this.form.getAttribute("autosave") == "form") data = this.build_query();
        try {
            const result = await this.send(data);
        } catch (error) {
            return this.autosave_reenable_field(el, autosave_spinner);
        }

        if(Date.now() - time > 1000) {
            this.autosave_reenable_field(el, autosave_spinner);
        } else {
            setTimeout(() => {
                this.autosave_reenable_field(el, autosave_spinner);
            }, 500);
        }
        
    }

    autosave_reenable_field(el, spinner) {
        el.classList.remove("form-request--autosave-feedback");
        spinner.parentNode.removeChild(spinner);
    }

    autosave_feedback(el) {
        const offset = get_offset(el);
        const working = document.createElement("loading-spinner");
        working.style.position = "absolute";
        working.style.justifyContent = "unset";
        working.style.alignItems = "unset";
        
        const width = Math.min(offset.h - 4, 30);
        working.style.top = `${offset.y + 2}px`;
        working.style.left = `${offset.right - 2 - width}px`;
        working.setAttribute("height", `${width}px`);
        working.setAttribute("width",  `${width}px`);

        document.body.appendChild(working);
        return working;
    }

    /** Build the list of items */
    build_query(list = null) {
        if (list === null) list = this.el_list;
        let query = {};
        for (var i in list) {
            // if(!list[i].validity_check()) 
            query[list[i].element.getAttribute("name")] = list[i].value;
        }
        if (this.autosave && this.include) query.include = this.include;
        return query;
    }

    errorHandler(error, result = null, post = null) {
        if (post && post.result.code === 422) this.handleFieldIssues(post.result);
        // let field = this.errorField;
        // if (!field) field = this.form.querySelector(".error")
        // if (field) field.innerText = error.result.error
        if (!this.statusMessage) {
            // this.statusMessage = new StatusError({
            //     id: this.form.getAttribute("action"),
            //     message: error.result.error,
            // })
        } else {
            this.statusMessage.update(error.result.error);
        }
        this.form.dispatchEvent(this.onfail);
    }

    reset_errors() {
        if (this.errorField) this.errorField.innerText = "";
        for (const i in this.el_list) {
            this.el_list[i].dismiss_error();
        }
    }

    update_fields(data) {
        for (const i in data) {
            if (i in this.el_list) {
                this.el_list[i].value = data[i];
            } else if (i[0] === "#" || i[0] === "." || i[0] === "[" && i[i.length - 1] === "]") { // Check if we want to query for an element in the form
                let elements = document.querySelectorAll(i);
                if(!elements) return console.warn("Query yielded no results.");

                for (let l of elements) {
                    switch(l.tagName) {
                        case "IMG":
                            l.src = data[i];
                            break;
                        case l.className.includes("update-bg"):
                        case l.className.includes("bg-splash"):
                        case l.hasAttribute("bg-splash"):
                            l.style.backgroundImage = data[i];
                            break;
                        case "value" in l:
                            l.value = data[i];
                            break;
                        default:
                            l.outerHTML = data[i]; // Replace element
                            break;
                    }
                }
            }
        }
    }

    /** @todo finish revert on failure */
    revert_fields() {

    }

    handleFieldIssues(data) {
        for (const i in data.data) {
            if (i in this.el_list === false) continue;
            this.el_list[i].set_error(data.data[i]);
        }
    }

    handleNextRequest(fields) {
        var supported = [
            "action",
            "method",
            "autosave",
        ];
        for (let i of supported) {
            if (fields && i in fields) {
                this["supported_next_" + i](fields[i]);
            }
        }
        console.log(fields, this)
    }

    supported_next_method(val) {
        this.method = val.toUpperCase();
        this.form.setAttribute('method', val);
    }

    supported_next_action(val) {
        this.action = val;
        this.form.setAttribute('action', val);
    }

    supported_next_autosave(val) {
        console.warn("Server-dictated auto-save is not implemented yet!");
        // this.autosave = val;
        // if (this.autosave) el.addEventListener("change", event => this.autosave_handler(this.el_list[name], event));
        // if (!this.form.querySelector(["type='submit'"])) {
        //     this.form.appendChild()
        // }
    }
}

class LoginFormRequest extends FormRequest {
    async send() {
        if (this.asJSON === false) return;
        let error_container = this.form.querySelector(".error");
        error_container.innerText = "";

        let data = this.build_query();
        let headers = {};
        const encoded = btoa(`${data.username}:${data.password}`);
        headers = { ...this.headers, "Authentication": encoded }
        data.Authentication = encoded;
        
        delete data.username;
        delete data.password;
        const post = new ApiFetch(this.action, this.method, { headers: headers });
        try {
            var result = await post.send(data, {});
        } catch (error) {
            // error_container.innerText = error.result.error
            this.errorHandler(error);
            return;
        }

        this.onsuccess = new CustomEvent("requestSuccess", { detail: result });
        this.form.dispatchEvent(this.onsuccess);
        // if (result.login === "successful") window.location.reload();
        // console.log(result)
    }
}
