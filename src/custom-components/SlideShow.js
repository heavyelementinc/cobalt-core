export default class SlideShow extends HTMLElement{
    timeout = null;
    index = 0;
    slides = [];
    BUTTON_MODE_PLAYPAUSE = 0;
    BUTTON_MODE_NEXT =  1;
    BUTTON_MODE_PREV = -1;

    SLIDE_FORWARDS  = "slide-show--direction-forwards";
    SLIDE_BACKWARDS = "slide-show--direction-backwards";
    SLIDE_SHOW__READY_STATE = "slide-show--ready-state";

    timeout = null;

    nextButton = null;
    prevButton = null;
    ready = new Deferred();
    readiness = [];

    constructor() {
        super();
    }
    
    get duration() {
        return parseInt(this.getAttribute("duration"));
    }

    set duration(value) {
        this.setAttribute("duration", value);
    }

    get animation() {
        return `slide-show--animation-${this.getAttribute("animation")}`;
    }

    set animation(value) {
        switch(value) {
            case "in-out":
                this.setAttribute("animation", "in-out");
                break;
            case "scale":
                this.setAttribute("animation", "scale");
                break;
            default:
                this.removeAttribute("animation");
        }
    }

    get controls() {
        if(this.hasAttribute("controls")){
            return ['next', 'previous'];
        }
        return [];
    }

    get autoplay() {
        if(this.hasAttribute("autoplay")) return cssUnitToNumber(this.getAttribute("autoplay"));
        return null;
    }

    connectedCallback() {
        this.initSlides();
        this.getSlide(0).show();
        this.initButtons();
        this.ready.resolve(true);
        this.dispatchEvent(new Event("ready", {bubbles: true}));
        setTimeout(() => {
            this.classList.add(this.SLIDE_SHOW__READY_STATE);
        }, 100)
    }
    
    findSlides() {
        return this.children;
    }

    initSlides() {
        this.addEventListener("slidedisplayed", (slide) => {
            if(!this.autoplay) return;
            this.timeout = setTimeout(() => {
                this.nextSlide(this.BUTTON_MODE_NEXT);
            }, slide.detail.slide.autoplay);
        });
        for(const el of this.findSlides()) {
            const slide = new Slide(el);
            this.slides.push(slide);
            this.readiness.push(slide.ready.promise);
        }
        if(this.slides.length === 0) throw new Error("A slide-show must have at least one child element!");
        (async () => {
            await Promise.all(this.readiness);
            let maxHeight = 0;
            let maxWidth = 0;
            for(const el of this.slides) {
                const rect = await el.ready.promise;
                if(rect.height > maxHeight) maxHeight = rect.height;
                if(rect.width > maxWidth) maxWidth = rect.width;
            }
            this.setDimensions(maxHeight, maxWidth, this);
        })()
    }

    setDimensions(height, width, target) {
        target.style.setProperty("--width",  `${Math.round(width * 100) / 100}px`);
        target.style.setProperty("--height", `${Math.round(height * 100) / 100}px`);
    }

    initButtons() {
        const controls = this.controls;
        if(controls.includes("next"))     this.nextButton = this.button(this.BUTTON_MODE_NEXT);
        if(controls.includes("previous")) this.prevButton = this.button(this.BUTTON_MODE_PREV);
    }

    button(mode = this.BUTTON_MODE_NEXT) {
        let type
        switch(mode) {
            case this.BUTTON_MODE_PREV:
                type = "[type='previous'],[type='prev'],[type='regress']";
                break;
            case this.BUTTON_MODE_NEXT:
            default:
                type = "[type='next'],[type='advance']";
                break;
        }
        
        let btn = document.querySelectorAll(`button[for='${this.id}']:where(${type})`);
        if(!btn) {
            btn = [this.createButton(mode)];
        }
        btn.forEach(el => {
            el.addEventListener("click", () => {
                clearTimeout(this.timeout);
                this.classList.remove(this.SLIDE_FORWARDS,this.SLIDE_BACKWARDS);
                switch(mode) {
                    case this.BUTTON_MODE_PREV:
                        this.classList.add(this.SLIDE_BACKWARDS);
                        break;
                    case this.BUTTON_MODE_NEXT:
                    default:
                        this.classList.add(this.SLIDE_FORWARDS);
                        break;
                }
                this.nextSlide(mode);
            });
        });
    }
    
    createButton(mode) {
        const btn = document.createElement("button");
        switch(mode) {
            case this.BUTTON_MODE_PREV:
                btn.type = "previous";
                break;
            case this.BUTTON_MODE_NEXT:
            default:
                btn.type = "next";
                break;
        }
        this.parentNode?.insertBefore(btn.nextElementSibling, this);
        return btn;
    }

    // showSlide(index = null){
        
    // }

    /** @returns {Slide} */
    getSlide(index = null, direction = 1, updateIndex = false) {
        // If the index is not set, we should fall back to the next slide
        index = index ?? (this.index + direction);
        if(index > (this.slides.length - 1)) {
            index = 0;
        }
        if(index < 0) {
            index = this.slides.length - 1;
        }
        if(updateIndex === true) this.index = index;
        return this.slides[index];
    }

    nextSlide(mode) {
        // Get the current slide
        const current = this.getSlide(this.index, mode);
        // Get our next slide
        const next = this.getSlide(this.index + 1, mode, true);
        current.hide();
        next.show();
    }
}

