var navigationEvent = new CustomEvent(
    "navigationEvent",
    {
        detail: {
            route: window.location
        },
        bubbles: true,
        cancelable: true
    }
);

document.dispatchEvent(navigationEvent);

function user_menu() {
    const menu_button = document.querySelector("#user-menu-button");
    if (!menu_button) return;
    const menu_container = document.querySelector("#user-menu-container");
    menu_container.style.top = `${document.querySelector("header").offsetHeight}px`
    flyoutHandler(menu_button, menu_container);

    const sign_out = document.querySelector("#main-menu-sign-out");
    const menu = new UserMenu(menu_container);
}

user_menu();

class MobileNavMenu{
    constructor () {
        this.header = document.querySelector("#nav-menu-spawn-nojs + header"),
        this.checkbox = document.querySelector("#nav-menu-spawn-nojs"),
        this.menuButton = document.querySelector("#nav-menu-spawn"),
        this.name = "js-nav-spawned"
        if (this.checkbox.checked) {
            this.menuVisible();
        }
    
        // When the button's clicked
        this.checkbox.addEventListener("input", e => this.updateState());

        if(app("Mobile_nav_menu_closes_on_anchor_link_click")) {
            console.log("Anchor links in the header will close the mobile nav menu")
            this.anchorLinkListeners();
        }
    }

    freezeBodyContent() {
        // Add "nav spawned" class to document list.
        document.body.classList.add(this.name);
        let width = get_offset(document.body).w;
        document.body.style.overflow = "hidden";
        document.body.style.width = `${width}px`
        console.warn("Body content frozen");
    }

    releaseBodyContent() {
        document.body.classList.remove(this.name);
        document.body.style.overflow = "unset";
        document.body.style.width = "unset";
        console.warn("Body content unfrozen");
    }

    updateState() {
        console.log(this.checkbox.checked);
        if (!this.checkbox.checked) this.releaseBodyContent();
        else this.freezeBodyContent();
    }

    accessibility(state = true) {
        console.log(state);
        if (this.checkbox.style.display !== "none") {
            this.menuButton.checked = state;
            this.menuButton.dispatchEvent(new Event("change"));
            this.updateState();
        }
    }

    anchorLinkListeners() {
        const headerLinks = this.header.querySelectorAll('a');

        for(const i of headerLinks) {
            console.log(i.href, i.href.indexOf("#"));

            if(i.href.indexOf("#") === -1) continue;

            i.addEventListener("click", () => this.close());
        }
    }

    open() {
        this.checkbox.checked = true;
        this.updateState()
    }

    close() {
        this.checkbox.checked = false;
        this.updateState()
    }
}

mobile_nav = new MobileNavMenu();
