window.closeGlyph = "&#10006;"; // "✖️";
var universal_input_element_query = "input[name], select[name], textarea[name], input-switch[name], input-array[name], input-object-array[name], input-autocomplete[name]";

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
        url: app("Auth_login_page"),
        chrome: false,
    });
    await modal.draw();
    // new LoginFormRequest(modal.dialog.querySelector("form"), {});
}

async function logOutConfirm() {
    let api = new ApiFetch("/api/v1/logout", "GET", {})
    let result = await api.send(null, {})
    if (result.result) window.location.reload();
}

async function confirmModal(message, yes = "Okay", no = "Cancel") {
    const modal = new Modal({});
    modal.draw();
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
    if (typeof imageUrl === "object" && "src" in imageUrl) imageUrl = imageUrl.src;
    let lightbox_content = `<img src='${imageUrl}'>`;
    if (imageUrl.indexOf("youtube.com") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split("?v=")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    if (imageUrl.indexOf("youtu.be") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split(".be/")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    const modal = new Modal({
        parentClass: "lightbox",
        body: lightbox_content,
        chrome: null,
        clickoutCallback: e => true,
    });
    modal.draw();
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
async function modalConfirm(message, okay = "Okay", cancel = "Cancel", dangerous = false) {
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: message,
            chrome: {
                cancel: {
                    label: cancel,
                    callback: async (container) => {
                        resolve(false); // Resolve promise
                        return true; // Close modal window
                    }
                },
                okay: {
                    label: okay,
                    dangerous: dangerous,
                    callback: async (container) => {
                        resolve(true); // Resolve promise
                        return true; // Close modal window
                    }
                }
            },
            close_btn: false
        });
        modal.draw();
    });
}

async function modalInput(message, { okay = "Okay", cancel = "Cancel", pattern = "", value = "" }) {
    if (pattern) pattern = ` pattern="${pattern}" required`;
    if (value) value = ` value="${value.replace("\"", "&quot;")}"`;
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: `<p>${message}</p><input type="text" name="modalInputField"${pattern}${value}>`,
            classes: "modal-window--input",
            chrome: {
                cancel: {
                    label: cancel,
                    callback: async (container) => {
                        resolve(false);
                        return true;
                    }
                },
                okay: {
                    label: okay,
                    callback: async (container) => {
                        const val = modal.dialog.querySelector("[name=\"modalInputField\"]");
                        if (val.validity.valueMissing) return false;
                        if (val.validity.patternMismatch) return false;
                        resolve(val.value);
                        return true;
                    }
                }
            }
        });
        modal.draw();
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

function float_pad(number, pad = 2, padWith = 0) {
    let num = String(number).split(".");
    if (!num[1]) return `${num[0]}.`.padEnd(num[0].length + pad + 1, padWith);
    if (num[1].length >= pad) return `${num[0]}.${num[1].substr(0, pad)}`;
    const toAdd = String(number).length + pad - 1;
    return `${num[0]}.${num[1]}`.padEnd(toAdd, padWith);
}


/** @param str string */
function string_to_bool(str) {
    if (str === null) return null;
    return (['on', 'true', 'y', 'yes', 'checked'].includes(str.toLowerCase())) ? true : false;
}


function plurality(number, returnValue = "s") {
    if (number == 1) return "";
    return returnValue;
}


function get_form_elements(form) {
    const elements = form.querySelectorAll(window.universal_input_element_query);
    let el_list = [];
    for (let el of elements) {
        iface = get_form_input(el, form);
        el_list[iface.name] = iface;
    }
    return el_list;
}

function get_form_input(el, form) {
    const name = el.getAttribute("name");
    if (!name) return false;
    let type = el.getAttribute("type") || "default";
    switch (el.tagName) {
        case "TEXTAREA":
            type = 'textarea';
            break;
        case "SELECT":
            type = 'select';
            break;
        case "INPUT-SWITCH":
            type = "switch";
            break;
        case "INPUT-ARRAY":
            type = "array";
            break;
        case "INPUT-OBJECT-ARRAY":
            type = "objectArray";
            break;
    }
    if (type in classMap === false) type = "default";
    return new classMap[type](el, { form: form });
}

