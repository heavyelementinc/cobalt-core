class ActionMenuElement extends HTMLElement {
    constructor() {
        super();
        this.tabIndex = 0;
        this.options = this.querySelectorAll("option");
        this.menuId = random_string();
        this.menu = null;
        this.setAttribute("arai-pressed", "false");
        this.setAttribute("aria-role", "button");
        this.initListeners();
    }

    initListeners() {
        this.addEventListener("click", this.toggleButton);
        this.addEventListener("keyup", this.toggleButtonWithKeypress);
    }

    toggleButton(event) {
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
        let action = {
            label: opt.innerHTML || "Default",
            icon: `<i name="${opt.getAttribute("icon")}"></i>`,
            dangerous: opt.hasAttribute("dangerous"),
            disabled: opt.hasAttribute("disabled")
        }

        if(opt.onclick) action.callback = opt.onclick;
        if(opt.hasAttribute("action")) {
            action.request = {
                action: opt.getAttribute("action"),
                method: opt.getAttribute("method") ?? "POST"
            }
        }
        
        return action;
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
