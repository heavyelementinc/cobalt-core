/**
 * @emits actionmenustate
 * @emits actionmenurequest
 * @emits actionmenuselect
 */
class ActionMenu extends EventTarget {
    constructor(button = null, mode = null) {
        super();
        // Constants
        this.ACTION_MENU_CLASS = "action-menu-wrapper";
        this.ACTION_MENU_TYPES = [
            "popover",
            "modal",
        ];
        this.SCROLL_LOCK_CLASS = "scroll-locked";
        this.BUTTON_TARGET_ACTION_COMPLETE = "action-menu--work-complete";
        this.BUTTON_TARGET_ACTION_ERROR = "action-menu--work-error";

        // Properties
        this.props = {
            registeredActions: [],
            type: this.ACTION_MENU_TYPES[0], // Defaults to 'popover'
        }

        // Initialization
        this.wrapper = document.createElement("div");
        this.button = button;
        this.headlineTitle = document.createElement("h1");
        this.actionMenuItems = document.createElement("menu");
        this.closeGlyph = document.createElement("button");
        this.closeGlyph.innerHTML =  `<span class='close-glyph'></span>`;
        this.initWrapper();
        
        this.type = mode;
        // if(window.menu_instance) window.menu_instance.closeMenu()
    }

    initWrapper() {
        // Set up our wrapper
        this.wrapper.classList.add(this.ACTION_MENU_CLASS);
        this.wrapper.setAttribute("popover", "auto");
        
        // Set up our button target, if we have one
        if(this.button) {
            this.wrapper.id = random_string();
            this.button.setAttribute("popovertarget", this.wrapper.id);
        }

        this.wrapper.addEventListener("beforetoggle", event => this.beforeToggleEvent(event));
        this.wrapper.addEventListener("toggle", event => this.toggleEvent(event));

        var headline = document.createElement("div");
        headline.classList.add("action-menu-header");
        this.wrapper.appendChild(headline);
        headline.appendChild(this.headlineTitle);
        headline.appendChild(this.closeGlyph);
        this.wrapper.appendChild(this.actionMenuItems);
        // If this element lives in main, we want to place its wrapper in main,
        // otherwise, we want it in the body tag.
        this.button.closest("main,body").appendChild(this.wrapper);
        this.closeGlyph.addEventListener("mousedown", () => this.closeMenu())
    }

    get isOpen() {
        return this.wrapper.matches(":popover-open");
    }

    get title() {
        return this.headlineTile.innerText;
    }

    set title(value) {
        this.headlineTitle.innerHTML = value;
    }

    get type() {
        return this.wrapper.getAttribute("mode");
    }

    set type(value) {
        let mode = value;
        let max = this.ACTION_MENU_TYPES.length
        if(this.ACTION_MENU_TYPES.includes(value)) mode = value;
        else if(mode >= 0 && mode < max) mode = this.ACTION_MENU_TYPES[mode];
        else mode = this.ACTION_MENU_TYPES[0]

        // Check if we're in mobile mode
        if(window.matchMedia("only screen and (max-width: 35em)")) {
            mode = this.ACTION_MENU_TYPES[1];
        }
        // There can only be one type set
        this.wrapper.setAttribute("mode", mode);
        if(this.type === "modal") {
            this.button.style.setProperty("anchor-name", '');
            // this.wrapper.style.bottom = ``;
            // this.wrapper.style.right = ``;
            this.wrapper.style.top = ``;
            this.wrapper.style.left = ``;
            return;
        }
        const anchorId = `--anchor-${this.button.getAttribute("popovertarget")}`;
        this.button.style.setProperty("anchor-name", anchorId);
        this.wrapper.style.setProperty("position-anchor", anchorId);
        // this.wrapper.style.right = `anchor(${anchorId} left)`;
        // this.wrapper.style.top = `anchor(${anchorId} bottom)`;
        // this.wrapper.style.left = `anchor(${anchorId} left)`;
    }

    get mode() {
        return this.type;
    }

    set mode(value) {
        this.type = value;
    }


    beforeToggleEvent(event) {
        if(this.button) {
            this.button.classList.remove(this.BUTTON_TARGET_ACTION_COMPLETE);
        }
    }

    toggleEvent(event) {
        if(event.newState === "open") {
            this.dispatchEvent(new CustomEvent("actionmenustate", {detail: {type: "open", open: true}}));
            const supports_CSS_anchor = CSS.supports("top", "anchor(bottom)");
            if(!supports_CSS_anchor) this.positionMenu();
            return
        }

        this.dispatchEvent(new CustomEvent("actionmenustate", {detail: {type: "closed", open: false}}));
        for(const action of this.props.registeredActions) {
            if(action.actionContainer.classList.contains(action.REQUEST_STATES.COMPLETE)) action.throbberEnd();
        }
    }

    /**
     * @deprecated
     */
    draw() {
        console.warn("Calling `draw` on an ActionMenu is deprecated and will throw an error in a later release of Cobalt Engine");
        this.openMenu();
    }

