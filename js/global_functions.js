window.closeGlyph = "&#10006;"; // "✖️";

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

/**
 * Supports dates and (will ultimately) support the exact same date formatting
 * string as https://www.php.net/manual/en/datetime.format.php
 * 
 * @param date Either an int (Unix micro) or { $date: { $numberLong: "(long)" } }
 * @param output The string which will be used to format the date
 */
class DateConverter {
    constructor(date, output = "Y-m-d") {
        this.tokens = {
            // DAY
            "d": "getDateWithLeadingZero",
            "D": "getShortTextualDayOfWeek",
            "j": "getDayOfMonthNoLeadingZero",
            "l": "getFullTextualDayOfWeek",
            "N": "getWeekdayNumber",
            "S": "getDateOrdinalSuffix",

            // Week


            // Month
            "F": "getTextualMonth",
            "m": "getMonthWithZeros",
            "M": "getShortTextualMonth",
            "n": "getMonthNoZeros",

            // Year
            "Y": "getFullYear",
            "y": "getTwoDigitYear",

            // Time
            "a": "getMeridiem",
            "A": "getMeridiemUppercase",
            "g": "get12HourNoZero",
            "G": "get24HourNoZero",
            "h": "get12HourWithZero",
            "H": "get24HourWithZero",

            "i": "getMinuteWithZero",
            "s": "getSecondWithZero"

            // Timezone

            // Full Date/Time
        }

        this.months = this.generateMonthList();
        this.weekdays = this.generateWeekdayList();
        this.ordinals = this.generateOrdinalsList();

        switch (typeof date) {
            case "string":
                if (/^\d+$/.test(date)) {
                    date = Number(date);
                    break;
                } else {
                    date = Number(JSON.parse(date).$date.$numberLong);
                    try {
                    } catch (error) {
                        console.log(error);
                    }
                }
                break;
            case "object":
                date = Number(date.$date.$numberLong);
                break;
            case "number":
                date = date;
                break;
            default:
                throw new Error("Cannot construct a valid date for item.");
        }

        this.date = new Date(date);
        this.output = output;
    }

    /** The primary method */
    format() {
        let formatted = "";
        let output = this.output;
        for (let i = 0; i < output.length; i++) {
            if (output[i] in this.tokens) {
                formatted += this[this.tokens[output[i]]]();
                continue;
            }
            formatted += output[i];
        }
        return formatted;
    }

    generateMonthList() {
        return [{ name: "January", short: "Jan", num: "01", }, { name: "February", short: "Feb", num: "02", }, { name: "March", short: "Mar", num: "03" }, { name: "April", short: "Apr", num: "04" }, { name: "May", short: "May", num: "05" }, { name: "June", short: "Jun", num: "06" }, { name: "July", short: "Jul", num: "07" }, { name: "August", short: "Aug", num: "08" }, { name: "September", short: "Sep", num: "09" }, { name: "October", short: "Oct", num: "10" }, { name: "November", short: "Nov", num: "11" }, { name: "December", short: "Dec", num: "12" }];
    }

    /** @todo implement locale/first day of week */
    generateWeekdayList() {
        let dow = [{ name: "Sunday", short: "Sun", }, { name: "Monday", short: "Mon", }, { name: "Tuesday", short: "Tue", }, { name: "Wednesday", short: "Wed", }, { name: "Thursday", short: "Thu", }, { name: "Friday", short: "Fri", }, { name: "Saturday", short: "Sat", }];
        for (const i of dow) {
            dow[i] = {
                ...dow[i],
                num: i + 1
            }
        }
        return dow;
    }

    generateOrdinalsList() {
        return ["th", "st", "nd", "rd", "th", "th", "th", "th", "th", "th"];
    }

    getMinuteWithZero() {
        return this.zeroPrefix(this.date.getMinutes());
    }

    getSecondWithZero() {
        return this.zeroPrefix(this.date.getSeconds());
    }

    get24HourNoZero() {
        return this.date.getHours();
    }

    get12HourNoZero() {
        const hour = this.get24HourNoZero();
        return (hour > 12) ? hour - 12 : hour;
    }

    get24HourWithZero() {
        return this.zeroPrefix(this.get24HourNoZero())
    }

    get12HourWithZero() {
        return this.zeroPrefix(this.get12HourNoZero())
    }

    getMeridiem() {
        const time = this.date.getHours();
        return (time > 11) ? "am" : "pm";
    }

    getMeridiemUppercase() {
        return this.getMeridiem().toUpperCase();
    }

    getFullYear() {
        return this.date.getFullYear();
    }

    getTwoDigitYear() {
        const year = String(this.date.getFullYear());
        return year.substr(2);
    }

    getMonthWithZeros() {
        const month = this.date.getMonth();
        return this.months[month].num;
    }

    getMonthNoZeros() {
        return this.date.getMonth() + 1;
    }

    getTextualMonth() {
        const month = this.date.getMonth();
        return this.months[month].name;
    }

    getShortTextualMonth() {
        const month = this.date.getMonth();
        return this.months[month].short;
    }

    getDateWithLeadingZero() {
        let date = String(this.date.getDate());
        // if (date.length < 2) date = `0${date}`;
        return this.zeroPrefix(date);
    }

    getDayOfMonthNoLeadingZero() {
        return this.date.getDate();
    }

