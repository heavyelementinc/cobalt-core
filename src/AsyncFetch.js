/**
 * @emits resubmission - When a confirmation dialog (or another header directive) causes the fetch request to be resubmitted
 * @emits submit  - When a request is being performed
 * @emits aborted - When the submit is prevented or .abort() is called
 * @emits error   - When a request has an error
 * @emits done    - When a request has finished
 */

class AsyncFetch extends EventTarget {
    constructor(action, method = "GET", {
        asJSON = true,
        credentials = true,
        cache = "default",
        headers = {},
        form = null,
    } = {}) {
        super();
        this.action = action;
        this.method = method;
        this.format = null;
        this.asJSON = asJSON;
        this.cache  = cache;
        this.credentials = credentials;
        this.requestHeaders = headers;
        this.abortController = null;
        this.reject = null;
        this.totalUploadSize = 0;
        this.form = form;
        
        this.data = "";
        this.response = null; // The raw response from the fetch request
        this.resolved = null; // The resolved data from the request
        this.headerDirectiveMap = {
            'location': XRedirect,
            'x-redirect': XRedirect,
            'x-status': XStatus,
            'x-modal': XModal,
            'x-refresh': XRefresh,
            'x-confirm': XConfirm,
            'x-reauthorization': XReauth,
        }
        this.headerReactions = {};
    }

    submit(data = {}) {
        this.data = data;
        return new Promise(async (resolve, reject) => {
            this.reject = reject;

            const submit = new Event("submit");
            this.dispatchEvent(submit);
            if(submit.defaultPrevented) {
                this.dispatchEvent(new CustomEvent("aborted", {detail: this}));
                return false;
            }
            
            const client = new XMLHttpRequest();

            client.onload = event => this._onload(event, client, resolve);
            client.onprogress = progress =>  this._onprogress(progress, client);
            client.onreadystatechange = statechange => this._onreadystatechange(statechange, client);
            client.onerror = error => this._onerror(error, client);
            client.onabort = abort => this._onabort(abort, client);
            client.ontimeout = (timeout) => this._ontimeout(timeout, client);

            this.client = client;

            client.open(this.method, this.action);

            for(const h in this.requestHeaders) {
                client.setRequestHeader(h, this.requestHeaders[h]);
            }

            client.setRequestHeader('X-Include', "fulfillment,update,events");
            client.setRequestHeader('X-Timezone', Intl.DateTimeFormat().resolvedOptions().timeZone);
            client.setRequestHeader('X-Request-Source', "AsyncFetch");
            const submission = this.encodeFormData(this.data)
            if(this.format) client.setRequestHeader('Content-Type', this.format);
            try{
                client.send(submission);
            } catch (error) {
                console.log(error);
            }
        });
    }

    encodeFormData(data) {
        // Assume any FormData object we've been handed is ready for submission
        if(data instanceof FormData) return data;
        
        // If we're supposed to send JSON, convert the data to JSON.
        if(this.asJSON) {
            this.format = "application/json; charset=utf-8";
            return JSON.stringify(data);
        }
        
        // if(typeof data !== "object") return JSON.stringify(data);

        // Otherwise, encode it as a FormData object
        let formData = new FormData();
        for(const d of data) {
            formData.append(d, data[d]);
        }
        return formData;
    }

