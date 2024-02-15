class Cobalt {
    constructor() {
        this.state = {
            router: null
        }
        this.resolvers = {
            router: null
        }
        this.promises = {
            router: new Promise(resolve => {
                this.resolvers.router = resolve;
            })
        }
        this.screenReaderAnnounceArea = document.querySelector("#sr-announce");
    }

    get router() {
        return this.state.router;
    }

    set router(rt) {
        this.state.router = rt;
    }


}