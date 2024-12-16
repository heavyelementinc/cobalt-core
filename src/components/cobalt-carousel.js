/** # Cobalt Carousel
 * @description An infinitely-scrolling carousel with buttons
 * @note Make sure you limit white space in this element as it can throw off alignment of fake targets going forward.
 * 
 * @element - <cobalt-carousel>
 * 
 * @attribute scroll [null]|"drag"|"drag-scroll" - Using "drag" or "drag-scroll" will disable buttons and allow the user to drag scroll the carousel with their mouse
 * @attribute center-align [null]|"false" - Using "false", you can prevent fake elements from being appended/prepended to the validTargets (prevents smooth infinite scrolling)
 * @attribute autoscroll [null]|Integer - Specifying an integer will delay a duration in milliseconds to wait before autoscrolling to the next item in the list.
 * @attribute height [null]|Integer - Specify an integer to apply a uniform height to all scrollable elements.
 * @attribute fit ["cover"]|"contain" - Changes how img, picture, and video tags fit their contents in available space.
 * @attribute sizing ["uniform-min"]|"uniform-max"|"natural" - Changes how img, picture, and video tags are styles versus each other
 * @attribute scroll INCOMPLETE and DISABLED
 * 
 * @copyright 2022 Heavy Element, Inc.
 * @author Gardiner Bryant
 */
class CobaltCarousel extends HTMLElement {
    constructor() {
        super();
        this.visible = "auto";    // Visible
        this.container = null;    // The scrollable container that items are moved into.
        this.validTargets = null; // The child elements that are meant to be scrolled through.
        this.sizingPromises = []; // The promises we're awaiting in updateSizing
        this.index = null;        // The index into the validTargets array that is currently visible.
        this.pagination = true;   // Controls a yet-to-be-implemented pagination routine.
        this.buttons = true;      // Controls if next/previous buttons are to be displayed.
    }

    connectedCallback() {
        this.previousScrollPosition = 0;
        this.scrollDirection = 1;
        this.attributeChangedCallback("fit",null,this.getAttribute("fit"));
        this.attributeChangedCallback("height",null,this.getAttribute("height"))
        // Let's initialize our element by moving the children of the element into the container
        this.index = 0;
        this.container = document.createElement("div");
        this.container.classList.add("cobalt-carousel--scroll-container");
        this.container.innerHTML = this.innerHTML; // Moving elements of this element into container
        this.innerHTML = ""; // Deleting everything in this element
        this.append(this.container); // Append the container to this element

        // Now let's add a scroll event so we can fire our custom event when scrolling stops.
        this.container.addEventListener("scroll", () => {
            clearTimeout(this.scrollStopTimeout);
            this.scrollDirection = (this.previousScrollPosition < this.container.scrollLeft) ? 1 : -1;
            this.previousScrollPosition = this.container.scrollLeft;
            this.scrollStopTimeout = setTimeout(() => this.container.dispatchEvent(new CustomEvent("scrollend")), 150);
        });

        this.initValidTargets();
        
        this.attributeChangedCallback("sizing",null,this.getAttribute("sizing"));
        // This html attribute changes the behavior of the element.
        if(this.getAttribute('center-align') !== "false") this.initFakeElements();

        // This makes the container scrollable via dragging
        if(this.getAttribute('scroll') && ['drag', 'drag-scroll'].includes(this.getAttribute('scroll'))) {
            this.initDragToScroll();
            this.buttons = false;
            this.pagination = false;
        }
        
        // Initialize our buttons
        if(this.buttons) this.initNextPrevButtons();
        
        // A yet-to-be-implemented pagination routine
        // if(this.pagination) this.initPagination();

        // Check if we're supposed to autoscroll through results.
        this.autoScroll = parseInt(this.getAttribute("autoscroll")) || null;
        if(this.autoScroll) this.invokeAutoScroll();

        this.updateScroll();

        this.observeIntersection();
    }