    /**
     * 
     * @param {ProgressEvent} event 
     * @param {XMLHttpRequest} client 
     * @param {function} resolve 
     * @returns {void}
     * @resolves the fetch request's resolved details (if in JSON)
     * @emits done
     */
    _onload(event, client, resolve) {
        this.response = client.response;
        this.setResponseHeaders(client.getAllResponseHeaders());
        this.dispatchHeaderDirectives();
        if(client.status !== 200) {
            const nonOkResponse = this.handleNonOkResponse(event, client, resolve);
            if(nonOkResponse === true) return true;
        }
        // Parse the response data
        if(this.getHeader('Content-Type')?.match(/json/i)) this.resolved = JSON.parse(client.response);
        else {
            try {
                this.resolved = JSON.parse(client.response);
            } catch (e) {
                this.resolved = client.response;
            }
        }

        if(client.status >= 300) {
            return this._communicatedError(event);
        }

        // new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
        new AsyncUpdate(this); // Handle update instructions

        this.dispatchEvent(new CustomEvent("done", {
            detail: {
                fulfillment: this.resolved.fulfillment || this.resolved,
                resource: this
            }
        }));
        resolve(this.resolved.fulfillment || this.resolved);
        this.reject = null;
    }

    /**
     * 
     * @param {ProgressEvent} event 
     * @param {XMLHttpRequest} client 
     * @param {function} resolve 
     * @return {true} do not call resolve without also returning true from this function!
     */
    handleNonOkResponse(event, client, resolve) {
        switch(client.status) {
            case 204:
                this.responseHeaders['Content-Type'] = "none";
                return false;
        }
        return false;
    }

    _onreadystatechange(statechange) {
        const client = this._getClient(statechange);
        switch(client.readyState) {
            case client.HEADERS_RECEIVED:
                this.setResponseHeaders(client.getAllResponseHeaders());
                break;
        }
    }

    _onprogress(progress) {
        const client = this._getClient(progress);
        this.dispatchEvent(new CustomEvent("progress", {detail: {client, progress}}));
    }

    _onerror(error) {
        const client = this._getClient(error);
        console.warn("There was an error with the XHR request");
        new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
        new AsyncUpdate(this); // Handle update instructions
        this.reject(error);
    }

    _ontimeout(timeout) {
        const client = this._getClient(timeout);
        this.reject(timeout);
        new StatusError({message: "Request timed out."});
    }

    _onabort(abort) {
        const client = this._getClient(abort);
        new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
        new AsyncUpdate(this); // Handle update instructions
        this.dispatchEvent(new CustomEvent("aborted", {detail: this}));
    }

    _getClient(event) {
        return event.target || event.srcTarget || event.originalTarget;
    }

    _communicatedError(err) {
        const client = this._getClient(err);
        // this.
        new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
        new AsyncUpdate(this); // Handle update instructions
        this.reject(client);
    }

    abort() {
        this.client.abort();
    }

    async get() {
        return await this.submit();
    }

    setRequestHeader(name, value) {
        this.requestHeaders[name] = value;
    }

    setResponseHeaders(value) {
        this.responseHeaders = {};
        for(const header of value.split("\r\n")) {
            const arr = header.split(":");
            if(!arr[0]) continue;
            if(!arr[1]) continue;
            this.responseHeaders[arr[0].toLowerCase()] = arr[1].trim();
        }
        return this.responseHeaders;
    }

    dispatchHeaderDirectives() {
        for(const headerName in this.responseHeaders) {
            if(headerName in this.headerDirectiveMap === false) continue;
            this.headerReactions[headerName] = new this.headerDirectiveMap[headerName](this.responseHeaders[headerName], headerName, this);
            this.headerReactions[headerName].execute();

        }
    }

    getHeader(name) {
        return this.responseHeaders[name.toLowerCase()] || null;
    }

}

async function get(url) {
    const api = new AsyncFetch(url, "GET", {});
    return await api.submit();
}

async function post(url, data) {
    const api = new AsyncFetch(url, "POST", {});
    return await api.submit(data);
}

async function put(url, data) {
    const api = new AsyncFetch(url, "PUT", {});
    return await api.submit(data);
}

/**
 * Datastructure:
 * [
 *   {
 *      target: ".some-query",
 *      value:  "Some value",
 *      innerHTML: "<p>Some value</p>",
 *      remove: "[name='element']",
 *      invalid: true,
 *      message: "Some validation message"
 *   },
 *   ...
 * ]
 */

