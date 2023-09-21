/**
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
        headers = {}
    }) {
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
        
        this.data = "";
        this.response = null; // The raw response from the fetch request
        this.resolved = null; // The resolved data from the request
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
        if(client.status !== 200) {
            const nonOkResponse = this.handleNonOkResponse(event, client, resolve);
            if(nonOkResponse === true) return true;
        }
        // Parse the response data
        if(this.getHeader('Content-Type')?.match(/json/i)) this.resolved = JSON.parse(client.response);
        else {
            try {
                JSON.parse(client.response);
            } catch (e) {
                this.resolved = client.response;
            }
        }

        if(client.status >= 299) {
            return this._communicatedError(event);
        }

        new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
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

    getHeader(name) {
        return this.responseHeaders[name.toLowerCase()] || null;
    }

}

// async submit(data = "") {
//     this.data = data;
//     return new Promise(async (resolve, reject) => {
//         // Let's set up our request so it's cancelable
//         this.reject = reject;
//         this.abortController = new AbortController();

//         const submit = new Event("submit"); // Prepare the submit event
//         this.dispatchEvent(submit, {detail: this}); 
//         if(submit.defaultPrevented) {
//             // Cancel the submit if the event was prevented
//             this.dispatchEvent(new CustomEvent("aborted", {detail: this}));
//             return false;
//         } 
        
//         // Prepare our fetch object
//         let send = {
//             method: this.method,
//             credentials: 'include',
//             cache: this.cache,
//             headers: {

//                 ...this.requestHeaders
//             },
//             signal: this.abortController.signal
//         }
//         if(this.method !== "GET") send.body = (this.asJSON) ? JSON.stringify(data) : data;
//         try {
//             this.response = await fetch(this.action, send);
//         } catch (error) {
//             console.log(error);
//             this.response = error;
//         }

//         // Parse the response data
//         if(this.response.headers.get('Content-Type').match(/json/i)) this.resolved = await this.response.json()
//         else this.resolved = await this.response.text();

//         if(this.response.ok === false) {
//             new AsyncMessageHandler(this, "AsyncFetch", "error"); // Process headers
//             new AsyncUpdate(this); // Handle update instructions
            
//             // If the result of our request has an error, process the error
//             this.dispatchEvent(new Event("error"), {
//                 detail: {
//                     fulfillment: this.resolved.fulfillment || this.resolved,
//                     resource: this
//                 }
//             });
//             reject(this.resolved.fulfillment || this.resolved);
            
//             return;
//         }
        
//         new AsyncMessageHandler(this, "AsyncFetch", "async"); // Process headers
//         new AsyncUpdate(this); // Handle update instructions

//         this.dispatchEvent(new CustomEvent("done"), {
//             detail: {
//                 fulfillment: this.resolved.fulfillment || this.resolved,
//                 resource: this
//             }
//         });

//         // Resolve this promise
//         resolve(this.resolved.fulfillment || this.resolved);

//         // Clean up
//         this.reject = null;
//         this.abortController = null;
//     });
// }

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
        const list = this.request.resolved.update;
        for(const instruction of list) {
            this.updateElement(this.getElement(instruction.target), instruction);
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

    updateElement(elements, instructions) {
        for(const i in instructions) {
            const directive = `fn_${i}`;
            if(directive in this === false) {
                if(directive === "fn_target") continue;
                console.warn(`Unsupported update directive: ${directive}`);
                continue;
            }
            for(const el of elements) {
                this[directive](el, instructions[i], instructions);
            }
        }
    }

    fn_value(el, value, instructions) {
        if("value" in el) el.value = value;
    }

    fn_innerHTML(el, value, instructions) {
        if("innerHTML" in el) el.innerHTML = value;
    }

    fn_outerHTML(el, value, instructions) {
        if("outerHTML" in el) el.fn_outerHTML = value;
    }

    fn_invalid(el, value, instructions) {
        if("ariaInvalid" in el) el.ariaInvalid = value;
        el.addEventListener("focusin", el => el.ariaInvalid = null, {once: true});
    }

    fn_remove(el, value, instructions) {
        if(typeof value === "string") el = this.getElement(value);
        if(el instanceof NodeList || Array.isArray(el)) el.forEach(e => e.parentNode.removeChild(e));
        
        el.parentNode.removeChild(el)
    }

    fn_message(el, value, instructions) {
        const messageElement = appendElementInformation(el, value, instructions);
        el.addEventListener("focusin", e => messageElement.dispatchEvent(new Event("click", e)), {once: true});
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
