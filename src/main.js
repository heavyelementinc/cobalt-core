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

// function user_menu() {
//     const menu_button = document.querySelector("#user-menu-button");
//     if (!menu_button) return;
//     const menu_container = document.querySelector("#user-menu-container");
//     menu_container.style.top = `${document.querySelector("header").offsetHeight}px`
//     flyoutHandler(menu_button, menu_container);

//     const sign_out = document.querySelector("#main-menu-sign-out");
//     const menu = new UserMenu(menu_container);
// }

// user_menu();

async function switchUserAccounts() {
    const modal = new Modal({id: "switchUser", chrome: {okay: null}, body: "<h2>Switch User</h2><loading-spinner></loading-spinner>"});
    modal.draw();
    const api = new AsyncFetch("/api/v1/session/authenticated/", "GET");
    const details = await api.get();
    const container = modal.container.querySelector(".modal-body");
    const spinner = container.querySelector("loading-spinner");
    spinner?.parentNode?.removeChild(spinner);

    let index = 0;
    for(const user of details) {
        const btn = document.createElement("button");
        btn.classList.add("user-login--avatar-button");
        btn.innerHTML = `${user.avatar}<label>${user.name}</label><span class="unread">${user.unread?.unseen || ""}</span>`;
        container.appendChild(btn);
        const userIndex = index;
        btn.addEventListener("click", async () => {
            const login = new AsyncFetch(`/api/v1/session/switch/${userIndex}`, "PUT");
            const completed = await login.get();
            modal.close();
            if(completed) location.reload();
        });
        index += 1;
    }
}