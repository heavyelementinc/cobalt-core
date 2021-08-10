
/**
 * Supports dates and (will ultimately) support the exact same date formatting
 * string as https://www.php.net/manual/en/datetime.format.php
 * 
 * @param date Either an int (Unix micro) or { $date: { $numberLong: "(long)" } }
 * @param output The string which will be used to format the date
 */
class DateConverter {
    constructor(date, output = "Y-m-d", tz = "America/New_York") {
        this.tz = tz;
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

        // let offset = d.getTimezoneOffset();

        // this.date = new Date(d.getTime() + offset * 60 * 1000);
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
        return (time < 12) ? "am" : "pm";
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
        // Handle special cases
        switch (String(date).substr(date.length - 2)) {
            case "11":
            case "12":
            case "13":
                return "th";
        }
        date = date[date.length - 1];
        return this.ordinals[Number(date)];
    }

    zeroPrefix(number, threshold = 10) {
        if (number < threshold) return `0${number}`;
        return number;
    }

}

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