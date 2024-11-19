class CobaltEvents {
    constructor() {
        this.eventQueue = [];
        this.currentEvents = [];
        this.eventTypes = {
            default: CobaltEvent_default,
            modal: CobaltEvent_modal
        }
        this.typesOnDisplay = {
            default: false,
            modal: false,
        }
        this.hasAnotherEventBeenShown = false;
        this.hasAnExclusiveEventBeenShown = false;
        this.api = new AsyncFetch('/api/v1/cobalt-events/current/', 'GET', {});
        this.init();
    }

    async init() {
        await this.getCurrentEvents();
        for (const evt of this.currentEvents) {
            if (await this.initializeEvent(evt)) continue;
        }
    }

    async getCurrentEvents() {
        this.currentEvents = await this.api.get();
    }

    async initializeEvent(evt, preview = false) {
        let type = "default";
        if (evt.type in this.eventTypes) type = evt.type;
        const event = new this.eventTypes[type](evt, preview);
        if(preview === false) {
            if (!event.isElligibleForDisplay(event)) return false;
            if (!this.isExclusiveAllowed(evt)) return false;
            this.hasAnotherEventBeenShown = true;
            this.typesOnDisplay[event.type] = true;
            await this.timeout(localStorage.getItem("eventDelay") || evt.advanced.delay);
        } else {
            if(this.eventQueue.preview) this.eventQueue.preview.dismiss();
        }
        event.draw();
        event.element.addEventListener("cobaltEventsClosed", e => this.eventClosure(e));
        
        // Store this event in the displayed event queue.
        if(!preview) this.eventQueue[evt._id.$oid] = event;
        else this.eventQueue.preview = event;
        return true;
    }

    isExclusiveAllowed(event) {
        if(!event || this.typesOnDisplay[event.type]) return false;

        // If the event is not exclusive
        // if(this.advanced.exclusive === false) return true;

        // Check if another event has been shown
        if (this.hasAnotherEventBeenShown) return false;
        return true
    }

    eventClosure(e) {
        // console.log(e.detail)
    }

    async timeout(length) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve()
            }, length * 1000)
        })
    }
}

class CobaltEvent_default {
    constructor(data, preview = false) {
        this.data = data;
        this.preview = preview;
    }

    destructor() {
        this.element.parentNode.removeChild(this.element);
    }

    isElligibleForDisplay() {
        const stored = this[this.storageMedium](this.data._id.$oid);
        const hasBeenClosed = stored?.closed ?? null;
        const includePathnameMatch = this.pathname(this.data.advanced.included_paths);
        if (!includePathnameMatch) return false;

        // Let's get a malliable version of our pathnames
        let excludedPathnames = [...this.data.advanced.excluded_paths];
        // Check if an pathname has been specified. If it has, push it to the excluded list
        if (this.data.call_to_action_href) excludedPathnames.push(this.data.call_to_action_href);
        const excludePathnameMatch = this.pathname(excludedPathnames);
        if (excludePathnameMatch) return false;

        if (hasBeenClosed === true) {
            // If the SessionPolicy has expired, we'll return true
            // otherwise we'll return false
            if(this.hasSessionPolicyExpired(stored)) return true;
            if(!this.hasBeenChanged(stored)) return false;
        };
        return true;
    }

    /**
     * 
     * @param {Object{date:string}} stored 
     * @returns {bool}
     */
    hasSessionPolicyExpired(stored) {
        const closedDate = new Date(stored.date).getTime(); // 199029
        const now = new Date().getTime();                   // 200010
        const diff = now - closedDate;                      // 981

        switch(this.data.session_policy) {
            case "nag":
                return true;
            case "with_session":
            case "24_hours":
                return diff > (1000 * 60 * 60 * 24);
            case "12_hours":
                return diff > (1000 * 60 * 60 * 12);
            case "hours":
                return diff > (1000 * 60 * 60 * this.data.session_policy_hours);
            case "half_date":
                const end = new DateConverter(this.data.start_time).date.getTime();
                const date = now + (end - now / 2);
                // If less than 12 hours, just ignore
                if (date <= 1000 * 60 * 60 * 12) return false;
                return diff > date; 
            case "never":
                return false;
        }
        return true;
    }

    hasBeenChanged(stored) {
        if(!this.data.changes_override) return false;
        if(!stored.lastUpdated) return true;
        if(!this.data.last_updated_on) return false;
        // If the dates don't match, then something has changed. Therefore, we
        // want to return the inverse bool of the comparison.
        return !(stored.lastUpdated.$date.$numberLong === this.data.last_updated_on.$date.$numberLong);
    }

    pathname(paths, empty = false) {
        if (paths.length === 0) return empty;
        let match = false;
        for (let p of paths) {
            p = (p[0] !== "^") ? `^${p}` : p;
            const regex = new RegExp(p);
            match = window.location.pathname.match(regex);
            if (match !== null) return true
        }
        return match;
    }

    draw() {
        this.element = document.createElement("div");
        this.element.id = this.data.id || random_string();
        this.element.classList.add("cobalt-events--default", this.classes);
        this.element.style.background = this.data.bgColor ?? "var(--project-events-banner-background)";
        this.element.style.color = colorMathBlackOrWhite(this.data.bgColor ?? "");
        this.element.style.color = this.data.txtColor ?? "var(--project-events-banner-text)";
        this.element.style.setProperty("--project-events-banner-text", this.element.style.color);
        
        // Justification may only be applied if there is no CTA button
        if(!this.hasCtaButton()) {
            this.element.style.justifyContent = this.data.txtJustification || app("CobaltEvents_default_h1_alignment");
        }

        this.innerContent();
        this.insert();

        this.closeButton();
    }

