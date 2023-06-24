class ActionMenuElement extends HTMLElement {
    constructor() {
        super();
        this.tabIndex = 0;
        this.options = this.querySelectorAll("option");
        this.type = this.getAttribute("type");
        this.menuId = random_string();
        this.menu = null;
        this.stopPropagation = this.hasAttribute("stop-propagation");
        this.setAttribute("aria-pressed", "false");
        this.setAttribute("aria-role", "button");
        this.initListeners();
    }

    initListeners() {
        this.addEventListener("click", this.toggleButton);
        this.addEventListener("keyup", this.toggleButtonWithKeypress);
    }

    toggleButton(event) {
        if(this.stopPropagation) {
            event.stopPropagation();
            event.stopImmediatePropagation();
            event.preventDefault();
        }
        if(this.getAttribute("aria-pressed") === "true") {
            this.menu.closeMenu();
            this.menu = null;
            this.setAttribute("aria-pressed", "false");
            return;
        }
        this.menu = new ActionMenu({
            event,
            title: this.title,
            // withIcons: true,
            attachTo: this,
            closeCallback: () => {
                this.menu = null;
                this.setAttribute("aria-pressed", "false");
            },
            menuClasses: ["action-menu-element-toggled"]
        });
        this.getOptions();
        this.menu.draw();
        this.setAttribute("aria-pressed", "true");
    }

    getOptions() {
        this.menu.actions = [];
        this.options = this.querySelectorAll("option");
        for(const opt of this.options) {
            this.menu.registerAction(this.actionFromOption(opt));
        }
    }

    actionFromOption(opt) {
        const icon = opt.getAttribute("icon");
        let action = {
            label: opt.innerHTML || "Default",
            icon: (icon) ? `<i name="${icon}"></i>` : "",
            onloadstart: event => 
                this.triggerEvent(opt, "loadstart", event), // <- loadstart has worked a few times...
            onload:  event => 
                this.triggerEvent(opt, "load", event),
            onerror: event => 
                this.triggerEvent(opt, "error", event), // <- error has worked 
            onclick: event => 
                this.triggerEvent(opt, "click", event), // <- Triggers correctly
            dangerous: opt.hasAttribute("dangerous"),
            disabled: opt.hasAttribute("disabled"),
            original: opt
        }

        // if(opt.onclick) action.callback = opt.onclick;
        if(opt.hasAttribute("action")) {
            let json = opt.getAttribute("value") ?? {};
            // try {
            //     json = JSON.parse(opt.getAttribute("value"));
            // } catch (error) {
            //     json = opt.getAttribute("value");
            // }
            action.request = {
                action: opt.getAttribute("action"),
                method: opt.getAttribute("method") ?? "POST",
                value: json,
            }
        }
        
        return action;
    }

    triggerEvent(option, type, event, custom = false) {
        let event_object = null;
        if(custom) event_object = new CustomEvent(event.type, {detail: {option, event}});
        else event_object = new Event(event.type, {detail: {option, event}});
        option.dispatchEvent(event_object);
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