    /**
     * Returns a registered action for you to manipulate. Valid properties are:
     *  -> icon - string, should be a valid MDI icon name, sets the `name` of the <i> element
     *  -> label - string, the label for this button, HTML supported
     *  -> dangerous - bool, let's the user know the action is dangerous
     *  -> disabled - bool, `true` to disable, `false` to enable
     *  -> requestMethod - ?string, the method (`POST`, `GET`, `DELETE`, etc) to 
     *  -> requestAction - ?string, the API endpoint to dispatch the request to
     *  -> requestData - mixed, the value is submitted with the API request (can be a function, return value is sent)
     *  -> callback - function, return truthy to close the menu, falsey to keep the menu open
     * @returns RegisteredAction
     */
    registerAction() {
        const index = this.props.registeredActions.length
        const action = new RegisteredAction(this, index);
        this.props.registeredActions.push(action);
        this.actionMenuItems.appendChild(action.actionContainer);
        action.actionContainer.addEventListener("click", async event => {
            const type = action.getType();
            let value = true;
            switch(type) {
                case "href":
                    window.Cobalt.router.location = action.href;
                    break;
                case "request":
                    value = await this.handleRequest(action, event)
                    break;
                case "callback":
                default:
                    if(typeof action.callback !== "function") {
                        throw new Error("callback is not a function!");
                    }
                    value = await action.callback(action.actionContainer, event, {})
                    break;
            }

            // Handle closure of this menu
            const actionmenuselect = new CustomEvent("actionmenuselect", {detail: {result: value, action: action}})
            this.dispatchEvent(actionmenuselect);
            if(actionmenuselect.defaultPrevented) return; // If the default is prevented, do nothing!
            
            if(value == true) this.closeMenu();
        });
        return action;
    }

    async handleRequest(action, event) {
        const api = new AsyncFetch(action.requestAction, action.requestMethod);
        action.throbberStart();
        let result
        try {
            result = await api.submit(action.requestData);
        } catch (e) {
            action.throbberError(e);
            return "";
        }
        action.throbberComplete(10);
        this.dispatchEvent(new CustomEvent("actionmenurequest", {detail: result}));
        value = await action.callback(action.actionContainer, event, result);
        return value;
    }

    closeMenu() {
        if(this.type === "modal") unlock_viewport();
        this.wrapper.hidePopover();
    }

    close() {
        this.closeMenu();
    }

    openMenu() {
        if(this.type === "modal") lock_viewport();
        this.wrapper.showPopover();
    }

    open() {
        this.openMenu();
    }

    toggle() {
        this.wrapper.togglePopover();
    }

    /**
     * @deprecated - As soon as Firefox and Safari support CSS anchors, we're going to delete this polyfill
     */
    positionMenu() {
        if (this.mode === "modal") {
            document.body.classList.add("scroll-locked");
            return;
        }

        let menuWidth = this.wrapper.offsetWidth;
        let menuHeight = this.wrapper.offsetHeight;
        let scrollTop = (document.documentElement || document.body.parentNode || document.body).scrollTop;
        let viewportWidth = window.innerWidth;
        let viewportHeight = window.innerHeight;
        let originX = this.getAbsolutePosition("left");
        let originY = this.getAbsolutePosition("top");
        if ((originX + menuWidth + 5) >= viewportWidth) {
            originX -= Math.abs((originX + menuWidth + 5) - viewportWidth);
            if(this.attachTo !== null) {
                // Set the origin right now since we need to not be seeing the
                // scrollbar as that throws off the attachTo X location.
                this.wrapper.style.left = `${originX}px`;
                this.wrapper.style.top = `${originY}px`;
                // reflow();
                const attached = get_offset(this.attachTo);
                originX = attached.x;
                originX -= menuWidth;
                originX += attached.w;
            } else {
            }
        }
        if (((originY + menuHeight + 5)) >= (viewportHeight + scrollTop)) {
            originY -= Math.abs((originY + menuHeight + 5)) - (viewportHeight + scrollTop);

        }
        this.wrapper.style.left = `${originX}px`;
        this.wrapper.style.top = `${originY}px`;
    }

    /**
     * @deprecated - As soon as Firefox and Safari support CSS anchors, we're going to delete this polyfill
     */
    getAbsolutePosition(type) {
        let translation = {
            left: 'X',
            top: 'Y'
        }
        return this.getAbsolutePositionElement(type);

        // return this.event['page' + translation[type]];
    }

    /**
     * @deprecated - As soon as Firefox and Safari support CSS anchors, we're going to delete this polyfill
     */
    getAbsolutePositionElement(type) {
        let target = this.button;

        switch(type) {
            case "right":
                return get_offset(target).right;
            case "left":
                return get_offset(target).x
            case "top":
                return get_offset(target).bottom;
            case "bottom":
                return get_offset(target).y;
        }

        let translation = {
            left: 'Left',
            top: 'Top'
        }

        let offset = target[`offset${translation[type]}`];

        if (type === "top") offset += target.offsetHeight;
        return offset;
    }

}

