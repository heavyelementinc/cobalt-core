class RouteObject {
    constructor(route) {
        window.router_entities = {};
        this.originalRoute = route; // The original route we're trying to match
        this.currentContext = "/";  // The current route context
        this.isLocalRoute = null;   // Bool that determines if a route belongs to this app
        this.requiresReload = false;// Bool that determiens if the page must be reloaded
        this.match = false;         // Determines if this route matches an entry in the router table
        this.regex = null;          // Matching RegExp() object or null
        this.variables = [];        // Any variables in the URL
        this.callbacks = {};        // All callbacks in 
        this.URL = null;            // A URL() object for the current route
        this.setRoute(route);       // 
    }

    get currentRoute() {
        return `${this.URL.pathname}`;
    }

    get currentRouteWithParams() {
        return `${this.URL.pathname}${(this.URL.search) ? "?" + this.URL.search : ""}`;
    }

    setRoute(href) {
        let route = href;
        let match = matches(route, /^http/);
        if(match === false) {
            if(href[0] === "/") {
                route = `${location.protocol}//${location.hostname || location.host}${href}`
            }
        }

        try {
            this.URL = new URL(route);
        } catch (error) {
            console.error(error);
        }
        if(this.URL.host !== window.location.host) {
            this.isLocalRoute = false;
            this.currentContext = "";
            this.regexMatch = "";
            this.variables = [];
            this.callbacks = {};
            return;
        }
        this.isLocalRoute = true;
        this.currentContext = this.getCurrentContext(this.currentRoute);
        this.matchRouteToRouterTableEntry(this.currentRoute)
    }

    get crossesCurrentBoundary() {
        if(this.currentContext !== this.getCurrentContext(window.location.pathname)) {
            Cobalt.router.debug(`Route ${this.URL.toString()} crosses the current route context boundary, refreshing`);
            return true;
        }
        return false;
    }

    get isBoundaryRoot() {
        const currentBoundary = this.getCurrentContext();
        return this.URL.pathname === currentBoundary;
        // return (Object.values(Cobalt.routeBoundaries).includes(this.URL.pathname));
        // if(currentBoundary === "/" || this.URL.pathname === "") return true;
        // return this.URL.pathname === currentBoundary;
    }

    getCurrentContext(route = null) {
        if(!route) route = this.URL.pathname;
        for(const i in window.Cobalt.router.routeBoundaries) {
            if(!window.Cobalt.router.routeBoundaries[i]) continue;
            const regex = new RegExp(i);
            const match = route.match(regex); //(0, i.length);
            if(match === null) continue;
            if(match.length <= 0) continue;
            return window.Cobalt.router.routeBoundaries[i];
        }
        return "/";
    }

    matchRouteToRouterTableEntry(route) {
        for(const regex in router_table) {
            const rt = new RegExp(regex);
            let match = route.match(rt);
            if (match === null) continue;
            if (match.length <= 0) continue;

            match.shift();
            this.variables = match;
            this.match = true;
            this.regex = rt;
            this.callbacks = (router_table[regex]) ? {...router_table[regex]} : {};
            for(const deprecated of [['navigation_callback', 'onload'], ['exit_callback', 'beforeunload']]) {
                if(deprecated[0] in router_table[regex] === false) continue;
                delete this.callbacks[deprecated[0]];
                this.callbacks[deprecated[1]] = () => {
                    router_table[regex][deprecated[0]];
                }
                console.warn(`DEPRECATED: A route uses a deprecated callback "${deprecated[0]}" and was automatically upgraded to "${deprecated[1]}". You should change this soon.`, regex);
            }
            this.requiresReload = this.crossesCurrentBoundary;
            return;
        }

        this.variables = [];
        this.match = false;
        this.regex = null;
        this.callbacks = {};
        this.requiresReload = this.crossesCurrentBoundary;
    }

    toString() {
        return this.URL.toString();
    }
}

class ClientRouter extends EventTarget{

