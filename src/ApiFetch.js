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
        console.warn("WARNING! The 'ApiFetch' class has been deprecated and is being phased out in a future version of Cobalt Engine! Use 'AsyncFetch' instead!");
    }

    // fetch requests are now abortable!
    async send(data = "") {
        console.warn("WARNING! This app is using ApiFetch to fetch data! This class has been deprecated! Use AsyncFetch instead!");
        return new Promise(async (resolve, reject) => {
            this.reject = reject;
            this.abortController = new AbortController();
            let send = {
                method: this.method,
                credentials: 'include',
                cache: this.cache,
                headers: {
                    "Content-Type": this.format,
                    // "X-Include": "fulfillment,update,events",
                    // "X-Mitigation": document.querySelector("meta[name='token']").getAttribute("content"),
                    "X-Request-Source": "ApiFetch",
                    ...this.headers
                },
                signal: this.abortController.signal
            }
            if (this.method !== "GET") send["body"] = (this.asJSON) ? JSON.stringify(data) : data
            let result = null;
            result = await fetch(this.uri, send);
            this.fetchResult = result;
            if(result.headers.get('Content-Type').match(/json/i)) this.result = await result.json();
            else this.result = result.text();

            if (result.ok === false) {
                reject(this.result);
                await this.handleErrors(result,this.result);
                return new AsyncMessageHandler(this, "ApiFetch", "error");
            }
            new AsyncMessageHandler(this, "ApiFetch", "status");
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

    pageRefresh(value) {
        if(value === "now") router.location = router.location;
        this.statusMessage(`@refresh Refreshing page in ${value} seconds`);
        setTimeout(() => {
            router.location = router.location
        },value * 1000);
    }
    statusMessage(status){
        let parsed = this.parseShorthandCommand(status);

        parsed.id = this.uri;
        new StatusMessage({...parsed});
    }

    modal(modal) {
        let parsed = this.parseShorthandCommand(modal);
        let modalContainer;
        try{
            parsed = JSON.parse(parsed.message.trim());
            if("id" in parsed === false) parsed.id = this.uri;
            modalContainer = new Modal({...parsed});
        } catch(Error) {
            if("id" in parsed === false) parsed.id = this.uri;
            modalContainer = new Modal({
                body: parsed.message,
                chrome: {cancel: null}
            });
        }
        
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

    // For now, we're just going to let this object handle FetchConfirms.
    async handleErrors(result, json) {
        switch (result.status) {
            case 300:
                let confirm = new FetchConfirm(json, this);
                result = await confirm.draw();
                if (json.error !== "Aborted") break;
                break;
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
