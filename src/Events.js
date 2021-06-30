class CobaltEvents {
    constructor() {
        this.eventQueue = [];
        this.currentEvents = [];
        this.eventTypes = {
            default: CobaltEvent_default
        }
        this.hasAnotherEventBeenShown = false;
        this.api = new ApiFetch('/api/v1/cobalt-events/current/', 'GET', {});
        this.init();
    }

    async init() {
        await this.getCurrentEvents();
        for (const evt of this.currentEvents) {
            if (!this.initializeEvent(evt)) break;
        }
    }

    async getCurrentEvents() {
        this.currentEvents = await this.api.get();
    }

    initializeEvent(evt) {
        let type = "default";
        if (evt.type in this.eventTypes) type = evt.type;
        const event = new this.eventTypes[type](evt);
        if (!event.isElligibleForDisplay()) return false;
        if (this.hasAnotherEventBeenShown) return false;
        this.hasAnotherEventBeenShown = true;
        event.draw();
        this.eventQueue[evt._id.$oid] = event;
        return true;
    }

}

class CobaltEvent_default {
    constructor(data) {
        this.data = data;
    }

    draw() {
        this.element = document.createElement("div");
        this.element.classList.add("cobalt-events--default", this.classes());
        this.insert();
    }

    classes() {
        return "cobalt-events--banner";
    }

    insert() {

    }

    isElligibleForDisplay() {
        const hasBeenClosed = this[this.storageMedium()](this.data._id.$oid)?.closed ?? null;
        if (hasBeenClosed === true) return false;
        return true;
    }

    closeItem() {
        this[this.storageMedium()](this.data._id.$oid, { closed: true, date: new Date() });
    }













    storageMedium() {
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
        window[type].setItem(JSON.stringify(value));
    }
}















if (app("CobaltEvents_enabled")) window.CobaltEventManager = new CobaltEvents();