    constructor() {
        super();
        this.properties = {
            // location: new RouteObject(window.location.pathname),
            routeBoundaries: JSON.parse(document.querySelector("#route-boundaries").innerText)
        }
        this.lastLocationChangeEvent = {};
        /**
         * Here we set the value of the last page request equal to window.__ in order to
         * preserve the variables exported publicly by the Cobalt Engine for the first page load
         * @property Stores the last navigation event result
         */
        this.lastPageRequestResult = window.__;
        this.mode = "spa";
        this.firstRun = true;
        this.setPushStateMode();
        this.initListeners();
        history.replaceState({ // Let's set up initial pages so async popstates work well
                title: document.title,
                url: window.location.toString(),
                scrollY: window.scrollY,
                scrollX: window.scrollX
            }, '', window.location.toString()
        );
        
    }

    get location() {
        return this.route;
    }

    set location(pathname) {
        const route = new RouteObject(pathname);
        if(route.URL === this.route?.URL) return console.log("This is the current route");
        this.previousRoute = this.route;
        this.route = route;
        if(!route.isLocalRoute) {
            this.debug(`The url ${route.URL.toString()} is not a local route, refreshing`)
            return window.location = pathname
        };
        if(route.requiresReload) return window.location = pathname;
        if(this.mode !== "spa") {
            this.debug("This Cobalt app is not set to SPA mode, refreshing.");
            return window.location = pathname;
        }

        const result = this.navigate(route);
    }

    get hash() {
        return window.location.hash;
    }

    set hash(newHash) {
        window.location.hash = newHash;
    }

    // set hashObject(hash) {
    //     let decoded = this.hashObject;
    //     let object = hash;
    //     switch(typeof hash){
    //         case "string":
    //             object = hash.split("/");
    //             break;
    //     }
    //     decoded = {
    //         ...decoded,
    //         ...hash
    //     };
    //     window.location.hash = this.serializeHashObject(decoded);
    // }

    // get hashObject() {
    //     return this.deserializeHashObject(window.location.hash);
    // }

    // deserializeHashObject(hash) {
    //     const encoded = hash;
    //     let arr = encoded.split("/");
    //     const decoded = {};
    //     for(let i = 0; i >= arr.length; i += 2) {
    //         decoded[arr[i]] = arr[i + 1];
    //     }
    //     return decoded;
    // }

    // serializeHashObject(object) {
    //     let string = "";
    //     for(let i = 0; i >= Object.keys(object).length; i += 2) {
            
    //     }
    // }

    get routeBoundaries() {
        return this.properties.routeBoundaries;
    }

    async navigate(route) {
        const forms = document.querySelectorAll("form-request");
        for(const f of forms) {
            if(f.unsavedChanges) {
                const conf = await dialogConfirm("This form has unsaved changes. Continue?", "Continue", "Stay on this page");
                if(!conf) return;
            }
        }

        const navStartEvent = new CustomEvent("navigationstart", {detail: {route}});
        const navStartEventResult = this.dispatchEvent(navStartEvent);
        document.dispatchEvent(navStartEvent);
        if(navStartEvent.defaultPrevented) return;

        const current = history.state;
        history.replaceState(
            {
                title: document.title,
                url: current.url,
                scrollY: window.scrollY,
                scrollX: window.scrollX,
            }, 
            '',
            current.url
        );

        this.progressBar.classList.add("navigation-start");

        if(this.firstRun) {
            this.firstRun = false;
            this.dispatchEvent(new CustomEvent("navigateend", {detail: {previous: this.previousRoute, next: route, pageData: window.__}}));
            this.navigationFinalize(route, window.__);
            this.progressBar.classList.remove("navigation-start");

            return;
        }
        
        // Retrieve the route HTML
        const rt = `${route.currentRoute}${(route.URL.search) ? "&" + route.URL.search.substring(1) : ""}`;
        const apiRoute = `/api/v1/page/?route=${rt}`;
        const api = new AsyncFetch(apiRoute, "GET", {});
        api.addEventListener("progress", e => {
            if(!this.progressBar) return;
            this.progressBar.value = e.detail.progress.loaded;
            this.progressBar.max = e.detail.progress.total;
        });
        let result = {};
        try {
            if(!this.skipRequest) result = await api.submit();
            this.lastPageRequestResult = result;
        } catch(error) {
            this.progressBar.classList.remove("navigation-start");
            this.dispatchEvent(new CustomEvent("navigateerror", {detail: {error, route: this.route}}));
            this.lastPageRequestResult = {};
            return;
        }
        
        this.dispatchEvent(new CustomEvent("navigateend", {detail: {previous: this.previousRoute, next: route, pageData: result}}));

        this.updateContent(result);
        this.updateScroll();
        if(!this.allowStateChange) {
            this.setPushStateMode();
            return;
        }
        
        history[this.historyMode]({
                title: document.title,
                url: route.originalRoute,
                scrollY: window.scrollY,
                scrollX: window.scrollX,
            }, '', route.originalRoute
        );

        this.setPushStateMode();
        
    }

