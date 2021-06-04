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
        if (result.ok === false) result = await this.handleErrors(result)
        return await result.json();
    }

    async get() {
        return await this.send("", "GET", {});
    }

    async handleErrors(result) {
        switch (result.status) {
            case 300:
                let confirm = new FetchConfirm(await result.json(), this);
                result = await confirm.draw();
                if (result.json().error !== "Aborted") break;
            default:
                throw new FetchError("HTTP Error", result, await result.json());
                break;
        }
        return result;
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