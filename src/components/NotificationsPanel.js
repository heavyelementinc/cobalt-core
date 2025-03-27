class NotificationsPanel {
    constructor(panel = null) {
        this.panel = panel || document.querySelector(".notifications--notifications-panel");
        if(this.panel === null) return console.warn("Are notifications turned off?");

        this.initCloseButton();
        this.initUserDuplicate();

        this.panel.querySelector("hgroup:first-of-type").appendChild(this.closeButton);
        this.panel.inert = true;
        this.stateFilterSelect = this.panel.querySelector(`[name="status"]`);
        this.sortFilterSelect  = this.panel.querySelector(`[name="sort"]`);
        this.mutedSelector     = this.panel.querySelector(`[name="mute"]`);
        this.initFilters();

        this.list = this.panel.querySelector(".notifications--list");
        this.send = this.panel.querySelector("form-request.notifications--send");
        this.send.addEventListener("requestSuccess", e => {    
            this.updatePanelContent();
            this.send.querySelector("textarea").value = "";
            this.send.querySelector("[name='for.user'] button")?.dispatchEvent(new Event("click"));
        });

        this.cacheKey = "notifications";
        document.addEventListener("navigationEvent", e => {
            this.close();
        });
    }

    initCloseButton(){
        this.closeButton = document.createElement("button");
        this.closeButton.classList.add("close-button");
        this.closeButton.addEventListener("click", e => this.close());
        this.closeButton.innerHTML = window.closeGlyph;
        this.closeButton.disabled = !this.ariaHidden;
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

    initFilters() {
        this.mutedSelector.checked = this.muted;
        this.mutedSelector.addEventListener("change", () => {
            this.muted = this.mutedSelector.checked;
            this.panel.dispatchEvent(new CustomEvent("muted"));
        })
        this.initFilterSelector(this.stateFilterSelect, "stateFilter", this.stateFilter);
        this.initFilterSelector(this.sortFilterSelect, "sortFilter", this.sortFilter);
        // const sortFilter = this.sortFilterSelect.querySelector(`[value="${this.sortFilter}"]`);
        // sortFilter.selected = true;
        // this.sortFilterSelect.addEventListener("change", () => {
        //     this.sortFilter = this.sortFilterSelect.value;
        // });
    }

    initFilterSelector(element, propertyName, value) {
        const selectedFilterOption = element.querySelector(`[value="${value}"]`);
        selectedFilterOption.selected = true;
        element.addEventListener("change", () => {
            this[propertyName] = element.value;
            this.updatePanelContent();
        });
    }

    state(state) {
        const s = !state;
        this.ariaHidden = s;
        this.panel.inert = s;
        
        this.panel.setAttribute("aria-hidden", s);
        this.panel.querySelectorAll("button,input,textarea").forEach(e => {
            e.disabled = s;
        });

        if(s === false) this.updatePanelContent();
        const bodyContentLockedStatus = ["notification-panel--open"];
        switch(s) {
            case false:
                lock_viewport();
                document.body.classList.add(...bodyContentLockedStatus);
                break;
            default:
            case true:
                unlock_viewport();
                document.body.classList.remove(...bodyContentLockedStatus);
                break;
        }
    }

    close() {
        this.state(false);
        this.panel.dispatchEvent(new CustomEvent("close", {}));
    }

    async updatePanelContent() {
        this.list.style.opacity = .3;
        this.list.style.pointerEvents = "none";
        
        const api = new AsyncFetch(`/api/notifications/me?${new URLSearchParams(this.value).toString()}`, "GET", {});
        const response = await api.get();
        this.list.innerHTML = response;
        
        this.list.style.opacity = 1;
        this.list.style.pointerEvents = "unset";
        this.panel.dispatchEvent(new Event("load"));
    }

    highlightById(id) {
        const target = this.panel.querySelector(`[data-id='${id}']`);
    }

    get value() {
        const params = {
            state: this.stateFilter,
            sort: this.sortFilter
        }
        return params;
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

    STATEKEY = "__ntfy_filter_state";
    get stateFilter() {
        return localStorage.getItem(this.STATEKEY) ?? this.stateFilterSelect.value;
    }

    set stateFilter(value) {
        const validOptions = this._getValidStates(this.stateFilterSelect);
        if(!validOptions.includes(value)) throw new TypeError("Invalid state selection");
        localStorage.setItem(this.STATEKEY, value);
    }

    SORTKEY = "__ntfy_filter_sort";
    get sortFilter() {
        return Number(localStorage.getItem(this.SORTKEY) ?? this.sortFilterSelect.value);
    }

    set sortFilter(value) {
        const validOptions = this._getValidStates(this.sortFilterSelect);
        if(!validOptions.includes(value)) throw new TypeError("Invalid state selection");
        localStorage.setItem(this.SORTKEY, value);
    }

    /**
     * @param {HTMLSelect} element 
     * @returns {Array}
     */
    _getValidStates(element) {
        let options = [];
        element.querySelectorAll("option").forEach(e => {
            options.push(e.value);
        });
        return options;
    }

    MUTED_KEY = "__ntfy_muted";

    get muted() {
        return localStorage.getItem(this.MUTED_KEY) ?? false;
    }

    set muted(value) {
        localStorage.setItem(this.MUTED_KEY, !!value);
        this.panel.dispatchEvent(new CustomEvent("muted"));
    }
}

window.Cobalt.NotificationsPanel = new NotificationsPanel()

class NotifyButton extends CustomButton {
    UNREAD_KEY = "__ntfy_unread_count";
    /** @property {HTMLElement} */
    unseenIndicator;
    CLASSES_FOR_UNREAD = ["notification-indicator--unread"];
    CLASSES_FOR_READ   = ["notification-indicator--read"];
    CLASSES_FOR_MUTED  = ["notification-indicator--mute"];
    constructor() {
        super();
        this.role = "button";
        this.ariaPressed = "false";
        window.addEventListener("storage", event => {
            if(event.key === this.UNREAD_KEY) {
                this.updateIndicators(event.newValue);
            }
            if(event.key === window.Cobalt.NotificationsPanel.MUTED_KEY) {
                this.displayMuted();
            }
        });
        window.Cobalt.NotificationsPanel.panel.addEventListener("close", () => {
            this.ariaPressed = "false";
        });
        window.Cobalt.NotificationsPanel.panel.addEventListener("muted", () => {
            this.displayMuted();
        });
    }

    get unread() {
        return Number(localStorage.getItem(this.UNREAD_KEY) ?? 0);
    }

    set unread(value) {
        localStorage.setItem(this.UNREAD_KEY, Number(value));
        this.updateIndicators(value);
    }

    connectedCallback() {
        this.createUnseenIndicator();
        this.addEventListener("click", () => {
            if(this.ariaPressed === "false") {
                window.Cobalt.NotificationsPanel.state(true)
                this.ariaPressed = "true";
            } else {
                window.Cobalt.NotificationsPanel.state(false);
                this.ariaPressed = "false";
            }
        });
    }

    createUnseenIndicator() {
        this.unseenIndicator = document.createElement("span");
        this.unseenIndicator.classList.add("unseen-indicator");
        this.appendChild(this.unseenIndicator);
        this.updateIndicators(this.getAttribute("value") ?? localStorage.getItem(this.UNREAD_KEY));
        this.displayMuted();
    }

    updateIndicators(value) {
        const visible_value = Math.min(value, 9);
        // Remove all classes
        this.classList.remove(...this.CLASSES_FOR_READ, ...this.CLASSES_FOR_UNREAD);

        if(visible_value === 0) {
            // Add visible classes
            this.classList.add(...this.CLASSES_FOR_READ);
        }

        if(visible_value >= 1) {
            if(this.unseenIndicator) this.unseenIndicator.innerText = visible_value;
            this.classList.add(...this.CLASSES_FOR_UNREAD);
        }
    }

    displayMuted() {
        this.classList.remove(...this.CLASSES_FOR_MUTED);
        if(window.Cobalt.NotificationsPanel.muted === "true") {
            this.classList.add(...this.CLASSES_FOR_MUTED);
            return;
        }
    }

    // constructor() {
    //     super();
    //     if("localStorage" in window === false) throw Error("Incompatible browser");
        
    //     this.cache("notificationFetchInterval",60000);
    //     this.classesForUnread = ["notification-indicator--unread"];
    //     this.classesForRead   = ["notification-indicator--read"];
    //     this.classesForMuted  = ["notification-indicator--mute"];
    // }

    // connectedCallback() {
    //     super.connectedCallback();
    //     this.addEventListener("click", () => this.clickHandler());
    //     this.unseenIndicator = document.createElement("span");
    //     this.unseenIndicator.classList.add("unseen-indicator");
    //     this.appendChild(this.unseenIndicator);
    //     window.Cobalt.NotificationsPanel.panel.addEventListener("close", e => {
    //         this.ariaPressed = false;
    //     });
        
    //     document.addEventListener("localDataStorage", e => {
    //         this.updateNotificationStatusIndicators();
    //     });

    //     this.addEventListener("load", e => {
    //         this.updateNotificationStatusIndicators();
    //     });

    //     window.Cobalt.NotificationsPanel.panel.addEventListener("load", e => {
    //         this.fetchUnreadCount();
    //     });

    //     this.initUpdateInterval();

    //     this.updateNotificationStatusIndicators();

    //     this.addEventListener("contextmenu", e => {
    //         const menu = new ActionMenu({event: e, title: "Notifications", mode: "modal", attachTo: this});
    //         const mute = pref("notifications_mute"); // Initially = null
    //         menu.registerAction({
    //             label: (mute === true) ? "Unmute Notifications" : "Mute Notifications",
    //             icon: `<i name="bell-${(mute === true) ? "ring" : "off"}"></i>`,
    //             callback: () => {
    //                 pref("notifications_mute", !mute);
    //                 this.initUpdateInterval();
    //                 console.warn("Notification status: ", !mute);
    //                 return;
    //             }
    //         });
    //         menu.draw();
    //     });

    //     // window.addEventListener("blur", e => {
    //     //     this.cache("notificationFetchInterval",500000);
    //     //     clearInterval(this.interval);
    //     //     this.initUpdateInterval();
    //     // });

    //     // window.addEventListener("load", e => {
            
    //     // });
    // }

    // async updateNotificationStatusIndicators() {
    //     if(pref("notifications_mute") === true) {
    //         this.classList.add(...this.classesForMuted);
    //         this.classList.remove(...this.classesForRead, ...this.classesForUnread);
    //         return;
    //     }
    //     const {unread, unseen} = this.cache("unread") ?? {unread: 0, unseen: 0};
    //     let classesToAdd = [...this.classesForRead],
    //     classesToRemove = [...this.classesForUnread, ...this.classesForMuted];
        
    //     if(unread) {
    //         classesToAdd = [...this.classesForUnread];
    //         classesToRemove = [...this.classesForRead, ...this.classesForMuted];
    //     }

    //     // const unseen = await this.cache("unseen");
    //     this.updateUnseenIndicator(unseen);

    //     this.classList.add(...classesToAdd);
    //     this.classList.remove(...classesToRemove);
    // }

    // updateUnseenIndicator(value) {
    //     if(value === 0) {
    //         value = "";
    //     }

    //     this.unseenIndicator.innerText = value;
    // }

    // async fetchUnreadCount(user = "me") {
    //     this.cache('lastPoll', new Date().getTime());
    //     this.cache('lastPollId', getTabId());
    //     const api = new AsyncFetch(`/api/notifications/${user}/unread-count`, "GET", {});
    //     const result = await api.get();
    //     this.cache("unread", result);
    //     this.dispatchEvent(new Event("load"));
    // }

    // clickHandler() {
    //     this.ariaPressed = !(this.ariaPressed === "true") ? true : false;
    //     this.togglePanel();
    // }

    // togglePanel() {
    //     window.Cobalt.NotificationsPanel.state((this.ariaPressed === "true"));
    // }

    // cache(name, value = null) {
    //     return window.Cobalt.NotificationsPanel.cache(name, value);
    // }

    // async initUpdateInterval() {
    //     if(pref("notifications_mute") === true) {
    //         clearInterval(this.interval);
    //         return;
    //     }
    //     if(getTabId() !== await this.cache("lastPollId")) {
    //         if(new Date().getTime() - await this.cache("lastPoll") < this.notificationsFetchInterval) return console.warn("Another instance of this app is listening");
    //     }

    //     // this.interval = setInterval(() => {
    //     //     this.fetchUnreadCount();
    //     // }, this.cache("notificationFetchInterval"));
    // }

    // modifyUpdateInterval(value) {
    //     this.cache("notificationFetchInterval", value);
    //     clearInterval(this.interval);
    //     this.initUpdateInterval();
    // }
}

customElements.define("notify-button", NotifyButton);

// class NotificationDispatch extends FormRequestElement {
//     connectedCallback() {

//     }
// }

// class UserAutocomplete 

class NotificationItem extends HTMLElement {
    READ_ATTRIBUTE = "read";
    SEEN_ATTRIBUTE = "seen";

    connectedCallback() {
        this.body = this.querySelector(".notification--body");
        this.date = this.querySelector(".notification--foot date-span");
        this.stateOption = this.querySelector(`[name="state"]`);
        this.updateStateOption(this.read);
        this.stateOption.addEventListener("click", () => {
            this.stateSubmit(this.READ_ATTRIBUTE, !this.read);
            this.click();
            return true;
        });

        this.deleteOption = this.querySelector(`[name="delete"]`);
        this.deleteOption.addEventListener("click", async () => {
            const api = new AsyncFetch(this.deleteOption.getAttribute("action"), "DELETE");
            api.submit({});
            this.parentNode.removeChild(this);
            this.click();
            return true;
        })

        this.addEventListener("click", e => {
            // if(e.ctrlKey)
            this.read = true;
            this.stateSubmit(this.READ_ATTRIBUTE, true);
        });
        this.body.addEventListener("click", this.navigateToAction.bind(this));
        this.date.addEventListener("click", this.navigateToAction.bind(this));
    }

    get read() {
        return JSON.parse(this.getAttribute(this.READ_ATTRIBUTE)) ?? false;
    }

    /** @param {bool} value */
    set read(value) {
        const state = this.stateValidate(this.READ_ATTRIBUTE,value);
        this.setAttribute(this.READ_ATTRIBUTE, state);
        this.updateStateOption(state);
    }

    get seen() {
        return JSON.parse(this.getAttribute(this.SEEN_ATTRIBUTE)) ?? false;
    }

    /** @param {bool} value */
    set seen(value) {
        const state = this.stateValidate(this.SEEN_ATTRIBUTE, value);
        this.setAttribute(this.SEEN_ATTRIBUTE, JSON.stringify(state))
    }

    stateValidate(type, value) {
        switch(value) {
            case type:
            case true:
            case "true":
            case "1":
            case 1:
                value = true;
                break;
            case "":
            case "false":
            case false:
            case "0":
            case 0:
                value = false;
                break;
            default:
                throw new TypeError(`${value} is an invalid state for a notification ${type} status`);
        }
        return value;
    }

    async stateSubmit(type, state) {
        const api = new AsyncFetch(this.stateOption.getAttribute("action"), "PUT");
        let data = {};
        data[type] = state;
        await api.submit(data);
        this.seen = state;
        this.read = state;
        return true;
    }

    navigateToAction(e) {
        window.Cobalt.router.location = this.getAttribute("action");
    }

    updateStateOption(state) {
        this.stateOption.innerHTML = `Mark as ${state ? "<strong>Unread</strong>" : "<strong>Read</strong>"}`
        this.stateOption.setAttribute("icon", state ? "email-open-outline" : "email");
        this.stateOption.value = JSON.stringify(state)
    }
}

customElements.define("notification-item", NotificationItem);