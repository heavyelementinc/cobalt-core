/**
 * @author Gardiner Bryant
 * 
 * This class does three things;
 *      * provides background image scrolling
 *      * provides element scrolling
 *      * lazy-reveals elements by adding a class
 * 
 * PARALLAX BACKGROUND
 * <div parallax-mode="background"></div>
 * 
 * PARALLAX ITEM TRANSFORMATION
 * <icon parallax-mode="position" parallax-speed="2.3"></icon>
 * 
 * Note that the 'parallax-speed' attribute is optional but allows you to move
 * items at different speeds.
 * 
 * 
 * LAZY REVEAL
 * <img src="..." lazy-reveal lazy-class="revealed" lazy-offset="-10px">
 * <img src="..." lazy-reveal="revert">
 */

class CobaltScrollManager {
    constructor(querySelector = null, modifier = 2) {
        this.useiOSWorkaround = iOS();

        this.allowUpdate = false; // The bool that controls the animation loop
        this.simultaneousDelayValue = 50;
        this.PARALLAX_SELECTOR = querySelector ?? "[parallax-mode],[parallax-speed]";
        this.LAZY_SELECTOR = "[lazy-reveal]";
        this.OBSERVER = null;
        
        this.parallaxElements = []; // The elements to be updated
        this.lazyElements = [];
        this.modifier = modifier;

        if(this.useiOSWorkaround) {
            // Let's warn to the console that we've detected iOS
            console.warn("Warning: you're using a browser that does not properly support parallax scrolling. Workaround may cause undocumented behavior");
            this.modifier = 4;
            // console.log({scrollModifier: this.modifier});
            this.iOSWorkaroundViewportHeight = cssToPixel("30vh");
        }

        this.debug = app("Parallax_enable_debug") ?? false;

        document.addEventListener("navigationEvent", this.selectElements.bind(this));
        document.addEventListener("scrollManagerUpdate", this.selectElements.bind(this));

        if(app("enable_default_parallax")) window.addEventListener("resize", () => {
            this.selectElements();
        });

        // this.initDebug();

        if(app("enable_default_parallax")) this.selectElements();
    }

    async selectElements() {
        // Stop the frame animation while we update our selected elements
        this.allowUpdate = false;
        // this.cleanUpDebug();

        this.innerScrollOffset = window.innerHeight * .33;

        // Create our list of parallax elements
        this.parallaxElements = [];

        let nodes = document.querySelectorAll(this.PARALLAX_SELECTOR);
        let index = 0;
        for(const e of nodes) {
            e.parallax = new ParallaxElement(e, index);
            this.parallaxElements[index] = e;
            index += 1;
        }

        this.allowUpdate = true;
        if(this.parallaxElements.length) requestAnimationFrame(this.animLoop.bind(this));

        // Create our list of lazy elements
        this.lazyElements = [];

        let lazy = document.querySelectorAll(this.LAZY_SELECTOR);
        index = 0;
        this.OBSERVER = new IntersectionObserver(this.observeCallback.bind(this), {
            // root: document.body,
            rootMargin: "-20% 0px"
            // threshold: []
        })

        for(const e of lazy){
            e.lazy = new LazyElement(e, index, null);
            this.lazyElements[index] = e;
            this.OBSERVER.observe(e);
            index += 1;
        }
    }

    observeCallback(entries) {
        /** @const {IntersectionObserverEntry} entry */
        for(const entry of entries) {
            if(entry.isIntersecting){ // && entry.intersectionRatio >= entry.target.lazy.ratio
                entry.target.lazy.intersectionStart();
            } else entry.target.lazy.intersectionEnd();
        }
    }
}

class LazyElement {
    /** @param {HTMLElement} element */
    constructor(element, index, delayOffset) {
        /** @property {HTMLElement} this.ELEMENT */
        this.ELEMENT = element;
        this.INDEX = index;
        this.REVEAL_OFFSET = delayOffset;
        this.QUERY_LAZY_CHILDREN = '[lazy-child]';

        // this.THRESHOLD_VALUE = this.ELEMENT.getAttribute("lazy-threshold") ?? "0.2 0 0 0";
        this.INTERSECTION_RATIO = this.ELEMENT.getAttribute("lazy-ratio") ?? "0.2"
        this.VISIBLE_CLASS = this.ELEMENT.getAttribute("lazy-class") ?? "lazy-reveal--revealed";
        this.RESET = string_to_bool(this.ELEMENT.getAttribute("lazy-reset") ?? "false");
        this.init();
    }
    
    init() {
        this.ELEMENT.style.setProperty("--lazy-delay", `${this.delay}ms`);
        this.LAZY_CHILDREN = this.ELEMENT.querySelectorAll(this.QUERY_LAZY_CHILDREN);
        let i = 0;
        for(const el of this.LAZY_CHILDREN) {
            el.lazy = new LazyElement(el, i, this.delay);
            i += 1;
        }
    }

