class CobaltCore {
    constructor() {
        this.state = {
            router: null
        }
        this.resolvers = {
            ready: null,
            router: null,
            editorjs: null,
        }
        this.promises = {
            ready: new Promise(resolve => {
                this.resolvers.ready = resolve;
            }),
            router: new Promise(resolve => {
                this.resolvers.router = resolve;
            }),
            editorjs: new Promise(resolve => {
                this.resolvers.editorjs = resolve;
            })
        }
        this.screenReaderAnnounceArea = document.querySelector("#sr-announce");
        window.dispatchEvent(new CustomEvent("cobaltready"))
        window.addEventListener("DOMContentLoaded", async () => {
            await Promise.all(window.asyncScripts)
            this.resolvers.ready(true);
        })
    }

    get router() {
        return this.state.router;
    }

    set router(rt) {
        this.state.router = rt;
    }

    get editorjs() {
        return this.promises.editorjs
    }

    announce(announcement) {
        const div = document.createElement("div");
        div.innerText = announcement;
        const screenReaderAnnounceArea = document.querySelector("#sr-announce");
        screenReaderAnnounceArea.appendChild(div);
        setTimeout(() => {
            div.parentNode.removeChild(div);
        }, 1000);
    }
}
window.Cobalt = new CobaltCore()