    updateScroll() {
        // Prevent smooth scrolling when transitioning pages
        document.body.parentNode.style.scrollBehavior = "auto";

        let scrollX = 0;
        let scrollY = 0;
        if(this.lastLocationChangeEvent.type === "popstate") {
            scrollX = this.lastLocationChangeEvent.state.scrollX;
            scrollY = this.lastLocationChangeEvent.state.scrollY;
        }
        
        window.scrollTo(scrollX, scrollY);
        // Restore initial scrolling behavior now that we've scrolled
        document.body.parentNode.style.scrollBehavior = '';
    }

    replaceState(location, {
        target = "main",
        updateProperty = "innerHTML",
        skipRequest = false,
        skipUpdate = false
    } = {}) {
        this.historyMode  = "replaceState";
        this.updateTarget = (typeof target === "string") ? document.querySelector(target) : target;
        this.updateProperty = updateProperty;
        this.skipRequest = skipRequest;
        this.skipUpdate  = skipUpdate;
        this.lastLocationChangeEvent = {
            type: "replaceState"
        }
        return new Promise(resolve =>{
            this.addEventListener("navigateend", e => resolve(e.detail), {once: true});
            this.location = location;
        });
    }

    // pushState(location, {
    //     target = "main", 
    //     updateProperty = "innerHTML", 
    //     skipRequest = false, 
    //     skipUpdate = false
    // } = {}) {
    //     this.setPushStateMode();
    //     this.historyMode  = "pushState";
    //     this.updateTarget = (typeof target === "string") ? document.querySelector(target) : target;
    //     this.updateProperty = updateProperty;
    //     this.skipRequest = skipRequest;
    //     this.skipUpdate  = skipUpdate;
    //     return new Promise(resolve =>{
    //         this.addEventListener("navigateend", e => resolve(e.detail), {once: true});
    //         this.location = location;
    //     });
    // }

    setPushStateMode() {
        this.historyMode  = "pushState";
        this.updateTarget = document.querySelector("main");
        this.updateProperty = "innerHTML";
        this.skipRequest = false;
        this.skipUpdate  = false;
        this.lastLocationChangeEvent = {}
    }

    navigationFinalize(route, pageData) {
        document.body.classList.remove();
        this.progressBar.classList.remove("navigation-start");
        this.dispatchEvent(new CustomEvent("load", {detail: {route, pageData}}));
        document.dispatchEvent(new CustomEvent("navigationEvent")); // Backwards compatibility
        
        const navLinks = document.querySelectorAll("nav a");
        if(!navLinks) return;
        for(const i of navLinks) {
            i.classList.remove("navigation--current");
            const href = i.getAttribute("href");
            
            if(href === route.currentContext) {
                if(route.isBoundaryRoot) i.classList.add("navigation--current");
                continue;
            }

            const regex = new RegExp(`(${i.getAttribute("href")})`);
            const match = route.URL.toString().match(regex);
            if(!match) continue;
            if(match.length <= 0) continue;
            i.classList.add("navigation--current");
        }
    }

    updateContent(pageData, query = this.updateTarget) {
        document.title = pageData.title || app("app_name");
        let main;
        if(typeof query === "string") main = document.querySelector(query);
        else main = query;
        main.id = pageData.main_id || "main";
        main[this.updateProperty] = pageData.body || "";

        this.applyLinkListeners();
        this.applyFormListeners();

        this.navigationFinalize(this.route, pageData);
        this.updateSrSkipToContent(`${document.title}. Skip to content`);
    }