    getShortTextualDayOfWeek() {
        const date = this.date.getDay();
        return this.weekdays[date].short;
    }

    getFullTextualDayOfWeek() {
        const date = this.date.getDay();
        return this.weekdays[date].name;
    }

    getWeekdayNumber() {
        const date = this.date.getDay();
        return this.weekdays[date].num;
    }

    getDateOrdinalSuffix() {
        let date = String(this.date.getDate());
        date = date[date.length - 1];
        return this.ordinals[Number(date)];
    }

    zeroPrefix(number, threshold = 10) {
        if (number < threshold) return `0${number}`;
        return number;
    }

}


// function relativeTime({ date, current = null, limit = null, mode = "string" }) {
//     if (current === null) current = new Date();
//     const diff = current - date;

//     const units = {
//         second: { value: 1000, unit: "second", },
//         min: { value: this.second * 60, unit: "minute", },
//         hour: { value: this.min * 60, unit: "hour", },
//         day: { value: this.hour * 24, unit: "day", },
//         month: { value: this.day * 30, unit: "month", },
//         year: { value: this.day * 365, unit: "year", }
//     }
//     const unit_list = Object.keys(units);
//     let limit_index = unit_list.length - 1;
//     if (limit !== null && limit in units) limit_index = unit_list.indexOf(limit);

//     let qualifier = "";
//     let quantity = false;
//     let unit = "second";

//     for (let i = 0; i < unit_list.length; i++) {
//         const current_key = unit_list[i];
//         const next_key = unit_list[i + 1];
//         if (i === limit_index + 1) break;
//         if (diff < units[current_key].value === false) continue;
//         quantity = Math.round(diff / units[current_key].value);
//         unit = units[next_key].unit;
//         qualifier = units[next_key].qualifier;
//     }

//     if (quantity === false) return false;
//     if (mode === "object") return { qualifier, quantity, unit, plurality: plurality(quantity) }
//     return `${qualifier} ${quantity} ${unit}${plurality(quantity)} ago`;
// }

function relativeTime(prev, current = null, mode = "string", limit = "day") {
    if (current === null) current = new Date();

    const min = 60 * 1000;
    const hour = min * 60;
    const day = hour * 24;
    const month = day * 30;
    const year = day * 365;

    let diff = current - prev;

    let plural = "s";
    let qualifier = "";
    let quantity = 0;
    let unit = "second";

    switch (true) {
        case (diff < min):
            // return `${} second ago'`;
            quantity = Math.round(diff / 1000);
            if (quantity < 30) {
                quantity = "";
                unit = "moment";
            }
            break;
        case (diff < hour):
            // return `${Math.round(diff / min)} minute ago`;
            quantity = Math.round(diff / min);
            unit = "minute";
            break;
        case (diff < day):
            // return `${} hour ago`;
            quantity = Math.round(diff / hour);
            unit = "hour";
            break;
        case (diff < month):
            // return `${} day ago`;
            qualifier = "About";
            quantity = Math.round(diff / day);
            unit = "day";
            break;
        case (diff < year):
            // return `About ${} months ago`;
            qualifier = "About";
            quantity = Math.round(diff / month);
            unit = "month";
            break;
        default:
            quantity = false;
            break;
    }
    if (quantity === false) return false;
    let result = `${qualifier} ${quantity} ${unit}${plurality(quantity)} ago`;
    if (mode === "object") return { qualifier, quantity, plurality: plurality(quantity), unit, result, units: { second: 1000, min, hour, day, } };
    return result;
}


// function relativeTime(prev, current = null) {
//     if (current === null) current = new Date();

//     const units = {
//         min: { value: 60 * 1000 },
//         hour: { value: min * 60 },
//         day: { value: hour * 24 },
//         month: { value: day * 30 },
//         year: { value: day * 365 }
//     }

//     let diff = current - prev;

//     let plural = "s";
//     let quantity = 0;
//     let unit = "second";

//     switch (true) {
//         case (diff < min):
//             // return `${Math.round(diff / 1000)} second ago'`;
//             break;
//         case (diff < hour):
//             // return `${Math.round(diff / min)} minute ago`;
//             quantity = Math.round(diff / min);
//             break;
//         case (diff < day):
//             return `${Math.round(diff / hour)} hour ago`;
//         case (diff < month):
//             return `${Math.round(diff / day)} day ago`;
//         case (diff < year):
//             return `About ${Math.round(diff / month)} months ago`;
//     }

//     return false;
// }

function plurality(number, returnValue = "s") {
    if (number == 1) return "";
    return returnValue;
}

var universal_input_element_query = "input[name], select[name], textarea[name], input-switch[name], input-array[name], input-object-array[name]";

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
    }
    if (type in classMap === false) type = "default";
    return new classMap[type](el, { form: form });
}

/** Must have a class name provided and that class name should have an animation. */
async function wait_for_animation(element, animationClass, maxDuration = 2000) {
    return new Promise((resolve, reject) => {
        if (element.classList.contains(animationClass)) console.warn(`Element already has ${animationClass} as a class.`);
        element.addEventListener("animationend", e => resolve(), { once: true });
        element.classList.add(animationClass);
        if (element.style.animationPlayState !== "running") element.style.animationPlayState = "running";
        setTimeout(() => resolve(), maxDuration);
    });
}