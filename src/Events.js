class CobaltEvents {
    constructor() {
        this.eventQueue = [];
        this.currentEvents = [];
        this.eventTypes = {
            default: CobaltEvent_default,
            modal: CobaltEvent_modal
        }
        this.hasAnotherEventBeenShown = false;
        this.api = new ApiFetch('/api/v1/cobalt-events/current/', 'GET', {});
        this.init();
    }

    async init() {
        await this.getCurrentEvents();
        for (const evt of this.currentEvents) {
            if (this.initializeEvent(evt)) continue;
        }
    }

    async getCurrentEvents() {
        this.currentEvents = await this.api.get();
    }

    async initializeEvent(evt) {
        let type = "default";
        if (evt.type in this.eventTypes) type = evt.type;
        const event = new this.eventTypes[type](evt);
        if (!event.isElligibleForDisplay()) return false;
        if (this.hasAnotherEventBeenShown) return false;
        this.hasAnotherEventBeenShown = true;
        await this.timeout(localStorage.getItem("eventDelay") || evt.advanced.delay);
        event.draw();
        event.element.addEventListener("cobaltEventsClosed", e => this.eventClosure(e));
        this.eventQueue[evt._id.$oid] = event;
        return true;
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
    constructor(data) {
        this.data = data;
    }

    isElligibleForDisplay() {
        const hasBeenClosed = this[this.storageMedium](this.data._id.$oid)?.closed ?? null;
        const includePathnameMatch = this.pathname(this.data.advanced.included_paths);

        // Let's get a malliable version of our pathnames
        let excludedPathnames = [...this.data.advanced.excluded_paths];
        // Check if an pathname has been specified. If it has, push it to the excluded list
        if (this.data.call_to_action_href) excludedPathnames.push(this.data.call_to_action_href);
        const excludePathnameMatch = this.pathname(excludedPathnames);

        if (hasBeenClosed === true) {
            return false
        };
        if (includePathnameMatch) return true;
        if (excludePathnameMatch) {
            return false;
        }
        return true;
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

        this.innerContent();
        this.insert();

        this.closeButton();
    }

    get classes() {
        return `cobalt-events--${this.data.type}`;
    }

    closeItem() {
        this[this.storageMedium](this.data._id.$oid, { closed: true, date: this.sessionPolicyDate });
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

    ctaButton() {
        if (this.data.call_to_action_href) {
            // Call to action
            const cta = document.createElement("a");
            cta.classList.add("cobalt-events--cta-button");
            cta.href = this.data.call_to_action_href;
            cta.innerText = this.data.call_to_action_prompt;
            cta.addEventListener("click", e => {
                e.preventDefault();
                window.router.location = cta.href;
                this.closeItem();
                
            }, { once: true });
            cta.style.backgroundColor = this.data.btnColor;
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

        close.addEventListener("click", e => this.closeItem(), { once: true })
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












if (app("CobaltEvents_enabled")) window.CobaltEventManager = new CobaltEvents();