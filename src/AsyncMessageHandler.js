/**
 * The Error object should be the XHR client for XHR requests or the response
 * to the fetch error
 */
class AsyncMessageHandler {
    constructor(fetch, type = "fetch", mode = "status") {
        this.status = 0;
        this.statusText = "";
        this.responseType = "";
        this.uri = "";
        this.headers = {};
        let method = "normalizeFetchError";
        switch(mode) {
            case "page":
            case "html":
            case "main":
            case "update":
                this.mode = "page";
                break;
            case "status":
            case "message":
            default:
                this.mode = "status";
                break;
        }
        switch(type.toLowerCase()) {
            case 1:
            case "xhr":
            case "xmlhttprequest":
                method = "normalizeXhrError";
                break;
            case 2:
            case "fetch":
                method = "normalizeFetchError";
                break;
            case 3:
            case 'apifetch':
                method = "normalizeApiFetchError";
                break;
            case 4:
            case "asyncfetch":
                method = "normalizeAsyncFetchError";
                break;
        }
        this.fetch = fetch;
        this.process(this.fetch, method);
    }

    async process(obj, method) {
        this.responseBody = await this[method](obj);
        this.handleHeaderMessaging();
        if(obj.response.status >= 300) this.handleError();
    }

    async normalizeXhrError(data) {
        this.status = data.status;
        this.statusText = data.statusText;
        this.headers = this.parseXHRheaders(data.getAllResponseHeaders());
        this.responseType = this.headers["content-type"] ?? null;
        this.uri = data.responseURL;
        if(this.responseType.indexOf("json") !== -1 && data.responseText) return JSON.parse(data.responseText);
        return data.response;
    }

    parseXHRheaders(headers) {
        let final = {};
        let split = headers.split("\r\n");
        for(const line of split) {
            let s = line.split(": ");
            final[s.shift().toLowerCase()] = s.join(": ");
        }
        return final;
    }

    async normalizeFetchError(data) {
        this.status = data.status;
        this.statusText = data.statusText;
        this.responseType = data.headers.ContentType;
        this.uri = data.url;
        this.headers = this._getHeaderObject(data);
        return await data.body.json();
    }

    async normalizeApiFetchError(data) {
        this.status = data.fetchResult.status;
        this.statusText = data.fetchResult.statusText;
        // this.responseType = data.fetchResult;
        this.uri = data.fetchResult.url;
        this.headers = this._getHeaderObject(data.fetchResult);
        return await data.result;
    }
    
    _getHeaderObject(data) {
        const object = {};
        for( const h of data.headers.entries()){
            object[h[0]] = h[1];
        }
        return object;
    }

    async normalizeAsyncFetchError(data) {
        this.status = data.client.status;
        this.statusText = data.client.statusText;
        this.responseType = data.getHeader('Content-Type');
        this.uri = data.action;
        this.headers = data.responseHeaders;
        return data.resolved.fulfillment || data.resolved;
    }

    _getAsyncHeaders(request) {
        const object = {};
        for( const h of request.headers.entries()) {
            object[h[0]] = h[1];
        }
        return object;
    }

    async handleHeaderMessaging() {
        const headers = {
            'x-redirect': "xredirect",
            'x-refresh':  'xrefresh',
            'x-status':   'xstatus',
            'x-modal':    'xmodal',
            'x-next-request': 'xnextrequest',
            'x-confirm': 'xconfirm',
            'x-reauthorization-request': 'xreauthrequest',
        };
        for(const h of Object.keys(headers)) {
            if(h in this.headers === false) continue;
            const params = this.parseHeaderTags(this.headers[h]);
            await this[headers[h]](params);
            this.errorHandled = true;
        }
    }

    handleError() {
        if(this.errorHandled) return;
        let handler = null;
        switch(this.status) {
            case this.status >= 300 && this.status <= 399:
                handler = new ThreeHundred(this, this.mode);
                break;
            case this.status >= 400 && this.status <= 499:
                handler = new FourHundred(this, this.mode);
                break;
            default:
                handler = new FiveHundred(this, this.mode);
                break;
        }
        handler.exec();
    }