    updateSrSkipToContent(mainTarget = null, setFocus = true) {
        if(mainTarget === null) mainTarget = document.querySelector("main");
        const skipToContent = document.querySelector("#sr-skip-to-content");
        skipToContent.href = `#${mainTarget.id}`;
        if(setFocus) skipToContent.focus({});
    }

    initListeners() {
        this.addEventListener("navigateend", e => {
            if(!this.previousRoute) return false;
            if("callbacks" in this.previousRoute === false) return false;
            // if("exit_callback" in this.previousRoute.callbacks) this.previousRoute.callbacks.exit_callback(...this.previousRoute.variables);
            if("onnavigateend" in this.previousRoute.callbacks) this.previousRoute.callbacks.onnavigateend(e, ...this.previousRoute.variables);
        });

        this.addEventListener("load", e => {
            if(!this.route) return false;
            if("callbacks" in this.route === false) return false;
            // if("navigation_callback" in this.route.callbacks) this.route.callbacks.navigation_callback(...this.route.variables);
            if("onload" in this.route.callbacks) this.route.callbacks.onload(e, ...this.route.variables);
        });

        this.addEventListener("navigationstart", e => {
            if(!this.route) return false;
            if("callbacks" in this.route === false) return false;

            if("beforeunload" in this.route.callbacks) this.route.callbacks.beforeunload(e, ...this.route.variables);
            return false;
        });

        this.addEventListener("navigateerror", e => {
            if(!this.route) return false;
            if("callbacks" in this.route === false) return false;
            if("onnavigateerror" in this.route.callbacks) this.route.callbacks.onnavigateerror(e, e.detail.error);
        });

        /**
         * This listener intercepts the browser history events and applys them
         */
        window.addEventListener("popstate", e => {            
            if(!e.state) return;

            if("modalState" in e.state) return;
            if("url" in e.state === false) return;
            if("alwaysReloadOnForward" in e.state === false);

            this.lastLocationChangeEvent = {type: e.type, state: e.state, detail: e.detail || {}, target: e.target || e.currentTarget || e.explicitTarget};
            this.location = e.state.url;
        });

        this.applyLinkListeners();
        this.applyFormListeners();

        if(this.mode !== "spa") return;
        this.progressBar = document.createElement("progress");
        this.progressBar.classList.add("spa-loading-indicator");
        document.body.prepend(this.progressBar);
    }

    applyLinkListeners() {
        const links = document.querySelectorAll("a");
        for( const link of links ){
            link.removeEventListener("click", this.linkClick);
            link.addEventListener("click", this.linkClick);
        }
    }

    linkClick(e) {
        const target = e.currentTarget || e.target || e.explicitTarget;
        if(target.getAttribute("href")[0] === "#") return true;
        if(e.ctrlKey) return true;
        if(e.button !== 0) return true;
        this.lastLocationChangeEvent = {type: e.type, detail: e.detail || {}, target: e.target || e.currentTarget || e.explicitTarget};
        e.preventDefault();
        window.Cobalt.router.location = target.href;
        return false;
    }

    applyFormListeners() {
        const forms = document.querySelectorAll("form");
        for( const form of forms ) {
            form.removeEventListener("submit", this.submitListener);
            form.addEventListener("submit", this.submitListener);
        }
    }

    submitListener(e) {
        this.lastLocationChangeEvent = {type: e.type, detail: e.detail || {}, target: e.target || e.currentTarget || e.explicitTarget};
        e.preventDefault();
        window.Cobalt.router.location = e.target.action || e.currentTarget.action || e.explicitTarget.action;
        return false;
    }

    get allowStateChange() {
        const event = this.lastLocationChangeEvent;
        if(!event) return true;
        if("type" in event === false) return true;
        if(event.type === "popstate") {
            this.debug("The last event caused was a popstate, disallowing a popstate change")
            return false;
        }
        return true;
    }

    debug(message) {
        if(pref("debug_router")) console.warn(message);
    }
}

window.Cobalt.router = new ClientRouter();
window.Cobalt.router.location = window.location.toString();