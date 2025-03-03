class TypeWriter extends HTMLElement {
    
    DISPLAY_MODE_NONE = 0;
    DISPLAY_MODE_ELEMENTS = 1;
    DISPLAY_MODE_REVEAL_ONCE = 2;
    DISPLAY_MODE_REVEAL_ALL_LOOP = 3;

    SCROLL_OUT_MODE_RESET = 0;
    SCROLL_OUT_MODE_STATIC = 1;

    CLASS_PLAYING = "playing";
    CHILD_CLASS_INITIALIZED = "initialized";
    CHILD_CLASS_REVEALED = "revealed";
    CHILD_CLASS_SLIDEOUT = "transform-out";

    DEFAULT_SPEED_INTERVAL = 90;
    DEFAULT_SPEED_RESET = 20;
    DEFAULT_DELAY_INTERVAL = 1500;

    _observer = null;

    _revealInterval = null;
    _revealPromise  = null;
    _clearInterval  = null;
    _clearPromise   = null;
    _max_char_count = 0;

    loopIndex = 0;
    _revealIndex = 0;

    get mode() {
        switch(this.getAttribute("mode")) {
            case "element":
            case "elements":
            case "children":
                return this.DISPLAY_MODE_ELEMENTS;
            case "reveal-once":
                return this.DISPLAY_MODE_REVEAL_ONCE;
            case "reveal-all-loop":
                return this.DISPLAY_MODE_REVEAL_ALL_LOOP;
            case "never":
            default:
                return this.DISPLAY_MODE_NONE;
        }
    }

    /** The speed at which characters appear */
    get speed() {
        if(!this.hasAttribute("speed")) return this.DEFAULT_SPEED_INTERVAL;
        const num = new Number(this.getAttribute("speed"));
        return (isNaN(num)) ? this.DEFAULT_SPEED_INTERVAL : num;
    }

    get delay() {
        if(!this.hasAttribute("delay")) return this.DEFAULT_DELAY_INTERVAL;
        const num = new Number(this.getAttribute("delay"));
        return (isNaN(num)) ? this.DEFAULT_DELAY_INTERVAL : num;
    }

    /** Controls the reset speed interval */
    get resetSpeed() {
        if(!this.hasAttribute("reset")) return this.DEFAULT_SPEED_RESET;
        const num = new Number(this.getAttribute("reset"));
        return (isNaN(num)) ? this.DEFAULT_SPEED_RESET : num;
    }

    /** Controls how resets are handled */
    get reset() {
        const reset = this.getAttribute("reset");
        switch(reset) {
            case "true":
            case "reset":
                return true; // If reset, then the reset is nearly instantaneous
            case "false":
                return false; 
            case "transform":
            case "slide-out":
                return "transform";
            case "delete":
            default:
                return "delete";
        }
    }

    get scrollOut() {
        const scrollOut = this.getAttribute("scroll-out");
        switch(scrollOut) {
            case "static":
                return this.SCROLL_OUT_MODE_STATIC;
            case "scroll-out":
            case "reset":
            default:
                return this.SCROLL_OUT_MODE_RESET;
        }
    }

    connectedCallback() {
        this._observer = new IntersectionObserver(this.visibilityChange.bind(this))
        this._observer.observe(this);

        switch(this.mode){
            case this.DISPLAY_MODE_ELEMENTS:
            case this.DISPLAY_MODE_REVEAL_ONCE:
                for(const child of this.children) {
                    this.initializeRevealableElement(child);
                }
                break;
            default:
                this.initializeRevealableElement(this);
                break;
        }
        
        this.style.setProperty("--char-count", `${this._max_char_count}ch`);
    }

    visibilityChange(e) {
        if(e[0].isIntersecting) {
            this.start(e[0].target, e[0]);
        } else {
            this.end(e[0].target, e[0]);
        }
    }

    start(el) {
        if(!document.body.parentNode.classList.contains("_parallax")) return;
        el.classList.add(this.CLASS_PLAYING);
        switch(this.mode) {
            case this.DISPLAY_MODE_ELEMENTS:
            case this.DISPLAY_MODE_REVEAL_ONCE:
                this.loopElements(el.children[this.loopIndex] ?? el.children[0]);
                break;
            case this.DISPLAY_MODE_NONE:
            default:
                this.revealElement(el.children[0] ?? el);
                break;
        }
        this.dispatchEvent(new CustomEvent("typewriterstart"));
    }

    end(el) {
        if(!document.body.parentNode.classList.contains("_parallax")) return;
        el.classList.remove(this.CLASS_PLAYING);

        clearInterval(this._revealInterval);
        clearInterval(this._clearInterval);
        // if(this.reset) this.resetElement(el);
        this.querySelectorAll(`.${this.CHILD_CLASS_REVEALED}`).forEach(e => {
            if(this.scrollOut === this.SCROLL_OUT_MODE_RESET) {
                e.classList.remove(this.CHILD_CLASS_REVEALED);
            }
        });
        this.dispatchEvent(new CustomEvent("typewriterend"));
    }

    async loopElements(el) {
        if(this.mode === this.DISPLAY_MODE_REVEAL_ONCE && !el) return;
        if(this.reset === false && !el) return;
        this._revealInterval = 0;
        // If el == null, let's reset to the first element;
        if(el === null) {
            el = this.children[0];
            this.loopIndex = 0; // When we've run out of children, let's reset the loopIndex
        }

        
        await this.revealElement(el)
        setTimeout(async () => {
            if(this.mode !== this.DISPLAY_MODE_REVEAL_ONCE) {
                await this.resetElement(el);
            }
            // Let's keep track of which element we're currently revealing. This is
            // so that if we scroll off of this element and come back, we restart in
            // the same place we left off.
            this.loopIndex += 1;
            this.loopElements(el.nextElementSibling)
        }, this.delay);
    }

    revealElement(line) {
        return new Promise((resolve, reject) => {
            this._revealPromise = resolve;
            this._revealIndex = 0;
            this._revealInterval = setInterval(() => {
                if(!line.children[this._revealIndex]) {
                    this.dispatchEvent(new CustomEvent("typewriterlinerevealed"));
                    resolve(true);
                    clearInterval(this._revealInterval);
                    this._revealIndex = 0;
                    return;
                }
                line.children[this._revealIndex].classList.add(this.CHILD_CLASS_REVEALED);
                this._revealIndex += 1;
            }, this.speed);
        });
    }

    resetElement(el) {
        new Promise(async (resolve, reject) => {
            this._clearPromise = resolve;
            if(this.reset === false) {
                resolve(false);
                return;
            }
            let interval = 0;
            switch(this.reset) {
                case "delete":
                    interval = this.resetSpeed;
                    break;
                case "transform":
                    await wait_for_transition(el, this.CHILD_CLASS_SLIDEOUT, false)
                default:
                    interval = 0;
            }
            let index = el.children.length - 1;
            this._clearInterval = setInterval(() => {
                if(index < 0) {
                    resolve(true);
                    clearInterval(this._clearInterval);
                    el.classList.remove(this.CHILD_CLASS_SLIDEOUT);
                    this.dispatchEvent(new CustomEvent("typewriterlinereset"));
                    return;
                }
                el.children[index].classList.remove(this.CHILD_CLASS_REVEALED);
                index -= 1;
            }, interval);
        });
    }

    initializeRevealableElement(line) {
        const text = line.innerText
        this._max_char_count = Math.max(this._max_char_count, text.length);
        line.innerHTML = "";
        for(let i = 0; i < text.length; i++) {
            const span = document.createElement("span");
            span.innerText = text[i];
            line.appendChild(span);
        }
        line.classList.add(this.CHILD_CLASS_INITIALIZED);
    }
}

customElements.define("type-writer", TypeWriter);
