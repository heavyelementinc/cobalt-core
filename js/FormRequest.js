/** 
 * @event requestSuccess Will return results in e.detail
 * @event requestFail Will return error messages in e.detail
 * 
 * @param
 */
class FormRequest {
    constructor(form, { asJSON = true, errorField = null }) {
        this.onsuccess = new Event("requestSuccess");
        this.onfail = new Event("requestFail");
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
        this.autosave = (["true", "autosave"].includes(this.form.getAttribute("autosave"))) ? true : false;
        this.include = this.form.getAttribute("include") ?? null;
        this.form_elements();
        this.errorField = errorField;
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

        let result = await this.send(data);

    }

    async send(data) {
        this.reset_errors();
        const post = new ApiFetch(this.action, this.method, { headers: this.headers });
        let result;
        try {
            result = await post.send(data, {});
        } catch (error) {
            this.errorHandler(error, result, post);
            throw new Error(error);
        }

        if (this.update) this.update_fields(result);
        if (this.revert) this.revert_fields();

        this.onsuccess = new CustomEvent("requestSuccess", { detail: result });
        this.form.dispatchEvent(this.onsuccess);
    }

    /** Autosave handler */
    autosave_handler(element, event) {
        let data = this.build_query([element]);
        this.send(data);
    }

    /** Build the list of items */
    build_query(list = null) {
        if (list === null) list = this.el_list;
        let query = {};
        for (var i in list) {
            query[list[i].element.getAttribute("name")] = list[i].value();
        }
        if (this.autosave && this.include) query.include = this.include;
        return query;
    }

    errorHandler(error, result = null, post = null) {
        if (error.result.code === 422) this.handleFieldIssues(error.result);
        let field = this.errorField;
        if (!field) field = this.form.querySelector(".error")
        if (field) field.innerText = error.result.error
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
                this.el_list[i].value(data[i]);
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
}

class LoginFormRequest extends FormRequest {
    async send() {
        if (this.asJSON === false) return;
        let error_container = this.form.querySelector(".error");
        error_container.innerText = "";

        let data = this.build_query();
        let headers = { ...this.headers, "Authentication": btoa(`${data.username}:${data.password}`) }
        delete data.username;
        delete data.password;
        const post = new ApiFetch(this.action, this.method, { headers: headers });
        try {
            var result = await post.send(data, {});
        } catch (error) {
            error_container.innerText = error.result.error
            return;
        }
        if (result.login === "successful") window.location.reload();
    }
}