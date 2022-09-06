class Router {
    constructor() {
        window.router_entities = {};
        this.isSPA = app().SPA;

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

        this.navigationStarted = false;

        this.linkSelector = "a:not([is])";
        this.formSelector = "form.cobalt-query-controls";
        this.mainContent = document.querySelector("main");

        document.addEventListener("navigationEvent", (e) => {
            // if (this.first_run) return;
            console.info("Navigation event")
            this.navigation_event(e);
            this.find_current_navlist_item();
        });

        this.initialize_SPA_navigation(true);

        document.dispatchEvent(new CustomEvent("navigationEvent"));
    }

    get route() {
        return this.current_route;
    }

    get location() {
        return this.current_route;
    }

    /** @todo Make the router handle smooth transitioning and change this! */
    set location(value) {
        console.info("Upading location via router location set method");
        if(!this.isSPA) {
            window.location = value;
            this.navigation_event(false, value);
            return;
        }
        this.handleSPANavigation(value);
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

    initialize_SPA_navigation(allLinks = null) {
        // Don't do anything if we're not in SPA mode.
        if(!this.isSPA) return;
        
        if(allLinks === null) allLinks = this.first_run;

        // Select the appropriate anchor tags
        let links;
        let forms;
        if(allLinks) {
            links = document.querySelectorAll(this.linkSelector);
            window.addEventListener("popstate",(event) => {
                console.info("Popstate firing");
                this.handle_SPA_navigation(event.state.url, event);
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
            i.addEventListener("submit", (event) => {
                event.preventDefault();
                if(i.method.toLowerCase() !== "get") return;
                console.info("Submit firing");
                let formData = new FormData(i);
                let submitter = {};
                if(event.submitter.name && event.submitter.value) submitter[event.submitter.name] = event.submitter.value;
                const params = new URLSearchParams({...formData, ...submitter}).toString();
                const search = new URL(i.action).search.toString();
                let location = i.action;
                if(search) location = i.action.replace(search,"");
                
                if(params) {
                    // this.location = `${location}?${params}`;
                    this.handle_SPA_navigation(`${location}?${params}`, event);
                }
                return false;
            });
        }
    }

    handleClick(element, event){
        event.preventDefault();
        console.info("Link Click Event Fired");
        this.handle_SPA_navigation(element.href, event);
        // element.addEventListener(event => {this.handleClick(element,event)},{once: true});
        return false;
    }

    async handle_SPA_navigation(url, event = {}) {
        // Parse the URL
        const urlData = this.getUrlData(url);
        if(!urlData.isLocal) window.location = url;

        // Set up to execute our fetch request from the API.
        const pageLoad = new ApiFetch(`/api/v1/page/?route=${urlData.pathname}${urlData.apiSearchParams}`,"GET", {});

        let result;
        try{
            result = await pageLoad.get();
        } catch (error) {
            console.warn("There was an error");
        }
        

        if(this.route_directives && "exit_callback" in this.route_directives) {
            console.info("Firing exit callback");
            this.route_directives.exit_callback(...this.route_args, result);
        }
        window.__ = result;

        if("type" in event && event.type !== "popstate") {
            history.pushState({
                title: result.title ?? "",
                url: url
            },'',url);
        }
        document.title = await result.title ?? "";

        document.dispatchEvent(new CustomEvent("navigationEvent"));
        
        this.mainContent.innerHTML = result.body;
        this.initialize_SPA_navigation(false);
    }

    getUrlData(url) {
        const parsed = new URL(url);
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

}

var router = new Router();