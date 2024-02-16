class NotificationsPanel {
    constructor(panel = null) {
        this.panel = panel || document.querySelector(".notifications--notifications-panel");
        if(this.panel === null) return console.warn("Are notifications turned off?");

        this.initCloseButton();
        this.initUserDuplicate();

        this.panel.querySelector("hgroup:first-of-type").appendChild(this.closeButton);
        this.list = this.panel.querySelector(".notifications--list");
        this.send = this.panel.querySelector("form-request.notifications--send");
        this.send.addEventListener("requestSuccess", e => {    
            this.updatePanelContent();
            this.send.querySelector("textarea").value = "";
            this.send.querySelector("[name='for.user'] button")?.dispatchEvent(new Event("click"));
        });

        this.cacheKey = "notifications";
    }

    initCloseButton(){
        this.closeButton = document.createElement("button");
        this.closeButton.classList.add("close-button");
        this.closeButton.addEventListener("click", e => this.close());
        this.closeButton.innerHTML = window.closeGlyph;
    }

    initUserDuplicate() {
        this.duplicateButton = this.panel.querySelector(".input-user-duplicate");
        this.duplicateButton.addEventListener("click", event => {
            const btnParent = this.duplicateButton.closest("fieldset");
            const reference = this.panel.querySelector("input-user:last-of-type");
            if(!reference) throw new Error("Missing reference");
            const clone = reference.cloneNode();
            clone.innerHTML = "";
            btnParent.insertBefore(clone, reference)
        })
    }

    state(state) {
        const s = !state;
        this.ariaHidden = s;

        this.panel.setAttribute("aria-hidden", s);

        if(s === false) this.updatePanelContent();
    }

    close() {
        this.state(false);
        this.panel.dispatchEvent(new CustomEvent("close", {}));
    }

    async updatePanelContent() {
        this.list.style.opacity = .3;
        this.list.style.pointerEvents = "none";
        
        const api = new AsyncFetch(`/api/notifications/me`, "GET", {});
        const response = await api.get();
        this.list.innerHTML = response;
        
        this.list.style.opacity = 1;
        this.list.style.pointerEvents = "unset";
        this.panel.dispatchEvent(new Event("load"));
    }

    highlightById(id) {
        const target = this.panel.querySelector(`[data-id='${id}']`);
        
    }

    async updateById(id) {
        const target = this.panel.querySelector(`[data-id='${id}']`);
        target.style.opacity = .3;
        const result = await quickRequest(`/api/notifications/${id}/`, {method: "GET"});
        const instantiator = document.createElement("div");
        instantiator.innerHTML = result;
        target.parentNode.insertBefore(instantiator.firstChild, target);
        target.parentNode.removeChild(target);

        this.panel.dispatchEvent(new Event("load"));
    }

    cache(name, value = null) {
        let cache = JSON.parse(localStorage.getItem(this.cacheKey) || "{}")
        if(!value) return cache[name] || null;
        cache[name] = value;
        localStorage.setItem(this.cacheKey, JSON.stringify(cache));
    }
}

window.Cobalt.NotificationsPanel = new NotificationsPanel()

class NotifyButton extends CustomButton {
    constructor() {
        super();
        if("localStorage" in window === false) throw Error("Incompatible browser");
        
        this.cache("notificationFetchInterval",60000);
        this.classesForUnread = ["notification-indicator--unread"];
        this.classesForRead   = ["notification-indicator--read"];
        this.classesForMuted  = ["notification-indicator--mute"];
    }

    connectedCallback() {
        super.connectedCallback();
        this.addEventListener("click", () => this.clickHandler());
        this.unseenIndicator = document.createElement("span");
        this.unseenIndicator.classList.add("unseen-indicator");
        this.appendChild(this.unseenIndicator);
        window.Cobalt.NotificationsPanel.panel.addEventListener("close", e => {
            this.ariaPressed = false;
        });
        
        document.addEventListener("localDataStorage", e => {
            this.updateNotificationStatusIndicators();
        });

        this.addEventListener("load", e => {
            this.updateNotificationStatusIndicators();
        });

        window.Cobalt.NotificationsPanel.panel.addEventListener("load", e => {
            this.fetchUnreadCount();
        });

        this.initUpdateInterval();

        this.updateNotificationStatusIndicators();

        this.addEventListener("contextmenu", e => {
            const menu = new ActionMenu({event: e, title: "Notifications", mode: "modal", attachTo: this});
            const mute = pref("notifications_mute"); // Initially = null
            menu.registerAction({
                label: (mute === true) ? "Unmute Notifications" : "Mute Notifications",
                icon: `<i name="bell-${(mute === true) ? "ring" : "off"}"></i>`,
                callback: () => {
                    pref("notifications_mute", !mute);
                    this.initUpdateInterval();
                    console.warn("Notification status: ", !mute);
                    return;
                }
            });
            menu.draw();
        });

        // window.addEventListener("blur", e => {
        //     this.cache("notificationFetchInterval",500000);
        //     clearInterval(this.interval);
        //     this.initUpdateInterval();
        // });

        // window.addEventListener("load", e => {
            
        // });
    }

    async updateNotificationStatusIndicators() {
        if(pref("notifications_mute") === true) {
            this.classList.add(...this.classesForMuted);
            this.classList.remove(...this.classesForRead, ...this.classesForUnread);
            return;
        }
        const {unread, unseen} = this.cache("unread") ?? {unread: 0, unseen: 0};
        let classesToAdd = [...this.classesForRead],
        classesToRemove = [...this.classesForUnread, ...this.classesForMuted];
        
        if(unread) {
            classesToAdd = [...this.classesForUnread];
            classesToRemove = [...this.classesForRead, ...this.classesForMuted];
        }

        // const unseen = await this.cache("unseen");
        this.updateUnseenIndicator(unseen);

        this.classList.add(...classesToAdd);
        this.classList.remove(...classesToRemove);
    }

    updateUnseenIndicator(value) {
        if(value === 0) {
            value = "";
        }

        this.unseenIndicator.innerText = value;
    }

    async fetchUnreadCount(user = "me") {
        this.cache('lastPoll', new Date().getTime());
        this.cache('lastPollId', getTabId());
        const api = new AsyncFetch(`/api/notifications/${user}/unread-count`, "GET", {});
        const result = await api.get();
        this.cache("unread", result);
        this.dispatchEvent(new Event("load"));
    }

    clickHandler() {
        this.ariaPressed = !(this.ariaPressed === "true") ? true : false;
        this.togglePanel();
    }

    togglePanel() {
        window.Cobalt.NotificationsPanel.state((this.ariaPressed === "true"));
    }

    cache(name, value = null) {
        return window.Cobalt.NotificationsPanel.cache(name, value);
    }

    async initUpdateInterval() {
        if(pref("notifications_mute") === true) {
            clearInterval(this.interval);
            return;
        }
        if(getTabId() !== await this.cache("lastPollId")) {
            if(new Date().getTime() - await this.cache("lastPoll") < this.notificationsFetchInterval) return console.warn("Another instance of this app is listening");
        }

        this.interval = setInterval(() => {
            this.fetchUnreadCount();
        }, this.cache("notificationFetchInterval"));
    }

    modifyUpdateInterval(value) {
        this.cache("notificationFetchInterval", value);
        clearInterval(this.interval);
        this.initUpdateInterval();
    }
}

customElements.define("notify-button", NotifyButton);

// class NotificationDispatch extends FormRequestElement {
//     connectedCallback() {

//     }
// }

// class UserAutocomplete 
