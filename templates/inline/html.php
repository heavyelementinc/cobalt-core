<script <?= nonce() ?>>
// Some user agents don't support (or don't enable) JavaScript. Therefore we
// should keep track of any content that would be hidden because of JS and
// style around that issue.
document.getElementsByTagName("html")[0].classList.add("js");
if(matchMedia("prefers-reduced-motion").matches == false) {
    document.getElementsByTagName("html")[0].classList.add("_parallax");
}

window.__ = JSON.parse(atob('@get_exportables_as_json(true);'));

/** @return mixed|array{Cobalt\Settings\Settings::DEFAULT_DEFINITIONS} */
function app(setting = null) {
    if ("GLOBAL_SETTINGS" in document === false) document.GLOBAL_SETTINGS = JSON.parse(document.querySelector("#app-settings").innerText);
    if (setting === null) return document.GLOBAL_SETTINGS;
    if (setting in document.GLOBAL_SETTINGS) return document.GLOBAL_SETTINGS[setting];
    throw new Error("Could not find that setting");
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


/**
 * Will convert units to pixels or return the same string
 * @param {String} ccsValue - A CSS value formatted as a string
 * @param {?HTMLElement} target - The element to derive relative values like `em` from
 * @param {Bool} error - Determines if this function can throw an error
 * @returns {?Number}
 */
 function cssToPixel( cssValue, target = null, error = true ) {
    if(cssValue === null) {
        if(error) throw new Error("CSS value not supplied");
        return null;
    }

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

    // return cssValue;
    return null;
}

/**
 * Will convert units to pixels or return the same string
 * @param {String} ccsValue - A CSS value formatted as a string
 * @param {?HTMLElement} target - The element to derive relative values like `em` from
 * @returns {?Number}
 */
function cssUnitToNumber(cssValue, target = null) {
    return cssToPixel(cssValue, target, false)
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

class Deferred {
    PROMISE_REJECTED = 0;
    PROMISE_PENDING  = 1;
    PROMISE_RESOLVED = 2;
    
    _props = {
        promise: null, 
        resolve: (value) => {},
        reject: (message) => {},
        state: this.PROMISE_PENDING,
    }
    constructor(callback = () => {}) {
        this._props.promise = new Promise(async (resolve, reject) => {
            this._props.resolve = resolve;
            this._props.reject = reject;
            await this.promise;
            callback(resolve, reject);
        });
    }

    get state() {
        return this._props.state;
    }

    get promise() {
        return this._props.promise;
    }

    resolve(value) {
        this._props.state = this.PROMISE_RESOLVED;
        return this._props.resolve(value);
    }

    reject(message) {
        this._props.state = this.PROMISE_REJECTED;
        return this._props.reject(message);
    }

    async await() {
        return this.promise;
    }

    then(callback) {
        return this.promise.then(callback);
    }
}

<?= view('src/ClientRouter.js') ?>
<?= view('src/Cobalt.js'); ?>
</script>