window.Cobalt = {
    announce: (announcement) => {
        const div = document.createElement("div");
        div.innerText = announcement;
        const screenReaderAnnounceArea = document.querySelector("#sr-announce");
        screenReaderAnnounceArea.appendChild(div);
        setTimeout(() => {
            div.parentNode.removeChild(div);
        }, 1000);
    }
};
window.closeGlyph = "<span class='close-glyph'></span>"; // "✖️";
var universal_input_element_query = "input[name]:not([type='radio']), select[name], textarea[name], markdown-area[name], input-text[name], input-number[name], input-switch[name], input-user[name], input-array[name], input-binary[name], input-user-array[name], input-object-array[name], input-datetime[name], input-autocomplete[name], input-password[name], input-tag-select[name], radio-group[name]";

function isRegisteredWebComponent(tag) {
    return !!customElements.get(tag.toLowerCase());
}

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

window.viewport_lock_level = 0;

function lock_viewport() {
    window.viewport_lock_level += 1;
    let width = get_offset(document.body).w;
    document.body.style.overflow = "hidden";
    document.body.style.width = `${width}px`;
}

function unlock_viewport(ignore_lock_level = false) {
    if(ignore_lock_level == false) {
        window.viewport_lock_level -= 1;
        if(window.viewport_lock_level > 0) return;
    }
    window.viewport_lock_level = 0; // Just in case we've somehow unlocked more times than locked
    document.body.style.overflow = "unset";
    document.body.style.width = "unset";
}

function random_number(min, max) {
    return Math.floor(Math.random() * (max - min + 1) + min);
}

function indirectEval(str) {
    return eval?.(`"use strict";(${str})`);
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
    const modal = new Modal({
        event: event
    });
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
async function lightbox(origin, animate = true) {
    let imageUrl = null;
    if (typeof origin === "object") imageUrl = origin?.getAttribute("full-resolution") ?? origin.src ?? null;
    else imageUrl = origin;

    let lightbox_content = `<img src='${imageUrl}'>`;
    if (imageUrl.indexOf("youtube.com") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split("?v=")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    if (imageUrl.indexOf("youtu.be") !== -1) lightbox_content = `<iframe width="560" height="315" src="https://www.youtube.com/embed/${imageUrl.split(".be/")[1]}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
    const modal = new Modal({
        parentClass: "lightbox",
        body: lightbox_content,
        event: event,
        chrome: null,
        animate: animate,
        clickoutCallback: e => true,
    });
    modal.draw();

    return modal;
}

function shadowbox(element, group = false) {
    if(group === false) {
        if(element.hasAttribute("data-group")) {
            group = element.hasAttribute("data-group");
        }
    }
    const box = new Shadowbox(group, element);
    box.initUI();
    return box;
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
async function modalConfirm(message, okay = "Okay", cancel = "Cancel", dangerous = false, event = null) {
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: message,
            event: event,
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

async function modalInput(message, { okay = "Okay", cancel = "Cancel", pattern = "", value = "", type = "text", event = null }) {
    if (pattern) pattern = ` pattern="${pattern}" required`;
    if (value) value = ` value="${value.replace("\"", "&quot;")}"`;
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            body: `<p>${message}</p><input type="${type}" name="modalInputField"${pattern}${value}>`,
            classes: "modal-window--input",
            event: event,
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

async function modalForm(url, { okay = "Submit", cancel = "Cancel", additional_callback = async (result) => true, event = null }) {
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            url: url,
            event: event,
            chrome: {
                cancel: {
                    label: cancel,
                    callback: async () => {
                        reject("");
                        return true;
                    }
                },
                okay: {
                    label: okay,
                    callback: async (event) => {
                        const form = modal.dialog.querySelector("form-request");
                        let result = null;
                        try {
                            result = await form.send(event);
                            resolve(result);
                            return true;
                        } catch (error) {
                            reject(result);
                            throw new Error(result);
                        }
                        // if (!additional_callback(result)) return false;
                        // console.log(form.lastResult);
                        // if (String(result.status)[0] !== "2") return false;
                    }
                }
            }
        })
        modal.draw();
    });
}

function modalView(url, close = "Close") {
    return new Promise((resolve, reject) => {
        const modal = new Modal({
            url: url,
            event: event,
            chrome: {
                cancel: {
                    label: close,
                    callback: async () => {
                        resolve("");
                        return true;
                    }
                },
                okay: null
            }
        })
        modal.draw();
    });
}

