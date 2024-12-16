/** TabNav - The Cobalt Engine Tabbed Navigation webcomponent
 */

 class TabNav extends HTMLElement {
    constructor() {
        super();
        this.props = {
            initialNavStyle: null
        }
        this.currentNavClass = "tab-nav--current-tab";
        this.currentContentClass = "tab-nav--current-content";
        this.TABNAV_ERROR = "tab-nav--validation-issue";
        this.nav = this.querySelector("nav");
        this.mode = (this.tagName === "TAB-NAV") ? 1 : 2;
        if(this.mode === 1 && this.getAttribute('type') === null) this.mode = 10;
        if(!this.nav) console.warn("`tab-nav` is missing a `nav` element",this);
    }
    
    connectedCallback() {
        this.initialNavStyle = this.getAttribute("type");
        this.classList.add("tab-nav--hydrated");
        this.init();
        this.mediaCallback();
        window.addEventListener("resize", this.mediaCallback.bind(this));
    }

    disconnectedCallback() {
        window.removeEventListener("hashchange", this.hashUpdate);
        window.removeEventListener("resize", this.mediaCallback);
    }

    init() {
        window.addEventListener("hashchange",this.hashUpdate.bind(this),{once: true});
        
        this.nav.querySelectorAll("a").forEach(e => {
            const url = new URL(e.href).hash;
            if(!url) {
                console.warn("URL is missing a hash location", e);
                e.setAttribute("disabled","disabled");
                return;
            }
            const content = this.querySelector(url);
            if(!content) {
                e.setAttribute("disabled","disabled");
                return;
            }

            content.addEventListener("validationissue", event => {
                e.classList.add(this.TABNAV_ERROR);
            });

            const hgroupselector = content.querySelector("hgroup:first-child");
            if(!hgroupselector) this.generateHgroup(e,content);
            e.addEventListener("click", evt => {
                // evt.preventDefault();
                // evt.stopPropagation();
                // history.replaceState({},'',e.href);
                // this.hashUpdate();
                e.classList.remove(this.TABNAV_ERROR);
            });
        })

        this.hashUpdate({});
    }

    checkIfError(navLink) {
        const content = this.querySelector(new URL(navLink.href).hash);
        if(!content) return;
        const error = content.querySelectorAll(`[aria-invalid="true"]`);
        if(error.length !== 0) navLink.classList.add(this.TABNAV_ERROR);
        else navLink.classList.remove(this.TABNAV_ERROR);
    }

    generateHgroup(anchor, target) {
        const hgroup = document.createElement("hgroup");
        hgroup.innerHTML = `<h2>${anchor.innerHTML}</h2>`;
        target.insertBefore(hgroup, target.firstElementChild);
    }

    hashUpdate(event = {}) {
        let newHash = location.hash || window.location.hash;
        if(!newHash || newHash === "#") newHash = new URL(this.nav.querySelector("a[href]").href).hash;
        newHash = newHash;

        const anchors = this.nav.querySelectorAll("a");
        anchors.forEach(e => {
            e.classList.remove(this.currentNavClass);
            this.checkIfError(e);
        });

        const anchor = this.nav.querySelector(`[href='${newHash}']`);
        if(anchor) anchor.classList.add(this.currentNavClass);

        const content = this.querySelectorAll(`.${this.currentContentClass}`);
        if(content) content.forEach(e => e.classList.remove(this.currentContentClass));
        
        const target = this.querySelector(newHash);
        if(target) target.classList.add(this.currentContentClass);

        window.addEventListener("hashchange",this.hashUpdate.bind(this),{once:true});
    }

    mediaCallback(event = {}) {
        // If we're a chip nav, we want to always stay a row
        if(this.constructor.name !== "TabNav") return;
        // If the type was set when the value was initialized, we want to stay with that explicit definition
        if(this.props.initialNavStyle !== null) return;
        // If we match this media query then we know we're a mobile screen
        if(window.matchMedia("(max-width: 35em").matches == true) {
            // We're a small screen, so let's check if we should change the layout of our tabnav
            this.setAttribute("type", "row")
            return;
        }
        // Otherwise we remove the type attribute
        this.removeAttribute("type");
    }
}

customElements.define("tab-nav", TabNav);

class ChipNav extends TabNav {

}

customElements.define("chip-nav", ChipNav);