/** Must have a class name provided and that class name should have all props
 * needed for an animation to occur.
 * @param element the element we want the animation to play on
 * @param animationClass the class to be applied to the animation
 * @param removeClass (true) determines if we want to clean up after animation
 *        has been played
 * @param maxDuration the maximum wait time before we cancel waiting
 * */
async function wait_for_animation(element, animationClass, removeClass = true, maxDuration = 2000) {
    if (!element) return;
    return new Promise((resolve, reject) => {
        element.addEventListener("animationend", e => {
            resolve();
            if (removeClass) element.classList.remove(animationClass);
            clearTimeout(timeout);
        }, { once: true });
        if (typeof animationClass === "string") animationClass = [animationClass];
        element.classList.add(...animationClass);
        if (element.style.animationPlayState !== "running") element.style.animationPlayState = "running";
        let timeout = setTimeout(() => {
            resolve();
            if (removeClass) element.classList.remove(animationClass);
        }, maxDuration);
    });
}

/** Pass it an element and it will return an object with the following properties
 * 
 *   * x      - Left edge calculated including all non-static offsets
 *   * y      - Top edge calculated including all non-static offsets
 *   * h      - The height of the element
 *   * w      - The width of the element
 *   * xPrime - Left edge of the element
 *   * yPrime - Top edge of the element
 *   * right  - The right edge of the element
 *   * bottom - The bottom edge of the element
 */
function get_offset(element) {
    let parent = element.offsetParent,
        offsetParentX = 0,
        offsetParentY = 0,
        zIndex = 0;

    // Just use a while loop.
    while (parent) {
        offsetParentX += parent.offsetLeft;
        offsetParentY += parent.offsetTop;
        if (getComputedStyle(parent).zIndex > zIndex) zIndex = getComputedStyle(parent).zIndex;
        parent = parent.offsetParent;
    }

    // Get the values for the actual element
    let x = element.offsetLeft + offsetParentX,
        y = element.offsetTop + offsetParentY,
        w = element.offsetWidth,
        h = element.offsetHeight,
        xPrime = element.offsetLeft,
        yPrime = element.offsetTop,
        right = x + w,
        bottom = y + h;

    return { x, y, w, h, right, bottom, xPrime, yPrime, zIndex }
}

/** Binary Contrast */
function colorMathBlackOrWhite(bgColor, lightColor = "#FFFFFF", darkColor = "#000000") {
    var color = bgColor.replace("#", '');
    // Parse out our color values
    var r = parseInt(color.substring(0, 2), 16);
    var g = parseInt(color.substring(2, 4), 16);
    var b = parseInt(color.substring(4, 6), 16);
    var uicolors = [r / 255, g / 255, b / 255];
    var c = uicolors.map((col) => {
        if (col <= 0.03928) {
            return col / 12.92;
        }
        return Math.pow((col + 0.055) / 1.055, 2.4);
    });
    var L = (0.2126 * c[0]) + (0.7152 * c[1]) + (0.0722 * c[2]);
    return (L > 0.179) ? darkColor : lightColor;
}

function normalize_all($schema, $data) {
    if ($data instanceof Iterator) $data = iterator_to_array($data);
    return array_map(
        function ($doc) { return new $schema($doc); },
        $data
    );
}

function distanceInKM(lat1, lon1, lat2, lon2) {
    var p = 0.017453292519943295;    // Math.PI / 180
    var c = Math.cos;
    var a = 0.5 - c((lat2 - lat1) * p) / 2 +
        c(lat1 * p) * c(lat2 * p) *
        (1 - c((lon2 - lon1) * p)) / 2;

    return 12742 * Math.asin(Math.sqrt(a)); // 2 * R; R = 6371 km
}