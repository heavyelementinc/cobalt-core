class ActionMenuElement extends CustomButton {
    constructor() {
        super();
        this.tabIndex = 0;
        this.options = this.querySelectorAll("option");
        this.type = this.getAttribute("type");
        
        this.stopPropagation = this.hasAttribute("stop-propagation");
        this.state = false;
        this.setAttribute("aria-role", "button");
        this.setAttribute("__custom-input", "true");
    }

    connectedCallback() {
        super.connectedCallback();
        this.menu = new ActionMenu(this, this.type);

        this.menu.title = this.title ?? "Edit";
        this.menu.mode = this.mode;
        this.getOptions();

        this.addEventListener("click", event => {
            if(this.stopPropagation) {
                event.stopPropagation();
                event.stopImmediatePropagation();
            }
            // else this.menu.open();
            // const currentState = this.menu.isOpen;
            // if(currentState) return;
            if(this.state) this.close();
            else this.open();
            return true;
        });

        this.menu.addEventListener("actionmenustate", event => {
            this.dispatchEvent(new CustomEvent("actionmenustate", {detail: event.detail}));
            if(event.detail.open) this.state = true;
            if(!event.detail.open) this.state = false;
        });

        this.menu.addEventListener("actionmenurequest", event => {
            this.dispatchEvent(new CustomEvent("actionmenurequest", {detail: event.detail}));
        });

        this.menu.addEventListener("actionmenuselect", event => {
            this.dispatchEvent(new CustomEvent("actionmenuselect", {detail: event.detail}));
        });
    }

    disconnectedCallback() {
        this.menu.close();
        if(this.menu.wrapper?.parentNode
        && "deleteChild" in this.menu.wrapper.parentNode) {
            this.menu.wrapper.parentNode.deleteChild(this.menu.wrapper);
        }
        delete this.menu;
    }

    attributeChangedCallback(name, oldValue, newValue) {
        switch(name) {
            case "type":
                this.menu.type = newValue;
                break;
        }
    }

    get mode() {
        return this.getAttribute("mode") ?? "popover";
    }

    set mode(value) {
        this.menu.mode = value;
        this.setAttribute("mode", value);
    }

    get stopPropagation() {
        return JSON.parse(this.getAttribute("stop-propagation"))
    }

    set stopPropagation(value) {
        if(typeof value !== "boolean") throw new TypeError("Must be a boolean value");
        this.setAttribute("stop-propagation", JSON.parse(value));
    }

    get title() {
        return this.dataset.title ?? this.getAttribute("title");
    }

    get state() {
        return (this.ariaPressed === "true") ? true : false;
    }

    set state(value) {
        this.setAttribute("aria-pressed", (value) ? "true" : "false");
    }
    
    open() {
        this.state = "true";
        this.menu.open();
    }

    close() {
        this.state = "false";
        this.menu.close();
    }

    getOptions() {
        this.menu.actions = [];
        this.options = this.querySelectorAll("option");
        for(const opt of this.options) {
            this.actionFromOption(opt);
        }
    }

    /**
     * Supported options
     * * icon
     * * label
     * * href - A page to link to 
     *   * target - "_blank"
     * * action - a route to dispatch a request to
     *   * method - GET, POST, PUT, DELETE
     *   * value  - JSON string
     * * onclick
     * * onload
     * * onloadstart
     * * onerror
     * * dangerous
     * * disabled
     * @param {HTMLOptionElement} opt 
     * @returns 
     */
    actionFromOption(opt) {
        const icon = opt.getAttribute("icon");
        const action = this.menu.registerAction();
        action.option = opt;

        action.label = opt.innerHTML ?? "Default";
        action.icon = icon;
        
        action.button.addEventListener("click", event => {
            if(this.stopPropagation) event.stopPropagation();
            this.triggerEvent(opt, "click", event, false, action)
        });
        action.button.addEventListener("load", event => {
            this.triggerEvent(opt, "load", event, false, action)
        });
        action.button.addEventListener("loadstart", event => {
            this.triggerEvent(opt, "loadstart", event, true, action)
        });
        action.button.addEventListener("error", event => {
            this.triggerEvent(opt, "error", event, false, action)
        });
        
        action.dangerous = opt.hasAttribute("dangerous");
        action.disabled = (opt.hasAttribute("disabled")) ? opt.disabled : false
        action.original = opt
        
        if(opt.hasAttribute("href")) {
            action.href = opt.getAttribute("href");
            action.target = opt.getAttribute("target");
        }

        if(opt.hasAttribute("action")) {
            let json = opt.getAttribute("value") ?? "{}";
            action.requestAction = opt.getAttribute("action");
            action.requestMethod = opt.getAttribute("method") ?? "POST";
            action.requestData = JSON.parse(json);
        }
        
        return action;
    }

    triggerEvent(option, type, event, custom = false) {
        let event_object = null;
        if(custom) event_object = new CustomEvent(event.type, {detail: {option, event}});
        else event_object = new Event(event.type, {detail: {option, event}});
        option.dispatchEvent(event_object);
        const details = {};
        if(option.hasAttribute("details")) details = option.getAttribute("details");
        if(option.hasAttribute("event")) this.dispatchEvent(option.getAttribute("event"), {detail: details});
        if(option.hasAttribute("custom-event")) this.dispatchEvent(option.getAttribute("custom-event"), {detail: details});
    }

    toggleButtonWithKeypress(event) {
        switch(event.code) {
            case "Space":
            case "Enter":
            case "Return":
                this.toggleButton(event);
                break;
        }
    }
}

customElements.define("action-menu", ActionMenuElement);

/** The InlineMenu hides/shows its children when the inline-menu button is active.
 *  This is useful if you want to have visually hidden form elements, such as advanced filters for a GET request
 */
class InlineMenu extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        this.createButton();
    }

    createButton() {
        this.button = document.createElement("button",{is: "button-toggle"});
        this.button.innerHTML = `<i name="${this.getAttribute("icon") || "dots-vertical"}"></i>`;
        this.parentNode.insertBefore(this.button, this);
        this.button.setAttribute("native","native");
        this.button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            this.toggleMenu();
        });
    }

    toggleMenu() {
        this.setAttribute("status", (this.button.getAttribute("aria-pressed") === "true") ? "open" : "closed");
        const btn = get_offset(this.button);
        const box = get_offset(this);

        if(btn.x + box.x > window.visualViewport.width) {
            this.style.left = `${btn.right - box.w}px`
        } else {
            this.style.left = `${btn.x}px`;
        }

        this.style.top = `${btn.bottom}px`
        this.clickOutListener();
    }

    closeMenu() {
        this.button.setAttribute("aria-pressed", "false");
        this.toggleMenu();
    }

    openMenu() {
        this.button.setAttribute("aria-pressed", "true");
        this.toggleMenu();
    }

    clickOutListener() {
        document.body.addEventListener("click", this.clickOutFunction.bind(this), {once: true});
    }

    // clearClickOutListener() {
    //     document.body.removeEventListener("click", this.clickOutFunction.bind(this));
    // }

    clickOutFunction(event) {
        let t = event.target;
        while(t.parentNode) {
            if(t === this) {
                this.clickOutListener();
                return;
            }
            t = t.parentNode;
        }
        this.closeMenu();
    }
}

customElements.define("inline-menu", InlineMenu);
