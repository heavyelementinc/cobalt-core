function app(setting = null) {
    if ("GLOBAL_SETTINGS" in document === false) document.GLOBAL_SETTINGS = JSON.parse(document.querySelector("#app-settings").innerText);
    if (setting === null) return document.GLOBAL_SETTINGS;
    if (setting in document.GLOBAL_SETTINGS) return document.GLOBAL_SETTINGS[setting];
    throw new Error("Could not find that setting");
}

function random_string(length = 8, validChars = null) {
    let chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    if (validChars) chars = validChars;
    let string = "";
    for (let i = 0; i <= length; i++) {
        string += chars[random_number(0, chars.length - 1)];
    }
    return string;
}

function random_number(min, max) {
    return Math.floor(Math.random() * (max - min + 1) + min);
}

function flyoutHandler(button, menu, callback = null) {
    // Hide the menu
    menu.classList.add("hidden");

    // Establish our listener
    const documentClickHandler = function (e) {
        // Check if the item we clicked on is contained within the menu
        let isClickedOutside = !menu.contains(e.target);
        if (isClickedOutside) {

            // Add the 'hidden' class
            menu.classList.add('hidden');

            // Cleanup the event handler
            document.removeEventListener('click', documentClickHandler);
        }
    };

    button.addEventListener('click', async e => {
        menu.classList.remove("hidden");
        await new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve();
            }, 50)
        });
        document.addEventListener('click', documentClickHandler);
    })
}

async function logInModal() {
    const api = new ApiFetch(`/api/v1/page/?route=${encodeURI(app("Auth_login_page"))}`, "GET", {})
    let login_body = [];
    login_body = await api.send(null, {});
    try {
    } catch (e) {
        return false;
    }
    const modal = new Modal({
        id: "login-modal",
        body: login_body.body,
        chrome: false,
    });
    new LoginFormRequest(modal.modal.querySelector("form"), {});
}

async function logOutConfirm() {
    let api = new ApiFetch("/api/v1/logout", "GET", {})
    let result = await api.send(null, {})
    if (result.result) window.location.reload();
}

async function confirmModal(message, yes = "Okay", no = "Cancel") {
    const modal = new Modal({});
}

async function removeLoadingSpinner(spinner) {
    return new Promise((resolve, reject) => {
        if (!spinner) resolve();
        const timeout = setTimeout(() => {
            resolve();
            console.warn("Timeout", spinner);
        }, 1500)
        const anon = () => {
            clearTimeout(timeout);
            resolve();
            console.log(spinner);
            spinner.parentNode.removeChild(spinner)
        }
        spinner.addEventListener("transitionend", anon, { once: true });
        spinner.addEventListener("-moz-transitionend", anon, { once: true });
        spinner.addEventListener("-webkit-transitionend", anon, { once: true });
        spinner.style.opacity = 0;
    })
}


/**
 * Creates a lightbox popup window to display a full size image or a YouTube
 * video embed.
 * 
 * @param {string} imageUrl A URL to an image or a youtube.com/youtu.be video
 * @returns Modal object
 */
function lightbox(imageUrl) {
    let lightbox_content = `<img src='${imageUrl}'>`;
    if (imageUrl.indexOf("youtube.com") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split("?v=")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    if (imageUrl.indexOf("youtu.be") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split(".be/")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    const modal = new Modal({
        parentClass: "lightbox",
        body: lightbox_content,
        chrome: null,
        clickoutCallback: e => true,
    });
    return modal;
}

/**
 * An async modal confirm. If you await modalConfirm(), a promise will be
 * returned and when resolved, will be either true or false.
 * 
 * @todo Fix ugly nesting
 * @todo Figure out some way to prevent this from being callback hell.
 * @param {string} message The message to prompt the user with
 * @param {string} okay Button label for the TRUE option
 * @param {string} cancel Button label for the FALSE option
 * @returns Promise which resolves to either true or false. Cannot reject.
 */
async function modalConfirm(message, okay = "Okay", cancel = "Cancel") {
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: message,
            chrome: {
                cancel: {
                    label: cancel,
                    callback: async (event) => {
                        resolve(false); // Resolve promise
                        return true; // Close modal window
                    }
                },
                okay: {
                    label: okay,
                    callback: async (event) => {
                        resolve(true); // Resolve promise
                        return true; // Close modal window
                    }
                }
            }
        });
    });
}

async function modalInput(message, { okay = "Okay", cancel = "Cancel", pattern = "" }) {
    if (pattern) pattern = ` pattern="${pattern}" required`
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: `<p>${message}</p><input type="text" name="modalInputField"${pattern}>`,
            classes: "modal-window--input",
            chrome: {
                cancel: {
                    label: cancel,
                    callback: async (event) => {
                        resolve(false);
                        return true;
                    }
                },
                okay: {
                    label: okay,
                    callback: async (event) => {
                        const val = modal.modal.querySelector("[name=\"modalInputField\"]");
                        if (val.validity.valueMissing) return false;
                        if (val.validity.patternMismatch) return false;
                        resolve(val.value);
                        return true;
                    }
                }
            }
        });
    })
}

function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };

    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
}