    get classes() {
        return `cobalt-events--${this.data.type}`;
    }

    closeItem(status = "closed") {
        if(this.preview) return this.dismiss();
        this[this.storageMedium](this.data._id.$oid, { closed: true, status, date: this.sessionPolicyDate, lastUpdated: this.data.last_updated_on });
        this.element.dispatchEvent(new CustomEvent("cobaltEventsClosed", {
            detail: this.data._id.$oid || this.data._id
        }));
        this.dismiss();
    }


    /** Storage handling */
    get storageMedium() {
        const mediums = {
            nag: "noStorageHandler",
            with_session: "sessionStorageHandler",
        };
        let type = "localStorageHandler";
        if (this.data.session_policy in mediums) type = mediums[this.data.session_policy];
        return type;
    }

    get sessionPolicyDate() {
        const end = new DateConverter(this.data.start_time).date.getTime(),
            now = new Date().getTime();
        let date;
        switch (this.data.session_policy) {
            case "nag":
                return now;
            case "with_session":
            case "24_hours":
                date = now + (1000 * 60 * 60 * 24);
                break;
            case "12_hours":
                date = now + (1000 * 60 * 60 * 12);
                break;
            case "hours":
                date = now + (1000 * 60 * 60 * this.data.session_policy_hours);
                break;
            case "half_date":
                date = now + (end - now / 2);
                // If less than 12 hours, just ignore
                if (date <= 1000 * 60 * 60 * 12) date = end;
                break;
            case "never":
                date = end + 1;
                break;
        }

        return new DateConverter(date).date;
    }

    noStorageHandler() {
        return null;
    }

    localStorageHandler(id, value) {
        return this.modernStorage("localStorage", id, value);
    }

    sessionStorageHandler(id, value) {
        return this.modernStorage("sessionStorage", id, value);
    }

    modernStorage(type, id, value = null) {
        if (typeof (Storage) === "undefined") throw new Error("Your browser doesn't support storage");
        if (value === null) return JSON.parse(window[type].getItem(id));
        window[type].setItem(id, JSON.stringify(value));
    }


    /** Specified by new derivatives */

    /** The inner content of the displayed element */
    innerContent() {
        this.element.innerHTML = `
            <h1>${this.data.headline}</h1>
        `;

        this.ctaButton();
    }

    hasCtaButton() {
        if(this.data.call_to_action_href) return true;
        return false;
    }
    
    ctaButton() {
        if (this.hasCtaButton()) {
            // Call to action
            const cta = document.createElement("a");
            cta.classList.add("cobalt-events--cta-button");
            cta.href = this.data.call_to_action_href;
            cta.innerText = this.data.call_to_action_prompt;
            cta.addEventListener("click", e => {
                e.preventDefault();
                window.router.location = cta.href;
                this.closeItem("cta");
                
            }, { once: true });
            cta.style.backgroundColor = this.data.btnColor;
            cta.style.color = this.data.btnTextColor;
            this.element.appendChild(cta);
            cta.style.color = colorMathBlackOrWhite(getComputedStyle(cta)['background-color']);
        }
    }

    /** Insert element into page */
    async insert() {
        document.body.parentNode.insertBefore(this.element, document.body);
        this.element.style.setProperty('--height', getComputedStyle(this.element).height);
        await wait_for_animation(this.element, "cobalt-events--animation");
        // this.element.style.animationName = getComputedStyle(this.element).getPropertyValue("--animation");
        this.element.classList.add("cobalt-events--banner-stablestate");
    }

    /** Remove element from page */
    async dismiss() {
        this.element.classList.remove("cobalt-events--banner-stablestate");
        this.element.classList.add("cobalt-events--dismiss");
        await wait_for_animation(this.element, "cobalt-events--animation");
        this.element.parentNode.removeChild(this.element);
    }

    closeButton() {
        // Dismiss button
        const close = document.createElement("button");
        close.classList.add("cobalt-events--banner-close")
        close.innerHTML = window.closeGlyph;
        close.style.color = colorMathBlackOrWhite(this.data.bgColor ?? "#FFFFFF");

        close.addEventListener("click", e => this.closeItem("close-button"), { once: true })
        this.element.appendChild(close);
    }
}


class CobaltEvent_modal extends CobaltEvent_default {
    innerContent() {
        this.element.innerHTML = `
            <h1>${this.data.headline}</h1>
            <article>${this.data.body}</article>
        `;

        this.ctaButton();
    }

    /** Insert element into page */
    async insert() {
        this.modal = new Modal({
            id: "cobalt-events--modal-window",
            close_btn: false,
            chrome: false,
            pageTitle: this.data.headline
        });
        const container = await this.modal.draw();
        container.querySelector(".modal-body").appendChild(this.element);
    }

    /** Remove element from page */
    async dismiss() {
        this.modal.close();
    }
}

class ExternalPromise {
    constructor() {
        this.resolve = null
        this.reject = null
        this.promise = new Promise((resolve, reject) => {
            this.resolve = resolve
            this.reject = reject
        })
    }
}










if (app("CobaltEvents_enabled")) window.CobaltEventManager = new CobaltEvents();
