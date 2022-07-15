class ActionMenu {
    constructor({ event, title = "", mode = null, withIcons = true }) {
        // Only one instance of a menu is allowed on a single page
        if (window.menu_instance) {
            window.menu_instance.closeMenu();
            window.menu_instance = null;
        }
        event.preventDefault();
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
            request: {
                // Specify an endpoint and an action
                // method: "POST",
                // action: "/api/v1/some/endpoint"
            },
            callback: async (element, event, asyncRequest) => {
                return true; // Return true to dismiss menu
            },
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
        const label = document.createElement("span");
        label.innerText = action.label;
        button.appendChild(label);

        if (action.dangerous) button.classList.add("action-menu-item--dangerous");
        button.addEventListener("click", (ev) => {
            this.handleAction(action, ev);
        });
        this.menuList.appendChild(button);
        document.addEventListener("click", this.handleClickOut);
    }

    async draw() {
        this.menu = document.createElement("action-menu")
        let header = document.createElement("header")
        header.innerHTML = `<h1>${this.title}</h1><button>${window.closeGlyph}</button>`
        this.menu.appendChild(header);

        header.querySelector("button").addEventListener("click", (e) => this.closeMenu())
        // await wait_for_animation("action-menu--deploy");
        // const api = new ApiFetch("", "GET");
        // const options = await api.send("");

        for (const i of this.actions) {
            this.renderAction(i);
        }
        this.menu.appendChild(this.menuList);

        this.wrapper = document.createElement('action-menu-wrapper');
        this.wrapper.appendChild(this.menu);
        this.wrapper.classList.add(this.mode);
        document.querySelector('body').appendChild(this.wrapper);

        window.menu_instance = this;

        await reflow();

        const spawnIndex = spawn_priority(this.event);
        if (spawnIndex) this.menu.style.zIndex = spawnIndex + 1;

        this.positionMenu();
    }

    /**
     * 
     * @param {object} action - The parameters of the action
     * @param {event} event - The event that triggered the callback
     */
    async handleAction(action, event) {
        let spinner = event.originalTarget.closest("button").querySelector("loading-spinner");
        if (spinner == null) spinner = document.createElement("loading-spinner");

        action.loading = {
            start: () => {
                event.originalTarget.closest("button").appendChild(spinner);
            },
            end: () => {
                spinner.parentElement.removeChild(spinner)
            },
            error: (errorMessage) => {
                spinner.innerHTML = `<ion-icon name='warning' style='color:red;pointer-events:none;'></ion-icon>`;
                console.log(errorMessage.error);
                spinner.title = errorMessage
            }
        }
        action.loading.start()
        let result = null;
        let requestData = null;
        const api = new ApiFetch(action.request.action, action.request.method,{});
        if("method" in action.request && "action" in action.request) {
            try {
                requestData = await api.send();
            } catch (error) {
                console.log(api);
                action.loading.error(error);
                new StatusError({message: api.result.message, icon: "ion-warning"});
                return;
            }
        }
        try {
            result = await action.callback(action, event, requestData);
        } catch (error) {
            console.log(error);
            console.log(requestData);
            action.loading.error(error);
            new StatusError({message: requestData.error, icon: "ion-warning"});
            return;
        }
        action.loading.end();
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