class Router {
    constructor() {
        window.router_entities = {};

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

        document.addEventListener("navigationEvent", (e) => {
            // if (this.first_run) return;
            this.navigation_event(e);
            this.find_current_navlist_item();
        });
    }

    get location() {
        return this.current_route;
    }

    /** @todo Make the router handle smooth transitioning and change this! */
    set location(value) {
        window.location = value;
        this.navigation_event(false, value);
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

        if (this.discover_route(url)) {
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

}

var router = new Router();