class AsyncUpdate {
    constructor(request) {
        this.request = request;
        this.exec();
    }
    
    exec() {
        const list = this.request.resolved?.update ?? [];
        for(const instruction of list) {
            switch(instruction.target) {
                case "sessionStorage":
                case "localStorage":
                    this.updateStorage(instruction);
                    break;
                case "@form":
                    this.updateForm(instruction);
                    break;
                case "@cookie":
                    this.updateCookie(instruction);
                    break;
                default:
                    this.updateElement(this.getElement(instruction.target), instruction);
                    break;
            }
        }
    }

    getElement(query) {
        const regex = /:closest\((.*)\)/;
        if(query.match(regex)) {
            const closestQuery = regex.exec(query)[1];
            const trueQuery = query.replace(`:closest(${closestQuery})`, ""); // Remove parent selector
            const elements = document.querySelectorAll(trueQuery);
            let closest = [];
            for(const el of elements) {
                const result = el.closest(closestQuery);
                closest.push(result);
            }
            return closest;
        }
        return document.querySelectorAll(query);
    }

    updateCookie(instructions) {
        for(const i in instructions) {
            switch(i) {
                case "set":
                    for(const c in instructions[i]) set_cookie(c, instructions[i][c]);
                    break;
                case "remove":
                    for(const c in instructions[i]) delete_cookie(c, instructions[i][c]);
                    break;
                default:
                    console.warn(`"${i}" is not a recognized updateCookie instruction`);
            }
        }
    }

    /**
     * 
     * @param  instructions 
     */
    updateStorage(instructions) {
        const target = instructions.target;
        for(const i in instructions) {
            const directive = `storage_${i}`;
            if(directive === "storage_target") continue;
            if(directive in this === false) {
                console.warn(`Unsupported storage directive: ${directive}`)
                continue;
            }
            
            this[directive](window[target], instructions[i]);
        }
    }

    storage_set(target, values) {
        for(const i in values) {
            target.setItem(i, values[i]);
        }
    }

    storage_remove(target, values) {
        for(const i of values) {
            target.removeItem(i);
        }
    }

    updateForm(instructions) {
        if(!this.request.form) {
            console.warn("The form is not specified for this request type");
            return;
        }
        if("clear" in instructions) {
            if(instructions.clear === true) {
                this.request.form.dispatchEvent(new CustomEvent("clearall"))
            }
            delete instructions.clear
        }
        this.updateElement(this.request.form, instructions)
    }

    updateElement(elements, instructions) {
        for(const i in instructions) {
            const directive = `fn_${i}`;
            if(directive in this === false) {
                if(directive === "fn_target") continue;
                console.warn(`Unsupported update directive: ${directive}`);
                continue;
            }
            // if(directive === `fn_invalid` && el.closest("form-request") !== this.form) continue;
            for(const el of elements) {
                this[directive](el, instructions[i], instructions);
            }
        }
    }

    fn_dispatchEvent(el, value, instructions) {
        let event
        switch(value) {
            case "click":
            case "mousedown":
            case "keydown":
            case "load":
            case "change":
                event = new Event(value, {detail: instructions});
                break;
            default:
                event = new CustomEvent(value, {detail: instructions});
                break;
        }
        el.dispatchEvent(event);
    }

    fn_value(el, value, instructions) {
        if("value" in el) el.value = value;
        else console.warn("Element lacks 'value' property", el);
    }

    fn_innerHTML(el, value, instructions) {
        if("innerHTML" in el) el.innerHTML = value;
    }

    fn_innerText(el, value, instructions) {
        if("innerText" in el) el.innerText = value;
        else console.warn("Element lacks 'innerText' property", el)
    }

    fn_outerHTML(el, value, instructions) {
        if("outerHTML" in el) el.outerHTML = value;
    }

    fn_invalid(el, value, instructions) {
        if("ariaInvalid" in el) {
            el.ariaInvalid = value;
            el.addEventListener("focusin", e => e.ariaInvalid = null, {once: true});
        }
    }