class RegisteredAction {
    constructor(menu, index) {
        this.REQUEST_STATES = {
            WORKING: "action-menu--request-working",
            COMPLETE: "action-menu--request-complete",
            ERROR: "action-menu--request-error",
        }

        this.props = {
            menu: menu,
            index: index,
            href: null,
            // label: "{{DEFAULT}}",
            // icon: null,
            dangerous: false,
            request: {
                method: null,
                action: null,
                data: (context) => {},
            },
            callback: async (element, event, asyncRequest) => {
                return true
            },
            disabled: false,
        }

        this.throbberCompleteTimeout = null;

        this.actionContainer = document.createElement("li")
        this.buttonContainer = document.createElement("button");
        this.iconContainer = document.createElement("i");
        this.labelContainer = document.createElement("label");
        this.throbberContainer = document.createElement("div");
        this.throbberContainer.classList.add("throbber");

        this.actionContainer.appendChild(this.buttonContainer);
        this.buttonContainer.appendChild(this.iconContainer);
        this.buttonContainer.appendChild(this.labelContainer);
        this.buttonContainer.appendChild(this.throbberContainer);
    }

    getType() {
        if(this.href) return "href";
        const callback = "callback";
        if(!this.requestMethod) return callback;
        if(!this.requestAction) return callback;
        return "request";
    }

    get option() {
        return this.props.option;
    }

    set option(reference) {
        this.props.option = reference;
    }

    get button() {
        return this.buttonContainer;
    }

    get icon() {
        return this.iconContainer.getAttribute("name");
    }

    set icon(value) {
        return this.iconContainer.setAttribute("name",value);
    }

    get label() {
        return this.labelContainer.innerHTML;
    }

    set label(value) {
        return this.labelContainer.innerHTML = value
    }

    get href() {
        return this.props.href;
    }
    
    set href(value) {
        this.props.href = value;
    }
    
    get dangerous() {
        const dangerous = this.buttonContainer.getAttribute("dangerous");
        if(["true", "dangerous"].indexOf(dangerous)) return true;
        return false;
    }

    set dangerous(value) {
        if(value == true) this.buttonContainer.setAttribute("dangerous", "dangerous");
        this.buttonContainer.setAttribute("dangerous", "false");
    }

    get disabled() {
        return this.buttonContainer.disabled;
    }

    set disabled(value) {
        this.buttonContainer.disabled = value;
    }

    get requestMethod() {
        return this.props.request.method;
    }

    set requestMethod(value) {
        return this.props.request.method = value;
    }
    
    get requestAction() {
        return this.props.request.action;
    }

    set requestAction(value) {
        return this.props.request.action = value;
    }
    
    get requestData() {
        const data = this.props.request.data;
        if(typeof data === "function") return data(this);
        return data;
    }

    set requestData(value) {
        return this.props.request.data = value;
    }

    get callback() {
        return this.props.callback;
    }

    set callback(value) {
        return this.props.callback = value;
    }

    actionActivated(originalEvent) {
        let detail = {}
        if(this.hasAttribute())
        if(this.hasAttribute("event")) {
            this.dispatchEvent(new Event(this.getAttribute("event")))
        }
    }

    dispatchEvent(event) {
        this.props.menu.dispatchEvent(event);
    }

    setRequestFeedback(state) {
        for(const states in this.REQUEST_STATES) {
            this.actionContainer.classList.remove(this.REQUEST_STATES[states])
        }
        if(state) this.actionContainer.classList.add(state);
    }

    throbberStart() {
        clearTimeout(this.throbberCompleteTimeout);
        this.setRequestFeedback(this.REQUEST_STATES.WORKING);
        this.disabled = true;
        this.throbberContainer.innerHTML = "<loading-spinner></loading-spinner>";
        this.throbberContainer.style.opacity = 1;
        this.throbberContainer.style.color = "";
        this.actionContainer.title = "";
    }

    throbberComplete(delay = 10) {
        this.setRequestFeedback(this.REQUEST_STATES.COMPLETE);
        this.disabled = false;
        this.throbberContainer.innerHTML = "<i name='check-circle-outline'></i>";
        this.throbberContainer.style.color = "var(--project-color-acknowledge)";
        if(delay) this.throbberCompleteTimeout = setTimeout(() => this.throbberEnd(), delay * 1000)
        if(this.props.menu.button && !this.props.menu.isOpen) {
            this.props.menu.button.classList.add(this.props.menu.BUTTON_TARGET_ACTION_COMPLETE);
        }
    }

    throbberEnd() {
        this.setRequestFeedback('');
        this.disabled = false;
        this.throbberContainer.style.opacity = 0;
        this.throbberContainer.style.color = "";
        this.actionContainer.title = "";
    }
    
    throbberError(description) {
        this.setRequestFeedback(this.REQUEST_STATES.ERROR);
        this.disabled = false;
        this.throbberContainer.innerHTML = "<i name='alert'></i>";
        this.throbberContainer.style.color = "var(--project-color-problem)";
        this.actionContainer.title = description;
        if(this.props.menu.button && !this.props.menu.isOpen) {
            this.props.menu.button.classList.add(this.props.menu.BUTTON_TARGET_ACTION_ERROR);
        }
    }
}