    parseHeaderTags(message) {
        let m = message.trim();
        if(m[0] === "@") {
            const tag = m.trim().match(/@(\w+)/);
            return {
                tag: tag[1],
                message: m.replace(tag[0], "")
            }
        }
        // We assume that the entire thing is JSON
        if(m[0] === "{") {
            try {
                return JSON.parse(m);
            } catch (error) {
                return {
                    message: m
                };
            }
        }
        return {
            message: m
        }
    }

    xredirect(params) {
        Cobalt.router.location = params.message;
    }

    xrefresh(params) {
        if("message" in params == false) params.message = "now";
        if(params.message === "now") return Cobalt.router.location = router.location;
        // this.statusMessage(`@refresh Refreshing page in ${value} seconds`);
        // setTimeout(() => {
        //     router.location = router.location
        // },value * 1000);
    }

    xstatus(params) {
        new StatusMessage({
            id:      this.fetch.uri,
            type:    params.tag,
            message: params.message,
        });
    }
    
    xmodal(params) {
        let modalContainer;
        try{
            parsed = JSON.parse(params.message.trim());
            if("id" in parsed === false) parsed.id = this.fetch.uri;
            modalContainer = new Modal({...parsed});
        } catch(Error) {
            if("id" in params === false) params.id = this.fetch.uri;
            if("tag" in params === false) params.tag = "warning";
            modalContainer = new Modal({
                id: params.id,
                body: params.message,
                chrome: {cancel: null}
            });
        }
        
        modalContainer.draw();
    }

    async xconfirm() {
        this.errorHandled = true;
        let confirm = new FetchConfirm(this.responseBody, this.fetch);
        result = await confirm.draw();
        if(this.responseBody.error !== "Aborted") return;
    }

    async xreauthrequest() {
        this.errorHandled = true;
        let password = new FetchReauth(this.responseBody, this.fetch);
        result = await password.draw();
        if(this.responseBody.error !== "Aborted") return;
    }
}

class GenericHTTPError {
    constructor(error, mode) {
        this.error = error;
        this.mode = mode;
        this.main = document.querySelector("main");
        this.clientError = (this.error.status >= 400 && this.error.status <= 499);
    }

    exec() {
        if(this.mode === "status") {
            new StatusMessage({
                message: this.pageContent(),
                id:      this.error.uri,
                // icon:    (this.clientError) ? "warning" : "error",
                type:    (this.clientError) ? "warning" : "error",
            });
        }
        // else if (this.mode = "page") {
        //     document.body.main.innerHTML = `<h1>${}</h1>`
        // }
    }

    pageContent() {
        return this.error.responseBody.message ?? this.error.responseBody.error ?? this.error.responseBody.content ?? this.error.statusMessage ?? this.error.statusText;
    }

    pageLoad() {
        return this.error.responseBody.message ?? this.error.responseBody.content;
    }
}

class ThreeHundred extends GenericHTTPError{
    constructor(error, mode) {
        super(error, mode);
    }
}

class FourHundred extends GenericHTTPError{
    constructor(error, mode) {
        super(error, mode);
    }
}

class FiveHundred extends GenericHTTPError {
    constructor(error, mode) {
        super(error, mode);
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

        this.fetch.requestHeaders = { ...this.fetch.requestHeaders, ...this.returnValues.data.requestHeaders, "X-Confirm-Dangerous": "true" };
        const result = await this.fetch.submit(this.returnValues.data.return);
        return { json: () => result };
    }
}

class FetchReauth {
    constructor(data, fetch) {
        this.data = data;
        this.fetch = fetch;
    }

    async draw() {
        let confirm = await modalConfirm(`${this.data.error}
            <form-request>
                <fieldset>
                    <legend>Password</legend>
                    <input-password name="password"></input-password>
                </fieldset>
            </form-request>`, this.data.okay, "Cancel", this.data.dangerous);
        const password = document.querySelector('modal-box input-password[name="password"]').value;
        if(confirm === false) return { json: () => {return {status: 400, error: "Aborted", data: false }}};

        this.fetch.requestHeaders = {...this.fetch.requestHeaders, ...this.data.data.requestHeaders, "X-Reauthorization": `${btoa(password)}`};
        const result = await this.fetch.submit(this.data.data.return);
        return {json:() => result};
    }
}
