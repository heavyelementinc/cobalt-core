class Router {
    constructor() {
        window.router_entities = {};
        this.isSPA = app().SPA;
        this.prefersLimitedMotion = window.matchMedia("prefers-reduced-motion").matches || false;
        if(this.prefersLimitedMotion) {
            document.body.parentNode.style.scrollBehavior = "initial";
        }

        /**  */
        this.navigation_items = document.querySelectorAll("header nav a, footer nav a");

        /** @property Bool - `true` if a route was discovered */
        this.route_discovered = false;
        /** @property String - the regex pointer into the router_table */
        this.current_route = null;
        /** @property Bool - `true` on first run, false the rest of the time */
        this.first_run = true;
        /** @property Array - the results of the regex match */
        this.route_args = null;
        /** @property Object - the route directives to be used */

        this.skipToContent = document.querySelector("#sr-skip-to-content");

        /** @method or @null */
        this.navigationEventReject = null;

        this.navigationStarted = false;

        this.linkSelector = `a[href^='/']:not([is],[real]),a[href^='?']:not([is],[real]),a[href^='${location.origin.toString()}']:not([is],[real])`;
        this.formSelector = "form";
        this.mainContent = document.querySelector("main");

        document.addEventListener("navigationEvent", (e) => {
            // if (this.first_run) return;
            console.info("Navigation event")
            this.navigation_event(e);
            this.find_current_navlist_item();
        });

        if(this.isSPA) {
            this.SPA_indicator = document.createElement("progress-bar");
            this.SPA_indicator.setAttribute("no-message", "true");
            this.SPA_indicator.classList.add("spa-loading-indicator");
            document.body.prepend(this.SPA_indicator);
            this.initialize_SPA_navigation(true);
        }

        document.dispatchEvent(new CustomEvent("navigationEvent"));
    }

    get route() {
        return this.location;
    }

    get location() {
        return this.current_route || `${location.pathname}${(location.search) ? `?${location.search}` : ""}`;
    }

    /** @todo Make the router handle smooth transitioning and change this! */
    set location(value) {
        console.info("Upading location via router location set method");
        if(!this.isSPA) {
            window.location = value;
            this.navigation_event(false, value);
            return;
        }
        this.handle_SPA_navigation(value);
    }

    /**
     * @param location The location we're heading to
     */
    go() {
        let location = arguments.pop(),
            args = arguments;

        if (this.route_args) args = [...this.route_args, ...args];
        this.location = location.replace(this.route_args, args);
    }

    discover_route(route = null) {
        if (route === null) route = location.pathname;
        for (const regex in router_table) {
            const rt = new RegExp(regex);
            let match = route.match(rt);
            if (match === null) continue;
            if (match.length <= 0) continue;
            match.shift();
            this.route_args = match;
            this.route_discovered = true;
            this.current_route = regex;
            break;
        }
        this.first_run = false;
        if (this.route_discovered) this.route_directives = router_table[this.current_route];
        return this.route_discovered;
    }

    navigation_event(e = null, url = null) {
        this.route_discovered = false;
        this.current_route = null;
        this.route_directives = {};
        const result = this.discover_route(url);
        if (result && "navigation_callback" in this.route_directives) {
            console.info("Firing navigation callback");
            this.route_directives.navigation_callback(...this.route_args);
        }
    }

    find_current_navlist_item() {
        for (const i of this.navigation_items) {
            i.classList.remove("navigation--current");
            if (location.href.length < i.href.length) continue;
            if (location.pathname !== "/" && i.getAttribute('href') === "/") continue;
            if (location.href.substr(0, i.href.length) !== i.href) continue;
            i.classList.add("navigation--current");
        }
    }

    async initialize_SPA_navigation(allLinks = null) {
        // Don't do anything if we're not in SPA mode.
        if(!this.isSPA) return;
        
        
        if(allLinks === null) allLinks = this.first_run;
        if(!this.first_run) await this.scrollToTop();
        // Select the appropriate anchor tags
        let links;
        let forms;
        if(allLinks) {
            let url = location.toString();
            history.replaceState({
                title: document.title,
                url: url,
            },'',url);
            links = document.querySelectorAll(this.linkSelector);
            window.addEventListener("hashchange",(event) => {
                // console.info("Hashchange triggered", event);
                event.preventDefault();
            });
            window.addEventListener("popstate",(event) => {
                if(event.state) {
                    if("modalState" in event.state) return;
                    if("url" in event.state || "alwaysReloadOnForward" in event.state) this.handle_SPA_navigation(event.state.url, event)
                } 
                else console.log(event);
            });
            forms = document.querySelectorAll(this.formSelector);
        } else {
            links = this.mainContent.querySelectorAll(this.linkSelector);
            forms = this.mainContent.querySelectorAll(this.formSelector);
        }

        for(const i of links) {
            i.addEventListener("click", (event) => {
                this.handleClick(i, event);
            });
        }

        for(const i of forms) {
            // Listen for a submit event
            i.addEventListener("submit", (event) => {
                // If the form is not a "get" method, do nothing
                if(i.method.toLowerCase() !== "get") return;
                
                // Get our form's data
                let formData = new FormData(i);
                // Check if we need to add our button's value to the data
                let submitter = {};
                if(event.submitter.name && event.submitter.value) submitter[event.submitter.name] = event.submitter.value;
                
                // Check if we need to include another form's data
                let include = i.getAttribute("include");
                let toInclude = {};
                if(include) {
                    try {
                        include = document.querySelectorAll(include)
                    } catch(error) {

                    }
                    if(include) {
                        // Load the form's data
                        include.forEach(included => {
                            if(included.tagName !== "FORM") return;
                            toInclude = {...toInclude, ...this.formdataToObject(new FormData(included))}
                        })
                    }
                }
                
                // Create URL Query Parameters
                const params = new URLSearchParams({...submitter, ...toInclude, ...this.formdataToObject(formData)}).toString();
                const search = new URL(i.action).search.toString();

                // Get the form's action
                let location = i.action;
                if(search) location = i.action.replace(search,""); // Replace the search params with nothing
                
                // Check if we have params
                if(params) {
                    // Prevent the default submit behavior
                    event.preventDefault();
                    // Handle the location traversal with an API fetch request
                    console.info("Caught SPA form submission");
                    this.handle_SPA_navigation(`${location}?${params}`, event);
                    return;
                }
                console.warn("Aborting submit");
                return false;
                // return false;
            });
        }

        // document.body.removeChild(load);
    }

    formdataToObject(formData) {
        let object = {};
        for(const key of formData.keys()) {
            object[key] = formData.get(key);
        }
        return object;
    }

    handleClick(element, event){
        event.preventDefault();
        console.info("Link Click Event Fired");
        this.handle_SPA_navigation(element.href, event);
        // element.addEventListener(event => {this.handleClick(element,event)},{once: true});
        return false;
    }

    async handle_SPA_navigation(url, event = {}) {
        this.abortSPANavigation();
        this.SPA_indicator.classList.add("navigation-start");
        
        let state = {...history.state};
        console.log(state);
        if(state !== null) {
            // Before we move on to the next page, we need to save the scroll position
            // of the page so we can restore it in the event of a popstate
            // history.replaceState({...state, scrollPosition: window.scrollY},'',state.url);
        }

        // Parse the URL
        const urlData = this.getUrlData(url);
        if(!urlData.isLocal) window.location = url;

        // Set up to execute our fetch request from the API.
        const pageLoad = new ApiFetch(`/api/v1/page/?route=${urlData.pathname}${urlData.apiSearchParams}`,"GET", {});


        let result;
        try{
            result = await new Promise(async (resolve, reject) => {
                this.navigationEventReject = reject;
                let result;
                try{
                    result = await pageLoad.get()
                } catch(error) {
                    reject(error);
                }
                this.navigationEventReject = null;
                resolve(result);
                this.navigationEnd();
            })
        } catch (error) {
            this.navigationEnd();
            if(error = "Navigation aborted") return console.log(error);
            console.warn("There was an error");
        }
        
        window.messageHandler.closeAll();

        if(this.route_directives && "exit_callback" in this.route_directives) {
            console.info("Firing exit callback");
            this.route_directives.exit_callback(...this.route_args, result);
        }

        window.__ = result;

        if("type" in event === false || "type" in event && event.type !== "popstate") {
            history.pushState({
                title: result.title ?? "",
                url: url,
                // scrollY: window.scrollY,
                // scrollX: window.scrollX
            },'',url);
        }
        document.title = await result.title ?? "";

        this.mainContent.id = result.main_id ?? "main";
        this.skipToContent.href = result.main_id ?? "main";
        this.mainContent.innerHTML = result.body;

        this.navigationEnd();
        document.dispatchEvent(new CustomEvent("navigationEvent"));

        mobile_nav.close();

        this.initialize_SPA_navigation(false);
    }

    navigationEnd() {
        this.SPA_indicator.classList.remove("navigation-start");
        // if("scrollPosition" in history.state) window.scrollY = history.state.scrollPosition;
    }

    async abortSPANavigation() {
        if(this.navigationEventReject === null) return;
        this.navigationEventReject("Navigation aborted");
    }

    getUrlData(url) {
        let parsed;
        try {
            parsed = new URL(url);
        } catch (e) {
            parsed = new URL(`${location.origin.toString()}${url}`)
        }
        let isLocal = true;
        if(parsed.host !== location.host) isLocal = false;

        let searchParams = "";

        if(parsed.search) {
            searchParams = "&" + parsed.search.toString().substring(1);
        }

        return {
            pathname: `${parsed.pathname}`,
            apiSearchParams: searchParams,
            finalAddress: `${parsed.pathname}${parsed.search}`,
            parsed,
            isLocal,
        }
    }

    async scrollToTop() {
        // Implement screen scrolling and setting
        const scrollBehaviorStore = document.body.parentNode.style.scrollBehavior;
        
        document.body.parentNode.style.scrollBehavior = (app("SPA_smooth_scroll_on_nav")) ? "smooth" : "initial";
        await reflow();
        window.scrollTo(0,0);
        document.body.parentNode.style.scrollBehavior = scrollBehaviorStore;
        return;
    }

    updateState(data = {}) {
        const state = history.state;
        history.replaceState({...state, ...data}, '');
    }

    modalState() {
        this.updateState({modalState: true});
    }

    replaceLocation(url, title = document.title, data = {}, triggerEvent = true) {
        history.replaceState({url: url, ...data}, document.title, url);
        let check;
        try {
            check = new URL(url);
        } catch (Error) {
            try {
                check = new URL(`${origin}${url}`);
            } catch (Error) {
                console.warn("Cannot create a URL");
            }
        }
        if(check.hash && triggerEvent) {
            window.dispatchEvent(new Event("hashchange"));
        } else if (triggerEvent) {
            this.location = check;
        }
    }

}

var router = new Router();
