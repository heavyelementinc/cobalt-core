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

        this.props = {allowUpdate: false}; // The bool that controls the animation loop
        this.simultaneousDelayValue = 50;
        this.PARALLAX_SELECTOR = querySelector ?? "[parallax-mode],[parallax-speed]";
        this.LAZY_SELECTOR = "[lazy-reveal]";
        this.OBSERVER = null;
        
        this.scrollAnimatedElements = []; // The elements to be updated
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

    get allowUpdate() {
        return this.props.allowUpdate || true;
    }

    set allowUpdate(value) {
        if(value !== true && value !== false) return;
        const prevState = this.props.allowUpdate;
        this.props.allowUpdate = value;
        if(value && value !== prevState) {
            console.warn("Selecting elements...")
            this.selectElements();
        }
        if(!value) {
            for(const el of this.scrollAnimatedElements) {
                el.cleanUp();
            }
        }
    }

    async selectElements() {
        // Stop the frame animation while we update our selected elements
        this.props.allowUpdate = false;
        // this.cleanUpDebug();

        this.innerScrollOffset = window.innerHeight * .33;

        // Create our list of parallax elements
        this.scrollAnimatedElements = [];

        let nodes = document.querySelectorAll(this.PARALLAX_SELECTOR);
        const settings = {
            useiOSWorkaround: this.useiOSWorkaround,
            modifier: this.modifier,
            debug: this.debug,
        }
        let promises = [];
        for(const e of nodes) {
            let index = this.scrollAnimatedElements.length;
            const attr = e.getAttribute("parallax-mode")?.toLowerCase();
            let parallaxElement = null;
            switch(attr) {
                case "scroll-pos":
                case "scroll-position":
                    parallaxElement = new ParallaxScrollPosition();
                    break;
                case "translate-x":
                case "translatex":
                case "x":
                    parallaxElement = new ParallaxPositionXElement(e,settings);
                    break;
                case "translate-y":
                case "translatey":
                case "position":
                case "y": 
                    parallaxElement = new ParallaxPositionYElement(e,settings);
                    break;
                case "background-x":
                case "bg-x":
                    parallaxElement = new ParallaxBackgroundXElement(e, settings);
                    if(!parallaxElement.enabled) continue;
                    break;
                case "background":
                case "bg":
                default:
                    parallaxElement = new ParallaxBackgroundElement(e,settings);
                    if(!parallaxElement.enabled) continue;
                    break;
            }
            this.scrollAnimatedElements.push(parallaxElement);
            promises.push(parallaxElement.initialize(index));
        }

        await Promise.all(promises); // Wait for all the image loading to complete

        if(app("apply_header_class_after_scroll") >= 1) {
            const scrolledClass = new ScrollConstraintElement(document.body, this.scrollAnimatedElements.length);
            scrolledClass.initialize(this.scrollAnimatedElements.length);
            this.scrollAnimatedElements.push(scrolledClass);
        }

        this.props.allowUpdate = true;
        requestAnimationFrame(this.animationLoop.bind(this));

        // Create our list of lazy elements
        this.lazyElements = [];

        let lazy = document.querySelectorAll(this.LAZY_SELECTOR);
        let index = 0;
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
        const revealableEntities = [];
        let index = 0;
        /** @const {IntersectionObserverEntry} entry */
        for(const entry of entries) {
            if(entry.isIntersecting){ // && entry.intersectionRatio >= entry.target.lazy.ratio
                revealableEntities.push({entry, index})
                index += 1;
            } else entry.target.lazy.intersectionEnd();
        }

        for(const entity of revealableEntities) {
            entity.entry.target.lazy.intersectionStart(entity.index);
        }
    }

    animationLoop() {
        if(this.allowUpdate === false) {
            console.warn("The animationLoop function returned because allowUpdate was `false`");
            return;
        }
        // Determine our actual scroll height
        const scrollHeight = (window.scrollY + window.innerHeight);
        this.visibleScrollPosition =  scrollHeight - this.innerScrollOffset;
        if(document.body.scrollHeight - scrollHeight < (this.innerScrollOffset > .5)) this.visibleScrollPosition = document.body.scrollHeight;
        this.simultaneousTickRevealDelay = 0;
        document.body.style.setProperty("--viewport-y", `${scrollHeight}px`);

        for(const element of this.scrollAnimatedElements) {
            element.animate(scrollHeight);
        }

        this.updateDebug();
        
        requestAnimationFrame(this.animationLoop.bind(this));
    }

    updateDebug() {
        if(!this.debug) return;
        this.scrollPositionDebug.style.top = this.visibleScrollPosition + 'px';
    }

    cleanUp() {

    }
}