    /** @property {Number} delay - milliseconds */
    get delay() {
        const getDelay = (unit) => {
            if(this.ELEMENT.hasAttribute("lazy-child")) {
                // if(!unit) return this.REVEAL_OFFSET;
                return ((unit ?? 100) * this.INDEX) + this.REVEAL_OFFSET
            }
            return 0;
        }
        let delay = this.ELEMENT.getAttribute("lazy-delay");
        // let parent = this.ELEMENT.closest("[lazy-reveal]");
        // let parentDelay = parent.lazy.delay;
        if(!delay) return getDelay(null);// + parentDelay;
        const last = delay[delay.length - 2] + delay[delay.length - 1] ?? "";
        const unit = cssUnitToNumber(delay);
        
        switch(last) {
            case "ms":
                return unit + this.REVEAL_OFFSET;
            case (delay[1] === "s"):
                return (unit * 1000) + this.REVEAL_OFFSET;
            default:
                return getDelay(unit) + this.REVEAL_OFFSET;
        }
    }

    get offset() {
        let topOffset = element.getBoundingClientRect().top;
        let value = element.getAttribute("lazy-offset");
        let operator = value[0];
        switch(operator) {
            case "+":
            case "-":
                value = value.substring(1);
        }
        if(operator === "-") offset *= -1;
        const offset = cssUnitToNumber(value);
        if(String(offset) !== "NaN") topOffset += offset;
        // topOffset += offset;
        while(element !== document.documentElement) {
            element = element.parentNode;
            topOffset += element.scrollTop;
        }
        return topOffset;
    }

    get ratio() {
        if(this.INTERSECTION_RATIO > 1) return this.INTERSECTION_RATIO * .01;
        return Number(this.INTERSECTION_RATIO);
    }

    // /** @property {array} threshold - an array of floats from 0 to 1 passed to the IntersectionObserver */
    // get threshold() {
    //     let threshold = this.THRESHOLD_VALUE;
    //     threshold = threshold.split(" ");
    //     const minThresholdLength = 4;
    //     if(threshold.length < minThresholdLength) {
    //         for(const i = threshold.length; i >= minThresholdLength; i++) {
    //             threshold.push(0);
    //         }
    //     }
    //     return threshold;
    // }

    intersectionStart() {
        this.ELEMENT.classList.add(this.VISIBLE_CLASS);
    }

    intersectionEnd() {
        if(this.RESET) this.ELEMENT.classList.remove(this.VISIBLE_CLASS);
    }
}

class ParallaxElement {
    constructor(element) {
        /** @property {HTMLElement} ELEMENT */
        this.ELEMENT = element;
    }
}

class CobaltScrollManagerOld {
    constructor(querySelector = null, modifier = 2) {
        this.useiOSWorkaround = iOS();

        this.allowUpdate = false; // The bool that controls the animation loop
        this.simultaneousDelayValue = 50;
        this.querySelector = querySelector ?? "[parallax-mode],[parallax-speed]";
        this.lazyRevealQuery = "[lazy-reveal]";
        
        this.parallaxElements = []; // The elements to be updated
        this.modifier = modifier;

        if(this.useiOSWorkaround) {
            // Let's warn to the console that we've detected iOS
            console.warn("Warning: you're using a browser that does not properly support parallax scrolling. Workaround may cause undocumented behavior");
            this.modifier = 4;
            // console.log({scrollModifier: this.modifier});
            this.iOSWorkaroundViewportHeight = cssToPixel("30vh");
        }

        this.debug = app("Parallax_enable_debug") ?? false;

        document.addEventListener("navigationEvent", this.selectElements.bind(this));
        document.addEventListener("scrollManagerUpdate", this.selectElements.bind(this));

        if(app("enable_default_parallax")) window.addEventListener("resize", () => {
            this.selectElements();
        });

        this.initDebug();

        if(app("enable_default_parallax")) this.selectElements();
    }