class Slide {
    SLIDE_GENERIC = "slide-show--slide";
    SLIDE_INERT   = "slide-show--inert";
    SLIDE_PREV    = "slide-show--previous";
    SLIDE_CURRENT = "slide-show--current";

    element = null;
    parent = null;
    ready = new Deferred();
    
    constructor(element) {
        this.element = element;
        this.parent = element.closest("slide-show") ?? element.parentNode;
        this.ready.resolve(element.getBoundingClientRect());
        element.classList.add(this.SLIDE_GENERIC, this.SLIDE_INERT, this.animation);
        if(element.style.display === "none") element.style.removeProperty("display");
        element.addEventListener("transitionend", evt => {
            this.cleanup(evt);
        });
        element.addEventListener("animationend", evt => {
            this.cleanup(evt);
        });
    }

    get duration() {
        if(this.hasAttribute("duration")) return parseInt(this.getAttribute("duration"));
        return this.parent.duration;
    }

    set duration(value) {
        this.setAttribute("duration", value);
    }

    get animation() {
        if(this.element.hasAttribute("animation")) return `slide-show--animation-${this.element.getAttribute("animation")}`;
        return this.parent.animation;
    }
    
    get autoplay() {
        if(this.element.hasAttribute("autoplay")) return cssUnitToNumber(this.element.getAttribute("autoplay"));
        return this.parent.autoplay;
    }

    show() {
        this.element.classList.remove(this.SLIDE_INERT, this.SLIDE_PREV);
        this.element.classList.add(this.SLIDE_CURRENT);
        this.element.dispatchEvent(new CustomEvent("slidedisplayed", {bubbles: true, detail: {slide: this}}));
    }

    hide() {
        this.element.classList.remove(this.SLIDE_CURRENT, this.SLIDE_INERT);
        this.element.classList.add(this.SLIDE_PREV);
        this.element.dispatchEvent(new CustomEvent("slidehidden",    {bubbles: true, detail: {slide: this}}));
    }

    cleanup(event) {
        // This function is called on `transitionend`. When this happens,
        // we check if it has the SLIDE_PREV class. If it does, we need to
        // set the element to SLIDE_INTER status
        if(this.element.classList.contains(this.SLIDE_PREV)) {
            this.parent.classList.remove(this.parent.SLIDE_FORWARDS, this.parent.SLIDE_BACKWARDS);
            this.element.classList.remove(this.SLIDE_PREV);
            this.element.classList.add(this.SLIDE_INERT);
        }
        if(this.element.classList.contains(this.SLIDE_CURRENT)) {
            const rect = this.element.getBoundingClientRect();
            this.parent.setDimensions(rect.height, rect.width, this.parent);
        }
    }
}