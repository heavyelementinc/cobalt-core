
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

    setRoute(route) {
        try {
            this.URL = new URL(route);
            return this._validateFromURLObject(this.URL);
        } catch (error) {
            let url = `${window.location.protocol}//${window.location.host}${this._getPathname(route)}`;
            this.URL = new URL(url);
        }
        this.isLocalRoute = true;
        this.currentContext = this.getCurrentContext(this.currentRoute);
        this.matchRouteToRouterTableEntry(this.currentRoute)
    }

    _getPathname(route) {
        if(route[0] === "/") return route;
        return `${window.location.pathname}/${route}`;
    }

    _validateFromURLObject(urlObject){
        if(urlObject.host === window.location.host) return this.setRoute(urlObject.pathname);
        this.isLocalRoute = false;
        // this.requiresReload = true;
        this.currentContext = "";
        this.regexMatch = "";
        this.variables = [];
        this.callbacks = {};
    }

    get crossesCurrentBoundary() {
        if(this.currentContext !== this.getCurrentContext(window.location.pathname)) return true;
        return false;
    }

    get isBoundaryRoot() {
        const currentBoundary = this.getCurrentContext();
        if(currentBoundary === "/" && this.URL.pathname === "") return true;
        return this.URL.pathname === currentBoundary;
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
            this.requiresReload = this.crossesCurrentBoundary;
            return;
        }

        this.variables = [];
        this.match = false;
        this.regex = null;
        this.callbacks = {};
        this.requiresReload = this.crossesCurrentBoundary;
    }
}

class ClientRouter extends EventTarget{

    constructor() {
        super();
        this.properties = {
            // location: new RouteObject(window.location.pathname),
            routeBoundaries: JSON.parse(document.querySelector("#route-boundaries").innerText)
        }
        this.lastLocationChangeEvent = {}
        this.mode = "spa";
        this.firstRun = true;
        this.initListeners();
    }

    get location() {
        return this.route;
    }

    set location(pathname) {
        const route = new RouteObject(pathname);
        if(route.URL === this.route?.URL) return console.log("This is the current route");
        this.previousRoute = this.route;
        this.route = route;
        if(!route.isLocalRoute) return window.location = pathname;
        if(route.requiresReload) return window.location = pathname;
        if(this.mode !== "spa") return window.location = pathname;

        const result = this.navigate(route);
    }

    get routeBoundaries() {
        return this.properties.routeBoundaries;
    }

    async navigate(route) {
        const navStartEvent = this.dispatchEvent(new CustomEvent("navigationstart", {detail: {route}}));
        if(navStartEvent.defaultPrevented) return;
        this.progressBar.classList.add("navigation-start");
        
        // if(this.allowStateChange) history.replaceState({
        //         title: document.title,
        //         url: window.location.toString(),
        //         scrollY: window.scrollY,
        //         scrollX: window.scrollX,
        //     }, '', window.location.toString()
        // );

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
        const result = await api.submit();
        
        this.dispatchEvent(new CustomEvent("navigateend", {detail: {previous: this.previousRoute, next: route, pageData: result}}));

        this.updateContent(result);

        if(!this.allowStateChange) return;
        
        history.pushState({
                title: document.title,
                url: route.originalRoute,
                scrollY: window.scrollY,
                scrollX: window.scrollX
            }, '', route.originalRoute
        );

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
            if(href === "/") {
                if(route.URL.pathname !== "/") continue;
                i.classList.add("navigation--current");
                continue;
            }
            const regex = new RegExp(`(${i.getAttribute("href")})`);
            const match = route.URL.toString().match(regex);
            if(!match) continue;
            if(match.length <= 0) continue;
            i.classList.add("navigation--current");
        }
    }

    updateContent(pageData) {
        document.title = pageData.title || app("app_name");

        const main = document.querySelector("main");
        main.id = pageData.main_id || "main";
        main.innerHTML = pageData.body || "";

        const skipToContent = document.querySelector("#sr-skip-to-content");
        skipToContent.href = `#${pageData.main_id}` || "#main";

        this.applyLinkListeners();
        this.applyFormListeners();

        this.navigationFinalize(this.route, pageData);
    }

    initListeners() {
        this.addEventListener("navigateend", e => {
            if(!this.previousRoute) return false;
            if("callbacks" in this.previousRoute === false) return false;
            if("exit_callback" in this.previousRoute.callbacks) this.previousRoute.callbacks.exit_callback(...this.previousRoute.variables);
        });

        this.addEventListener("load", e => {
            if(!this.route) return false;
            if("callbacks" in this.route === false) return false;
            if("navigation_callback" in this.route.callbacks) this.route.callbacks.navigation_callback(...this.route.variables);
        });

        window.addEventListener("popstate", e => {            
            if(!e.state) return;

            if("modalState" in e.state) return;
            if("url" in e.state === false) return;
            if("alwaysReloadOnForward" in e.state === false);

            this.lastLocationChangeEvent = {type: e.type, detail: e.detail || {}, target: e.target || e.currentTarget || e.explicitTarget};
            return this.location = e.state.url;
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
        const target = e.target || e.currentTarget || e.explicitTarget;
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
        if(event.type === "popstate") return false;
        return true;
    }
}

window.Cobalt.router = new ClientRouter();
window.Cobalt.router.location = window.location.toString();