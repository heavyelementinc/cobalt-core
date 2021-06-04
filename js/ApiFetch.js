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
    }

    async send(data = "") {
        let send = {
            method: this.method,
            credentials: 'include',
            cache: this.cache,
            headers: {
                "Content-Type": this.format,
                // "X-Mitigation": document.querySelector("meta[name='token']").getAttribute("content"),
                ...this.headers
            },
        }
        if (this.method !== "GET") send["body"] = (this.asJSON) ? JSON.stringify(data) : data
        let result = await fetch(this.uri, send);
        if (result.ok === false) {
            switch (result.status) {
                case 300:
                    let confirm = FetchConfirm(await result.json(), this);
                    result = await confirm.draw();
                    if (result === false)
                        break;
                default:
                    throw new FetchError("HTTP Error", result, await result.json());
                    break;
            }
        }
        return await result.json();
    }

    async get() {
        return await this.send("", "GET", {});
    }
}

class FetchError extends Error {
    constructor(message, data, result) {
        super();
        this.message = message;
        this.request = data;
        this.result = result;
    }
}

class FetchConfirm {
    constructor(data, original_fetch) {
        this.data = data;
        this.fetch = original_fetch;
    }

    async draw() {
        let confirm = await new modalConfirm(this.data.message);
        if (confirm === false) return;
        this.fetch.headers
        return await 
    }
}