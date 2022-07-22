class StatusMessage {
    constructor({ message, id = null, icon = "", duration = 2000, action = e => true, close = true, type = "status" }) {
        this.message = message;
        this.icon = icon || "information-circle-outline";
        this.id = id || random_string();
        this.action = action;
        this.closeable = close;
        this.type = type;
        this.element = window.messageHandler.message(this);
    }

    /** @todo add action event updating */
    async update(message, icon = this.icon, action = this.action) {
        const article = this.element.querySelector("article");
        const ionIcon = this.element.querySelector("ion-icon");
        const animClass = "status-message--update";
        article.innerHTML = message;
        ionIcon.name = icon;
        await wait_for_animation(this.element, animClass);
        this.element.classList.remove(animClass);
    }

    async close() {
        window.messageHandler.dismiss({ id: this.id }, {}, false);
    }
}

class StatusError extends StatusMessage {
    constructor({ message, id, icon = null, action = e => true }) {
        super({ message, id, icon: icon || `warning-outline`, action, type: "error"});
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
        message.classList.add(`status-message--${details.type}`);
        message.setAttribute("data-id",details.id);
        message.innerHTML = `<ion-icon name='${details.icon}'></ion-icon><article>${details.message}</article>`;
        let close_btn = document.createElement("button");
        close_btn.innerHTML = window.closeGlyph;
        // close_btn.addEventListener()
        if (details.closeable) message.appendChild(close_btn);

        message.addEventListener("click", event => this.dismiss(details, event, true));
        close_btn.addEventListener("click", event => {
            event.stopPropagation();
            this.dismiss(details, event, false)
        });

        if(details.id in this.messageQueue) {
            return details.update({message: details.});
        }
        this.messageQueue[details.id] = message;
        this.container.appendChild(message);

        this.spawn(message);

        if (details.duration) this.timeout(details)

        return message;
    }

    async dismiss(details, event, withAction = true) {
        let element = this.messageQueue[details.id];
        if (!element) return;
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
}

window.messageHandler = new MessageHandler();