    async selectElements() {
        // Stop the frame animation while we update our selected elements
        this.allowUpdate = false;
        this.cleanUpDebug();

        this.innerScrollOffset = window.innerHeight * .33;

        // Create our list of elements
        this.parallaxElements = [];

        let nodes = document.querySelectorAll(this.querySelector);

        for(const e of nodes) {
            // Let's store our elements in the parallaxElements property
            const mode = this.getMode(e.getAttribute("parallax-mode"));
            const offsets = get_offset(e);
            if(this.useiOSWorkaround) {
                e.style.backgroundSize = "100lvh";
                e.style.setProperty("-webkit-background-size", "140lvh");
                e.style.backgroundAttachment = "scroll";
                // e.style.backgroundColor = "red";
                // e.style.backgroundPosition 
            }
            this.parallaxElements.push({
                mode,
                speed: Math.abs(e.getAttribute("parallax-speed") ?? this.modifier),
                offset: e.getAttribute("parallax-offset") ?? (this.getPageOffset(e)) * -1,
                dimensions: offsets,
                element: e
            });
            this[mode + "Init"](e, this.parallaxElements[this.parallaxElements.length - 1]);
        }

        let lazy = document.querySelectorAll(this.lazyRevealQuery);

        for(const e of lazy){
            // console.log(e);
            this.parallaxElements.push({
                mode: "lazyReveal",
                class: e.getAttribute("lazy-class") ?? "lazy-reveal--revealed",
                revert: (["revert","true"].includes(e.getAttribute("lazy-reveal"))) ? true : false,
                offset: this.getPageOffset(e),
                debug: null,
                element: e
            });
            const el = this.lazyRevealInit(e, this.parallaxElements[this.parallaxElements.length - 1]);
            this.parallaxElements[this.parallaxElements.length - 1].debug = el;
            
            let lazyChildren = e.querySelectorAll(`:is(${this.lazyRevealQuery}) [lazy-child]`);

            lazyChildren.forEach((el,i) => {
                const delay = this.delayParse(el.getAttribute('lazy-delay'), i, cssUnitToNumber(e.getAttribute("lazy-delay")) ?? 0);
                const len = delay.length;
                el.style.setProperty('--lazy-delay', `${delay}ms` || `${100 * i}ms`);
            });
        }

        this.allowUpdate = true;
        if(this.parallaxElements.length) requestAnimationFrame(this.animLoop.bind(this));
    }

    delayParse(delay, iteration, parentDelay = 0) {
        if(!delay) return 100 * iteration + (parentDelay ?? 0);
        const last = delay[delay.length - 2] + delay[delay.length - 1] ?? "";
        const unit = cssUnitToNumber(delay);
        
        switch(last) {
            case "ms":
                return unit + parentDelay;
            case (delay[1] === "s"):
                return (unit * 1000) + parentDelay;
            default:
                return (unit ?? 100) * iteration + (parentDelay ?? 0);
        }
    }

    getMode(mode) {
        if(["position","y"].includes(mode)) return "parallaxPosition";
        if(["background","bg"].includes(mode)) return "parallaxBackground";
        if(["x"].includes(mode)) return "parallaxPositionX";
        return "parallaxPosition";
    }

    getPageOffset(element) {
        let topOffset = element.getBoundingClientRect().top;
        const offset = parseInt(cssUnitToNumber(element.getAttribute("lazy-offset")));
        if(String(offset) !== "NaN") topOffset += offset;
        // topOffset += offset;
        while(element !== document.documentElement) {
            element = element.parentNode;
            topOffset += element.scrollTop;
        }
        return topOffset;
    }

    initDebug() {
        if(!this.debug) return;
        // if(this.scrollPositionDebug.parentNode === null) return;
        this.scrollPositionDebug = document.createElement("div");
        this.scrollPositionDebug.setAttribute("style","border-bottom:1px solid red; position:absolute; top:0; width:100vw;");
        document.body.appendChild(this.scrollPositionDebug);
    }

    animLoop() {
        if(this.allowUpdate === false) {
            console.warn("The animLoop function returned because allowUpdate was `false`");
            return;
        }
        const scrollHeight = (window.scrollY + window.innerHeight);
        this.visibleScrollPosition =  scrollHeight - this.innerScrollOffset;
        if(document.body.scrollHeight - scrollHeight < (this.innerScrollOffset > .5)) this.visibleScrollPosition = document.body.scrollHeight;
        this.simultaneousTickRevealDelay = 0;

        for(const e of this.parallaxElements) {
            this[e.mode](e.element, e);
        }

        this.updateDebug();
        
        requestAnimationFrame(this.animLoop.bind(this));
    }

    updateDebug() {
        if(!this.debug) return;
        this.scrollPositionDebug.style.top = this.visibleScrollPosition + 'px';
    }