class LazyElement {
    MODE_STAGGER = "stagger";
    MODE_REVERT = "revert";

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

    intersectionStart(index) {
        if(this.ELEMENT.getAttribute("lazy-reveal") === this.MODE_STAGGER) {
            const delay = (this.ELEMENT.getAttribute("lazy-offset") ?? 100) * index
            this.ELEMENT.style.transitionDelay = `${delay}ms`;
            this.ELEMENT.style.animationDelay = `${delay}ms`;
        }
        this.ELEMENT.classList.add(this.VISIBLE_CLASS);
    }

    intersectionEnd() {
        if(this.RESET) this.ELEMENT.classList.remove(this.VISIBLE_CLASS);
        // if(this.ELEMENT.getAttribute("lazy-reveal") === this.MODE_REVERT) this.ELEMENT.classList.remove(this.VISIBLE_CLASS);
    }

    cleanUp() {

    }
}

class AnimatedElement {
    get speed() {
        return Math.abs(this.ELEMENT.getAttribute("parallax-speed") ?? this.SETTINGS.modifier);
    }

    get modifier() {
        return this.SETTINGS.modifier ?? 2;
    }

    initialize(index) {
        // Called on page change, on window resize, etc.
    }

    animate(scrollHeight) {
        // Called on every requestAnimationFrame
    }

    cleanUp() {

    }
}

class ParallaxCommon extends AnimatedElement {
    // get speed() {
    //     return this.ELEMENT.getAttribute("parallax-speed") ?? 2;
    // }
    constructor(element, settings = {}) {
        super();
        /** @property {HTMLElement} ELEMENT */
        this.ELEMENT = element;
        this.SETTINGS = settings;
        this.INDEX = null;
        // this.speed = null;
        // this.offset = null;
    }

    get offset() {
        return 0;
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

    cleanUp() {

    }
}

class ParallaxScrollPosition extends ParallaxCommon {
    animate(scrollHeight) {
        this.ELEMENT.style.setProperty("--pos-y", `${scrollHeight}px`);
    }
}

class ParallaxBackgroundElement extends ParallaxCommon {
    PARALLAX_CLASS = "cobalt-parallax--bg-parallax";
    scaleFactor = 1;

    dimensions() {
        return get_offset(this.ELEMENT);
    }

    get allowNativeCover() {
        return (this.ELEMENT.getAttribute("native-cover") === "true");
    }

    get scaleFactorAdjustment() {
        return Number(this.ELEMENT.getAttribute("scale-factor") ?? 1.1);
    }

    get offset() {
        return Number(this.ELEMENT.getAttribute("parallax-offset") ?? 0);
    }

    get enabled() {
        /** On a PC, we'll always enable parallax */
        if(!isMobile()) return true;
        // If we're here, then we need to determine if parallax is enabled
        let enabled = this.ELEMENT.getAttribute("parallax-mobile-enabled")
        switch(enabled) {
            case true:
            case "true":
            case "parallax-mobile-enabled":
            case "": // If the attribute exists but has no value, we assume parallax is enabled
                return true;
        }
        return false; // If we've made it this far, we know that parallax is not enabled!
    }

    async initialize(index) {
        const e = this.ELEMENT;

        this.INDEX = index;

        if(!this.allowNativeCover) {
            // Height and width of image
            const {height, width} = await this.loadImage(e); // height: 2788, width: 4190
            if(height == -1 && width == -1) return;


            const rect = this.ELEMENT.getBoundingClientRect();
            const containerHeight = rect.height || window.innerHeight,
            containerWidth = rect.width || window.innerWidth;
            const HEIGHT_CONSTRAINT = 0;
            const WIDTH_CONSTRAINT = 1;

            let constrait = HEIGHT_CONSTRAINT;
            if(containerHeight < containerWidth) constrait = WIDTH_CONSTRAINT;
            
            // Check which dimension needs to grow the most
            if(constrait === HEIGHT_CONSTRAINT) {
                if(height > containerHeight) {
                    // If the image is larger than the container, scale down
                    this.scaleFactor = containerHeight / height;
                } else {
                    // If the image is smaller than the container, scale up
                    this.scaleFactor = height / containerHeight;
                }
            } else {
                if(width > containerWidth) {
                    this.scaleFactor = containerWidth / width;
                } else {
                    this.scaleFactor = width / containerWidth;
                }
            }

            this.scaleFactor *= this.scaleFactorAdjustment;

            e.style.backgroundSize = `${width * this.scaleFactor}px ${height * this.scaleFactor}px`;
        }

        e.classList.add(this.PARALLAX_CLASS);

        const position = e.getAttribute("parallax-start-position") ?? "top";
        e.style.backgroundPosition = `${e.getAttribute('parallax-justification') || "center"} ${position}`;
    }

