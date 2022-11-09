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
        const section = this.element.querySelector("section");
        const ionIcon = this.element.querySelector("ion-icon");
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
        if(icon) return icon;
        switch(type) {
            case "success":
                return "checkmark-circle-outline";
            case "heartbeat":
                return "pulse-outline";
            case "money":
            case 402:
                return "cash-outline";
            case "bookmark":
                return "bookmark-outline";
            case "pizza":
                return "pizza-outline";
            case "teapot":
            case 418:
                return "cafe-outline";
            case "fire":
            case 451:
                return "flame-outline";
            case "update":
                return "refresh-circle-outline";
            case "email":
                return "mail-outline";
            case "notification":
            case "notify":
                return "notifications-outline";
            case "chat":
            case "text":
            case "message":
                return "chatbubbles-outline";
            case "auth":
            case "authentication":
            case "user":
            case 401:
                return "finger-print-outline";
            case "fail":
            case "missing":
            case 400:
            case type >= 403 && type <= 499:
                return "ban-outline";
            case type >= 500 && type <= 599:
            case "error":
                return "warning-outline";
            case "status":
            default:
                return "information-circle-outline";
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
        message.innerHTML = `<ion-icon name='${details.icon}'></ion-icon><section>${details.message}</section>`;
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