    get observedAttributes() {
        return ['height', 'fit', 'sizing', 'aspect-ratio'];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        switch(name) {
            case "aspect-ratio":
                this.updateAspectRatio(newValue);
                break;
            case "height":
                this.handleHeightChange(newValue);
            case "fit":
                this.updateCSSVar(name, newValue);
                break;
            case "sizing":
                this.updateSizing(name, newValue);
                break;
        }
    }

    updateCSSVar(name, value) {
        this.style.setProperty(`--${name}`, value);
    }

    async updateSizing(name, value) {
        if(!this.validTargets) return;
        let targets = [];
        if(this.sizingPromises.length === 0) {
            this.validTargets.forEach(el => {
                let target = null;
                if(["IMG","PICTURE","VIDEO"].includes(el.tagName)) target = el;
                else {
                    const element = el.querySelector("img, picture, video");
                    if(element) target = element;
                }
                if(target) this.sizingPromises.push(new Promise((resolve, reject) => {
                    target.addEventListener("load", e => resolve());
                    target.addEventListener("error", e => resolve());
                    if(target.complete === true) resolve();
                }));
                targets.push(target);
            });
        }

        await Promise.all(this.sizingPromises);

        let max = this.getAttribute("height"), min = this.getAttribute("height");
        if(!max) {
            let sizes = [];
    
            targets.forEach(el => {
                sizes.push(el.getAttribute("height") || el.naturalHeight || 100);
            });
    
            max = Math.max(...sizes),
            min = Math.min(...sizes);
        }
        if(Math.abs(max) === Infinity || Math.abs(min) === Infinity) {
            max = 100;
            min = 100;
        }

        if(typeof value === "number") value = this.getAttribute("sizing");

        // This is the sizing attribute
        switch(value) {
            case "natural":
                this.style.removeProperty("--fit");
                this.handleHeightChange(max.toString());
                break;
            case "uniform-max":
                this.setAttribute("fit", this.getAttribute("fit") || "cover");
                this.updateCSSVar("fit", this.getAttribute("fit"));
                this.handleHeightChange(max.toString());
                break;
            case "uniform-min":
            case "initial":
            default:
                this.setAttribute("fit", this.getAttribute("fit") || "cover");
                this.updateCSSVar("fit", this.getAttribute("fit"));
                this.handleHeightChange(min.toString());
                break;
        }
    }

    initNextPrevButtons() {
        const next = document.createElement("button"),
        prev = document.createElement("button");

        next.classList.add("cobalt-carousel--button", "cobalt-carousel--next");
        prev.classList.add("cobalt-carousel--button", "cobalt-carousel--prev");

        next.addEventListener("click", evt => {
            this.scrollButton(1);
            this.updateScroll();
        });
        prev.addEventListener("click", evt => {
            this.scrollButton(-1);
            this.updateScroll();
        });

        this.prepend(prev);
        this.append(next);
    }

    async scrollButton(direction = 1, index = false) {
        if(this.resolveScrollComplete) this.resolveScrollComplete();
        // Add our direction to the index.
        if(index === false) this.index += direction;
        // OR set the index's value to an arbitrary number
        else this.index = index;
        // Now let's check for overflow and handle it
        if(this.index >= this.validTargets.length && direction >= 1) {
            // If we've overflowed, set the index to zero
            this.index = 0;
            // And set the jump scroll pointer to first clone... why?!?
            this.jumpScrollClonePointer = this.firstClone;
        }
        if(this.index < 0 && direction <= -1) {
            this.index = this.validTargets.length - 1;
            this.jumpScrollClonePointer = this.lastClone;
        }
    }

    fallback() {
        this.classList.remove("locked");
        this.container.style.overflowX = "scroll";
    }

    async updateScroll(fakeElement = null) {
        if(this.jumpScrollClonePointer) this.classList.add("locked");
        clearTimeout(this.timeout);
        
        // Select the current element
        let element = this.setCurrentTarget(this.validTargets[this.index]);

        let elementCenter = this.containerOffset(this.jumpScrollClonePointer || element);

        try{
            this._observationTargetIgnoreScroll = true;
            await this.waitForScrollComplete(elementCenter);
        } catch (error) {
            console.warn(error);
            this.fallback();
            return;
        }

        this.invokeAutoScroll();

        this.performJumpScroll();
        this.classList.remove("locked");
        this._observationTargetIgnoreScroll = false;
    }

