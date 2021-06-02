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

    async send(data = "", { throwOnError = true, encapsulate_as_array = false }) {
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
        if (result.ok === false) throw new FetchError("HTTP Error", result, await result.json());
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