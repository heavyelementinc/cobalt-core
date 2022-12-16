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