async function dialogViewReplace(url) {
    const dialog = new Dialog({body: "<loading-spinner></loading-spinner>"});
    dialog.draw();
    const previous = Cobalt.router.location.currentRoute;
    dialog.addEventListener("modalclose", evt => {
        Cobalt.router.replaceState(previous);
    });
    const result = await Cobalt.router.replaceState(url, {target: dialog, updateProperty: "body"});
    return result;
}

async function dialogView(url) {
    const dialog = new Dialog({body: "<loading-spinner></loading-spinner>"});
    dialog.draw();
    const result = await Cobalt.router.popState(url, {target: dialog, updateProperty: "body"});
    return result;
}

/**
 * @param string string to test
 * @param pattern  pattern to test
 * @returns bool
 */
function matches(string, pattern) {
    let regex = pattern;
    if(typeof regex === "string") regex = new RegExp(pattern);
    const match = string.match(regex);
    if (match === null) return false;
    if (match.length <= 0) return false;
    return true;
}

// async function dialogView(url, close = "Close") {
//     const dialog = new Dialog({body: "<loading-spinner></loading-spinner>"});
//     dialog.draw();
//     const previous = Cobalt.router.location.currentRoute;
//     dialog.addEventListener("modalclose", evt => {
//         Cobalt.router.replaceState(previous);
//     });
//     const result = await Cobalt.router.replaceState(url, {target: dialog, updateProperty: "body"});
//     return result;
// }

function dialogConfirm(message, confirmLabel = "Okay", cancelLabel = "Cancel") {
    return new Promise(resolve => {
        const dialog = new Dialog({close_btn: false});
        dialog.addCloseButton(cancelLabel);
        dialog.addConfirmButton(confirmLabel);
        dialog.addEventListener("modalconfirm", e => resolve(true));
        dialog.addEventListener("modalcancel", e => resolve(false));
        dialog.draw(message)
    });
}

