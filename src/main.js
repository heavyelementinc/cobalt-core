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
        this.header = document.querySelector("#nav-menu-spawn-nojs + header");
        this.checkbox = document.querySelector("#nav-menu-spawn-nojs");
        this.menuButton = document.querySelector("#nav-menu-spawn");
        this.name = "js-nav-spawned";

        this.submenuStack = [];
        this.activeClass = "directory--submenu--active";
        this.previousClass = "diretoy--submenu--previous";

        // Let's make the menu visible if the box is checked when loading the page
        if (this.checkbox.checked) this.updateState();
    
        // When the button's clicked
        this.checkbox.addEventListener("input", e => this.updateState());

        this.initMobileNav();

        if(app("Mobile_nav_menu_closes_on_anchor_link_click")) this.anchorLinkListeners();
        
    }

    initMobileNav() {
        const isMobile = window.matchMedia("(max-width: 35em)").matches;
        if(!isMobile) return;
        
        const submenus = this.header.querySelectorAll(".directory--submenu");
        
        for(const sub of submenus) {
            this.createSubmenu(sub);
        }
    }

    createSubmenu(ul) {
        // Set up containers
        const container = document.createElement("div");
        container.innerHTML = `<div class='header'><button class='back-button'></button></div>`;
        container.classList.add("mobile-navigation--submenu-container");
        const backButton = container.querySelector("button.back-button");
        backButton.addEventListener("click", () => this.back());
        const li = document.createElement("li");
        ul.prepend(li);
        // Find our parent anchor and then attach a clone of it
        // to the li element we created
        const anchor = ul.parentNode.querySelector("a");
        li.appendChild(anchor.cloneNode(true));

        // Prevent all stuff from happening and display our container
        anchor.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            this.activeSubmenu(container);
        });
        anchor.classList.add("mobile-navigation--initiator");
        container.appendChild(ul);
        this.header.appendChild(container);
    }

    activeSubmenu(container) {
        const previous = this.submenuStack[this.submenuStack.length - 1] || null;
        this.submenuStack.push(container);
        this.cleanupSubmenuClasses();
        if(previous) previous.classList.add(this.previousClass);
        container.classList.add(this.activeClass);
    }

    back() {
        const previous = this.submenuStack.pop();
        this.cleanupSubmenuClasses();
        const next = this.submenuStack[this.submenuStack.length - 1] || null;
        previous.classList.add(this.previousClass);
        if(next) next.classList.add(this.activeClass);
    }

    cleanupSubmenuClasses() {
        const menus = this.header.querySelectorAll(`.${this.activeClass}, .${this.previousClass}`);
        menus.forEach(e => e.classList.remove(this.activeClass,this.previousClass));
    }

    freezeBodyContent() {
        // Add "nav spawned" class to document list.
        document.body.parentNode.classList.add(this.name);
        let width = get_offset(document.body.parentNode).w;
        document.body.parentNode.style.overflow = "hidden";
        document.body.parentNode.style.width = `${width}px`
        console.warn("Body content frozen");
    }

    releaseBodyContent() {
        document.body.parentNode.classList.remove(this.name);
        document.body.parentNode.style.overflow = "unset";
        document.body.parentNode.style.width = "unset";
        this.cleanupSubmenuClasses();
        this.submenuStack = [];
        console.warn("Body content unfrozen");
    }

    updateState() {
        if (!this.checkbox.checked) this.releaseBodyContent();
        else this.freezeBodyContent();
    }

    accessibility(state = true) {
        if (this.checkbox.style.display !== "none") {
            this.menuButton.checked = state;
            this.menuButton.dispatchEvent(new Event("change"));
            this.updateState();
        }
    }

    anchorLinkListeners() {
        const headerLinks = this.header.querySelectorAll('a');

        for(const i of headerLinks) {

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