    animate() {
        const element = this.ELEMENT;
        // const data = this.offset;
        let x = element.getBoundingClientRect().top / (element.getAttribute("parallax-speed") ?? this.modifier);
        let y = Math.round(x * 100) / 100;
        if(this.SETTINGS.useiOSWorkaround) {
            y += element.scrollTop;
            y -= this.iOSWorkaroundViewportHeight;
        }
        // (data.offset ?? 0)
        element.style.backgroundPosition = `${element.getAttribute('parallax-justification') || "center"} ${y + (this.offset * this.scaleFactor)}px`;
    }

    loadImage(element) {
        return new Promise((resolve, reject) => {
            const bgValue = getComputedStyle(element).backgroundImage;
            const src = bgValue.replace(/url\((['"])?(.*?)\1\)/gi, '$2').split(',')[0];

            const image = new Image();
            image.onload = () => {
                resolve({height: image.naturalHeight, width: image.naturalWidth});
            }
            image.onerror = (e) => {
                console.warn(`Failed to load image`, element, e);
                resolve({height: -1, width: -1});
            }
            image.src = src;
            if(image.error) resolve({height: -1, width: -1});
            if(image.complete) resolve({height: image.naturalHeight, width: image.naturalWidth});
        });
    }

    cleanUp() {
        this.ELEMENT.classList.remove(this.PARALLAX_CLASS);
        this.ELEMENT.style.backgroundPosition = ``;
    }
}

class ParallaxBackgroundXElement extends ParallaxBackgroundElement {
    initialize(index) {

    }
}

class ParallaxPositionXElement extends ParallaxCommon {
    animate(scrollHeight) {
        let x = this.ELEMENT.getBoundingClientRect().top / (data.speed ?? this.modifier);
        let y = Math.round(x * 100) / 100;
        this.ELEMENT.style.transform = this.transformStyle(this.ELEMENT, {translateY: y + 'px'});
    }
}

class ParallaxPositionYElement extends ParallaxCommon {
    get startTop() {
        return this.ELEMENT.getAttribute("parallax-start-top") ?? 0;
    }
    initialize(index) {
        this.index = index;
    }

    animate(scrollHeight){
        const element = this.ELEMENT;
        // const data = this.offset;
        let x = element.getBoundingClientRect().top / this.speed;
        let y = Math.round(x * 100) / 100;
        // if(this.SETTINGS.useiOSWorkaround) {
        //     y += element.scrollTop;
        //     y -= this.iOSWorkaroundViewportHeight;
        // }
        element.style.translate = `0 ${y}px`;
    }
}

class ScrollConstraintElement extends AnimatedElement {
    CLASS = "scroll-manager--scroll-constraint-satisfied";

    lastValue = 0;

    allow_animation = true;
    last_scrolled_upwards = false;
    applied_class = false;

    initialize() {
        // Let's listen to the router and control our animation state based on
        // the events its giving us.
        document.addEventListener("navigationstart", () => {
            // this.allowAnimation = false;
            this.allow_animation = false;
            document.body.classList.remove(this.CLASS);
            this.lastValue = 0;
        });
        document.addEventListener("load", () => {
            this.allow_animation = true;
        });
        document.addEventListener("navigateerror", () => {
            this.allow_animation = true;
        })

    }

    animate(scrollHeight) {
        if(this.allow_animation == false) return;
        let threshold = app("apply_header_class_after_scroll");
        
        if(this.lastValue < window.scrollY) {
            this.last_scrolled_upwards = false;
            if(this.debug) console.log("Last scrolled upwards: false")
        } else if (this.lastValue === window.scrollY) {
            // Do nothing
        } else {
            this.last_scrolled_upwards = true;
            if(this.debug) console.log("Last scrolled upwards: true")
        }

        if(this.last_scrolled_upwards) {
            threshold *= app("apply_header_class_scroll_upwards_multiplier");
        }
        
        if(window.scrollY < threshold) {
            this.applied_class = true;
        } else {
            this.applied_class = false;
        }

        if(this.applied_class) {
            document.body.classList.remove(this.CLASS);
        } else {
            document.body.classList.add(this.CLASS)
        }

        this.lastValue = window.scrollY;
    }
}

if(app("enable_default_parallax")) window.parallax = new CobaltScrollManager();
else document.body.classList.add("parallax-disabled");

function parallaxState(value) {
    if(!window.parallax) throw new Error("CobaltScrollManager is not initialized.");
    window.parallax.allowUpdate = value
}