    setCurrentTarget(element) {
        // Clean up the current target
        const currentTargetClass = "cobalt-carousel--current-scroll-target";
        const currentTargetElements = this.container.querySelectorAll(`.${currentTargetClass}`);

        currentTargetElements.forEach(e => e.classList.remove(currentTargetClass));
        
        element.classList.add(currentTargetClass); // Add the current target class
        if(this.jumpScrollClonePointer) element = this.jumpScrollClonePointer; // Let's get ready for a jump scroll
        element.classList.add(currentTargetClass);
        return element;
    }

    performJumpScroll() {
        if(this.jumpScrollClonePointer) {
            this.container.style.scrollBehavior = "auto";
            this.container.style.overflow = "hidden";
            let offset = this.containerOffset(this.validTargets[this.index]);
            this.container.scrollLeft = offset;
            this.container.style.removeProperty("scroll-behavior");
            this.container.style.removeProperty("overflow");
            this.jumpScrollClonePointer = false;
        }
    }

    waitForScrollComplete(offset) {
        return new Promise((resolve, reject) => {
            this.resolveScrollComplete = resolve;
            this.container.addEventListener("scrollend",() => {
                resolve();
                this.resolveScrollComplete = null;
            }, {once: true});
            // if(offset > this.container.scrollHeight) reject("Scroll height is greater than the current scroll height");
            this.container.scrollLeft = offset;
            setTimeout(() => reject("It's been 1500 ms and we haven't received a scrollend signal, aborting."),1500);
        });
    }

    containerOffset(element) {
        const isMobile = window.matchMedia("(max-width: 35em)").matches;
        let offset = .5;
        
        const elementOffset = get_offset(element);
        const elementOffsetFromX = element.offsetLeft;
        const elementCenter = elementOffset.w * offset;

        const containerOffset = get_offset(this.container);
        const containerOffsetFromX = this.container.offsetLeft;
        const containerCenter = containerOffset.w * offset;
        const finalScrollPosition = elementOffsetFromX - containerOffsetFromX;

        const middleOffset = containerCenter - elementCenter;
        return finalScrollPosition - middleOffset;
        // console.log({isMobile, offset, elementOffsetFromX, elementCenter,finalScrollPosition, final: finalScrollPosition - elementCenter});
        if(isMobile) return finalScrollPosition - (containerOffset.w * .05);
        return finalScrollPosition - elementCenter;
    }


    invokeAutoScroll() {
        if(this.autoScroll) this.timeout = setTimeout(() => {
            this.scrollButton(1);
            this.updateScroll();
        }, this.autoScroll);
    }


    initDragToScroll() {
        this.classList.add("cobalt-carousel--draggable");
        this.addEventListener('mousedown', this.mouseDownHandler.bind(this));
    }

    mouseDownHandler(evt) {
        this.classList.add("grabbing");

        const position = {
            left: this.scrollLeft,
            top: this.scrollTop,
            x: evt.clientX,
            y: evt.clientY
        }

        const mouseMoveHandler = (evt) => {
            const deltaX = evt.clientX - position.x;
            const deltaY = evt.clientY - position.y;
            
            this.scrollTop = position.top - deltaY;
            this.scrollLeft = position.left - deltaX;
        }

        const mouseUpHandler = (evt) => {
            document.removeEventListener('mousemove', mouseMoveHandler);
            document.removeEventListener('mouseup', mouseUpHandler);
    
            this.classList.remove("grabbing");
        }

        document.addEventListener('mousemove', mouseMoveHandler);
        document.addEventListener('mouseup', mouseUpHandler);
    }


