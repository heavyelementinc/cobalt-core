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
    const header = document.querySelector("#nav-menu-spawn-nojs + header"),
        visibilityController = document.querySelector("#nav-menu-spawn-nojs"),
        spawner = document.querySelector("#nav-menu-spawn"),
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
            },
            accessibility: (state = true) => {
                console.log(state);
                if (visibilityController.style.display !== "none") {
                    spawner.checked = state;
                    spawner.dispatchEvent(new Event("change"));
                    functs.handle();
                }
            }
        };
    // Handle when the menu is already open when loading the page.
    if (visibilityController.checked) {
        functs.menuVisible();
    }

    // When the button's clicked
    spawner.addEventListener("click", e => functs.handle());

    // header.addEventListener("focusin", e => functs.accessibility())

    // header.addEventListener("focusout", e => functs.accessibility(false))
}

navigation_menu();