    fn_disabled(el, value, instructions) {
        if(!value) {
            el.ariaDisabled = false;
            return el.disabled = false;
        }
        el.ariaDisabled = true;
        return el.disabled = true;
    }

    fn_delete(el, value, instructions) {
        if(value !== true) return;
        el.parentNode.removeChild(el)
    }

    fn_remove(el, value, instructions) {
        try {
            if(typeof value === "string") el = el.querySelectorAll(value);
        } catch (Error) {
            console.warn("Malformed selector")
        }

        if(el instanceof NodeList || Array.isArray(el)) el.forEach(e => e.parentNode.removeChild(e));
        
        if(el.constructor.name === "Array") {
            el.forEach(e => e.parentNode?.removeChild(e))
        } else el.parentNode.removeChild(el)
    }

    fn_message(el, value, instructions) {
        const messageElement = appendElementInformation(el, value, instructions);
        el.dispatchEvent(new CustomEvent("validationissue", {bubbles: true}));
        el.addEventListener("focusin", e => messageElement.dispatchEvent(new Event("click", e)), {once: true});
    }

    fn_img(el, value, instructions){
        this.fn_src(el, value.filename, instructions);
        el.height = value.meta.height;
        el.width = value.meta.width;
    }

    fn_src(el, value, instructions) {
        el.setAttribute("src", value);
    }

    fn_href(el, value, instructions) {
        el.setAttribute("href", value);
    }

    fn_attribute(el, value, instructions) {
        this.fn_attributes(el, value, instructions);
    }

    fn_attributes(el, value, instructions) {
        for(const v in value) {
            el.setAttribute(v, value[v]);
        }
    }

    fn_style(el, value, instructions) {
        for(const v in value) {
            el.style[v] = value[v];
        }
    }

}

function appendElementInformation(element, value, instructions) {
    let el = document.createElement("validation-issue");
    const spawnIndex = spawn_priority(element);
    if (spawnIndex) el.style.zIndex = spawnIndex + 1;

    el.addEventListener('click', () => {
        if (el) {
            el.parentNode.removeChild(el);
            element.ariaInvalid = false;
        }
    });

    el.classList.add("form-request--field-issue-message");
    el.innerText = value;
    el.addEventListener("click", async e => {
        element.ariaInvalid = false;
        await wait_for_animation(el, "form-request--issue-fade-out");
        el.parentNode.removeChild(el);
    })

    const offsets = get_offset(element);
    el.style.top = `${offsets.bottom}px`;
    el.style.left = `${offsets.x}px`;
    el.style.width = `${offsets.w}px`;
    document.body.appendChild(el);
    wait_for_animation(el, "form-request--issue-fade-in");
    return el;
}

class HeaderDirective {
    constructor(headerContent, name, xhrRequest) {
        this.props = {
            content: null,
            rawContent: headerContent,
            method: xhrRequest.method,
            action: xhrRequest.action,
            name,
            tag: null,
            tagArguments: [],
            xhrRequest,
        };
        // this.content = headerContent;
        this.parseTag();
    }

    get content() {
        return this.props.content ?? this.props.rawContent;
    }

    set content(headerContent) {
        this.props.content = headerContent ?? null;
    }

    get tag() {
        return this.props.tag;
    }

    get tagArgs() {
        return this.props.tagArguments;
    }

    get method() {
        return this.props.method;
    }

    get action() {
        return this.props.action;
    }

    get identifier() {
        return `${this.props.method}__${this.props.action.replace('/',"_")}`;
    }


    execute() {

    }

    parseTag() {
        if(this.content[0] !== "@") return;
        const tagRegex = /@(\w*)(\(.*\))?/;
        const tagMatch = this.props.rawContent.match(tagRegex);
        if(tagMatch[1]) this.props.tag = tagMatch[1];
        this.props.content = this.props.rawContent.replace(tagMatch[0], "").trim();

        if(tagMatch.length >= 3) this.parseTagArguments(tagMatch[2]);
    }

