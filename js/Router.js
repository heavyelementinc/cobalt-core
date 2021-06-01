class Router {
    constructor() {
        window.router_entities = {};
        this.navigation_items = document.querySelectorAll("header nav a, footer nav a");
        this.current_route = null;
        document.addEventListener("navigationEvent", (e) => {
            this.navigation_event(e);
            this.find_current_navlist_item();
        });
    }

    discover_route(route = null) {
        if (route === null) route = location.pathname;
        for (const route in router_table) {
            const rt = new RegExp(route.substr(1, route.length - 2));
            let match = route.match(rt);
            if (!match) continue;
            if (match.length <= 0) continue;
            match.shift();
            this.route_args = match;
            this.route_discovered = true;
            this.current_route = route;
            break;
        }
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