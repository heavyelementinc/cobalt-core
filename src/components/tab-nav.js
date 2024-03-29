/** TabNav - The Cobalt Engine Tabbed Navigation webcomponent
 */

 class TabNav extends HTMLElement {
    constructor() {
        super();
        this.currentNavClass = "tab-nav--current-tab";
        this.currentContentClass = "tab-nav--current-content";
        this.nav = this.querySelector("nav");
        this.mode = (this.tagName === "TAB-NAV") ? 1 : 2;
        if(this.mode === 1 && this.getAttribute('type') === null) this.mode = 10;
        if(!this.nav) console.warn("`tab-nav` is missing a `nav` element",this);
    }
    
    connectedCallback() {
        this.classList.add("tab-nav--hydrated");
        this.init();
    }

    disconnectedCallback() {
        window.removeEventListener("hashchange", this.hashUpdate);
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
            if(!content) e.setAttribute("disabled","disabled");
            const hgroupselector = content.querySelector("hgroup:first-child");
            if(!hgroupselector) this.generateHgroup(e,content);
            e.addEventListener("click", evt => {
                // evt.preventDefault();
                // evt.stopPropagation();
                // history.replaceState({},'',e.href);
                // this.hashUpdate();
            });
        })

        this.hashUpdate({});
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
        });

        const anchor = this.nav.querySelector(`[href='${newHash}']`);
        if(anchor) anchor.classList.add(this.currentNavClass);

        const content = this.querySelectorAll(`.${this.currentContentClass}`);
        if(content) content.forEach(e => e.classList.remove(this.currentContentClass));
        
        const target = this.querySelector(newHash);
        if(target) target.classList.add(this.currentContentClass);

        window.addEventListener("hashchange",this.hashUpdate.bind(this),{once:true});
    }
}

customElements.define("tab-nav", TabNav);

class ChipNav extends TabNav {

}

customElements.define("chip-nav", ChipNav);
