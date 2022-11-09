class ApiFetch {
    constructor(uri, method = "GET", {
        format = "application/json; charset=utf-8",
        cache = "default",
        asJSON = true,
        credentials = true,
        headers = {}
    }) {
        this.uri = uri;
        this.method = method;
        this.asJSON = asJSON;
        this.format = format;
        this.cache = cache;
        this.credentials = credentials;
        this.headers = headers;
        this.result = null;
        this.abortController = null;
        this.reject = null;
    }

    // fetch requests are now abortable!
    async send(data = "") {
        return new Promise(async (resolve, reject) => {
            this.reject = reject;
            this.abortController = new AbortController();
            let send = {
                method: this.method,
                credentials: 'include',
                cache: this.cache,
                headers: {
                    "Content-Type": this.format,
                    // "X-Mitigation": document.querySelector("meta[name='token']").getAttribute("content"),
                    ...this.headers
                },
                signal: this.abortController.signal
            }
            if (this.method !== "GET") send["body"] = (this.asJSON) ? JSON.stringify(data) : data
            let result = null;
            result = await fetch(this.uri, send);
            this.result = await result.json();

            if (result.headers.get("X-Redirect")) router.location = result.headers.get('X-Redirect');
            if (result.headers.get("X-Status")) this.statusMessage(result.headers.get("X-Status"));
            if (result.headers.get("X-Modal")) this.modal(result.headers.get('X-Modal'));
            if (result.ok === false) {
                reject(this.result);
                await this.handleErrors(result,this.result);
            }
            // this.result = result;
    
            if("headers" in result) {
                this.headers['X-Next-Request'] = result.headers.get("X-Next-Request") ?? null;
                if(this.headers['X-Next-Request']) this.headers['X-Next-Request'] = JSON.parse(this.headers['X-Next-Request']) ?? null;
            }
            
            this.execPlugins("after", this.result, result);
            resolve(this.result);
            this.reject = null;
            return this.result;
        })
    }

    statusMessage(status){
        let parsed = this.parseShorthandCommand(status);

        parsed.id = this.uri;
        new StatusMessage({...parsed});
    }

    modal(modal) {
        let parsed = this.parseShorthandCommand(modal);
        parsed.id = this.uri;

        const modalContainer = new Modal({...parsed});
        modalContainer.draw();
    }

    parseShorthandCommand(command) {
        let parsed = command;
        try{
            parsed = JSON.parse(command);
        } catch (error) {

        }

        if(typeof parsed === "string") parsed = {message: parsed}

        // Handle shorthand type selection
        if(parsed.message[0] === "@") {
            let shorthand = parsed.message.substring(1, parsed.message.indexOf(" "));
            parsed.type = parsed.type || shorthand;
            parsed.message = parsed.message.substring(`${shorthand} `.length);
        }

        return parsed;
    }

    async abort() {
        this.abortController.abort();
    }

    async get() {
        return await this.send("", "GET", {});
    }

    async handleErrors(result, json) {
        // const json = await result.json();
        // this.result = json;
        
        switch (result.status) {
            case 300:
                let confirm = new FetchConfirm(json, this);
                result = await confirm.draw();
                if (json.error !== "Aborted") break;
            case 301:
                router.location = json.message;
                break;
            default:
                throw new FetchError("HTTP Error", result, json, this.uri);
        }
        return json;
    }

    async execPlugins(type, result, request) {
        if (!window.ApiFetchPlugins) return;
        if (!window.ApiFetchPlugins[type]) return;
        for (const callback in window.ApiFetchPlugins[type]) {
            callback(result, request); 0
        }
    }
}

class ApiFile extends ApiFetch {
    constructor(uri, method = "POST", {
        format = "multipart/form-data; charset=utf-8",
        cache = "default",
        asJSON = true,
        credentials = true,
        headers = {},
        progressBar = null,
    }) {
        super(uri, method, { format, cache, asJSON, credentials, headers });
        this.progressBar = progressBar;
    }

    async send(post = "") {
        return new Promise((resolve, reject) => {
            const data = new FormData();

            data.append("json_payload", JSON.stringify(post));

            const fileFields = document.querySelectorAll("[type='file'],[type='files']");

            let file_size = 0;

            for (const i of fileFields) {
                for (const l of i.files) {
                    data.append(`${i.name}[]` || 'files[]', l);
                    file_size += parseFloat(l.size);
                }
            }

            let request = new XMLHttpRequest();
            if (this.credentials) request.withCredentials = true;
            request.open(this.method, this.uri);

            request.upload.addEventListener("progress", (e) => {
                let percent_complete = (e.loaded / e.total) * 100;
                if (this.progressBar) this.progressBar.percent = percent_complete;
            });

            request.addEventListener("load", (e) => {
                if (this.progressBar) {
                    this.progressBar.percent = 100;
                    this.progressBar.complete = "complete";
                }


                var headers = request.getAllResponseHeaders();

                var arr = headers.trim().split(/[\r\n]+/);

                var headerMap = {};
                arr.forEach(function (line) {
                    var parts = line.split(': ');
                    var header = parts.shift();
                    var value = parts.join(': ');
                    headerMap[header] = value;
                });

                if ("x-redirect" in headerMap && headerMap["x-redirect"]) router.location = headerMap['x-redirect'];
                resolve(JSON.parse(request.response));
            });

            request.addEventListener("error", (e) => {
                reject(e);
            })

            for (const i in this.headers) {
                request.setRequestHeader(i, this.headers[i]);
            }

            request.setRequestHeader("X-Total-Upload-Size", file_size);

            request.send(data);
        })
    }

}

class FetchError extends Error {
    constructor(message, data, result, url) {
        super();
        this.message = message;
        this.request = data;
        this.result = result;
        this.url = url;
        this.statusMessage();
    }

    statusMessage() {
        if("error" in this.result && this.result.error) new StatusError({message: this.result.error, id: this.url, type: this.result.code});
        else new StatusError({message:"An unknown error occurred", id: this.url});
    }
}

class FetchConfirm {
    constructor(data, original_fetch) {
        this.returnValues = data;
        this.fetch = original_fetch;
    }

    async draw() {
        let confirm = await modalConfirm(this.returnValues.error, this.returnValues.okay, "Cancel", this.returnValues.dangerous);
        if (confirm === false) return { json: () => { return { status: 400, error: "Aborted", data: false } } };
        console.log(this.returnValues.data)
        this.fetch.headers = { ...this.fetch.headers, ...this.returnValues.data.headers };
        const result = await this.fetch.send(this.returnValues.data.return);
        return { json: () => result };
    }
}