class UserManager {
    constructor() {
        this.tabs = new TabbedUI(document.querySelectorAll(".tab-list--tab-row button"), document.querySelector("#tab-list--parent"));
        this.basics = new FormRequest("#basic-info-form", {});
        this.permissions = new FormRequest("#permissions-form", {});
    }
}