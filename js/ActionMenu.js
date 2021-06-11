class ActionMenu {
    constructor({ event, title = "", mode = null, withIcons = true }) {
        // Only one instance of a menu is allowed on a single page
        if (window.menu_instance) {
            window.menu_instance.closeMenu();
            window.menu_instance = null;
        }
        this.event = event;
        this.title = title;
        this.mode = mode || (window.matchMedia("only screen and (max-width: 900px)").matches) ? "modal" : "element";
        this.withIcons = withIcons;
        this.toggle = false;
        /** @property the list of actions to display in the menu */
        this.actions = [];
        /** @property the default properties of a single action */
        this.actionDefaultProperties = {
            label: "{{Default}}",
            icon: null,
            dangerous: false,
            callback: async (element, event) => {
                return true; // Return true to dismiss menu
            }
        }
        this.wrapper = null;
        this.menu = null;
        this.menuList = document.createElement('action-menu-items');
    }

    registerAction(action) {
        action = { ...this.actionDefaultProperties, ...action }
        this.actions.push(action);
    }

    renderAction(action) {
        const button = document.createElement('button');
        if (this.withIcons) button.innerHTML = action.icon;
        button.append(action.label);
        if (action.dangerous) button.classList.add("action-menu-item--dangerous");
        button.addEventListener("click", (ev) => {
            this.handleAction(action, ev);
        });
        this.menuList.appendChild(button);
        document.addEventListener("click", this.handleClickOut);
    }

    draw() {
        this.menu = document.createElement("action-menu")
        let header = document.createElement("header")
        header.innerHTML = `<h1>${this.title}</h1><button>✖️</button>`
        this.menu.appendChild(header);
        header.querySelector("button").addEventListener("click", (e) => this.closeMenu())
        for (const i of this.actions) {
            this.renderAction(i);
        }
        this.menu.appendChild(this.menuList);

        this.wrapper = document.createElement('action-menu-wrapper');
        this.wrapper.appendChild(this.menu);
        this.wrapper.classList.add(this.mode);
        document.querySelector('body').appendChild(this.wrapper);
        window.menu_instance = this;

        this.positionMenu();
    }

    async handleAction(action, event) {
        let result = await action.callback(action, event);
        if (result === true) this.closeMenu();
    }

    /** Close this menu */
    closeMenu() {
        document.body.classList.remove("scroll-locked");
        if ("parentNode" in this.wrapper && this.wrapper.parentNode)
            this.wrapper.parentNode.removeChild(this.wrapper);
        this.clearEvent();
    }

    clearEvent() {
        document.removeEventListener("click", this.handleClickOut)
    }

    handleClickOut(e) {
        // If the spawning target is also the current event target:
        if (window.menu_instance.event.target === e.target && window.menu_instance.toggle === false) {
            window.menu_instance.toggle = true;
            return;
        }

        // If we're in modal mode and the event is on the wrapper
        if (window.menu_instance.mode === "modal" && e.target === window.menu_instance.wrapper)
            window.menu_instance.closeMenu();

        // If the target has an action-menu-wrapper ancestor don't close
        if (e.target.closest("action-menu-wrapper")) return;
        window.menu_instance.closeMenu();
    }

    positionMenu() {
        if (this.mode === "modal") {
            document.body.classList.add("scroll-locked")
            return;
        }
        let menuWidth = this.wrapper.offsetWidth;
        let menuHeight = this.wrapper.offsetHeight;
        let scrollTop = (document.documentElement || document.body.parentNode || document.body).scrollTop;
        let viewportWidth = window.innerWidth;
        let viewportHeight = window.innerHeight;
        let originX = this.getAbsolutePosition("left");
        let originY = this.getAbsolutePosition("top");
        if ((originX + menuWidth + 5) >= viewportWidth) originX -= menuWidth;
        if (((originY + menuHeight + 5)) >= (viewportHeight + scrollTop)) originY -= menuHeight;
        this.wrapper.style.left = `${originX}px`;
        this.wrapper.style.top = `${originY}px`;
    }

    getAbsolutePosition(type) {
        let translation = {
            left: 'X',
            top: 'Y'
        }
        if (this.event.target.tagName === "BUTTON" || this.mode === "spawn")
            return this.getAbsolutePositionElement(type)
        return this.event['page' + translation[type]];
    }

    getAbsolutePositionElement(type) {
        let translation = {
            left: 'Left',
            top: 'Top'
        }
        let offset = this.event.target[`offset${translation[type]}`];
        if (type === "top") offset += this.event.target.offsetHeight;
        return offset;
    }
}