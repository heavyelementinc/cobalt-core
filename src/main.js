var navigationEvent = new CustomEvent(
    "navigationEvent",
    {
        detail: {
            route: window.location
        },
        bubbles: true,
        cancelable: true
    }
);

document.dispatchEvent(navigationEvent);

function user_menu() {
    const menu_button = document.querySelector("#user-menu-button");
    if (!menu_button) return;
    const menu_container = document.querySelector("#user-menu-container");
    menu_container.style.top = `${document.querySelector("header").offsetHeight}px`
    flyoutHandler(menu_button, menu_container);

    const sign_out = document.querySelector("#main-menu-sign-out");
    const menu = new UserMenu(menu_container);
}

user_menu();

function navigation_menu() {
    const spawner = document.querySelector("#nav-menu-spawn"),
        visibilityController = document.querySelector("#nav-menu-spawn-nojs"),
        name = "js-nav-spawned",
        functs = {
            menuHidden: () => {
                document.body.classList.remove(name);
                document.body.style.overflow = "unset";
                document.body.style.width = "unset";
            },
            menuVisible: () => {
                document.body.classList.add(name);
                let width = get_offset(document.body).w;
                document.body.style.overflow = "hidden";
                document.body.style.width = `${width}px`
            },
            handle: () => {
                if (!visibilityController.checked) functs.menuVisible();
                else functs.menuHidden();
            }
        };
    // Handle when the menu is already open when loading the page.
    if (visibilityController.checked) {
        functs.menuVisible();
    }

    // When the button's clicked
    spawner.addEventListener("click", e => functs.handle());
}

navigation_menu();