    parseTagArguments(argMatch) {
        if(!argMatch) return;
        // // const originalTag = tag;
        // const operandStartChar = tag.indexOf("(");
        // const args = tag.substring(operandStartChar);
        // const mutatedTag = args.replace(args, "");
        let parsedArguments = [];
        
        try {
            parsedArguments = JSON.parse(argMatch.replace("(", "[").replace(")","]"));
        } catch (error) {
            return this.props.tagArguments = args;
        }

        // this.props.tag = mutatedTag;
        this.props.tagArguments = parsedArguments;
    }

    tagToIcon() {

    }
}

class XStatus extends HeaderDirective {
    execute() {
        new StatusMessage({message: this.content, id: this.identifier, icon: this.tagToIcon()});
    }
}

/**
 * Supported tags:
 * @view - followed by the path to load
 * none  - followed by the body content
 */
class XModal extends HeaderDirective {
    execute() {
        switch(this.tag) {
            case "view":
                modalView(this.content, "Close");
                break;
            case null:
            default:
                const modal = new Modal({id: this.identifier, body: this.content, close_btn: true});
                modal.draw();
        }
    }
}

/**
 * Supported tags:
 * @wait({int})
 * @delay({int}) alias of @wait
 */
class XRedirect extends HeaderDirective {
    execute() {
        switch(this.tag) {
            case "delay":
            case "wait":
                new StatusMessage({message: `Redirecting in ${this.tagArgs[0]} seconds`});
                setTimeout(() => {
                    this.redirect();
                }, this.tagArgs[0] * 1000);
                break;
            default:
                this.redirect();
        }
    }

    redirect() {
        Cobalt.router.location = this.content;
    }
}

class XReplace extends XRedirect {
    redirect() {
        Cobalt.router.replaceState({}, )
    }
}

/** Supported tags
 * @now           - no parameters, executes refresh now. (deprecated "now" as the header content)
 * @wait({num})   - a number (in seconds) to wait before refreshing the page. A status message is displayed
 * @silent({num}) - exactly the same as @wait, except it does not display a status message
*/
class XRefresh extends HeaderDirective {
    execute() {
        // let location = String(location)
        switch(this.tag) {
            case "wait":
                new StatusMessage({message: `Refreshing in ${wait} seconds`});
            case "silent":
                let wait = this.tagArgs[0] || Number(this.content);
                setTimeout( () => {
                    Cobalt.router.location = String(window.location);
                }, wait * 1000);
            case "now":
            case "true":
            default:
                Cobalt.router.location = String(window.location);
                break; 
                
        }
    }
}

class XConfirm extends HeaderDirective { 
    async execute() {
        const xhr = this.props.xhrRequest;
        const responseBody = JSON.parse(xhr.response);
        const fulfillment = responseBody.fulfillment;
        let confirm = await modalConfirm(fulfillment.error, fulfillment.data.okay, "Cancel", fulfillment.data.dangerous);
        if(confirm === false) return xhr.dispatchEvent(new CustomEvent("resubmission", {detail: false}));
        xhr.dispatchEvent(new CustomEvent("resubmission", {detail: true}));

        xhr.setRequestHeader('X-Confirm-Dangerous', 'true');
        return await xhr.submit(fulfillment.data.return);
    }
}

class XReauth extends HeaderDirective {
    async execute() {
        const xhr = this.props.xhrRequest;
        const responseBody = JSON.parse(xhr.response);
        const fulfillment = responseBody.fulfillment;
        let password = await modalInput("Please supply your password to verify your identity", {okay: fulfillment.data.okay, cancel: "cancel"});
        xhr.setRequestHeader("X-Reauthorization", btoa(password));
        const result = await xhr.submit(fulfillment.data.return);
        return result;
    }
}