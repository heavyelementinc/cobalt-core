/** Make this into a webcomponent? */
class TabbedUI {
    constructor(tab_buttons, tabItemContainer = null) {
        this.tabItemContainer = tabItemContainer;
        this.tab_buttons = [];
        this.drawer_list = [];
        let init = window.location.hash || location.hash;
        if (init) init = init.substr(1);
        for (const i of tab_buttons) {
            this.tab_buttons.push(i);
            this.init_tab(i);
            if (init && i.getAttribute('for') === init) {
                this.select_current_tab(i);
            }
        }
        window.addEventListener("hashchange", e => {
            this.select_current_tab(document.querySelector(`[for="${window.location.hash.substr(1)}"]`));
        })
        // this.tab_buttons[0];
    }

    init_tab(tab) {
        let id = "#" + tab.getAttribute("for");
        const el = this.tabItemContainer.querySelector(id);
        if (!el) return;
        this.drawer_list.push(el);
        tab.classList.add("tab-list--item");
        el.classList.add("drawer-list--item");
        tab.addEventListener('click', e => {
            this.select_current_tab(tab, e);
            this.select_current_drawer(el, tab, e)
        })

        return tab;
    }

    select_current_tab(tab, event) {
        for (const el of this.tab_buttons) {
            el.classList.remove("tab-list--active");
        }
        tab.classList.add("tab-list--active");
        window.location.hash = tab.getAttribute("for");
        this.select_current_drawer(document.querySelector(window.location.hash));
    }

    select_current_drawer(drawer, tab, event) {
        for (const d of this.drawer_list) {
            d.classList.remove("drawer-list--active");
        }
        drawer.classList.add("drawer-list--active");
    }
}