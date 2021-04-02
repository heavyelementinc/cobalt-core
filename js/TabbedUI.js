class TabbedUI {
    constructor(tab_buttons, tabItemContainer = null) {
        this.tabItemContainer = tabItemContainer;
        this.tab_buttons = [];
        this.drawer_list = [];
        for (const i of tab_buttons) {
            this.tab_buttons.push(i);
            this.init_tab(i);
        }
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
    }

    select_current_drawer(drawer, tab, event) {
        for (const d of this.drawer_list) {
            d.classList.remove("drawer-list--active");
        }
        drawer.classList.add("drawer-list--active");
    }
}