    async parallaxBackgroundInit(element, data) {
        if(this.allowNativeCover === false) {
            console.warn("Parallax Background Init");
            // Height and width of image
            const {height, width} = await this.loadImage(element, data); // height: 2788, width: 4190
            
            // const containerHeight = element.offsetHeight, // 977
            // containerWidth = element.offsetWidth; // 1258
    
            const containerHeight = window.innerHeight, // 977
            containerWidth = window.innerWidth; // 1258
    
            // Determine the smallest and largest dimensions
            const smallestImageDimension = Math.min(height, width);
    
            // Determine which height is smaller so we can constrain our image dimensions        
            let divisor = containerHeight;
            if (containerHeight > containerWidth) divisor = containerWidth;
            
            let scaleFactor;
            if(divisor > smallestImageDimension) scaleFactor = divisor / smallestImageDimension;
            else divisor = smallestImageDimension / divisor;
    
            element.style.backgroundSize = `${width * scaleFactor}px ${height * scaleFactor}px`;
        }

        element.classList.add("cobalt-parallax--bg-parallax");

        const position = element.getAttribute("parallax-start-position") ?? "top";
        element.style.backgroundPosition = `${element.getAttribute('parallax-justification') || "center"} ${position}`;

    }

    parallaxBackground(element, data) {
        let x = element.getBoundingClientRect().top / (element.getAttribute("parallax-speed") ?? this.modifier);
        let y = Math.round(x * 100) / 100;
        if(this.useiOSWorkaround) {
            y += element.scrollTop;
            y -= this.iOSWorkaroundViewportHeight;
        }
        // console.log({x,y,data});
        (data.offset ?? 0)
        element.style.backgroundPosition = `${element.getAttribute('parallax-justification') || "center"} ${y}px`;
    }

    parallaxPositionInit(element, data) {
        // element.style.position = "absolute";
        // element.style.top = element.getAttribute("parallax-start-top") ?? element.style.top ?? 0;
        // element.style.left = element.getAttribute("parallax-start-left") ?? element.style.left ?? 0;
    }

    parallaxPosition(element, data) {
        let x = element.getBoundingClientRect().top / (data.speed ?? this.modifier);
        let y = Math.round(x * 100) / 100;
        // element.style.top = y + 'px';
        element.style.transform = this.transformStyle(element, {translateY: y + 'px'});
    }

    parallaxPositionXInit(element, data) {
        element.style.position = "absolute";
        element.style.left = element.getAttribute("parallax-start-top") ?? 0;
    }

    parallaxPositionX(element, data){
        let y = element.getBoundingClientRect().left / (data.speed ?? this.modifier);
        let x = Math.round(x * 100) / 100;
        element.style.left = x + 'px';
    }

    lazyRevealInit(element, data) {
        const delay = element.getAttribute("lazy-delay");
        if(delay) element.style.setProperty("--lazy-delay", delay);
        if(!this.debug) return {};
        const elem = document.createElement("div");
        elem.classList.add('lazy-reveal--debug-marker');
        const pos = element.getBoundingClientRect();
        elem.setAttribute("style",`position:absolute; box-sizing: border-box; top: ${data.offset}px; left: ${pos.left}px; width: ${pos.width}px; border-bottom: 1px solid blue`);
        document.body.appendChild(elem);
        return elem;
    }

    lazyReveal(element, data) {
        let y = element.getBoundingClientRect().top + (element.getAttribute("lazy-offset") ?? 0)
        if(this.visibleScrollPosition >= data.offset) {
            element.classList.remove("lazy-reveal--reverted");
            
            if(this.simultaneousTickRevealDelay !== 0) element.style.setProperty("--lazy-delay", `${this.simultaneousTickRevealDelay}ms`);
            this.simultaneousTickRevealDelay += this.simultaneousDelayValue;

            element.classList.add(data.class);
        }
        else if(data.revert && element.classList.contains(data.class)) {
            element.classList.remove(data.class);
            element.classList.add("lazy-reveal--reverted");
        }
    }

    transformStyle(e, props = {}) {
        let transform = e.style.transform;
        if(["none","unset","initial"].includes(transform)) return "none";

        let tf = "";
        for(const k in props) {
            tf += `${k}(${props[k]}) `;
        }

        return tf;
    }

    

    cleanUpDebug() {
        const debugs = document.querySelectorAll(".lazy-reveal--debug-marker");

        for(const i of debugs) {
            i.parentNode.removeChild(i);
        }
    }

    loadImage(element, data) {
        return new Promise((resolve, reject) => {
            const src = element.style.backgroundImage.replace(/url\((['"])?(.*?)\1\)/gi, '$2').split(',')[0];
            // let height = element.getAttribute("parallax-height");

            // if(height) {
            //     data.height = height;
            //     resolve(image);
            // }

            const image = new Image();
            image.onload = () => {
                // data.height = image.height;
                // data.width = image.width;
                
                resolve({height: image.naturalHeight, width: image.naturalWidth});
            }
            image.onerror = () => {
                reject({height: 0, width: 0});
            }
            image.src = src;
            if(image.error) reject({height: 0, width: 0});
            if(image.complete) resolve({height: image.naturalHeight, width: image.naturalWidth});
        });
    }
}

if(app("enable_default_parallax")) window.parallax = new CobaltScrollManager();
else document.body.classList.add("parallax-disabled");
