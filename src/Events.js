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
            if (this.initializeEvent(evt)) break;
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
        await this.timeout(event.advanced?.delay || 0);
        event.draw();
        event.element.addEventListener("cobaltEventsClosed", e => this.eventClosure(e));
        this.eventQueue[evt._id.$oid] = event;
        return true;
    }

    eventClosure(e) {
        console.log(e.detail)
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
        const excludePathnameMatch = this.pathname(this.data.advanced.excluded_paths);

        if (hasBeenClosed === true) return false;
        if (includePathnameMatch) return true;
        if (excludePathnameMatch) return false;
        return true;
    }

    pathname(paths, empty = true) {
        if (!paths || paths.length === 0) return empty;
        let match = false;
        for (let p of paths) {
            p = (p[0] !== "^") ? `^${p}` : p;
            const regex = new RegExp(p);
            match = window.location.pathname.match(regex);
            if (match) return match
        }
        return match;
    }

    draw() {
        this.element = document.createElement("div");
        this.element.id = this.data.id || random_string();
        this.element.classList.add("cobalt-events--default", this.classes);
        this.innerContent();
        this.insert();

        this.closeButton();
    }

    get classes() {
        return `cobalt-events--${this.data.type}`;
    }

    closeItem() {
        this[this.storageMedium](this.data._id.$oid, { closed: true, date: new Date() });
        this.element.dispatchEvent(new CustomEvent("cobaltEventsClosed", {
            detail: this.data._id.$oid || this.data._id
        }));
        this.dismiss();
    }

    get storageMedium() {
        const mediums = {
            nag: "noStorageHandler",
            with_session: "sessionStorageHandler",
        };
        let type = "localStorageHandler";
        if (this.data.session_policy in mediums) type = mediums[this.data.session_policy];
        return type;
    }

    noStorageHandler() {
        return null;
    }

    localStorageHandler(id, value) {
        return this.modernStorage("localStorage", id, value);
    }

    localStorageHandler(id, value) {
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

        if (this.data.call_to_action_href) {
            // Call to action
            const cta = document.createElement("a");
            cta.href = this.data.call_to_action_href;
            cta.innerText = this.data.call_to_action_prompt;
            cta.addEventListener("click", e => this.closeItem(), { once: true })
            this.element.appendChild(cta);
        }
    }

    /** Insert element into page */
    async insert() {
        document.body.parentNode.insertBefore(this.element, document.body);
    }

    /** Remove element from page */
    async dismiss() {
        await wait_for_animation(this.element, "cobalt-events--dismiss");
        this.element.parentNode.removeChild(this.element);
    }

    closeButton() {
        // Dismiss button
        const close = document.createElement("button");
        close.classList.add("cobalt-events--banner-close")
        close.innerHTML = window.closeGlyph;
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

        if (this.data.call_to_action_href) {
            // Call to action
            const cta = document.createElement("a");
            cta.href = this.data.call_to_action_href;
            cta.innerText = this.data.call_to_action_prompt;
            cta.addEventListener("click", e => this.closeItem(), { once: true })
            this.element.appendChild(cta);
        }
    }

    /** Insert element into page */
    async insert() {
        this.modal = new Modal({
            close_btn: false,
            chrome: false
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