    initFakeElements() {
        // Clone nodes
        const fakeClass = "cobalt-carousel--fake-entry",
        firstClone = this.validTargets[0].cloneNode(true),
        secondClone = this.validTargets[1].cloneNode(true),
        penultimateClone = this.validTargets[this.validTargets.length - 2].cloneNode(true),
        lastClone = this.validTargets[this.validTargets.length - 1].cloneNode(true);

        this.validTargets[0].clone = firstClone;
        this.validTargets[1].clone = secondClone;
        this.validTargets[this.validTargets.length - 2].clone = penultimateClone;
        this.validTargets[this.validTargets.length - 1].clone = lastClone;

        firstClone.classList.add(fakeClass);
        firstClone.reference = this.validTargets[0];
        secondClone.classList.add(fakeClass);
        secondClone.reference = this.validTargets[1];
        penultimateClone.classList.add(fakeClass);
        penultimateClone.reference = this.validTargets[this.validTargets.length - 2];
        lastClone.classList.add(fakeClass);
        lastClone.reference = this.validTargets[this.validTargets.length - 1];

        this.firstClone = firstClone;
        this.lastClone = lastClone;

        this.container.append(firstClone);
        this.container.append(secondClone);
        this.container.prepend(lastClone);
        this.container.prepend(penultimateClone);
    }

    handleHeightChange(height) {
        if(height === null) return this.style.setProperty("--height", "200px");
        if(height < 50) height = 100;
        const clamp = cssToPixel("60vh"); // (window.matchMedia("(max-width: 35em)").matches) ? cssToPixel("80vh")

        const lastChar = height.toString()[height.toString().length - 1];
        const parsed = cssToPixel(height);

        const min = Math.min(clamp, parsed);
        if(min !== parsed) height = clamp;
        // Check if the last character is a string
        // !is not a number === true
        if(!isNaN(lastChar)) {
            this.style.setProperty("--height",`${height}px`);
            this.updateAspectRatio(this.getAttribute("aspect-ratio"));
            return;
        }
        this.style.setProperty("--height", height);
        this.updateAspectRatio(this.getAttribute("aspect-ratio"));
    }

    observeIntersection() {
        let options = {
            root: this.container,
            rootMargin: '0px',
            threshold: 1.0
        }

        this.observe = new IntersectionObserver((element) => {
            if(!this.hasAttribute("scroll")) return;
            this.observationTarget(element[0].target);
        }, options);
        
        for(const el of this.container.children) {
            this.observe.observe(el);
        }
    }

    observationTarget(target) {
        if(this._observationTargetIgnoreScroll) return;
        this.validTargets.forEach((e, i) => {
            if(e === target) this.scrollButton(this.scrollDirection, i);
        });
        
        this.setCurrentTarget(target);

        if("reference" in target) {
            this.jumpScrollClonePointer = target.reference;
            this.performJumpScroll();
        }
    }

    initValidTargets() {
        // Let's instantiate the valid targets of this carousel
        // We're spreading the children property so we break the pointer to the
        // children property (this gets modified later)
        this.validTargets = [...this.container.children];

        if(!this.elementObserver) this.elementObserver = new MutationObserver(e => {
            console.log(e);
            e[0].target.style.setProperty("--position". e[0].target.getAttribute("position"));
        });
        const options = {
            attributes: true,
            attributeFilter: ["position"]
        }
        
        this.validTargets.forEach(el => {
            el.classList.add("cobalt-carousel--carousel-item");
            el.style.setProperty("--position",el.getAttribute("position") || "");
            this.elementObserver.observe(el, options);
        });
    }

    updateAspectRatio(value) {
        console.log(value);
        if(!value) return;
        const split = value.split(":");
        const hR = split[0], wR = split[1];
        const factor = hR / wR; // For 16:9, should be 1.77
        const pixelHeight = cssToPixel(this.style.getPropertyValue("--height"), this); // Reduced to 720 pixels
        const pixelWidth = pixelHeight * factor; // About 1280 pixels
        this.style.setProperty("--ratio-width", `${pixelWidth}px`);
        console.log(pixelWidth)
    }

}

customElements.define("cobalt-carousel", CobaltCarousel);

class IgEmbed extends HTMLElement {
    constructor() {
        super();
        this.initIGScript();
        
    }

    initIGScript() {
        let script = document.querySelector("#ig-script");
        if(script) return;
        script = document.createElement("script");
        script.src = "//www.instagram.com/embed.js";
    }
    
    create() {

    }
}

customElements.define("ig-embed", IgEmbed);