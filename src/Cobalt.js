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
    }

    get router() {
        return this.state.router;
    }

    set router(rt) {
        this.state.router = rt;
    }

    
}