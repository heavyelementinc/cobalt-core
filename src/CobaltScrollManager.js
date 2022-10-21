/**
 * Author: Gardiner Bryant
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
 * <img src="..." lazy-reveal lazy-class="revealed">
 * <img src="..." lazy-reveal="revert">
 */

class CobaltScrollManager {
    constructor(querySelector = null, modifier = 2) {
        this.allowUpdate = false; // The bool that controls the animation loop
        this.simultaneousDelayValue = 50;
        this.querySelector = querySelector ?? "[parallax-mode],[parallax-speed]";
        this.lazyRevealQuery = "[lazy-reveal]";
        
        this.parallaxElements = []; // The elements to be updated
        this.modifier = modifier;

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
            const mode = this.getMode(e.getAttribute("parallax-mode"));
            this.parallaxElements.push({
                mode,
                speed: Math.abs(e.getAttribute("parallax-speed") ?? this.modifier),
                offset: e.getAttribute("parallax-offset") ?? (this.getPageOffset(e)) * -1,
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
                const delay = el.getAttribute('lazy-child');
                el.style.setProperty('--lazy-delay', delay || `${100 * i}ms`);
            });
        }

        this.allowUpdate = true;
        if(this.parallaxElements.length) requestAnimationFrame(this.animLoop.bind(this));
    }

    getMode(mode) {
        if(["position","y"].includes(mode)) return "parallaxPosition";
        if(["background","bg"].includes(mode)) return "parallaxBackground";
        if(["x"].includes(mode)) return "parallaxPositionX";
        return "parallaxPosition";
    }

    getPageOffset(element) {
        let topOffset = element.getBoundingClientRect().top;
        const offset = parseInt(element.getAttribute("lazy-offset"));
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
        if(this.allowUpdate === false) return;
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
        // await new Promise((resolve, reject) => {
        //     const src = element.style.backgroundImage.replace(/url\((['"])?(.*?)\1\)/gi, '$2').split(',')[0];
        //     let height = element.getAttribute("parallax-height");

        //     if(height) {
        //         data.height = height;
        //         resolve();
        //     }

        //     const image = new Image();
        //     image.onload = () => {
        //         data.height = image.height;
        //         data.width = image.width;
                
        //         resolve();
        //     }
    
        //     image.src = src;
        // });

        element.classList.add("cobalt-parallax--bg-parallax");

        // element.style.backgroundRepeat = "no-repeat";
        // element.style.backgroundAttachment = "fixed";
        // element.style.backgroundSize = "cover";

        const position = element.getAttribute("parallax-start-position") ?? "top";
        element.style.backgroundPosition = `center ${position}`;

    }

    parallaxBackground(element, data) {
        let x = element.getBoundingClientRect().top / (element.getAttribute("parallax-speed") ?? this.modifier);
        let y = Math.round(x * 100) / 100;
        // console.log({x,y,data});
        (data.offset ?? 0)
        element.style.backgroundPosition = `center ${y}px`;
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
}

if(app("enable_default_parallax")) window.parallax = new CobaltScrollManager();
else document.body.classList.add("parallax-disabled");
