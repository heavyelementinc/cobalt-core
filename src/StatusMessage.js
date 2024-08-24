class StatusMessage {
    constructor({ message, id = null, icon = "", duration = 0, action = e => true, close = true, type = "status" }) {
        this.message = message;
        this.icon = this.decideIcon(icon, type);
        this.type = type;
        this.id = id || random_string();
        this.action = action;
        this.closeable = close;
        this.duration = duration;
        this.classes = {
            StatusMessage: "status-message--status",
            StatusError: "status-message--error",
        };

        // Let's now set this message up to be added to the MessageHandler class
        this.element = window.messageHandler.message(this);

        this.updateClasses();
    }

    /** @todo add action event updating */
    async update(message, icon = this.icon, action = this.action, type = this.type) {
        const section = this.element.querySelector(".message-container");
        const ionIcon = this.element.querySelector("i");
        this.element.setAttribute("name", type);

        this.updateClasses();
        const animClass = "status-message--update";
        section.innerHTML = message;
        ionIcon.name = icon;
        await wait_for_animation(this.element, animClass);
        this.element.classList.remove(animClass);
    }

    async close() {
        window.messageHandler.dismiss({ id: this.id }, {}, false);
    }

    decideIcon(icon, type) {
        // if(icon) return icon;
        let icons = {
            "default": "information-slab-circle-outline",
            "success":   "check-circle-outline",
            400:       "cancel",
            "warning": "alert-outline",
            "error":   "alert-octagon-outline",
            "auth":           "fingerprint",
            "authentication": "fingerprint",
            "user":           "fingerprint",
            401:              "fingerprint",
            "heartbeat": "pulse",
            "money":  "cash",
            402:      "cash",
            "bookmark": "bookmark-outline",
            "pizza":  "pizza",
            "teapot": "kettle",
            418:      "kettle",
            "fire": "fire-alert",
            451:    "fire-alert",
            "update": "refresh-circle",
            "email":  "email-fast",
            "notification":   "bell-outline",
            "notify":         "bell-outline",
            "chat":           "forum-outline",
            "text":           "forum-outline",
            "message":        "forum-outline",
            "fail":    "missing",
        }

        if(icon in icons) return icons[icon];
        if(type in icons) return icons[type];
        return icons.default;
    }

    oldSwitch() {
        switch(type) {
            case "success":
                return "check-circle-outline";
            case "heartbeat":
                return "pulse";
            case "money":
            case 402:
                return "cash";
            case "bookmark":
                return "bookmark-outline";
            case "pizza":
                return "pizza";
            case "teapot":
            case 418:
                return "kettle";
            case "fire":
            case 451:
                return "fire-alert";
            case "update":
                return "refresh-circle";
            case "email":
                return "email-fast";
            case "notification":
            case "notify":
                return "bell-outline";
            case "chat":
            case "text":
            case "message":
                return "forum-outline";
            case "auth":
            case "authentication":
            case "user":
            case 401:
                return "fingerprint";
            case "fail":
            case "missing":
            case 400:
            case type >= 403 && type <= 499:
                return "cancel";
            case "warning":
                return "alert-outline";
            case type >= 500 && type <= 599:
            case "error":
                return "alert-octagon-outline";
            case "status":
            default:
                return "information-slab-circle-outline";
        }
    }

    updateClasses() {
        let classes = "";

        if(this.element.classList) {
            this.element.classList.remove(...Object.values(this.classes));
            this.element.classList.add(this.classes[this.constructor.name]);
        }
    }
}

class StatusError extends StatusMessage {
    constructor({ message, id, icon = null, action = e => true, type = "error" }) {
        super({ message, id, icon, action, type});
    }
}

class MessageHandler {
    constructor() {
        this.container = document.createElement("message-container");
        document.body.appendChild(this.container);
        this.messageQueue = {};
    }

    message(details) {
        const message = document.createElement("message-item");
        message.setAttribute("name",details.type);
        message.classList.add(`status-message`);
        message.setAttribute("data-id",details.id);
        message.innerHTML = `<i name='${details.icon}'></i><div class="message-container">${details.message}</div>`;
        let close_btn = document.createElement("button");
        close_btn.innerHTML = window.closeGlyph;
        // close_btn.addEventListener()
        if (details.closeable) message.appendChild(close_btn);

        // message.addEventListener("click", event => this.dismiss(details, event, true));
        close_btn.addEventListener("click", event => {
            event.stopPropagation();
            this.dismiss(details, event, false)
        });

        if(details.id in this.messageQueue) {
            return this.messageQueue[details.id].update(
                details.message || this.messageQueue[details.id].message || "",
                details.icon || this.messageQueue[details.id].icon || "",
                details.action || this.messageQueue[details.id].action || function () {return true},
                details.type || this.messageQueue[details.id].type || "status",
            );
        }

        details.container = message;

        this.messageQueue[details.id] = details;
        this.container.appendChild(message);

        message.style.setProperty("--height", `${get_offset(message).h || 100}px`);

        this.spawn(message);

        if (details.duration) this.timeout(details)

        return message;
    }

    async dismiss(details, event, withAction = true) {
        let element = this.messageQueue[details.id].container || event.target.closest("message-item") || null;
        if (!element) return console.warn("Could not find status message to close");
        try {
            if (withAction) await details.action(event, details);
        } catch (error) {
            await this.no(element);
            return;
        }
        await wait_for_animation(element, "status-message--closing");
        if (!element.parentNode) return;
        element.parentNode.removeChild(element);
        delete this.messageQueue[details.id];
    }



    async timeout(details) {
        setTimeout(() => {
            this.dismiss(details, null, false);
        }, details.duration)

    }

    async spawn(element) {
        await wait_for_animation(element, "status-message--opening");
        element.classList.remove("status-message--opening");
    }

    async no(element) {
        await wait_for_animation(element, "status-message--no");
        element.classList.remove("status-message--no");
    }

    async closeAll() {
        for(const i in this.messageQueue) {
            const message = this.messageQueue[i];
            message.close();
        }
    }
}

window.messageHandler = new MessageHandler();
