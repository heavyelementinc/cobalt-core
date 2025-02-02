class CoblatLazyReveal {
    constructor(targetQuery, options = {root: null, rootMargin: '0px', threshold: .5}) {
        this.lazyRevealQuery = "[lazy-reveal]";
        this.options = {root: null, rootMargin: '0px', threshold: .5, ...options};

        this.observer = new IntersectionObserver(this.lazyReveal.bind(this),this.options);
    }

    // selectElements() {
    //     let lazy = document.querySelectorAll(this.lazyRevealQuery);

    //     for(const e of lazy){
    //         // console.log(e);
    //         this.parallaxElements.push({
    //             mode: "lazyReveal",
    //             class: e.getAttribute("lazy-class") ?? "lazy-reveal--revealed",
    //             revert: (["revert","true"].includes(e.getAttribute("lazy-reveal"))) ? true : false,
    //             offset: this.getPageOffset(e),
    //             debug: null,
    //             element: e
    //         });
    //         const el = this.lazyRevealInit(e, this.parallaxElements[this.parallaxElements.length - 1]);
    //         this.parallaxElements[this.parallaxElements.length - 1].debug = el;
            
    //         let lazyChildren = e.querySelectorAll(`:is(${this.lazyRevealQuery}) [lazy-child]`);

    //         lazyChildren.forEach((el,i) => {
    //             const delay = el.getAttribute('lazy-child');
    //             el.style.setProperty('--lazy-delay', delay || `${100 * i}ms`);
    //         });
    //     }
    // }

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
        let y = element.getBoundingClientRect().top + (cssUnitToNumber(element.getAttribute("lazy-offset")) ?? 0)
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

}