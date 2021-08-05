class UserMenu {
    constructor(container) {
        this.container = container;
        for (const i of container.querySelectorAll("li")) {
            this.menuItem(i);
        }
    }

    close() {
        this.container.classList.add("hidden");
    }

    menuItem(item) {
        const name = "UserMenu" + item.getAttribute("name");
        try {
            this[name](item);
        } catch (error) {

        }
    }

    UserMenuSignIn(element) {
        element.addEventListener("click", async e => {
            await logInModal();
            this.close()
        })
    }

    UserMenuSignOut(element) {
        element.addEventListener("click", e => {
            logOutConfirm();
            this.close();
        })
    }
}

