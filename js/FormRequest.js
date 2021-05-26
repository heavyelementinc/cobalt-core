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
        this.headers = {
            'X-Mitigation': document.querySelector("meta[name='token']").getAttribute("content") || null
        };
        this.autosave = (["true", "autosave"].includes(this.form.getAttribute("autosave"))) ? true : false;
        this.include = this.form.getAttribute("include") ?? null;
        this.form_elements();
        this.errorField = errorField;
    }

    /** Add all the form elements to this instance's list of elements */
    form_elements() {
        this.elements = this.form.querySelectorAll("input[name], select[name], textarea[name], input-switch[name], input-array[name]");
        this.el_list = [];
        for (let el of this.elements) {
            this.add(el);
        }
        return this.el_list;
    }

    /** Add an individual item to this list */
    add(el) {
        const name = el.getAttribute("name");
        if (!name) return false;
        let type = el.getAttribute("type") || "default";
        switch (el.tagName) {
            case "TEXTAREA":
                type = 'textarea';
                break;
            case "SELECT":
                type = 'select';
                break;
            case "INPUT-SWITCH":
                type = "switch";
                break;
            case "INPUT-ARRAY":
                type = "array";
                break;
        }
        if (type in classMap === false) type = "default";
        // const className = "InputClass_" + type;
        this.el_list[name] = new classMap[type](el, { form: this.form });
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

        console.log(result);
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


        this.form.dispatchEvent(this.onsuccess);
    }

    /** Autosave handler */
    autosave_handler(element, event) {
        // console.log(element, element.value())
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
        let field = this.errorField;
        if (!field) field = this.form.querySelector(".error")
        if (field) field.innerText = error.result.error
        this.form.dispatchEvent(this.onfail);
    }

    reset_errors() {
        if (this.errorField) this.errorField.innerText = "";
    }

    update_fields(data) {

    }

}

class LoginFormRequest extends FormRequest {
    async submit(e) {
        if (this.asJSON === false) return;
        e.preventDefault();
        let error_container = this.form.querySelector(".error");
        error_container.innerText = "";
        let formdata = new FormData(this.form);
        let data = Object.fromEntries(formdata);
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