async function quickRequest(url, { data = {}, method = "POST", }) {
    return await new Promise((resolve, reject) => {
        const api = new ApiFetch(url, method, {});
        let result;
        try {
            result = api.send(data);
            resolve(result);
        } catch (Error) {
            reject(Error);
        }
        return result;
    });
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


/**
 * # string_to_bool
 * @description Converts a string like "true" or "false" to a boolean `true` or `false`
 * @param str the string to be evaluated
 * @param altName [true] if set to `true`, the value of str will be added to the list of truthy values to follow the standard HTML `attribute="attribute"` paradigm.
 * If altName is a string, it will be added to the list.
 * All comparisons are lower case.
 * */
function string_to_bool(str, altName = true) {
    if (str === null) return null;
    let truthy = ['on', 'true', 'y', 'yes', 'checked', 'selected'];
    if(altName === true) truthy.push(str.toLowerCase);
    else if(typeof altName === "string") truthy.push(altName.toLowerCase());
    else if(Array.isArray(altName)) truthy = [...truthy, ...altName.forEach(value => value.toLowerCase())];
    return truthy.includes(str.toLowerCase())
}

/**
 * # compare_arrays
 * @description Seeing as array comparisons are really wonky in JavaScript, compare_arrays looks to resolve this issue.
 * @param arr1 
 * @param arr2 
 * @param sort 
 * @returns bool
 */
function compare_arrays(arr1, arr2, sort = false) {
    // if(sort) {
        
    // }
    return JSON.stringify(arr1) === JSON.stringify(arr2);
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
        case "INPUT-TAG-SELECT":
            type = "tagSelect";
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
async function wait_for_animation(element, animationClass, removeClass = true, maxDuration = 2000, callback = (element) => { }) {
    if (!element) return;
    return new Promise((resolve, reject) => {
        element.addEventListener("animationend", e => {
            resolve();
            callback(element);
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

async function wait_for_transition(element, animationClass, removeClass = true, maxDuration = 2000, callback = (element) => { }) {
    if (!element) return;
    return new Promise((resolve, reject) => {
        element.addEventListener("transitionend", e => {
            resolve();
            callback(element);
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

/**
 * 
 * @param HtmlElement element 
 * @returns {
 *   x, // element + parents offset
 *   y, // element + parents offset
 *   w, // element width,
 *   h, // element height
 *   right,  // 
 *   bottom, // 
 *   xPrime, // The element's initial offset
 *   yPrime, // The element's initial offset
 *   zIndex, // The element's zIndex
 * }
 */
function get_offset(element) {
    if(element === null) throw new Error("Element must not be null!");
    let parent = element,
        offsetParentX = 0,
        offsetParentY = 0,
        scrollX = 0,
        scrollY = 0,
        zIndex = 0;
    if("scrollLeft" in element) {
        scrollX = element.scrollLeft;
        scrollY = element.scrollTop;
    }

    // Just use a while loop.
    while (parent) {
        offsetParentX += parent.offsetLeft;
        offsetParentY += parent.offsetTop;
        if (getComputedStyle(parent).zIndex > zIndex) zIndex = getComputedStyle(parent).zIndex;
        parent = parent.offsetParent;
    }

    parent = element;

    while (parent) {
        if(parent === document.body.parentNode) break;
        if("scrollLeft" in element) {
            scrollX += parent.scrollLeft || 0;
            scrollY += parent.scrollTop || 0;
        }
        parent = parent.parentNode;
    }

    // Get the values for the actual element
    let xNoScroll = offsetParentX,
        yNoScroll = offsetParentY,   
        x = xNoScroll - scrollX,
        y = yNoScroll - scrollY,
        w = element.offsetWidth,
        h = element.offsetHeight,
        xPrime = element.offsetLeft,
        yPrime = element.offsetTop,
        right = x + w,
        bottom = y + h;

    return { x, y, w, h, right, bottom, xPrime, yPrime, zIndex, xNoScroll, yNoScroll };
}

/** Binary Contrast */
function colorMathBlackOrWhiteOld(bgColor, lightColor = "#FFFFFF", darkColor = "#000000") {
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

/** Binary Contrast */
function colorMathBlackOrWhite(bgColor, lightColor = "#FFFFFF", darkColor = "#000000") {
    var color = bgColor.replace("#", "");
    var r = parseInt(color.substring(0, 2), 16);
    var g = parseInt(color.substring(2, 4), 16);
    var b = parseInt(color.substring(4, 6), 16);

    var L1 = Math.pow(0.2126 * r / 255 +
        0.7152 * g / 255 +
        0.0722 * b / 255, 2.2);

    if (L1 + 0.05 >= 0.5) {
        return darkColor;
    } else {
        return lightColor;
    }
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

function parse_params(param) {
    let decoded = decodeURI(param);
    let split = decoded.split("&");
    let object = {};
    for (const i of split) {
        const keyVal = i.split("=");
        object[keyVal[0]] = keyVal[1];
    }
    return object;
}

function encode_params(object) {
    let string = "";

    for (const i in object) {
        if (typeof object[i] === "object") {
            for (const key in object[i]) {
                string += `${i}[${key}]=${object[i][key]}&`;
            }
        } else string += `${i}=${object[i]}&`;
    }

    return string.substr(0, string.length - 1);
}

function set_cookie(name, value, days = "") {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function get_cookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function delete_cookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

function consent_cookie(value) {
    set_cookie('cookie_consent', value, 30);
    const consent_cookie = document.querySelector("#cookie-consent");
    consent_cookie.parentNode.removeChild(consent_cookie);
}

function spawn_priority(event) {
    let node = event;
    if ("originalTarget" in event) node = event.originalTarget;
    if ("target" in event) node = event.target;
    if ("parentNode" in node === false) return false;

    let zIndex = "auto";
    while (true) {
        if (node === null) break;
        try {
            zIndex = window.getComputedStyle(node).zIndex
        } catch (Error) {
            zIndex = "auto";
        }
        if (zIndex === "auto") {
            if ("parentNode" in node) node = node.parentNode;
        } else break;
    }

    console.log(zIndex);

    return (zIndex === "auto") ? false : zIndex;
}

function reflow() {
    return new Promise((resolve) => {
        return resolve(window.scrollX);
    });
}

/**
 * Will convert units to pixels or return the same string
 */
 function cssToPixel( cssValue, target = null, error = true ) {

    target = target || document.body;

    const supportedUnits = {

        // Absolute sizes
        'px': value => value,
        'cm': value => value * 38,
        'mm': value => value * 3.8,
        'q': value => value * 0.95,
        'in': value => value * 96,
        'pc': value => value * 16,
        'pt': value => value * 1.333333,

        // Relative sizes
        'rem': value => value * parseFloat( getComputedStyle( document.documentElement ).fontSize ),
        'em': value => value * parseFloat( getComputedStyle( target ).fontSize ),
        'vw': value => value / 100 * window.innerWidth,
        'vh': value => value / 100 * window.innerHeight,

        // Times
        'ms': value => value,
        's': value => value * 1000,

        // Angles
        'deg': value => value,
        'rad': value => value * ( 180 / Math.PI ),
        'grad': value => value * ( 180 / 200 ),
        'turn': value => value * 360

    };

    // Match positive and negative numbers including decimals with following unit
    const pattern = new RegExp( `^([\-\+]?(?:\\d+(?:\\.\\d+)?))(${ Object.keys( supportedUnits ).join( '|' ) })$`, 'i' );

    // If is a match, return example: [ "-2.75rem", "-2.75", "rem" ]
    const matches = String.prototype.toString.apply( cssValue ).trim().match( pattern );

    if ( matches ) {
        const value = Number( matches[ 1 ] );
        const unit = matches[ 2 ].toLocaleLowerCase();
        
        // Sanity check, make sure unit conversion function exists
        if ( unit in supportedUnits ) {
            return Math.round(supportedUnits[unit]( value ) * 10) / 10;
        } else if (error) throw Error("The value supplied cannot be converted");
    }

    return cssValue;
}

function iOS() {
    if("platform" in navigator === false) return (navigator.userAgent.includes("Mac") && "ontouchend" in document);
    return [
      'iPad Simulator',
      'iPhone Simulator',
      'iPod Simulator',
      'iPad',
      'iPhone',
      'iPod'
    ].includes(navigator.platform);
}

function imagePromise(url) {
    return new Promise(async (resolve, reject) => {
        const img = new Image();
        img.addEventListener("load", () => {
            resolve(url);
        });
        img.addEventListener();
        img.addEventListener("error",() => {
            resolve(null);
        })
        img.src = url;
    })
}

/**
 * Returns a string
 * @param {string} url 
 * @param {string|element} throbber 
 * @param {string|element} progressBar 
 * @returns string
 */
function getBlobWithLoadingBar(url, throbber, progressBar) {
    return new Promise(async (resolve, reject) => {

        try{
            if(typeof throbber === "string" && throbber) throbber = document.querySelector(throbber);
        } catch (error) {
            throbber = null;
        }
        if(throbber) {
            throbber.style.transition = "opacity .5s";
            throbber.style.opacity = "1";
        }
        if(typeof progressBar === "string") progressBar = document.querySelector(progressBar);
        
        if(progressBar) {
            progressBar.value = 0;
            progressBar.max = 100;
        }

        const client = new XMLHttpRequest();

        client.open("GET", url);
        client.responseType = 'blob';
        client.onprogress = (event) => {
            if(!progressBar) return;
            if(!event.lengthComputable) return;
            progressBar.max = event.total;
            progressBar.value = event.loaded;
        }
        
        client.onload = (event) => {
            const blobAddress = URL.createObjectURL(client.response);
            resolve(blobAddress);
            if(throbber) throbber.style.opacity = "0";
        }

        client.onerror = (event) => {
            reject(event);
            if(throbber) throbber.style.opacity = "0";
        }
        
        client.send();
    })
}

function copyToClipboard(valueToCopy) {
    const element = document.createElement("input");
    document.body.appendChild(element);
    element.value = valueToCopy;
    element.select();
    element.setSelectionRange(0, valueToCopy.length + 1);

    navigator.clipboard.writeText(element.value);
    
    document.body.removeChild(element);

    const message = new StatusMessage({
        message: "Copied text to clipboard",
        duration: 4000
    })

    // setTimeout(() => message.close(), 4000);
}

function removeNearest(element, ancestorSelector) {
    let ancestor = element.closest(ancestorSelector);
    ancestor.parentNode.removeChild(ancestor);
}

function promiseTimeout(callback, value) {
    return Promise((resolve, reject) => {
        setTimeout(() => {
            resolve(callback());
        }, value);
    })
}

function getTabId() {
    if (window.sessionStorage.tabId) {
        return window.sessionStorage.tabId;
    }
    const tabId = Math.floor(Math.random() * 1000000) + Math.floor(Math.random() * 1000000);
    window.sessionStorage.tabId = tabId;
    return tabId;
}

getTabId();

class Rt {
    get location() {
        this.warn();
        return Cobalt.router.location.URL.pathname;
    }

    set location(loc) {
        this.warn();
        Cobalt.router.location = loc;
    }

    warn() {
        console.warn("Your app is using a deprecated Cobalt API! Please change any reference to `router.location` to utilize `Cobalt.router.location`");
    }
}

window.router = new Rt();

function upload_field_update(element) {
    const name = element.name;
    const container = element.closest(`.upload-field`);
    const previewTarget = container.querySelector("img");
    // previewTarget.src = 
}

function dateFromObjectId(objectId) {
	return new Date(parseInt(objectId.substring(0, 8), 16) * 1000);
};